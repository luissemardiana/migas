<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
function authThrottle(string $action, string $email): void {
    // Both IP and account buckets: rotating addresses cannot bypass an account limit.
    foreach ([$action . '|ip|' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'), $action . '|email|' . strtolower($email)] as $key) {
        $bucket = hash('sha256', $key);
        $count = one('SELECT COUNT(*) n FROM auth_attempts WHERE bucket=? AND created_at > DATE_SUB(NOW(),INTERVAL 15 MINUTE)',[$bucket]);
        if ((int)$count['n'] >= 10) { http_response_code(429); throw new DomainException('Terlalu banyak percobaan. Coba kembali dalam 15 menit.'); }
        run('INSERT INTO auth_attempts(bucket) VALUES (?)',[$bucket]);
    }
    run('DELETE FROM auth_attempts WHERE created_at < DATE_SUB(NOW(),INTERVAL 1 DAY)');
}
function validPassword(string $password): void {
    if (strlen($password) < 12 || strlen($password) > 72) throw new DomainException('Kata sandi harus sepanjang 12–72 karakter.');
}
function resetMail(string $email, string $token): void {
    if (!getenv('SMTP_HOST') || !getenv('SMTP_FROM') || !getenv('APP_URL') || !class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) throw new DomainException('Pemulihan melalui email belum tersedia. Hubungi tim melalui halaman Kontak.');
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP(); $mail->Host = getenv('SMTP_HOST'); $mail->Port = (int)(getenv('SMTP_PORT') ?: 587);
    $mail->SMTPAuth = (bool)getenv('SMTP_USER'); $mail->Username = getenv('SMTP_USER') ?: ''; $mail->Password = getenv('SMTP_PASSWORD') ?: '';
    $mail->SMTPSecure = getenv('SMTP_ENCRYPTION') ?: 'tls'; $mail->Timeout = 15;
    $mail->CharSet = 'UTF-8'; $mail->setFrom(getenv('SMTP_FROM'),'MIGAS Training'); $mail->addAddress($email);
    $mail->Subject = 'Atur ulang kata sandi MIGAS Training';
    $mail->Body = "Buka tautan berikut untuk mengatur ulang kata sandi. Tautan berlaku 30 menit dan hanya dapat digunakan sekali.\n\n" . rtrim(getenv('APP_URL'),'/') . '/auth.php?mode=reset&token=' . $token . "\n\nAbaikan pesan ini jika Anda tidak mengajukan permintaan.";
    $mail->send();
}
