<?php
declare(strict_types=1);
require __DIR__.'/../app/participant.php';
$error='';$batch=null;
try {
    $user=requireUser();
    $batch=one('SELECT b.*,p.title,s.capacity,s.registration_deadline,s.price,s.payment_instructions FROM training_batches b JOIN training_programs p ON p.slug=b.program_slug AND p.is_active=1 LEFT JOIN batch_settings s ON s.batch_id=b.id WHERE b.id=?',[(int)query('batch')]);
    if (!$batch) { http_response_code(404); throw new DomainException('Jadwal tidak ditemukan.'); }
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        checkCsrf(); if(input('consent')!=='1')throw new DomainException('Persetujuan pendaftaran wajib dicentang.');
        $id=registerBatch((int)$user['id'],(int)$batch['id']);flash('Pendaftaran berhasil. Tunggu verifikasi admin.');redirect('portal.php?view=registrations&id='.$id);
    }
} catch(Throwable $e) { $error=$e instanceof DomainException?$e->getMessage():'Pendaftaran belum dapat diproses. Coba beberapa saat lagi.'; if(!$e instanceof DomainException){error_log($e->getMessage());http_response_code(503);} }
require __DIR__.'/../resources/views/components/header.php';
?>
<main class="public-page"><div class="page-heading"><p class="eyebrow">PENDAFTARAN PESERTA</p><h1>Konfirmasi pilihan pelatihan.</h1><p>Admin akan memeriksa pendaftaran sebelum membuka akses ke kelas dan materi Anda.</p></div>
<?php if($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if($batch): ?><section class="panel"><h2><?= e($batch['title']) ?></h2><dl class="meta-list"><div><dt>Periode</dt><dd><?= e($batch['start_date'].' — '.$batch['end_date']) ?></dd></div><div><dt>Metode</dt><dd><?= e($batch['delivery_format']) ?></dd></div><div><dt>Biaya</dt><dd><?= e(rupiah($batch['price'])) ?></dd></div><div><dt>Lokasi</dt><dd><?= e($batch['location']?:'Diumumkan oleh admin') ?></dd></div></dl>
<?php if($batch['status']==='confirmed' && $batch['price']!==null && $batch['capacity'] && $batch['registration_deadline'] && strtotime($batch['registration_deadline'])>time()): ?><form method="post" class="portal-form" data-consent-form data-busy-form><?= csrfField() ?><label class="check"><input type="checkbox" name="consent" value="1" required>Saya telah membaca jadwal dan biaya, serta menyetujui penggunaan data untuk pendaftaran pelatihan.</label><button class="btn" type="submit" disabled>Kirim pendaftaran</button></form><?php else: ?><p class="notice info">Pendaftaran resmi belum tersedia untuk jadwal ini.</p><a class="btn" href="contact.php?type=public&amp;program=<?= e($batch['program_slug']) ?>">Kirim daftar minat</a><?php endif; ?></section><?php endif; ?></main>
<?php require __DIR__.'/../resources/views/components/footer.php'; ?>
