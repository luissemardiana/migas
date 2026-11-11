<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/batches.php';
require_once __DIR__ . '/../app/catalog.php';
try { $batches = availableBatches(); } catch (Throwable $exception) { $batches = publicTrainingBatches(); }
require __DIR__ . '/../resources/views/components/header.php';
?>
<main class="pt-20">
  <section class="bg-[#f3f9ff] px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-28">
    <div class="mx-auto max-w-7xl">
      <p class="eyebrow">Public Training</p>
      <h1 class="section-title max-w-4xl">Temukan Jadwal Pelatihan Anda.</h1>
      <p class="mt-4 max-w-3xl text-base leading-7 text-slate-600 sm:text-lg">Lihat agenda pelatihan, bandingkan metode dan biaya, lalu daftar pada batch resmi atau kirim minat untuk jadwal yang masih diusulkan.</p>
      <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-950 sm:p-6">
        <strong>Perlu diketahui:</strong> jadwal berlabel <strong>Usulan</strong> belum dikonfirmasi dan belum membuka pendaftaran. Mengirim minat tidak mengunci kursi. Admin akan menghubungi Anda setelah tanggal, kuota, dan biaya resmi tersedia.
      </div>
      <div class="mt-6 flex flex-wrap gap-3"><a href="programs.php" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-navy px-5 py-3 text-sm font-bold text-white transition hover:bg-industrial">Lihat Semua Program</a><a href="#agenda" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-navy/20 bg-white px-5 py-3 text-sm font-bold text-navy transition hover:border-industrial hover:text-industrial">Lihat Jadwal Usulan</a></div>

      <div id="agenda" class="mt-10 scroll-mt-28">
      <?php $calendarEvents = array_map(fn($b) => ['date'=>$b['start_date'],'title'=>$b['title'],'url'=>'program.php?program='.$b['program_slug']],$batches); require __DIR__.'/../resources/views/components/calendar.php'; ?>
      <h2 class="text-2xl font-extrabold sm:text-3xl">Agenda Pelatihan</h2>
      <p class="mt-2 text-sm leading-6 text-slate-500">Tanggal, durasi, format, dan status setiap batch ditampilkan di bawah ini.</p>
      <?php if ($batches !== []): ?>
        <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
          <?php foreach ($batches as $batch): ?>
            <?php
              $start = new DateTimeImmutable($batch['start_date']);
              $end = new DateTimeImmutable($batch['end_date']);
              $duration = $start->diff($end)->days + 1;
              $isConfirmed = $batch['status'] === 'confirmed';
            ?>
            <article class="flex min-w-0 flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
              <div class="flex flex-wrap items-center justify-between gap-3">
                <span class="rounded-full bg-[#e8f4ff] px-3 py-1.5 text-xs font-bold text-industrial"><?= htmlspecialchars($batch['category'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="rounded-full px-3 py-1.5 text-xs font-bold <?= $isConfirmed ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' ?>"><?= $isConfirmed ? 'Dikonfirmasi' : 'Usulan' ?></span>
              </div>
              <h2 class="mt-5 break-words text-xl font-bold sm:text-2xl"><?= htmlspecialchars($batch['title'], ENT_QUOTES, 'UTF-8') ?></h2>
              <p class="mt-2 text-sm leading-6 text-slate-500"><?= htmlspecialchars($batch['subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
              <dl class="mt-6 grid gap-4 border-t border-slate-100 pt-5 text-sm">
                <div><dt class="font-semibold text-slate-500">Tanggal</dt><dd class="mt-1 font-bold text-navy"><?= htmlspecialchars(indonesianDateRange($batch['start_date'], $batch['end_date']), ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div class="grid grid-cols-2 gap-3">
                  <div><dt class="font-semibold text-slate-500">Durasi</dt><dd class="mt-1 font-bold text-navy"><?= $duration ?> hari</dd></div>
                  <div><dt class="font-semibold text-slate-500">Format</dt><dd class="mt-1 font-bold text-navy"><?= htmlspecialchars($batch['delivery_format'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                </div>
                <?php if (!empty($batch['location'])): ?><div><dt class="font-semibold text-slate-500">Lokasi</dt><dd class="mt-1 font-bold text-navy"><?= htmlspecialchars($batch['location'], ENT_QUOTES, 'UTF-8') ?></dd></div><?php endif; ?>
              </dl>
              <dl class="meta-list"><div><dt>Batas daftar (WIB)</dt><dd><?= e($batch['registration_deadline']??'Belum diumumkan') ?></dd></div><div><dt>Sisa kuota</dt><dd><?= $isConfirmed && isset($batch['capacity']) ? max(0,(int)$batch['capacity']-(int)$batch['reserved']).' peserta' : 'Belum diumumkan' ?></dd></div><div><dt>Biaya</dt><dd><?= e($isConfirmed ? rupiah($batch['price']??null) : 'Belum diumumkan') ?></dd></div></dl>
              <?php if (isset($batch['id']) && batchOpen($batch)): ?><a class="btn" href="enroll.php?batch=<?= (int)$batch['id'] ?>">Daftar pelatihan</a><?php else: ?><a href="contact.php?type=public&amp;program=<?= rawurlencode($batch['program_slug']) ?>" class="btn">Kirim Daftar Minat</a><?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-6 text-sm leading-6 text-slate-600">Belum ada batch yang ditampilkan. Pilih program dan kirim daftar minat; admin akan menghubungi setelah jadwal tersedia.</div>
      <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
    <div class="mx-auto max-w-7xl">
      <p class="eyebrow">Langkah berikutnya</p>
      <h2 class="section-title max-w-4xl">Apa yang Terjadi Setelah Anda Mengirim Minat?</h2>
      <p class="mt-4 max-w-3xl leading-7 text-slate-500">Daftar minat bukan pendaftaran final. Admin menghubungi Anda setelah informasi batch disahkan agar Anda bisa memutuskan dengan data yang lengkap.</p>
      <?php $steps = [['Pilih topik', 'Pilih program yang sesuai kebutuhan belajar Anda.'], ['Kirim daftar minat', 'Isi kontak dan program. Permintaan disimpan untuk ditindaklanjuti.'], ['Admin menghubungi', 'Admin menyampaikan jadwal, biaya, kuota, dan persyaratan resmi.'], ['Tentukan keikutsertaan', 'Lanjut mendaftar setelah menerima informasi dan konfirmasi resmi.']]; ?>
      <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($steps as $i => $step): ?>
          <article class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6"><span class="grid h-11 w-11 place-items-center rounded-full <?= $i % 2 ? 'bg-turquoise' : 'bg-industrial' ?> text-lg font-bold text-white"><?= $i + 1 ?></span><h3 class="mt-5 font-bold"><?= htmlspecialchars($step[0], ENT_QUOTES, 'UTF-8') ?></h3><p class="mt-2 text-sm leading-6 text-slate-500"><?= htmlspecialchars($step[1], ENT_QUOTES, 'UTF-8') ?></p></article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>
<?php require __DIR__ . '/../resources/views/components/footer.php'; ?>
