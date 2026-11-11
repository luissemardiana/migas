<?php
$carouselCount = count($programItems);
$initialCarouselIndex = min(2, max(0, $carouselCount - 1));
?>
<div class="program-carousel" data-program-carousel>
  <div class="program-stage" data-program-stage role="region" aria-roledescription="carousel" aria-label="Program pelatihan">
    <?php foreach ($programItems as $index => $program): ?>
      <?php
        $detailUrl = 'program.php?program=' . rawurlencode($program['slug']);
      ?>
      <?php $slideDistance = abs($index - $initialCarouselIndex); ?>
      <article class="program-slide<?= $slideDistance === 0 ? ' is-active' : ($slideDistance === 1 ? ' is-near' : ' is-far') ?>" data-program-slide data-title="<?= htmlspecialchars($program['title'], ENT_QUOTES, 'UTF-8') ?>">
        <a class="program-slide-link" href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>">
          <img class="program-slide-image" src="<?= htmlspecialchars($program['image'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" decoding="async">
          <span class="program-slide-shade" aria-hidden="true"></span>
          <span class="program-slide-number"><?= htmlspecialchars($program['number'], ENT_QUOTES, 'UTF-8') ?></span>
          <span class="program-slide-copy">
            <span class="program-slide-category"><?= htmlspecialchars($program['category'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="program-slide-title"><?= htmlspecialchars($program['title'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="program-slide-subtitle"><?= htmlspecialchars($program['subtitle'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="program-slide-cta">Lihat detail program</span>
          </span>
        </a>
      </article>
    <?php endforeach; ?>
  </div>
  <div class="program-carousel-footer">
    <p class="program-carousel-status" data-program-status aria-live="polite">Program <?= $initialCarouselIndex + 1 ?> dari <?= $carouselCount ?></p>
    <div class="program-carousel-dots" role="group" aria-label="Pilih program">
      <?php foreach ($programItems as $index => $program): ?>
        <button class="program-carousel-dot<?= $index === $initialCarouselIndex ? ' is-active' : '' ?>" type="button" data-program-dot="<?= $index ?>" aria-label="Tampilkan <?= htmlspecialchars($program['title'], ENT_QUOTES, 'UTF-8') ?>" aria-pressed="<?= $index === $initialCarouselIndex ? 'true' : 'false' ?>"></button>
      <?php endforeach; ?>
    </div>
  </div>
</div>
