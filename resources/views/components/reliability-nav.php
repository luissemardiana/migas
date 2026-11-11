<?php
(static function (string $navMode, array $trainingLinks, string $currentReliabilityCategory, ?array $service): void {
$navigationCategories = reliabilityCategories();
$navigationItems = reliabilityItems();
?>
<?php if ($navMode === 'desktop'): ?>
<nav class="reliability-nav" aria-label="Navigasi utama">
  <div class="nav-section">
    <button type="button" class="nav-trigger <?= $currentReliabilityCategory === '' ? 'nav-current' : '' ?>" aria-expanded="false" aria-controls="nav-training">Training</button>
    <div class="nav-panel nav-training-panel" id="nav-training" hidden>
      <p class="nav-kicker">BELAJAR BERSAMA MIGAS</p><p class="nav-panel-title">Temukan langkah belajar Anda.</p>
      <div class="training-nav-links"><?php foreach ($trainingLinks as $label => $href): ?><a href="<?= e($href) ?>"><?= e($label) ?></a><?php endforeach; ?></div>
    </div>
  </div>
  <?php foreach ($navigationCategories as $key => $category): ?>
  <div class="nav-section">
    <button type="button" class="nav-trigger <?= $currentReliabilityCategory === $key ? 'nav-current' : '' ?>" aria-expanded="false" aria-controls="nav-<?= e($key) ?>"><?= e($category['title']) ?></button>
    <div class="nav-panel" id="nav-<?= e($key) ?>" hidden>
      <div class="nav-panel-heading"><div><p class="nav-kicker"><?= e($category['eyebrow']) ?></p><p class="nav-panel-title"><?= e($category['title']) ?></p></div><a class="nav-overview" href="services.php?category=<?= e($key) ?>">Lihat seluruh kategori</a></div>
      <div class="nav-columns nav-columns-<?= count($category['groups']) ?>">
        <?php foreach ($category['groups'] as $group => $groupTitle): ?>
        <section class="nav-link-group"><h2><?= e($groupTitle) ?></h2>
          <?php foreach ($navigationItems as $item): if ($item['category'] !== $key || $item['group'] !== $group) continue; ?>
          <a href="<?= e(reliabilityUrl($item)) ?>" <?= ($service['slug'] ?? '') === $item['slug'] ? 'aria-current="page"' : '' ?>><?= e($item['title']) ?></a>
          <?php endforeach; ?>
        </section>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</nav>
<?php else: ?>
<nav class="reliability-mobile-nav" aria-label="Navigasi seluler">
  <details><summary>Training</summary><div class="mobile-nav-links"><?php foreach ($trainingLinks as $label => $href): ?><a href="<?= e($href) ?>"><?= e($label) ?></a><?php endforeach; ?></div></details>
  <?php foreach ($navigationCategories as $key => $category): ?>
  <details><summary><?= e($category['title']) ?></summary>
    <div class="mobile-nav-links"><a class="nav-overview" href="services.php?category=<?= e($key) ?>">Lihat seluruh kategori</a>
      <?php foreach ($category['groups'] as $group => $groupTitle): ?>
      <p class="mobile-group-title"><?= e($groupTitle) ?></p>
      <?php foreach ($navigationItems as $item): if ($item['category'] !== $key || $item['group'] !== $group) continue; ?>
      <a href="<?= e(reliabilityUrl($item)) ?>" <?= ($service['slug'] ?? '') === $item['slug'] ? 'aria-current="page"' : '' ?>><?= e($item['title']) ?></a>
      <?php endforeach; endforeach; ?>
    </div>
  </details>
  <?php endforeach; ?>
  <a class="mobile-training-cta" href="in-house.php">Ajukan Pelatihan</a>
</nav>
<?php endif; ?>
<?php })($navMode, $trainingLinks, $reliabilityCategory ?? '', $service ?? null); ?>
