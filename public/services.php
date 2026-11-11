<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/services.php';
$reliabilityCategory = is_string($_GET['category'] ?? null) ? $_GET['category'] : 'services';
$category = reliabilityCategories()[$reliabilityCategory] ?? null;
if (!$category) http_response_code(404);
$pageTitle = $category['title'] ?? 'Kategori tidak ditemukan';
require __DIR__.'/../resources/views/components/header.php';
?>
<main class="reliability-page">
<?php if (!$category): ?>
  <section class="service-container service-empty"><p class="service-eyebrow">404 / KATEGORI</p><h1>Kategori tidak ditemukan.</h1><a class="service-button" href="services.php?category=services">Lihat layanan</a><a class="service-text-link" href="<?= e($homeReturnUrl) ?>">Kembali ke beranda</a></section>
<?php else: ?>
  <section class="service-category-hero"><div class="service-container">
    <nav class="service-breadcrumb" aria-label="Breadcrumb"><a href="<?= e($homeReturnUrl) ?>">Beranda</a><span aria-hidden="true">/</span><span aria-current="page"><?= e($category['title']) ?></span></nav>
    <p class="service-eyebrow"><?= e($category['eyebrow']) ?></p><div class="service-category-heading"><h1><?= e($category['headline']) ?></h1><p><?= e($category['description']) ?></p></div>
    <div class="service-category-tabs"><?php foreach (reliabilityCategories() as $key => $tab): ?><a href="services.php?category=<?= e($key) ?>" <?= $key === $reliabilityCategory ? 'aria-current="page"' : '' ?>><?= e($tab['title']) ?></a><?php endforeach; ?></div>
  </div></section>
  <div class="service-container service-catalog">
  <?php foreach ($category['groups'] as $group => $groupTitle): ?>
    <section class="service-catalog-group"><div class="service-group-heading"><p class="service-eyebrow"><?= e($category['title']) ?></p><h2><?= e($groupTitle) ?></h2></div>
    <div class="service-card-grid">
      <?php foreach (reliabilityItems() as $item): if ($item['category'] !== $reliabilityCategory || $item['group'] !== $group) continue; ?>
      <article class="service-card"><a class="service-card-main" href="<?= e(reliabilityUrl($item)) ?>"><div class="service-card-photo"><img src="<?= e(reliabilityImage($item['image'])) ?>" alt="<?= e($item['image_alt'] ?? $item['title']) ?>" width="1200" height="900" loading="lazy"></div><div class="service-card-copy"><h3><?= e($item['title']) ?></h3><p><?= e($item['subtitle']) ?></p><span class="service-card-action">Jelajahi detail</span></div></a><?php $photoCredit = reliabilityPhotoCredit($item['image']); require __DIR__.'/../resources/views/components/service-photo-credit.php'; ?></article>
      <?php endforeach; ?>
    </div></section>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
</main>
<?php require __DIR__.'/../resources/views/components/footer.php'; ?>
