<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
$certificate=null;$error='';$token=query('token');
try {
    if(preg_match('/^[a-f0-9]{64}$/',$token))$certificate=one("SELECT c.* FROM certificates c JOIN enrollments en ON en.id=c.enrollment_id JOIN registrations r ON r.id=en.registration_id WHERE c.verification_token=? AND c.revoked_at IS NULL AND en.status='completed' AND r.status='approved'",[$token]);
    if(!$certificate)http_response_code(404);
    if(query('qr')==='1'){
        if(!$certificate){http_response_code(404);exit;}
        if(!class_exists(\BaconQrCode\Writer::class)||!getenv('APP_URL')){http_response_code(503);exit;}
        $renderer=new \BaconQrCode\Renderer\ImageRenderer(new \BaconQrCode\Renderer\RendererStyle\RendererStyle(240),new \BaconQrCode\Renderer\Image\SvgImageBackEnd());
        header('Content-Type: image/svg+xml');header('Cache-Control: no-store');echo(new \BaconQrCode\Writer($renderer))->writeString(rtrim(getenv('APP_URL'),'/').'/verify.php?token='.$token);exit;
    }
}catch(Throwable $e){error_log($e->getMessage());http_response_code(503);$error='Verifikasi belum dapat diproses.';}
require __DIR__.'/../resources/views/components/header.php';
?>
<main class="public-page"><div class="page-heading"><p class="eyebrow">VERIFIKASI SERTIFIKAT</p><h1><?= $certificate?'Sertifikat terverifikasi.':'Sertifikat tidak ditemukan.' ?></h1></div><section class="panel"><?php if($certificate): ?><span class="badge approved">Terverifikasi</span><dl class="meta-list"><div><dt>Peserta</dt><dd><?= e($certificate['participant_name']) ?></dd></div><div><dt>Program</dt><dd><?= e($certificate['program_name']) ?></dd></div><div><dt>Nomor sertifikat</dt><dd><?= e($certificate['certificate_number']) ?></dd></div><div><dt>Tanggal terbit</dt><dd><?= e($certificate['issued_at']) ?></dd></div></dl><?php else: ?><p class="muted"><?= e($error?:'Tautan tidak valid atau sertifikat telah dicabut. Hubungi tim untuk konfirmasi.') ?></p><?php endif; ?><div class="actions"><a href="index.php?return=home" class="btn secondary">Kembali ke beranda</a></div></section></main>
<?php require __DIR__.'/../resources/views/components/footer.php'; ?>
