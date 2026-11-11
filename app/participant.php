<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
function registerBatch(int $userId, int $batchId): int {
    $db=database(); $db->beginTransaction();
    try {
        $batch=one("SELECT b.*,s.capacity,s.registration_deadline,s.price FROM training_batches b JOIN training_programs p ON p.slug=b.program_slug AND p.is_active=1 LEFT JOIN batch_settings s ON s.batch_id=b.id WHERE b.id=? FOR UPDATE",[$batchId]);
        if (!$batch || $batch['status']!=='confirmed' || !$batch['capacity'] || $batch['price']===null || !$batch['registration_deadline'] || strtotime($batch['registration_deadline'])<time() || strtotime($batch['start_date'].' 23:59:59')<time()) throw new DomainException('Batch ini belum membuka pendaftaran atau masa pendaftarannya sudah berakhir.');
        $existing=one('SELECT id FROM registrations WHERE user_id=? AND batch_id=?',[$userId,$batchId]);
        if ($existing) throw new DomainException('Anda sudah memiliki pendaftaran pada batch ini. Periksa menu Pendaftaran.');
        $count=one("SELECT COUNT(*) n FROM registrations WHERE batch_id=? AND status IN ('pending','under_review','approved')",[$batchId]);
        if ((int)$count['n'] >= (int)$batch['capacity']) throw new DomainException('Kuota batch telah terisi. Silakan pilih jadwal lain.');
        run('INSERT INTO registrations(user_id,batch_id,price,payment_status) VALUES (?,?,?,?)',[$userId,$batchId,$batch['price'],(float)$batch['price']===0.0?'not_required':'unpaid']);
        $id=(int)$db->lastInsertId();
        notifyUser($userId,'Pendaftaran diterima','Pendaftaran #'.$id.' menunggu verifikasi admin.','registrations');
        audit('registration.created',$userId,$id); $db->commit(); return $id;
    } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
}
function assessmentForUser(int $id,int $userId): array {
    $assessment=one('SELECT * FROM assessments WHERE id=? AND is_published=1',[$id]);
    if (!$assessment) throw new DomainException('Evaluasi tidak tersedia.');
    authorizedBatch($userId,(int)$assessment['batch_id']); return $assessment;
}
function finishAssessment(array $assessment, int $userId, array $answers): void {
    $db=database(); $db->beginTransaction();
    try {
        $attempt=one('SELECT * FROM assessment_attempts WHERE assessment_id=? AND user_id=? FOR UPDATE',[$assessment['id'],$userId]);
        if (!$attempt || $attempt['submitted_at']) throw new DomainException('Evaluasi belum dimulai atau sudah dikirim.');
        $questions=rows('SELECT * FROM assessment_questions WHERE assessment_id=? ORDER BY sort_order,id',[$assessment['id']]);
        $ontime=time()<=min(strtotime($attempt['expires_at']),strtotime($assessment['closes_at']));
        $correct=0;
        foreach ($questions as $question) {
            $selected=$ontime ? ($answers[$question['id']]??null) : null;
            $options=json_decode($question['options_json'],true);
            $valid=is_scalar($selected) && ctype_digit((string)$selected) && array_key_exists((int)$selected,$options);
            $selected=$valid ? (int)$selected : null;
            if ($selected!==null && $selected===(int)$question['correct_option']) $correct++;
            run('INSERT INTO assessment_answers(attempt_id,question_id,selected_option) VALUES (?,?,?)',[$attempt['id'],$question['id'],$selected]);
        }
        $score=count($questions)?round($correct/count($questions)*100,2):0;
        run('UPDATE assessment_attempts SET score=?,passed=?,submitted_at=NOW() WHERE id=?',[$score,$score>=(float)$assessment['pass_score']?1:0,$attempt['id']]);
        notifyUser($userId,'Hasil evaluasi tersedia','Hasil '.$assessment['title'].' dapat dilihat di menu Evaluasi.','assessments');
        $db->commit();
    } catch(Throwable $e) { if($db->inTransaction())$db->rollBack(); throw $e; }
}
function participantAction(array $user): void {
    checkCsrf(); $uid=(int)$user['id']; $action=input('action'); $id=(int)input('id');
    if ($action==='cancel') {
        $db=database(); $db->beginTransaction();
        try {
            $changed=run("UPDATE registrations SET status='cancelled' WHERE id=? AND user_id=? AND status IN ('pending','under_review')",[$id,$uid]);
            if (!$changed) throw new DomainException('Pendaftaran ini tidak dapat dibatalkan. Hubungi admin untuk pendaftaran yang telah disetujui.');
            notifyUser($uid,'Pendaftaran dibatalkan','Pendaftaran #'.$id.' telah dibatalkan.','registrations'); audit('registration.cancelled',$uid,$id); $db->commit();
        } catch(Throwable $e) { if($db->inTransaction())$db->rollBack(); throw $e; }
    } elseif ($action==='complete_lesson') {
        $lesson=one('SELECT * FROM lessons WHERE id=? AND is_published=1',[$id]);
        if (!$lesson) throw new DomainException('Materi tidak ditemukan.');
        authorizedBatch($uid,(int)$lesson['batch_id']);
        if (input('completed')!=='1') throw new DomainException('Konfirmasikan bahwa materi sudah dipelajari.');
        run('INSERT IGNORE INTO lesson_completions(user_id,lesson_id) VALUES (?,?)',[$uid,$id]);
    } elseif ($action==='assignment') {
        $assignment=one('SELECT * FROM assignments WHERE id=? AND is_published=1',[$id]);
        if (!$assignment) throw new DomainException('Tugas tidak tersedia.');
        authorizedBatch($uid,(int)$assignment['batch_id']);
        if ($assignment['due_at'] && strtotime($assignment['due_at'])<time()) throw new DomainException('Batas pengumpulan tugas telah berakhir.');
        $existing=one('SELECT * FROM assignment_submissions WHERE user_id=? AND assignment_id=?',[$uid,$id]);
        if ($existing && $existing['status']==='passed') throw new DomainException('Tugas sudah dinyatakan lulus.');
        $answer=input('answer'); if(mb_strlen($answer)>20000)throw new DomainException('Jawaban maksimal 20.000 karakter.');
        $upload=storeUpload('document');
        if (!$answer && !$upload) throw new DomainException('Isi jawaban atau unggah tugas PDF.');
        run("INSERT INTO assignment_submissions(assignment_id,user_id,answer,file_path,file_name) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE answer=VALUES(answer),file_path=COALESCE(VALUES(file_path),file_path),file_name=COALESCE(VALUES(file_name),file_name),status='submitted',feedback=NULL,submitted_at=NOW()",[$id,$uid,$answer,$upload['path']??null,$upload['name']??null]);
    } elseif ($action==='checkin') {
        $session=one('SELECT * FROM training_sessions WHERE id=?',[$id]);
        if (!$session) throw new DomainException('Sesi tidak tersedia.');
        authorizedBatch($uid,(int)$session['batch_id']);
        if (!$session['attendance_token_hash'] || !$session['attendance_open_at'] || !$session['attendance_close_at'] || strtotime($session['attendance_open_at'])>time() || strtotime($session['attendance_close_at'])<time() || !hash_equals($session['attendance_token_hash'],hash('sha256',input('token')))) throw new DomainException('Kode kehadiran tidak valid atau waktu presensi belum dibuka/sudah ditutup.');
        run("INSERT IGNORE INTO attendance_records(user_id,session_id,status,checked_in_at) VALUES (?,?,'present',NOW())",[$uid,$id]);
    } elseif ($action==='start_assessment') {
        $assessment=assessmentForUser($id,$uid);
        if ($assessment['kind']==='practical' || strtotime($assessment['opens_at'])>time() || strtotime($assessment['closes_at'])<=time()) throw new DomainException('Evaluasi belum dibuka atau sudah ditutup.');
        if (!one('SELECT id FROM assessment_questions WHERE assessment_id=? LIMIT 1',[$id])) throw new DomainException('Soal belum dipublikasikan.');
        $expires=date('Y-m-d H:i:s',min(time()+(int)$assessment['duration_minutes']*60,strtotime($assessment['closes_at'])));
        run('INSERT IGNORE INTO assessment_attempts(assessment_id,user_id,started_at,expires_at) VALUES (?,?,NOW(),?)',[$id,$uid,$expires]);
        redirect('portal.php?view=assessments&assessment='.$id);
    } elseif ($action==='submit_assessment') {
        $assessment=assessmentForUser($id,$uid);
        if ($assessment['kind']==='practical') throw new DomainException('Penilaian praktik dilakukan instruktur.');
        finishAssessment($assessment,$uid,is_array($_POST['answers']??null)?$_POST['answers']:[]);
    } elseif ($action==='read_notifications') {
        run('UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL',[$uid]);
    } elseif ($action==='profile') {
        $name=input('full_name');$email=strtolower(input('email'));$phone=input('phone');
        if (mb_strlen($name)<2 || mb_strlen($name)>160 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190) throw new DomainException('Nama dan email belum valid.');
        if ($phone && !preg_match('/^[+0-9 ()-]{6,40}$/',$phone)) throw new DomainException('Nomor telepon belum valid.');
        foreach(['company'=>180,'position'=>140,'address'=>2000,'identity_info'=>190] as $key=>$limit) if(mb_strlen(input($key))>$limit) throw new DomainException('Data '.$key.' terlalu panjang.');
        if ($email!==$user['email'] && !password_verify(input('current_password'),$user['password_hash'])) throw new DomainException('Masukkan kata sandi saat ini untuk mengubah email.');
        if (one('SELECT id FROM users WHERE email=? AND id<>?',[$email,$uid])) throw new DomainException('Email tidak dapat digunakan.');
        $photo=storeUpload('photo',true);
        database()->beginTransaction();
        try {
            run('UPDATE users SET full_name=?,email=? WHERE id=?',[$name,$email,$uid]);
            run('UPDATE participant_profiles SET phone=?,company=?,position=?,address=?,identity_info=?,photo_path=COALESCE(?,photo_path) WHERE user_id=?',[$phone,input('company'),input('position'),input('address'),input('identity_info'),$photo['path']??null,$uid]);
            database()->commit();
        } catch(Throwable $e) { database()->rollBack();throw $e; }
    } elseif ($action==='password') {
        require_once __DIR__.'/auth.php';
        if (!password_verify(input('current_password'),$user['password_hash'])) throw new DomainException('Kata sandi saat ini tidak sesuai.');
        $password=input('password');validPassword($password);
        if ($password!==input('password_confirmation')) throw new DomainException('Konfirmasi kata sandi tidak sama.');
        run('UPDATE users SET password_hash=?,session_version=session_version+1 WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$uid]);
        loginUser(one('SELECT * FROM users WHERE id=?',[$uid]));
    } else { throw new DomainException('Tindakan tidak dikenal.'); }
    flash('Perubahan berhasil disimpan.');
}
