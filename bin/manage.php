<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../app/administration.php';
try{
    $command=$argv[1]??'help';
    if($command==='help'){echo "MIGAS admin CLI (hanya akses server)\n".
      "promote EMAIL              Jadikan akun terdaftar sebagai admin\n".
      "import ACTOR_ID TABLE JSON Impor/update konten resmi\n".
      "review ACTOR_ID JSON       {id,status,remarks,payment}\n".
      "issue ACTOR_ID JSON        {enrollment_id,number,pdf_path}\n".
      "grade ACTOR_ID JSON        {submission_id,status,feedback} atau {assessment_id,user_id,score,feedback}\n".
      "attendance ACTOR_ID JSON   {session_id,user_id,status}\n".
      "open-attendance ACTOR_ID JSON {session_id,opens_at,closes_at}\n".
      "revoke ACTOR_ID CERT_ID    Cabut sertifikat\n".
      "store-file ACTOR_ID PATH   Salin PDF/PPTX/MP4 ke storage privat\n";exit;}
    if($command==='promote'){
        $email=$argv[2]??'';$user=one('SELECT id FROM users WHERE email=? AND is_active=1',[$email]);
        if(!$user)throw new DomainException('Daftar akun melalui website terlebih dahulu.');
        run("UPDATE users SET role='admin',session_version=session_version+1 WHERE id=?",[$user['id']]);audit('role.admin',null,(int)$user['id']);echo "Role administrator diperbarui. Masuk ulang diperlukan.\n";exit;
    }
    $actor=(int)($argv[2]??0);requireAdministrator($actor);
    if($command==='store-file'){
        $path=$argv[3]??'';if(!is_file($path)||filesize($path)>200*1024*1024)throw new DomainException('File tidak ditemukan atau melebihi 200 MB.');
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);$ext=['application/pdf'=>'pdf','video/mp4'=>'mp4','application/vnd.openxmlformats-officedocument.presentationml.presentation'=>'pptx'][$mime]??null;
        if(!$ext)throw new DomainException('Format file tidak didukung.');$name=bin2hex(random_bytes(24)).'.'.$ext;
        if(!copy($path,privateDirectory().'/'.$name))throw new RuntimeException('Gagal menyimpan file.');echo $name."\n";exit;
    }
    if($command==='revoke'){$id=(int)($argv[3]??0);run('UPDATE certificates SET revoked_at=NOW() WHERE id=?',[$id]);audit('certificate.revoked',$actor,$id);echo "Sertifikat dicabut.\n";exit;}
    $jsonPath=$argv[$command==='import'?4:3]??'';
    if(!is_file($jsonPath))throw new DomainException('File JSON diperlukan.');
    $data=json_decode(file_get_contents($jsonPath),true,512,JSON_THROW_ON_ERROR);
    if(!is_array($data))throw new DomainException('JSON harus berupa objek.');
    if($command==='import')echo 'ID: '.importContent($actor,$argv[3],$data)."\n";
    elseif($command==='review'){reviewRegistration($actor,(int)$data['id'],$data['status'],$data['remarks']??'',$data['payment']??'');echo "Pendaftaran diperbarui.\n";}
    elseif($command==='issue')echo 'Sertifikat ID: '.issueCertificate($actor,(int)$data['enrollment_id'],$data['number'],$data['pdf_path'])."\n";
    elseif($command==='open-attendance'){
        if(!strtotime($data['opens_at'])||!strtotime($data['closes_at'])||strtotime($data['closes_at'])<=strtotime($data['opens_at']))throw new DomainException('Waktu presensi tidak valid.');
        $token=bin2hex(random_bytes(24));$id=(int)$data['session_id'];
        if(!run('UPDATE training_sessions SET attendance_token_hash=?,attendance_open_at=?,attendance_close_at=? WHERE id=?',[hash('sha256',$token),$data['opens_at'],$data['closes_at'],$id]))throw new DomainException('Sesi tidak ditemukan.');
        $url=rtrim(getenv('APP_URL')?:'https://migastraining.duckdns.org','/').'/portal.php?view=attendance&session='.$id.'&token='.$token;
        echo $url."\n";
        if(class_exists(\BaconQrCode\Writer::class)){$renderer=new \BaconQrCode\Renderer\ImageRenderer(new \BaconQrCode\Renderer\RendererStyle\RendererStyle(400),new \BaconQrCode\Renderer\Image\SvgImageBackEnd());$path=privateDirectory().'/attendance-'.$id.'.svg';file_put_contents($path,(new \BaconQrCode\Writer($renderer))->writeString($url));echo "QR SVG: $path\n";}
        audit('attendance.opened',$actor,$id);
    }elseif($command==='attendance'){
        $session=one('SELECT batch_id FROM training_sessions WHERE id=?',[$data['session_id']]);if(!$session)throw new DomainException('Sesi tidak ditemukan.');authorizedBatch((int)$data['user_id'],(int)$session['batch_id']);
        if(!in_array($data['status'],['present','late','absent','excused'],true))throw new DomainException('Status tidak valid.');
        run('INSERT INTO attendance_records(user_id,session_id,status,checked_in_at) VALUES (?,?,?,NOW()) ON DUPLICATE KEY UPDATE status=VALUES(status)',[$data['user_id'],$data['session_id'],$data['status']]);audit('attendance.recorded',$actor,(int)$data['session_id']);
    }elseif($command==='grade'){
        if(isset($data['submission_id'])){
            if(!in_array($data['status'],['passed','revision'],true))throw new DomainException('Status penilaian tidak valid.');
            $sub=one('SELECT s.*,a.batch_id FROM assignment_submissions s JOIN assignments a ON a.id=s.assignment_id WHERE s.id=?',[$data['submission_id']]);if(!$sub)throw new DomainException('Tugas tidak ditemukan.');authorizedBatch((int)$sub['user_id'],(int)$sub['batch_id']);
            run('UPDATE assignment_submissions SET status=?,feedback=? WHERE id=?',[$data['status'],$data['feedback']??'',$data['submission_id']]);notifyUser((int)$sub['user_id'],'Tugas sudah dinilai','Lihat hasil dan masukan instruktur di Pusat Belajar.','learning');
        }else{
            $assessment=assessmentForUser((int)$data['assessment_id'],(int)$data['user_id']);if($assessment['kind']!=='practical'||!is_numeric($data['score'])||$data['score']<0||$data['score']>100)throw new DomainException('Penilaian ini khusus praktik dengan skor 0–100.');
            run('INSERT INTO assessment_attempts(assessment_id,user_id,started_at,expires_at,submitted_at,score,passed,feedback) VALUES (?,?,NOW(),NOW(),NOW(),?,?,?) ON DUPLICATE KEY UPDATE score=VALUES(score),passed=VALUES(passed),feedback=VALUES(feedback),submitted_at=NOW()',[$data['assessment_id'],$data['user_id'],$data['score'],$data['score']>=$assessment['pass_score']?1:0,$data['feedback']??'']);notifyUser((int)$data['user_id'],'Hasil praktik tersedia','Lihat hasil praktik pada menu Evaluasi.','assessments');
        }audit('assessment.graded',$actor,(int)($data['submission_id']??$data['assessment_id']));echo "Penilaian tersimpan.\n";
    }else throw new DomainException('Perintah tidak dikenal. Jalankan help.');
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
