<?php
$topics = [
    ['category' => 'Reliability', 'title' => 'Apa itu Reliability Centered Maintenance?'],
    ['category' => 'Diagnostics', 'title' => 'Kapan Vibration Analysis dilakukan?'],
    ['category' => 'Diagnostics', 'title' => 'Analisis pelumas dan kondisi mesin'],
];
require __DIR__ . '/../resources/views/components/header.php';
?>
<?php require __DIR__ . '/../resources/views/components/article-list.php'; ?>
<main class="pt-20"><section class="bg-mist px-6 py-20 lg:px-8 lg:py-28"><div class="mx-auto max-w-7xl"><p class="eyebrow">Wawasan industri</p><h1 class="section-title max-w-4xl">Topik Teknis untuk Dibahas dan Dipelajari.</h1><p class="mt-6 max-w-3xl text-lg leading-8 text-slate-600">Rancangan konten pendukung mencakup artikel teknis, profil instruktur, studi kasus edukatif yang mendapat izin publikasi, serta FAQ tentang materi dan metode pelatihan.</p><p class="mt-4 max-w-3xl text-sm leading-6 text-slate-500">Topik di bawah merupakan usulan. Artikel akan diterbitkan setelah ditinjau dan disetujui instruktur.</p><div class="mt-10 grid gap-5 md:grid-cols-3"><?php foreach ($topics as $topic): ?><article class="rounded-[20px] border border-slate-200 bg-white p-7"><p class="text-xs font-bold uppercase tracking-widest text-industrial"><?= htmlspecialchars($topic['category']) ?></p><h2 class="mt-5 text-xl font-bold leading-snug"><?= htmlspecialchars($topic['title']) ?></h2><p class="mt-4 text-sm text-slate-500">Status: menunggu tinjauan instruktur</p></article><?php endforeach; ?></div><div class="mt-12 rounded-[20px] bg-[#e8f4ff] p-7"><h2 class="text-lg font-bold">Informasi pelatihan</h2><p class="mt-3 max-w-3xl leading-7 text-slate-600">Informasi program akan menjelaskan materi, metode, instruktur, dan fasilitas setelah terkonfirmasi. Profil dan studi kasus hanya ditampilkan dengan persetujuan publikasi.</p></div></div></section></main>
<?php require __DIR__ . '/../resources/views/components/footer.php'; ?>
