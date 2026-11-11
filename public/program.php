<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/catalog.php';
$slug = $_GET['program'] ?? 'vibration-analysis';
$slug = is_string($slug) && $slug !== '' ? $slug : 'vibration-analysis';
$program = findProgram($slug);
if ($program === null) {
    http_response_code(404);
    $program = ['title' => 'Program tidak ditemukan', 'category' => 'Katalog program', 'subtitle' => 'Program ini belum tersedia.'];
}
require __DIR__ . '/../resources/views/components/header.php';
?>
<main class="pt-20">
  <section class="bg-[#e8f4ff] px-4 py-16 sm:px-6 lg:px-8"><div class="mx-auto max-w-5xl"><p class="eyebrow"><?= e($program['category']) ?> / Detail program</p><h1 class="section-title"><?= e($program['title']) ?></h1><p class="mt-5 text-lg text-slate-500"><?= e($program['subtitle']) ?></p><div class="actions"><a href="programs.php" class="btn secondary">Kembali ke katalog</a><?php if(isset($program['slug'])): ?><a href="contact.php?type=public&amp;program=<?= rawurlencode($slug) ?>" class="btn">Minta silabus / daftar minat</a><?php endif; ?></div></div></section>
  <?php if (isset($program['slug'])) require __DIR__ . '/../resources/views/components/program-information.php'; ?>
</main>
<?php require __DIR__ . '/../resources/views/components/footer.php'; ?>
