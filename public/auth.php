<?php
declare(strict_types=1);
require __DIR__ . '/../app/auth.php';
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
$mode = query('mode','login');
if (!in_array($mode,['login','register','forgot','reset','logout'],true)) $mode='login';
$error = '';
$token = query('token');
$titles = ['login'=>'Selamat datang kembali.','register'=>'Mulai perjalanan belajar Anda.','forgot'=>'Pulihkan akses akun.','reset'=>'Buat kata sandi baru.','logout'=>'Keluar dari akun?'];
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        checkCsrf();
        if ($mode === 'logout') { $_SESSION=[]; session_regenerate_id(true); flash('Anda sudah keluar.'); redirect('auth.php'); }
        $email = strtolower(input('email'));
        if (in_array($mode,['login','register','forgot'],true)) {
            if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190) throw new DomainException('Masukkan alamat email yang valid.');
            authThrottle($mode,$email);
        }
        if ($mode === 'register') {
            $name=input('full_name'); $password=input('password'); validPassword($password);
            if (mb_strlen($name)<2 || mb_strlen($name)>160 || input('consent')!=='1') throw new DomainException('Lengkapi nama dan persetujuan penggunaan data akun.');
            if ($password !== input('password_confirmation')) throw new DomainException('Konfirmasi kata sandi tidak sama.');
            if (one('SELECT id FROM users WHERE email=?',[$email])) throw new DomainException('Email tidak dapat digunakan. Coba masuk atau pulihkan kata sandi.');
            database()->beginTransaction();
            run('INSERT INTO users(full_name,email,password_hash) VALUES (?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
            $id=(int)database()->lastInsertId(); run('INSERT INTO participant_profiles(user_id) VALUES (?)',[$id]);
            notifyUser($id,'Akun Anda siap','Pilih program dan jadwal resmi untuk mulai mendaftar.','registrations');
            database()->commit(); loginUser(one('SELECT * FROM users WHERE id=?',[$id])); flash('Akun berhasil dibuat.');
            $target=$_SESSION['after_login']??'portal.php'; unset($_SESSION['after_login']); redirect($target);
        }
        if ($mode === 'login') {
            $user=one('SELECT * FROM users WHERE email=? AND is_active=1',[$email]);
            $hash=$user['password_hash']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
            if (!password_verify(input('password'),$hash) || !$user) throw new DomainException('Email atau kata sandi tidak sesuai.');
            if (password_needs_rehash($hash,PASSWORD_DEFAULT)) run('UPDATE users SET password_hash=? WHERE id=?',[password_hash(input('password'),PASSWORD_DEFAULT),$user['id']]);
            loginUser($user); $target=$_SESSION['after_login']??'portal.php'; unset($_SESSION['after_login']); redirect($target);
        }
        if ($mode === 'forgot') {
            if (!getenv('SMTP_HOST') || !getenv('SMTP_FROM') || !getenv('APP_URL')) throw new DomainException('Pemulihan email belum diaktifkan. Hubungi tim melalui halaman Kontak.');
            $user=one('SELECT id FROM users WHERE email=? AND is_active=1',[$email]);
            if ($user) {
                $resetToken=bin2hex(random_bytes(32));
                run('UPDATE password_reset_tokens SET used_at=NOW() WHERE user_id=? AND used_at IS NULL',[$user['id']]);
                run('INSERT INTO password_reset_tokens(user_id,token_hash,expires_at) VALUES (?,?,DATE_ADD(NOW(),INTERVAL 30 MINUTE))',[$user['id'],hash('sha256',$resetToken)]);
                resetMail($email,$resetToken);
            }
            flash('Jika email terdaftar, tautan pemulihan akan dikirim. Periksa juga folder spam.'); redirect('auth.php?mode=forgot');
        }
        if ($mode === 'reset') {
            $password=input('password'); validPassword($password);
            if ($password!==input('password_confirmation')) throw new DomainException('Konfirmasi kata sandi tidak sama.');
            database()->beginTransaction();
            $reset=one('SELECT * FROM password_reset_tokens WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW() FOR UPDATE',[hash('sha256',$token)]);
            if (!$reset) throw new DomainException('Tautan sudah kedaluwarsa atau sudah digunakan. Ajukan pemulihan kembali.');
            run('UPDATE users SET password_hash=?,session_version=session_version+1 WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$reset['user_id']]);
            run('UPDATE password_reset_tokens SET used_at=NOW() WHERE user_id=?',[$reset['user_id']]);
            database()->commit(); unset($_SESSION['user_id']); flash('Kata sandi diperbarui. Silakan masuk kembali.'); redirect('auth.php');
        }
    }
} catch (Throwable $exception) {
    try { if (database()->inTransaction()) database()->rollBack(); } catch (Throwable) {}
    $error=$exception instanceof DomainException ? $exception->getMessage() : 'Layanan akun belum dapat diproses. Coba kembali beberapa saat lagi.';
    if (!$exception instanceof DomainException) { error_log('MIGAS auth: '.$exception->getMessage()); http_response_code(503); }
}
require __DIR__ . '/../resources/views/components/header.php';
?>
<main class="account-page"><div class="account-intro"><p class="eyebrow">MIGAS / AKUN PESERTA</p><h1><?= e($titles[$mode]) ?></h1><p>Satu akun untuk pendaftaran, materi pembelajaran, jadwal, dan hasil pelatihan Anda.</p><a href="index.php?return=home">Kembali ke halaman sebelumnya di beranda</a></div>
<section class="panel auth-panel"><h2><?= e(['login'=>'Masuk ke portal','register'=>'Daftar akun peserta','forgot'=>'Lupa kata sandi','reset'=>'Atur ulang kata sandi','logout'=>'Konfirmasi keluar'][$mode]) ?></h2>
<?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if ($message=$_SESSION['portal_flash']??''): unset($_SESSION['portal_flash']); ?><p class="notice" role="status"><?= e($message) ?></p><?php endif; ?>
<form method="post" class="portal-form" data-busy-form><?= csrfField() ?>
<?php if ($mode==='register'): ?><label>Nama lengkap<input name="full_name" autocomplete="name" required maxlength="160" value="<?= e(input('full_name')) ?>"></label><?php endif; ?>
<?php if (in_array($mode,['login','register','forgot'],true)): ?><label>Email<input name="email" type="email" autocomplete="email" required maxlength="190" value="<?= e(input('email')) ?>"></label><?php endif; ?>
<?php if (in_array($mode,['login','register','reset'],true)): ?><label>Kata sandi<input name="password" type="password" autocomplete="<?= $mode==='login'?'current-password':'new-password' ?>" required <?= $mode==='login'?'':'minlength="12"' ?> maxlength="72"></label><?php endif; ?>
<?php if (in_array($mode,['register','reset'],true)): ?><p class="muted">Gunakan 12–72 karakter untuk kata sandi.</p><label>Konfirmasi kata sandi<input name="password_confirmation" type="password" autocomplete="new-password" required minlength="12" maxlength="72"></label><?php endif; ?>
<?php if ($mode==='register'): ?><label class="check"><input type="checkbox" name="consent" value="1" required>Saya menyetujui penggunaan data untuk pengelolaan akun dan pelatihan sesuai <a href="privacy.php">kebijakan privasi</a>.</label><?php endif; ?>
<button class="btn" type="submit"><?= e(['login'=>'Masuk','register'=>'Buat akun','forgot'=>'Kirim tautan pemulihan','reset'=>'Simpan kata sandi','logout'=>'Keluar'][$mode]) ?></button></form>
<div class="auth-links"><a href="auth.php?mode=<?= $mode==='login'?'register':'login' ?>"><?= $mode==='login'?'Belum punya akun? Daftar':'Kembali ke halaman masuk' ?></a><?php if ($mode==='login'): ?><a href="auth.php?mode=forgot">Lupa kata sandi?</a><?php endif; ?></div></section></main>
<?php require __DIR__ . '/../resources/views/components/footer.php'; ?>
