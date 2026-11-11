<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');
if (is_file(__DIR__ . '/../vendor/autoload.php')) require_once __DIR__ . '/../vendor/autoload.php';
if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params(['httponly' => true, 'secure' => $secure, 'samesite' => 'Lax', 'path' => '/']);
    session_start();
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
}
if (PHP_SAPI !== 'cli' && basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'download.php') ob_start();
function e(mixed $value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function rows(string $sql, array $params = []): array { $q = database()->prepare($sql); $q->execute($params); return $q->fetchAll(); }
function one(string $sql, array $params = []): ?array { return rows($sql, $params)[0] ?? null; }
function run(string $sql, array $params = []): int { $q = database()->prepare($sql); $q->execute($params); return $q->rowCount(); }
function input(string $key, string $default = ''): string { return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : $default; }
function query(string $key, string $default = ''): string { return is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : $default; }
function redirect(string $path): never { header('Location: ' . $path, true, 303); exit; }
function csrf(): string { return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32)); }
function csrfField(): string { return '<input type="hidden" name="csrf_token" value="' . e(csrf()) . '">'; }
function checkCsrf(): void {
    if (!hash_equals(csrf(), input('csrf_token'))) { if(PHP_SAPI !== 'cli') http_response_code(419); throw new DomainException('Sesi formulir kedaluwarsa. Muat ulang halaman dan coba lagi.'); }
}
function flash(string $message): void { $_SESSION['portal_flash'] = $message; }
function currentUser(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    $user = one('SELECT * FROM users WHERE id = ? AND is_active = 1', [$_SESSION['user_id']]);
    if (!$user || (int)$user['session_version'] !== (int)($_SESSION['session_version'] ?? 0) || time() - (int)($_SESSION['last_activity'] ?? 0) > 7200) {
        unset($_SESSION['user_id'], $_SESSION['session_version']); return null;
    }
    $_SESSION['last_activity'] = time();
    return $user;
}
function requireUser(): array {
    header('Cache-Control: no-store, private');
    $user = currentUser();
    if (!$user) { $_SESSION['after_login'] = basename($_SERVER['SCRIPT_NAME']) . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''); redirect('auth.php?mode=login'); }
    return $user;
}
function loginUser(array $user): void {
    session_regenerate_id(true); $_SESSION['user_id'] = $user['id']; $_SESSION['session_version'] = $user['session_version'];
    $_SESSION['last_activity'] = time(); $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function notifyUser(int $userId, string $title, string $body, string $destination = 'overview'): void {
    run('INSERT INTO notifications (user_id,title,body,destination) VALUES (?,?,?,?)', [$userId,$title,$body,$destination]);
}
function audit(string $action, ?int $actor, ?int $subject, string $detail = ''): void {
    run('INSERT INTO audit_logs (actor_id,action,subject_id,detail) VALUES (?,?,?,?)', [$actor,$action,$subject,$detail]);
}
function authorizedBatch(int $userId, int $batchId): array {
    $enrollment = one("SELECT en.*, r.user_id,r.batch_id FROM enrollments en JOIN registrations r ON r.id=en.registration_id WHERE r.user_id=? AND r.batch_id=? AND r.status='approved' AND en.status IN ('active','completed')", [$userId,$batchId]);
    if (!$enrollment) { if(PHP_SAPI !== 'cli') http_response_code(403); throw new DomainException('Anda belum memiliki akses ke pelatihan ini.'); }
    return $enrollment;
}
function privateDirectory(): string {
    $path = getenv('PRIVATE_STORAGE') ?: dirname(__DIR__) . '/storage/private';
    if (!is_dir($path) && !mkdir($path, 0700, true) && !is_dir($path)) throw new RuntimeException('Penyimpanan belum tersedia.');
    return $path;
}
function storeUpload(string $field, bool $photo = false): ?array {
    $file = $_FILES[$field] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if (!is_int($file['error']) || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024 || !is_uploaded_file($file['tmp_name'])) throw new DomainException('Unggah file yang valid, maksimal 2 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = $photo ? ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'] : ['application/pdf'=>'pdf'];
    if (!isset($allowed[$mime])) throw new DomainException($photo ? 'Foto harus JPG, PNG, atau WebP.' : 'Dokumen harus berupa PDF.');
    if ($photo) { $size = getimagesize($file['tmp_name']); if (!$size || $size[0] > 6000 || $size[1] > 6000) throw new DomainException('Ukuran foto maksimal 6000 × 6000 piksel.'); }
    $name = bin2hex(random_bytes(24)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], privateDirectory() . '/' . $name)) throw new RuntimeException('File gagal disimpan.');
    return ['path'=>$name,'name'=>mb_substr(basename($file['name']),0,180)];
}
function safeUrl(?string $url): string { return $url && preg_match('~^https?://~i', $url) && filter_var($url, FILTER_VALIDATE_URL) ? $url : ''; }
function rupiah(mixed $price): string { return $price === null ? 'Biaya belum diumumkan' : ((float)$price === 0.0 ? 'Tanpa biaya' : 'Rp ' . number_format((float)$price,0,',','.')); }
function statusText(string $status): string {
    return ['pending'=>'Menunggu verifikasi','under_review'=>'Sedang ditinjau','approved'=>'Disetujui','rejected'=>'Ditolak','cancelled'=>'Dibatalkan','active'=>'Aktif','completed'=>'Selesai','revoked'=>'Akses dicabut','paid'=>'Lunas','unpaid'=>'Belum dibayar','not_required'=>'Tanpa pembayaran','present'=>'Hadir','late'=>'Terlambat','absent'=>'Tidak hadir','excused'=>'Izin','submitted'=>'Menunggu penilaian','passed'=>'Lulus','revision'=>'Perlu revisi','proposed'=>'Usulan','confirmed'=>'Dikonfirmasi','closed'=>'Ditutup'][$status] ?? $status;
}
function progress(int $userId, int $batchId): array {
    $lesson = one('SELECT COUNT(*) total, COUNT(c.lesson_id) done FROM lessons l LEFT JOIN lesson_completions c ON c.lesson_id=l.id AND c.user_id=? WHERE l.batch_id=? AND l.is_published=1',[$userId,$batchId]);
    $assignment = one("SELECT COUNT(*) total, SUM(CASE WHEN s.status='passed' THEN 1 ELSE 0 END) done FROM assignments a LEFT JOIN assignment_submissions s ON s.assignment_id=a.id AND s.user_id=? WHERE a.batch_id=? AND a.is_published=1",[$userId,$batchId]);
    $attendance = one("SELECT COUNT(*) total, SUM(CASE WHEN a.status IN ('present','late') THEN 1 ELSE 0 END) done FROM training_sessions s LEFT JOIN attendance_records a ON a.session_id=s.id AND a.user_id=? WHERE s.batch_id=?",[$userId,$batchId]);
    $total = (int)$lesson['total']+(int)$assignment['total']+(int)$attendance['total'];
    $done = (int)$lesson['done']+(int)$assignment['done']+(int)$attendance['done'];
    return ['lessons'=>$lesson,'assignments'=>$assignment,'attendance'=>$attendance,'total'=>$total,'done'=>$done,'percent'=>$total ? (int)floor($done*100/$total) : 0];
}
