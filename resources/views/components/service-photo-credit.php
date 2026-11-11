<?php if (!empty($photoCredit) && !empty($photoCredit['source'])): ?>
<p class="service-photo-credit">Foto: <a href="<?= e($photoCredit['source']) ?>" target="_blank" rel="noopener noreferrer"><?= e($photoCredit['author']) ?></a><span aria-hidden="true"> · </span><?php if (!empty($photoCredit['license_url'])): ?><a href="<?= e($photoCredit['license_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($photoCredit['license']) ?></a><?php else: ?><?= e($photoCredit['license']) ?><?php endif; ?><span> · Dipotong dan dioptimalkan.</span></p>
<?php endif; ?>
