<?php
require_once __DIR__ . '/../app/catalog.php';
$items = allPrograms();
$programItems = $items;
require __DIR__ . '/../resources/views/components/header.php';
?>
<main class="pt-20"><section class="bg-mist px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-28"><div class="mx-auto max-w-7xl"><p class="eyebrow">Katalog program</p><h1 class="section-title max-w-4xl">Delapan Topik Pelatihan Prioritas.</h1><div class="mt-8"><?php require __DIR__ . '/../resources/views/components/program-carousel.php'; ?></div><p class="mt-8 text-sm leading-6 text-slate-500">Jadwal, biaya, durasi, kuota, dan informasi sertifikat hanya ditampilkan setelah dikonfirmasi.</p></div></section></main>
<?php require __DIR__ . '/../resources/views/components/catalog-search.php'; ?>
<?php require __DIR__ . '/../resources/views/components/footer.php'; ?>
