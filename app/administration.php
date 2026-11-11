<?php
declare(strict_types=1);
require_once __DIR__.'/participant.php';
function requireAdministrator(int $id): void {
    if(!one("SELECT id FROM users WHERE id=? AND role='admin' AND is_active=1",[$id]))throw new DomainException('Akun administrator aktif diperlukan.');
}
function reviewRegistration(int $actor,int $id,string $status,string $remarks,string $payment=''): void {
    requireAdministrator($actor);
    if(!in_array($status,['under_review','approved','rejected','cancelled'],true))throw new DomainException('Status tidak valid.');
    $db=database();$db->beginTransaction();
    try {
        $registration=one('SELECT * FROM registrations WHERE id=? FOR UPDATE',[$id]);
        if(!$registration)throw new DomainException('Pendaftaran tidak ditemukan.');
        if(in_array($registration['status'],['rejected','cancelled'],true)&&$status==='approved')throw new DomainException('Pendaftaran yang ditutup tidak dapat disetujui kembali.');
        $batch=one('SELECT b.*,s.capacity FROM training_batches b LEFT JOIN batch_settings s ON s.batch_id=b.id WHERE b.id=? FOR UPDATE',[$registration['batch_id']]);
        if($status==='approved'){
            if($batch['status']!=='confirmed')throw new DomainException('Hanya batch resmi yang dapat disetujui.');
            $count=(int)one("SELECT COUNT(*) n FROM registrations WHERE batch_id=? AND status='approved' AND id<>?",[$registration['batch_id'],$id])['n'];
            if(!$batch['capacity']||$count>=(int)$batch['capacity'])throw new DomainException('Kuota penuh atau belum ditetapkan.');
        }
        $payment=$payment?:$registration['payment_status'];
        if(!in_array($payment,['not_required','unpaid','pending','paid'],true))throw new DomainException('Status pembayaran tidak valid.');
        if((float)$registration['price']>0&&$payment==='not_required')throw new DomainException('Batch berbayar tidak boleh ditandai tanpa pembayaran.');
        run('UPDATE registrations SET status=?,payment_status=?,admin_remarks=? WHERE id=?',[$status,$payment,$remarks,$id]);
        if($status==='approved')run("INSERT INTO enrollments(registration_id) VALUES (?) ON DUPLICATE KEY UPDATE status=IF(status='completed','completed','active')",[$id]);
        else run("UPDATE enrollments SET status='revoked' WHERE registration_id=?",[$id]);
        notifyUser((int)$registration['user_id'],'Pendaftaran '.statusText($status),$remarks?:'Status pendaftaran Anda telah diperbarui.','registrations');
        audit('registration.review',$actor,$id,json_encode(['status'=>$status,'payment'=>$payment,'remarks'=>$remarks],JSON_UNESCAPED_UNICODE));$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function issueCertificate(int $actor,int $enrollmentId,string $number,string $pdfPath): int {
    requireAdministrator($actor);
    if(!preg_match('/^[A-Za-z0-9\/._-]{3,100}$/',$number))throw new DomainException('Nomor sertifikat tidak valid.');
    if(!is_file($pdfPath)||(new finfo(FILEINFO_MIME_TYPE))->file($pdfPath)!=='application/pdf'||filesize($pdfPath)>10*1024*1024)throw new DomainException('Sertifikat wajib PDF resmi, maksimal 10 MB.');
    $db=database();$db->beginTransaction();$stored=null;
    try{
        $en=one("SELECT en.*,r.user_id,r.batch_id,r.status registration_status,r.payment_status,r.price,u.full_name,p.title,b.end_date FROM enrollments en JOIN registrations r ON r.id=en.registration_id JOIN users u ON u.id=r.user_id JOIN training_batches b ON b.id=r.batch_id JOIN training_programs p ON p.slug=b.program_slug WHERE en.id=? FOR UPDATE",[$enrollmentId]);
        if(!$en||$en['status']==='revoked'||$en['registration_status']!=='approved'||$en['end_date']>=date('Y-m-d'))throw new DomainException('Kelas belum berakhir atau peserta tidak memiliki akses aktif.');
        if((float)$en['price']>0&&$en['payment_status']!=='paid')throw new DomainException('Pembayaran belum dikonfirmasi.');
        $pr=progress((int)$en['user_id'],(int)$en['batch_id']);
        if(!$pr['total']||$pr['done']!==$pr['total'])throw new DomainException('Materi, tugas, dan kehadiran belum seluruhnya terpenuhi.');
        $exam=one('SELECT COUNT(*) total,SUM(CASE WHEN at.passed=1 AND at.submitted_at IS NOT NULL THEN 1 ELSE 0 END) done FROM assessments a LEFT JOIN assessment_attempts at ON at.assessment_id=a.id AND at.user_id=? WHERE a.batch_id=? AND a.is_published=1',[$en['user_id'],$en['batch_id']]);
        if(!(int)$exam['total']||(int)$exam['done']!==(int)$exam['total'])throw new DomainException('Seluruh evaluasi wajib sudah dinyatakan lulus.');
        $stored=bin2hex(random_bytes(24)).'.pdf';if(!copy($pdfPath,privateDirectory().'/'.$stored))throw new RuntimeException('PDF gagal disimpan.');
        run('INSERT INTO certificates(enrollment_id,certificate_number,participant_name,program_name,verification_token,file_path,approved_by,issued_at) VALUES (?,?,?,?,?,?,?,NOW())',[$enrollmentId,$number,$en['full_name'],$en['title'],bin2hex(random_bytes(32)),$stored,$actor]);
        $id=(int)$db->lastInsertId();run("UPDATE enrollments SET status='completed' WHERE id=?",[$enrollmentId]);
        notifyUser((int)$en['user_id'],'Sertifikat telah diterbitkan','Sertifikat '.$number.' tersedia untuk diunduh.','certificates');audit('certificate.issued',$actor,$id);$db->commit();return $id;
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();if($stored&&is_file(privateDirectory().'/'.$stored))unlink(privateDirectory().'/'.$stored);throw $e;}
}
function importContent(int $actor,string $table,array $data): int {
    requireAdministrator($actor);
    $columns=[
      'program_details'=>['program_slug','level','duration','method','objectives','curriculum','requirements','audience'],
      'batch_settings'=>['batch_id','capacity','registration_deadline','price','method','payment_instructions','equipment','practical_info'],
      'training_batches'=>['id','program_slug','start_date','end_date','delivery_format','location','status'],
      'instructors'=>['id','name','bio','expertise','experience','certifications','training_history','photo','is_published'],
      'program_instructors'=>['program_slug','instructor_id'],
      'articles'=>['id','slug','title','category','excerpt','body','image','published_at'],
      'testimonials'=>['id','name','organization','quote','is_published'],
      'faqs'=>['id','question','answer','sort_order','is_published'],
      'training_sessions'=>['id','batch_id','title','starts_at','ends_at','method','location','meeting_url','instructor_id'],
      'lessons'=>['id','batch_id','module_title','title','body','kind','file_path','file_name','video_url','sort_order','is_published'],
      'assignments'=>['id','batch_id','title','instructions','due_at','is_published'],
      'assessments'=>['id','batch_id','title','instructions','kind','opens_at','closes_at','duration_minutes','pass_score','reveal_answers','is_published'],
      'assessment_questions'=>['id','assessment_id','question','options_json','correct_option','sort_order']
    ];
    if(!isset($columns[$table])||!$data||array_diff(array_keys($data),$columns[$table]))throw new DomainException('Tabel atau kolom impor tidak diizinkan.');
    foreach(['meeting_url','video_url','photo','image'] as $key)if(!empty($data[$key])&&!safeUrl($data[$key]))throw new DomainException('URL harus HTTP/HTTPS yang valid.');
    foreach(['capacity','duration_minutes'] as $key)if(isset($data[$key])&&(!is_numeric($data[$key])||(int)$data[$key]<1))throw new DomainException('Nilai '.$key.' minimal 1.');
    if(isset($data['price'])&&(!is_numeric($data['price'])||(float)$data['price']<0))throw new DomainException('Biaya tidak valid.');
    if(isset($data['pass_score'])&&((float)$data['pass_score']<0||(float)$data['pass_score']>100))throw new DomainException('Nilai kelulusan harus 0–100.');
    foreach([['start_date','end_date'],['starts_at','ends_at'],['opens_at','closes_at']] as [$start,$end])if(isset($data[$start],$data[$end])&&(!strtotime($data[$start])||!strtotime($data[$end])||strtotime($data[$start])>strtotime($data[$end])))throw new DomainException('Rentang tanggal tidak valid.');
    if($table==='assessment_questions'){
        $options=is_array($data['options_json']??null)?$data['options_json']:json_decode($data['options_json']??'',true);
        if(!is_array($options)||!array_is_list($options)||count($options)<2||count($options)>10||!isset($options[$data['correct_option']??-1])||array_filter($options,fn($o)=>!is_string($o)))throw new DomainException('Soal memerlukan 2–10 opsi teks dan indeks jawaban yang valid.');
        $data['options_json']=json_encode($options,JSON_UNESCAPED_UNICODE);
        if(one('SELECT id FROM assessment_attempts WHERE assessment_id=? LIMIT 1',[$data['assessment_id']??0]))throw new DomainException('Soal terkunci setelah ada peserta yang memulai evaluasi.');
    }
    if($table==='assessments'&&!empty($data['id'])&&one('SELECT id FROM assessment_attempts WHERE assessment_id=? LIMIT 1',[$data['id']]))throw new DomainException('Evaluasi terkunci setelah dimulai peserta.');
    if(!empty($data['file_path'])&&(!preg_match('/^[a-f0-9]{48}\.[a-z0-9]+$/',$data['file_path'])||!is_file(privateDirectory().'/'.$data['file_path'])))throw new DomainException('Impor file ke penyimpanan privat terlebih dahulu.');
    $keys=array_keys($data);$updates=array_map(fn($key)=>"`$key`=VALUES(`$key`)",$keys);
    $db=database();$db->beginTransaction();
    try{
        run('INSERT INTO `'.$table.'` (`'.implode('`,`',$keys).'`) VALUES ('.implode(',',array_fill(0,count($keys),'?')).') ON DUPLICATE KEY UPDATE '.implode(',',$updates),array_values($data));
        $id=(int)($data['id']??$db->lastInsertId());
        $batchId=$data['batch_id']??($table==='training_batches'?$id:null);
        if($batchId&&in_array($table,['training_batches','training_sessions','lessons','assignments','assessments'],true)){
            $targets=rows("SELECT r.user_id FROM registrations r JOIN enrollments en ON en.registration_id=r.id WHERE r.batch_id=? AND r.status='approved' AND en.status IN ('active','completed')",[$batchId]);
            $destination=in_array($table,['lessons','assignments'])?'learning':($table==='assessments'?'assessments':'schedule');
            foreach($targets as $target)notifyUser((int)$target['user_id'],'Informasi pelatihan diperbarui','Ada pembaruan pada pelatihan Anda. Lihat informasi terbaru di portal.',$destination);
        }
        audit('content.'.$table,$actor,$id);$db->commit();return $id;
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
