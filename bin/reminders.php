<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../app/bootstrap.php';
try{
    $sessions=rows("SELECT s.id,s.title,s.starts_at,r.user_id FROM training_sessions s JOIN registrations r ON r.batch_id=s.batch_id JOIN enrollments en ON en.registration_id=r.id WHERE r.status='approved' AND en.status='active' AND s.starts_at BETWEEN NOW() AND DATE_ADD(NOW(),INTERVAL 24 HOUR)");
    $sent=0;
    foreach($sessions as $s){
        database()->beginTransaction();
        try{
            $key='session:'.$s['id'].':'.$s['user_id'].':'.$s['starts_at'];
            if(run('INSERT IGNORE INTO notification_dispatches(event_key) VALUES (?)',[$key])){notifyUser((int)$s['user_id'],'Pengingat sesi pelatihan',$s['title'].' dimulai '.$s['starts_at'].' WIB. Lihat lokasi atau tautan kelas pada Jadwal Saya.','schedule');$sent++;}
            database()->commit();
        }catch(Throwable $e){if(database()->inTransaction())database()->rollBack();throw $e;}
    }
    echo "Pengingat dibuat: $sent\n";
}catch(Throwable $e){error_log('MIGAS reminders: '.$e->getMessage());exit(1);}
