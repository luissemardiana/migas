<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||!str_starts_with(getenv('DB_NAME')?:'','migas_test_'))exit(1);
require __DIR__.'/../app/administration.php';
$f=json_decode(file_get_contents(getenv('TEST_FIXTURE_FILE')),true);$command=$argv[1]??'';
if($command==='reset'){
    $email=$argv[2];$token=$argv[3];$expired=($argv[4]??'')==='expired';$user=one('SELECT id FROM users WHERE email=?',[$email]);
    run('INSERT INTO password_reset_tokens(user_id,token_hash,expires_at) VALUES (?,?,?)',[$user['id'],hash('sha256',$token),date('Y-m-d H:i:s',time()+($expired?-60:1800))]);
}elseif($command==='open-batch'){
    $offset=40+(int)one('SELECT COUNT(*) n FROM training_batches')['n'];
    $batch=importContent($f['admin'],'training_batches',['program_slug'=>'vibration-analysis','start_date'=>date('Y-m-d',time()+86400*$offset),'end_date'=>date('Y-m-d',time()+86400*($offset+2)),'delivery_format'=>'Daring','status'=>'confirmed']);
    importContent($f['admin'],'batch_settings',['batch_id'=>$batch,'capacity'=>2,'registration_deadline'=>date('Y-m-d H:i:s',time()+86400),'price'=>0,'method'=>'online']);echo $batch;
}elseif($command==='approve'){
    $reg=one('SELECT id FROM registrations WHERE user_id=? AND batch_id=?',[$f['bob'],(int)$argv[2]]);reviewRegistration($f['admin'],(int)$reg['id'],'approved','Uji persetujuan');
}elseif($command==='expire-attendance')run('UPDATE training_sessions SET attendance_close_at=DATE_SUB(NOW(),INTERVAL 1 MINUTE) WHERE id=?',[$f['session']]);
elseif($command==='certificate-revoke')run('UPDATE certificates SET revoked_at=NOW() WHERE id=?',[$f['certificate']]);
elseif($command==='certificate-restore')run('UPDATE certificates SET revoked_at=NULL WHERE id=?',[$f['certificate']]);
elseif($command==='close-assessment')run('UPDATE assessments SET closes_at=DATE_SUB(NOW(),INTERVAL 1 MINUTE) WHERE id=?',[$f['assessment']]);
elseif($command==='open-assessment')run('UPDATE assessments SET closes_at=DATE_ADD(NOW(),INTERVAL 1 HOUR) WHERE id=?',[$f['assessment']]);
elseif($command==='reset-throttle')run('DELETE FROM auth_attempts');
elseif($command==='view-state')echo json_encode(['leads'=>(int)one('SELECT COUNT(*) n FROM training_requests')['n'],'submissions'=>(int)one('SELECT COUNT(*) n FROM assignment_submissions')['n']]);
