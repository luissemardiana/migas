<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
try {
    $user=requireUser();$uid=(int)$user['id'];$id=(int)query('id');$kind=query('kind');$record=null;$inline=false;
    if($kind==='photo'){$record=one('SELECT photo_path file_path FROM participant_profiles WHERE user_id=?',[$uid]);$inline=true;}
    elseif($kind==='lesson'){$record=one('SELECT * FROM lessons WHERE id=? AND is_published=1',[$id]);if($record)authorizedBatch($uid,(int)$record['batch_id']);}
    elseif($kind==='submission'){$record=one('SELECT s.*,a.batch_id FROM assignment_submissions s JOIN assignments a ON a.id=s.assignment_id WHERE s.id=? AND s.user_id=?',[$id,$uid]);if($record)authorizedBatch($uid,(int)$record['batch_id']);}
    elseif($kind==='certificate'){$record=one("SELECT c.* FROM certificates c JOIN enrollments en ON en.id=c.enrollment_id JOIN registrations r ON r.id=en.registration_id WHERE c.id=? AND r.user_id=? AND r.status='approved' AND en.status='completed' AND c.revoked_at IS NULL",[$id,$uid]);}
    if(!$record||empty($record['file_path'])){http_response_code(404);throw new DomainException('File tidak tersedia.');}
    $directory=realpath(privateDirectory());$path=realpath($directory.'/'.basename($record['file_path']));
    if(!$path||!str_starts_with($path,$directory.DIRECTORY_SEPARATOR)||!is_file($path)){http_response_code(404);throw new DomainException('File tidak tersedia.');}
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);
    if($inline&&!in_array($mime,['image/png','image/jpeg','image/webp'],true))throw new DomainException('Format tidak didukung.');
    if($kind==='lesson'&&query('inline')==='1'&&$mime==='video/mp4')$inline=true;
    $filename=$record['file_name']??($kind==='certificate'?'sertifikat.pdf':basename($path));
    $size=filesize($path);$start=0;$end=$size-1;
    if($mime==='video/mp4'){
        header('Accept-Ranges: bytes');
        if(isset($_SERVER['HTTP_RANGE'])){
            if(!preg_match('/^bytes=(\d*)-(\d*)$/',$_SERVER['HTTP_RANGE'],$range)||($range[1]===''&&$range[2]==='')){http_response_code(416);header('Content-Range: bytes */'.$size);exit;}
            if($range[1]===''){$start=max(0,$size-(int)$range[2]);}else{$start=(int)$range[1];if($range[2]!=='')$end=min($end,(int)$range[2]);}
            if($start>$end||$start>=$size){http_response_code(416);header('Content-Range: bytes */'.$size);exit;}
            http_response_code(206);header('Content-Range: bytes '.$start.'-'.$end.'/'.$size);
        }
    }
    header('Content-Type: '.$mime);header('Content-Length: '.($end-$start+1));
    header("Content-Disposition: ".($inline?'inline':'attachment')."; filename=\"download\"; filename*=UTF-8''".rawurlencode($filename));
    header("Content-Security-Policy: default-src 'none'; sandbox");session_write_close();
    $stream=fopen($path,'rb');fseek($stream,$start);$remaining=$end-$start+1;
    while($remaining>0&&!feof($stream)&&!connection_aborted()){$chunk=fread($stream,min(65536,$remaining));if($chunk===false||$chunk==='')break;echo $chunk;$remaining-=strlen($chunk);}fclose($stream);exit;
}catch(Throwable $error){if(http_response_code()<400)http_response_code($error instanceof DomainException?403:503);header('Content-Type: text/plain; charset=UTF-8');echo $error instanceof DomainException?$error->getMessage():'File belum dapat diunduh.';}
