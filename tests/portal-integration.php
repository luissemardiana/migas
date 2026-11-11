<?php
declare(strict_types=1);
// Run only against a fresh, isolated migas_test_* database. Never production.
if(PHP_SAPI!=='cli'||!str_starts_with(getenv('DB_NAME')?:'','migas_test_'))exit("Use an isolated DB_NAME=migas_test_* database.\n");
$name=getenv('DB_NAME');if(!preg_match('/^migas_test_[a-z0-9_]+$/',$name))exit(1);
$server=new PDO('mysql:host='.(getenv('DB_HOST')?:'127.0.0.1').';port='.(getenv('DB_PORT')?:'3307').';charset=utf8mb4',getenv('DB_USER')?:'root',getenv('DB_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
require __DIR__.'/../app/administration.php';
database()->exec(file_get_contents(__DIR__.'/../database/schema.sql'));
require __DIR__.'/../bin/migrate.php';require __DIR__.'/../bin/migrate.php';
$passed=0;
function check(bool $condition,string $label): void {global $passed;if(!$condition)throw new RuntimeException('FAIL: '.$label);$passed++;echo "PASS $label\n";}
function denied(callable $fn,string $label): void {try{$fn();}catch(DomainException){check(true,$label);return;}throw new RuntimeException('FAIL denied: '.$label);}
function fixtureUser(string $email,string $role='participant'): int {
    run('INSERT INTO users(full_name,email,password_hash,role) VALUES (?,?,?,?)',['Peserta Uji',$email,password_hash('Testing-Portal-2026!',PASSWORD_DEFAULT),$role]);$id=(int)database()->lastInsertId();run('INSERT INTO participant_profiles(user_id) VALUES (?)',[$id]);return $id;
}
$admin=fixtureUser('admin@example.test','admin');$alice=fixtureUser('alice@example.test');$bob=fixtureUser('bob@example.test');
$batch=importContent($admin,'training_batches',['program_slug'=>'rcm','start_date'=>date('Y-m-d',time()+86400*10),'end_date'=>date('Y-m-d',time()+86400*12),'delivery_format'=>'Daring','status'=>'confirmed']);
importContent($admin,'batch_settings',['batch_id'=>$batch,'capacity'=>1,'registration_deadline'=>date('Y-m-d H:i:s',time()+86400*5),'price'=>0,'method'=>'online']);
$proposed=(int)one("SELECT id FROM training_batches WHERE status='proposed' LIMIT 1")['id'];
denied(fn()=>registerBatch($alice,$proposed),'proposed batch cannot enroll');
$registration=registerBatch($alice,$batch);check($registration>0,'real registration stored');
denied(fn()=>registerBatch($alice,$batch),'duplicate registration blocked');
denied(fn()=>registerBatch($bob,$batch),'capacity enforced');
denied(fn()=>authorizedBatch($alice,$batch),'pending registration cannot access lessons');
denied(fn()=>reviewRegistration($bob,$registration,'approved',''),'participant cannot approve own access');
reviewRegistration($admin,$registration,'approved','Lulus verifikasi');check((bool)authorizedBatch($alice,$batch),'admin approval grants access');
denied(fn()=>authorizedBatch($bob,$batch),'other participant cannot access batch');
importContent($admin,'program_details',['program_slug'=>'rcm','level'=>'Dasar','duration'=>'3 hari','method'=>'online','objectives'=>'Tujuan resmi uji','curriculum'=>'Modul resmi uji']);
$lesson=importContent($admin,'lessons',['batch_id'=>$batch,'module_title'=>'Modul 1','title'=>'Materi uji privat','body'=>'Materi untuk peserta terdaftar.','is_published'=>1]);
check(progress($alice,$batch)['done']===0,'opening a lesson does not complete progress');
$_SESSION=['csrf_token'=>'test-csrf'];$_POST=['csrf_token'=>'test-csrf','action'=>'complete_lesson','id'=>(string)$lesson,'completed'=>'1'];participantAction(one('SELECT * FROM users WHERE id=?',[$alice]));check(progress($alice,$batch)['done']===1,'explicit completion is recorded');
$_POST=['csrf_token'=>'invalid','action'=>'complete_lesson','id'=>(string)$lesson,'completed'=>'1'];denied(fn()=>participantAction(one('SELECT * FROM users WHERE id=?',[$alice])),'CSRF rejected');
$session=importContent($admin,'training_sessions',['batch_id'=>$batch,'title'=>'Sesi daring privat','starts_at'=>date('Y-m-d H:i:s',time()+3600),'ends_at'=>date('Y-m-d H:i:s',time()+7200),'method'=>'online','meeting_url'=>'https://example.test/private-meeting']);
run('UPDATE training_sessions SET attendance_token_hash=?,attendance_open_at=DATE_SUB(NOW(),INTERVAL 5 MINUTE),attendance_close_at=DATE_ADD(NOW(),INTERVAL 10 MINUTE) WHERE id=?',[hash('sha256','test-checkin-token'),$session]);
$_POST=['csrf_token'=>'test-csrf','action'=>'checkin','id'=>(string)$session,'token'=>'wrong'];denied(fn()=>participantAction(one('SELECT * FROM users WHERE id=?',[$alice])),'attendance invalid token blocked');
$_POST['token']='test-checkin-token';participantAction(one('SELECT * FROM users WHERE id=?',[$alice]));check((bool)one('SELECT * FROM attendance_records WHERE user_id=? AND session_id=?',[$alice,$session]),'attendance checked in');
$assessment=importContent($admin,'assessments',['batch_id'=>$batch,'title'=>'Evaluasi uji','opens_at'=>date('Y-m-d H:i:s',time()-3600),'closes_at'=>date('Y-m-d H:i:s',time()+3600),'duration_minutes'=>30,'pass_score'=>70,'reveal_answers'=>1,'is_published'=>1]);
$question=importContent($admin,'assessment_questions',['assessment_id'=>$assessment,'question'=>'Pilih tindakan benar','options_json'=>['Pilihan satu','Pilihan dua'],'correct_option'=>1]);
run('INSERT INTO assessment_attempts(assessment_id,user_id,started_at,expires_at) VALUES (?,?,NOW(),DATE_ADD(NOW(),INTERVAL 30 MINUTE))',[$assessment,$alice]);
denied(fn()=>assessmentForUser($assessment,$bob),'assessment IDOR rejected');
finishAssessment(assessmentForUser($assessment,$alice),$alice,[$question=>1]);
$attempt=one('SELECT * FROM assessment_attempts WHERE user_id=? AND assessment_id=?',[$alice,$assessment]);check((float)$attempt['score']===100.0&&$attempt['passed']==1,'server grading stores result');
denied(fn()=>finishAssessment(assessmentForUser($assessment,$alice),$alice,[$question=>0]),'assessment cannot be submitted twice');
denied(fn()=>importContent($admin,'assessment_questions',['id'=>$question,'assessment_id'=>$assessment,'question'=>'Changed','options_json'=>['A','B'],'correct_option'=>0]),'answer key cannot change after attempts');
$enrollment=(int)one('SELECT id FROM enrollments WHERE registration_id=?',[$registration])['id'];
$pdf=privateDirectory().'/test-certificate.pdf';file_put_contents($pdf,"%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
denied(fn()=>issueCertificate($admin,$enrollment,'TEST-2026-001',$pdf),'certificate blocked before training ends');
run('UPDATE training_batches SET start_date=DATE_SUB(CURRENT_DATE(),INTERVAL 3 DAY),end_date=DATE_SUB(CURRENT_DATE(),INTERVAL 1 DAY) WHERE id=?',[$batch]);
$certificate=issueCertificate($admin,$enrollment,'TEST-2026-001',$pdf);check($certificate>0,'eligible certificate requires explicit admin issue');
$token=one('SELECT verification_token FROM certificates WHERE id=?',[$certificate])['verification_token'];
$assignment=importContent($admin,'assignments',['batch_id'=>$batch,'title'=>'Tugas uji','instructions'=>'Unggah laporan PDF atau jawaban teks.','due_at'=>date('Y-m-d H:i:s',time()+86400),'is_published'=>1]);
$privateFile=bin2hex(random_bytes(24)).'.pdf';copy($pdf,privateDirectory().'/'.$privateFile);run('UPDATE lessons SET file_path=?,file_name=? WHERE id=?',[$privateFile,'materi.pdf',$lesson]);
require_once __DIR__.'/../config/programs.php';run("UPDATE training_programs SET is_active=0 WHERE slug='guest-lecture'");check(findProgram('guest-lecture')===null,'inactive program cannot reappear through fallback');run("UPDATE training_programs SET is_active=1 WHERE slug='guest-lecture'");
$renderer=new \BaconQrCode\Renderer\ImageRenderer(new \BaconQrCode\Renderer\RendererStyle\RendererStyle(240),new \BaconQrCode\Renderer\Image\SvgImageBackEnd());check(str_contains((new \BaconQrCode\Writer($renderer))->writeString('https://example.test/verify?token='.$token),'<svg'),'local SVG QR generation');
$fixture=['admin'=>$admin,'alice'=>$alice,'bob'=>$bob,'batch'=>$batch,'registration'=>$registration,'lesson'=>$lesson,'session'=>$session,'assessment'=>$assessment,'question'=>$question,'assignment'=>$assignment,'certificate'=>$certificate,'verification_token'=>$token,'enrollment'=>$enrollment];
file_put_contents(getenv('TEST_FIXTURE_FILE')?:sys_get_temp_dir().'/migas-portal-fixture.json',json_encode($fixture));
echo "Integration checks: $passed passed.\n";
