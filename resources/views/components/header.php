<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
require_once __DIR__ . '/../../../app/services.php';
$isHomePage = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'index.php';
$homeReturnUrl = $isHomePage ? 'index.php' : 'index.php?return=home';
$sectionUrl = static fn(string $section): string => $isHomePage ? '#' . $section : 'index.php#' . $section;
$trainingLinks = ['Beranda' => $sectionUrl('home'), 'Program Pelatihan' => $sectionUrl('programs'), 'Jadwal' => 'public-training.php', 'Pelatihan In-House' => $sectionUrl('inhouse'), 'Instruktur' => $sectionUrl('instructors'), 'Wawasan' => $sectionUrl('insights'), 'Kontak' => $sectionUrl('contact')];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php if ($isHomePage): ?>
  <style>html[data-home-restoring] body{visibility:hidden}html[data-home-restoring]{scroll-behavior:auto!important}</style>
  <script src="assets/js/home-navigation.js?v=home-return-2"></script>
  <?php endif; ?>
  <meta name="description" content="Pelatihan profesional minyak, gas, dan pemeliharaan industri.">
  <title><?= e(($pageTitle ?? 'Pelatihan Industri').' — MIGAS Training') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','sans-serif'],display:['Manrope','sans-serif']},colors:{navy:'#142B45',navy2:'#1C3553',industrial:'#2478EA',turquoise:'#13B9AD',warm:'#FFCF65',mist:'#F7F9FC'}}}}</script>
  <link rel="stylesheet" href="assets/css/app.css?v=program-carousel-touch-3">
  <link rel="stylesheet" href="assets/css/modern.css?v=refinery-autoplay-3">
  <link rel="stylesheet" href="assets/css/portal.css?v=portal-1">
  <link rel="stylesheet" href="assets/css/interactions.css?v=20261109-1">
  <link rel="stylesheet" href="assets/css/reliability.css?v=service-photos-4">
  <script src="assets/js/reliability-nav.js?v=reliability-1" defer></script>
  <script src="assets/js/portal.js?v=portal-1" defer></script>
</head>
<body class="bg-white font-sans text-navy antialiased">
<header id="site-header" class="fixed inset-x-0 top-0 z-50 transition-all duration-500 <?= $isHomePage ? '' : 'is-inner-page' ?>">
  <div class="nav-shell mx-auto flex h-20 max-w-7xl items-center justify-between px-6 lg:px-8">
    <a href="<?= $homeReturnUrl ?>" class="flex items-center gap-3 text-white transition-colors" aria-label="Beranda MIGAS Training">
      <span class="grid h-9 w-9 place-items-center border border-current text-xs font-extrabold tracking-tighter">M</span><span class="text-sm font-extrabold tracking-[.16em]">MIGAS<span class="font-medium">TRAINING</span></span>
    </a>
    <?php $navMode = 'desktop'; require __DIR__.'/reliability-nav.php'; ?>
    <div class="flex items-center gap-3">
      <a href="<?= !empty($_SESSION['user_id']) ? 'portal.php' : 'auth.php' ?>" class="nav-account rounded-lg px-3 py-2 text-xs font-bold border"><?= !empty($_SESSION['user_id']) ? 'Portal Peserta' : 'Masuk' ?></a>
      <?php if (!$isHomePage): ?><a href="<?= $homeReturnUrl ?>" class="mobile-home-link grid h-10 w-10 place-items-center rounded border border-navy/20 text-navy transition hover:border-industrial hover:text-industrial lg:hidden" aria-label="Kembali ke Halaman Utama"><i data-lucide="house" class="h-5 w-5"></i><span class="sr-only">Kembali ke Halaman Utama</span></a><?php endif; ?>
      <?php if ($isHomePage): ?><a href="in-house.php" class="header-cta hidden rounded-sm bg-industrial px-4 py-3 text-[11px] font-bold uppercase tracking-[.12em] text-white transition hover:bg-navy lg:inline-block">Ajukan Pelatihan</a><?php else: ?><a href="<?= $homeReturnUrl ?>" class="header-cta hidden rounded-sm bg-industrial px-4 py-3 text-[11px] font-bold uppercase tracking-[.12em] text-white transition hover:bg-navy lg:inline-block">Kembali ke Beranda</a><?php endif; ?>
      <button id="menu-toggle" class="text-white lg:hidden" aria-controls="mobile-menu" aria-expanded="false" aria-label="Buka menu"><i data-lucide="menu" class="h-6 w-6"></i></button>
    </div>
  </div>
  <div id="mobile-menu" class="hidden border-t border-slate-100 bg-white px-6 py-5 shadow-lg lg:hidden">
    <?php $navMode = 'mobile'; require __DIR__.'/reliability-nav.php'; ?>
  </div>
</header>
