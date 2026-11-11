<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/services.php';
$slug = is_string($_GET['slug'] ?? null) ? $_GET['slug'] : '';
$service = reliabilityItems()[$slug] ?? null;
if (!$service) http_response_code(404);
$reliabilityCategory = $service['category'] ?? '';
$category = reliabilityCategories()[$reliabilityCategory] ?? null;
$pageTitle = $service['title'] ?? 'Layanan tidak ditemukan';
$isCompetency = $reliabilityCategory === 'competency';
require __DIR__.'/../resources/views/components/header.php';
?>
<main class="reliability-page">
<?php if (!$service): ?>
  <section class="service-container service-empty"><p class="service-eyebrow">404 / LAYANAN</p><h1>Layanan tidak ditemukan.</h1><p>Pilih topik melalui menu layanan untuk membuka detail yang tersedia.</p><a class="service-button" href="services.php?category=services">Lihat layanan</a><a class="service-text-link" href="<?= e($homeReturnUrl) ?>">Kembali ke beranda</a></section>
<?php else: ?>
  <section class="service-detail-hero"><div class="service-container">
    <nav class="service-breadcrumb" aria-label="Breadcrumb"><a href="<?= e($homeReturnUrl) ?>">Beranda</a><span aria-hidden="true">/</span><a href="services.php?category=<?= e($reliabilityCategory) ?>"><?= e($category['title']) ?></a><span aria-hidden="true">/</span><span aria-current="page"><?= e($service['title']) ?></span></nav>
    <div class="service-hero-grid"><div class="service-hero-copy"><p class="service-eyebrow"><?= e($category['groups'][$service['group']]) ?></p><h1><?= e($service['title']) ?></h1><p class="service-subtitle"><?= e($service['subtitle']) ?></p><div class="service-actions"><a class="service-button" href="<?= e(reliabilityInquiry($service)) ?>" target="_blank" rel="noopener noreferrer"><?= $isCompetency ? 'Diskusikan Pelatihan' : 'Diskusikan Kebutuhan' ?></a><a class="service-text-link" href="#scope">Lihat cakupan</a></div><p class="service-hero-note">Mulai dari kebutuhan Anda. Ruang lingkup dibahas bersama.</p></div>
    <div class="service-hero-media"><figure class="service-hero-visual"><img src="<?= e(reliabilityImage($service['image'])) ?>" alt="<?= e($service['image_alt'] ?? $service['title']) ?>" width="1200" height="900" fetchpriority="high"><figcaption><span>MIGAS / <?= $isCompetency ? 'LEARNING' : 'RELIABILITY' ?></span><span>Ilustrasi bidang layanan</span></figcaption></figure><?php $photoCredit = reliabilityPhotoCredit($service['image']); require __DIR__.'/../resources/views/components/service-photo-credit.php'; ?></div></div>
  </div></section>
  <section class="service-container service-intro"><div><p class="service-eyebrow">01 / PENDEKATAN</p><h2><?= $isCompetency ? 'Belajar dengan konteks yang jelas.' : 'Mulai dari persoalan yang ingin dijawab.' ?></h2></div><div><p class="service-intro-text"><?= e($service['intro']) ?></p><div class="service-audience"><span><?= $isCompetency ? 'Untuk siapa' : 'Tim yang terlibat' ?></span><p><?= e($service['audience']) ?></p></div></div></section>
  <section id="scope" class="service-scope-section"><div class="service-container"><p class="service-eyebrow">02 / <?= $isCompetency ? 'RUANG BELAJAR' : 'CAKUPAN PEMBAHASAN' ?></p><h2><?= $isCompetency ? 'Apa yang akan dipelajari.' : 'Fokus yang dibahas bersama.' ?></h2><div class="service-scope-grid"><?php foreach ($service['scope'] as $index => $scope): ?><article><span class="service-step-number"><?= sprintf('%02d', $index + 1) ?></span><h3><?= e($scope) ?></h3></article><?php endforeach; ?></div></div></section>
  <section class="service-container service-outcomes"><div><p class="service-eyebrow">03 / HASIL YANG DITUJU</p><h2><?= $isCompetency ? 'Bawa pembelajaran ke pekerjaan.' : 'Informasi yang bisa ditindaklanjuti.' ?></h2><p>Hasil akhir disesuaikan dengan kebutuhan, ketersediaan data, dan ruang lingkup yang disepakati.</p></div><ul><?php foreach ($service['outputs'] as $output): ?><li><span aria-hidden="true">✓</span><?= e($output) ?></li><?php endforeach; ?></ul></section>
  <section class="service-container"><div class="service-consultation"><div><p class="service-eyebrow">MULAI PERCAKAPAN</p><h2>Ceritakan kebutuhan tim Anda.</h2><p>Sampaikan topik, konteks pekerjaan, dan target waktu. Tim kami akan membahas kebutuhan serta ketersediaan ruang lingkup ini bersama Anda.</p></div><div class="service-consultation-actions"><a class="service-button" href="<?= e(reliabilityInquiry($service)) ?>" target="_blank" rel="noopener noreferrer">Hubungi Tim via WhatsApp</a><a class="service-text-link" href="mailto:jogjacreativeproduction@gmail.com?subject=<?= rawurlencode('Diskusi '.$service['title']) ?>">Kirim pertanyaan melalui email</a></div></div></section>
  <section class="service-container service-related"><div class="service-group-heading"><p class="service-eyebrow">LANJUTKAN EKSPLORASI</p><h2>Topik terkait.</h2><a class="service-text-link" href="services.php?category=<?= e($reliabilityCategory) ?>">Lihat semua <?= e($category['title']) ?></a></div><div class="service-related-grid"><?php $shown = 0; foreach (reliabilityItems() as $related): if ($related['category'] !== $reliabilityCategory || $related['slug'] === $slug || $shown >= 3) continue; $shown++; ?><a href="<?= e(reliabilityUrl($related)) ?>"><span><?= e($related['title']) ?></span></a><?php endforeach; ?></div><a class="service-back-home" href="<?= e($homeReturnUrl) ?>">Kembali ke halaman sebelumnya di beranda</a></section>
<?php endif; ?>
</main>
<?php require __DIR__.'/../resources/views/components/footer.php'; ?>
