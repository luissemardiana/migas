<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/programs.php';

require_once __DIR__ . '/../app/bootstrap.php';

$requestedType = $_GET['type'] ?? 'public';
$requestedType = is_string($requestedType) ? $requestedType : '';
$type = in_array($requestedType, ['public', 'inhouse', 'guest-lecture'], true) ? $requestedType : 'public';
$isInHouse = $type === 'inhouse';
$isGuest = $type === 'guest-lecture';
$program = $_GET['program'] ?? '';
$program = is_string($program) ? $program : '';
$availablePrograms = allPrograms();
$programSlugs = array_column($availablePrograms, 'slug');
$errors = [];
$formError = '';
$success = !empty($_SESSION['request_success']);
unset($_SESSION['request_success']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($requestMethod === 'POST') {
    $postedType = $_POST['request_type'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    $postedType = is_string($postedType) ? $postedType : '';
    $token = is_string($token) ? $token : '';
    if (!in_array($postedType, ['public', 'inhouse', 'guest-lecture'], true) || $postedType !== $type) {
        http_response_code(400);
        $formError = 'Jenis permintaan tidak valid. Muat ulang halaman dan coba lagi.';
    } elseif (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        $formError = 'Sesi formulir sudah kedaluwarsa. Muat ulang halaman dan kirim kembali.';
    } else {
        $value = static function (string $key): string {
            $raw = $_POST[$key] ?? '';
            return is_string($raw) ? trim($raw) : '';
        };
        $name = $value($isInHouse || $isGuest ? 'contact_name' : 'name');
        $organization = $value('organization');
        $contact = $value('contact');
        $programSlug = $value('program');
        $scheduleLocation = $value('schedule_location');
        $sectorProgram = $value('sector_program');
        $participantsText = $value('participants');
        $preferences = $value('preferences');
        $learningGoal = $value('learning_goal');
        $sessionTopic = $value('session_topic');

        if ($value('website') !== '') {
            $formError = 'Permintaan tidak dapat diproses.';
        } elseif ($name === '' || mb_strlen($name) > 160) {
            $errors[] = 'Nama wajib diisi dan maksimal 160 karakter.';
        }
        if (($isInHouse || $isGuest) && $organization === '') {
            $errors[] = 'Nama perusahaan atau institusi wajib diisi (maksimal 180 karakter).';
        }
        if (mb_strlen($organization) > 180) {
            $errors[] = 'Nama perusahaan atau institusi maksimal 180 karakter.';
        }
        $validEmail = filter_var($contact, FILTER_VALIDATE_EMAIL) !== false;
        $validPhone = preg_match('/^[+0-9() .-]{8,30}$/', $contact) === 1;
        if ((!$validEmail && !$validPhone) || mb_strlen($contact) > 190) {
            $errors[] = 'Isi email yang valid atau nomor WhatsApp yang dapat dihubungi.';
        }
        if (!$isInHouse && !$isGuest && !in_array($programSlug, $programSlugs, true)) {
            $errors[] = 'Pilih salah satu program yang tersedia.';
        }
        if ($isInHouse && ($sectorProgram === '' || $learningGoal === '')) {
            $errors[] = 'Sektor/program dan tujuan belajar wajib diisi.';
        }
        if ($isInHouse && (filter_var($participantsText, FILTER_VALIDATE_INT) === false || (int) $participantsText < 1)) {
            $errors[] = 'Jumlah peserta harus berupa angka minimal 1.';
        }
        if ($isGuest && $sessionTopic === '') {
            $errors[] = 'Topik dan tujuan sesi wajib diisi.';
        }
        if (mb_strlen($scheduleLocation) > 255 || mb_strlen($sectorProgram) > 255 || mb_strlen($preferences) > 5000 || mb_strlen($learningGoal) > 10000 || mb_strlen($sessionTopic) > 10000) {
            $errors[] = 'Beberapa isian terlalu panjang.';
        }
        if (($_POST['privacy_consent'] ?? '') !== '1') {
            $errors[] = 'Persetujuan penggunaan data wajib dicentang.';
        }

        if ($formError === '' && $errors === []) {
            try {
                $statement = database()->prepare(
                    'INSERT INTO training_requests (
                        request_type, full_name, organization, contact, program_slug,
                        schedule_location, sector_program, participants, preferences,
                        learning_goal, session_topic, privacy_consent
                    ) VALUES (
                        :request_type, :full_name, :organization, :contact, :program_slug,
                        :schedule_location, :sector_program, :participants, :preferences,
                        :learning_goal, :session_topic, 1
                    )'
                );
                $statement->execute([
                    'request_type' => $type,
                    'full_name' => $name,
                    'organization' => $organization !== '' ? $organization : null,
                    'contact' => $contact,
                    'program_slug' => $programSlug !== '' ? $programSlug : null,
                    'schedule_location' => $scheduleLocation !== '' ? $scheduleLocation : null,
                    'sector_program' => $sectorProgram !== '' ? $sectorProgram : null,
                    'participants' => $participantsText !== '' ? (int) $participantsText : null,
                    'preferences' => $preferences !== '' ? $preferences : null,
                    'learning_goal' => $learningGoal !== '' ? $learningGoal : null,
                    'session_topic' => $sessionTopic !== '' ? $sessionTopic : null,
                ]);

                $_SESSION['request_success'] = true;
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: contact.php?type=' . rawurlencode($type), true, 303);
                exit;
            } catch (PDOException $error) {
                error_log('MIGAS training request could not be stored: ' . $error->getMessage());
                $formError = 'Permintaan belum tersimpan karena database belum terhubung. Coba lagi nanti atau hubungi admin.';
            }
        }
    }
}

require __DIR__ . '/../resources/views/components/header.php';
?>
<main class="pt-20"><section id="contact-request" class="bg-[#e9fbf7] px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-28"><div class="mx-auto max-w-4xl"><p class="eyebrow"><?= $isInHouse ? 'In-House Training' : ($isGuest ? 'Guest Lecture' : 'Public Training') ?></p><h1 class="section-title"><?= $isInHouse ? 'Ajukan Kebutuhan Pelatihan Perusahaan.' : ($isGuest ? 'Ajukan Sesi Guest Lecture.' : 'Kirim Daftar Minat Pelatihan.') ?></h1><p class="mt-5 max-w-2xl leading-7 text-slate-600"><?= $isInHouse ? 'Bagikan informasi perusahaan dan kebutuhan belajar tim untuk pembahasan silabus serta proposal.' : ($isGuest ? 'Kampus atau asosiasi dapat menyampaikan kebutuhan kuliah tamu atau sesi berbagi teknis.' : 'Jika jadwal kelas belum tersedia, permintaan akan menjadi daftar minat untuk ditindaklanjuti saat batch resmi dibuka.') ?></p>
<?php if ($success): ?><div role="status" class="mt-8 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">Permintaan berhasil disimpan. Tim MIGAS Training akan menindaklanjutinya.</div><?php endif; ?>
<?php if ($formError !== ''): ?><div role="alert" class="mt-8 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($errors !== []): ?><div role="alert" class="mt-8 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><ul class="list-inside list-disc space-y-1"><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form data-consent-gated-form class="mt-8 grid min-w-0 gap-5 rounded-2xl bg-white p-5 shadow-sm sm:mt-10 sm:gap-6 sm:rounded-[24px] sm:p-7 lg:p-10" action="contact.php?type=<?= rawurlencode($type) ?><?= $program !== '' ? '&amp;program=' . rawurlencode($program) : '' ?>" method="post">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
  <input type="hidden" name="request_type" value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
  <div aria-hidden="true" class="absolute -left-[10000px] top-auto h-px w-px overflow-hidden"><label>Jangan isi kolom ini<input name="website" tabindex="-1" autocomplete="off"></label></div>
  <?php if ($isInHouse || $isGuest): ?><label class="min-w-0 text-sm font-bold">Nama PIC<input required maxlength="160" autocomplete="name" class="mt-2 block min-w-0 w-full rounded-xl border border-slate-200 p-3 font-normal" name="contact_name"></label><?php else: ?><label class="min-w-0 text-sm font-bold">Nama lengkap<input required maxlength="160" autocomplete="name" class="mt-2 block min-w-0 w-full rounded-xl border border-slate-200 p-3 font-normal" name="name"></label><?php endif; ?>
  <?php if ($isInHouse || $isGuest): ?><label class="min-w-0 text-sm font-bold">Nama perusahaan / institusi<input required maxlength="180" class="mt-2 block min-w-0 w-full rounded-xl border border-slate-200 p-3 font-normal" name="organization"></label><?php else: ?><label class="min-w-0 text-sm font-bold">Perusahaan / institusi (opsional)<input maxlength="180" class="mt-2 block min-w-0 w-full rounded-xl border border-slate-200 p-3 font-normal" name="organization"></label><?php endif; ?>
  <label class="min-w-0 text-sm font-bold">Email atau nomor WhatsApp<input required maxlength="190" autocomplete="email" class="mt-2 block min-w-0 w-full rounded-xl border border-slate-200 p-3 font-normal" name="contact"></label>
  <?php if ($isInHouse): ?>
    <label class="min-w-0 text-sm font-bold">Sektor industri dan program yang dibutuhkan<input required maxlength="255" class="mt-2 block min-w-0 w-full rounded-xl border border-slate-200 p-3 font-normal" name="sector_program"></label>
    <label class="min-w-0 text-sm font-bold">Jumlah peserta<input required min="1" max="100000" type="number" inputmode="numeric" class="mt-2 block min-w-0 w-full rounded-xl border border-slate-200 p-3 font-normal" name="participants"></label>
    <label class="min-w-0 text-sm font-bold">Preferensi lokasi, format, dan target waktu<input maxlength="5000" class="mt-2 block min-w-0 w-full rounded-xl border border-slate-200 p-3 font-normal" name="preferences"></label>
    <label class="min-w-0 text-sm font-bold">Kebutuhan dan tujuan belajar<textarea required maxlength="10000" class="mt-2 block min-h-32 min-w-0 w-full rounded-xl border border-slate-200 p-3 font-normal" name="learning_goal"></textarea></label>
  <?php elseif ($isGuest): ?>
    <label class="min-w-0 text-sm font-bold">Topik sesi dan tujuan berbagi<textarea required maxlength="10000" class="mt-2 block min-h-32 min-w-0 w-full rounded-xl border border-slate-200 p-3 font-normal" name="session_topic"></textarea></label>
    <label class="min-w-0 text-sm font-bold">Perkiraan peserta dan preferensi waktu<input maxlength="5000" class="mt-2 block min-w-0 w-full rounded-xl border border-slate-200 p-3 font-normal" name="preferences"></label>
  <?php else: ?>
    <label class="min-w-0 text-sm font-bold">Program yang diminati<select required class="mt-2 block min-w-0 w-full rounded-xl border border-slate-200 bg-white p-3 font-normal" name="program"><option value="">Pilih program pelatihan</option><?php foreach ($availablePrograms as $option): ?><option value="<?= htmlspecialchars($option['slug'], ENT_QUOTES, 'UTF-8') ?>" <?= $program === $option['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($option['title'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
    <label class="min-w-0 text-sm font-bold">Jadwal atau lokasi yang diminati (opsional)<input maxlength="255" class="mt-2 block min-w-0 w-full rounded-xl border border-slate-200 p-3 font-normal" name="schedule_location"></label>
  <?php endif; ?>
  <label class="flex min-w-0 items-start gap-3 text-sm leading-6 text-slate-600"><input required type="checkbox" value="1" name="privacy_consent" class="mt-1 h-4 w-4 shrink-0 accent-[#2478EA]"><span>Saya menyetujui informasi ini digunakan untuk menindaklanjuti permintaan pelatihan.</span></label>
  <button type="submit" disabled class="consent-submit w-full rounded-xl bg-industrial px-5 py-4 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-45 sm:text-base">Kirim Permintaan</button>
  <p class="text-xs leading-5 text-slate-500">Data permintaan hanya digunakan untuk menindaklanjuti kebutuhan pelatihan. Jangan kirimkan kata sandi atau data pembayaran melalui formulir ini.</p>
</form></div></section></main>
<?php require __DIR__ . '/../resources/views/components/footer.php'; ?>
