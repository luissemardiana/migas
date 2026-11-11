<?php
declare(strict_types=1);
require __DIR__.'/../app/participant.php';
$navigation=['overview'=>'Ringkasan','training'=>'Pelatihan Saya','registrations'=>'Pendaftaran','learning'=>'Pusat Belajar','schedule'=>'Jadwal Saya','attendance'=>'Kehadiran','assessments'=>'Evaluasi','certificates'=>'Sertifikat','notifications'=>'Notifikasi','profile'=>'Profil Saya'];
$view=query('view','overview'); if(!isset($navigation[$view])){$view='overview';http_response_code(404);}
$error='';$user=null;$enrollments=[];$notices=[];$unread=0;
try {
    $user=requireUser();$uid=(int)$user['id'];
    if($_SERVER['REQUEST_METHOD']==='POST') { participantAction($user); redirect('portal.php?view='.$view.($view==='learning'&&query('batch')?'&batch='.(int)query('batch'):'')); }
    $enrollments=rows("SELECT en.*,r.batch_id,p.title,b.start_date,b.end_date,b.delivery_format,b.location FROM enrollments en JOIN registrations r ON r.id=en.registration_id JOIN training_batches b ON b.id=r.batch_id JOIN training_programs p ON p.slug=b.program_slug WHERE r.user_id=? AND r.status='approved' AND en.status IN ('active','completed') ORDER BY b.start_date DESC",[$uid]);
    $notices=rows('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC,id DESC LIMIT 5',[$uid]);
    $unread=(int)one('SELECT COUNT(*) n FROM notifications WHERE user_id=? AND read_at IS NULL',[$uid])['n'];
} catch(Throwable $e) { $error=$e instanceof DomainException?$e->getMessage():'Portal belum dapat dimuat. Silakan coba beberapa saat lagi.';if(!$e instanceof DomainException){error_log('MIGAS portal: '.$e->getMessage());http_response_code(503);} }
require __DIR__.'/../resources/views/components/header.php';
function emptyState(string $title,string $body,string $url='programs.php',string $cta='Jelajahi program'): void { ?><div class="empty"><h2><?= e($title) ?></h2><p><?= e($body) ?></p><a class="btn secondary" href="<?= e($url) ?>"><?= e($cta) ?></a></div><?php }
function actionFields(string $action,int $id=0): string { return csrfField().'<input type="hidden" name="action" value="'.e($action).'"><input type="hidden" name="id" value="'.$id.'">'; }
?>
<div class="portal-shell"><nav class="portal-sidebar" aria-label="Menu peserta"><div class="portal-brand">RUANG BELAJAR / MIGAS</div><?php foreach($navigation as $key=>$label): ?><a href="portal.php?view=<?= $key ?>" <?= $key===$view?'aria-current="page"':'' ?>><?= e($label) ?></a><?php endforeach; ?><a href="auth.php?mode=logout">Keluar</a></nav>
<main class="portal-main"><div class="portal-topbar"><small>PORTAL PESERTA / <?= e(strtoupper($navigation[$view])) ?></small><details class="portal-dropdown"><summary>Notifikasi <?= $unread ? '('.$unread.')':'' ?></summary><div class="dropdown-content"><?php foreach($notices as $notice): ?><a href="portal.php?view=<?= e(isset($navigation[$notice['destination']])?$notice['destination']:'notifications') ?>"><?= e($notice['title']) ?></a><?php endforeach; ?><?php if(!$notices): ?><p class="muted">Belum ada notifikasi.</p><?php endif; ?><a href="portal.php?view=notifications">Lihat semua notifikasi</a></div></details></div>
<?php if($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if($message=$_SESSION['portal_flash']??''): unset($_SESSION['portal_flash']); ?><p class="notice" role="status"><?= e($message) ?></p><?php endif; ?>
<?php if($user): try { require __DIR__.'/../resources/views/participant/'.$view.'.php'; } catch(Throwable $e) { error_log('MIGAS portal view: '.$e->getMessage()); ?><p class="notice error" role="alert"><?= e($e instanceof DomainException?$e->getMessage():'Data belum dapat dimuat. Coba kembali beberapa saat lagi.') ?></p><?php } endif; ?>
</main></div>
<?php require __DIR__.'/../resources/views/components/footer.php'; ?>
