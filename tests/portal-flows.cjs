const {request,chromium}=require('playwright');const {execFileSync}=require('node:child_process');const fs=require('fs');const assert=require('node:assert/strict');
const base='http://127.0.0.1:8088';const f=JSON.parse(fs.readFileSync(process.env.TEST_FIXTURE_FILE));
const php=(...args)=>execFileSync('D:/xamppp/php/php.exe',['tests/portal-fixtures.php',...args],{encoding:'utf8'}).trim();
const csrf=html=>html.match(/name="csrf_token" value="([^"]+)"/)[1];let count=0;
const ok=(value,label)=>{assert.ok(value,label);count++;console.log('PASS',label);};
async function token(ctx,path){return csrf(await(await ctx.get(base+'/'+path)).text());}
async function login(ctx,email,password='Testing-Portal-2026!'){return ctx.post(base+'/auth.php',{form:{csrf_token:await token(ctx,'auth.php'),email,password},maxRedirects:0});}
(async()=>{
  php('reset-throttle');
  const alice=await request.newContext();const bob=await request.newContext();await login(alice,'alice@example.test');await login(bob,'bob@example.test');
  const batch=php('open-batch');
  const before=await token(bob,'enroll.php?batch='+batch);
  let r=await bob.post(base+'/enroll.php?batch='+batch,{form:{csrf_token:before},maxRedirects:0});ok((await r.text()).includes('Persetujuan pendaftaran wajib'),'enroll requires consent');
  r=await bob.post(base+'/enroll.php?batch='+batch,{form:{csrf_token:before,consent:'1'},maxRedirects:0});ok(r.status()===303,'enroll POST saves registration');
  r=await bob.post(base+'/enroll.php?batch='+batch,{form:{csrf_token:before,consent:'1'},maxRedirects:0});ok((await r.text()).includes('sudah memiliki pendaftaran'),'double submit rejected');
  php('approve',batch);r=await bob.get(base+'/portal.php?view=training');ok((await r.text()).includes('Vibration Analysis'),'approved training appears');
  r=await alice.post(base+'/portal.php?view=learning&batch='+f.batch,{multipart:{csrf_token:await token(alice,'portal.php?view=profile'),action:'assignment',id:String(f.assignment),answer:'Laporan pengujian',document:{name:'payload.php',mimeType:'application/pdf',buffer:Buffer.from('<?php echo "no";')}}});ok((await r.text()).includes('Dokumen harus berupa PDF'),'forged PDF upload rejected');
  r=await alice.post(base+'/portal.php?view=learning&batch='+f.batch,{multipart:{csrf_token:await token(alice,'portal.php?view=profile'),action:'assignment',id:String(f.assignment),answer:'Laporan pengujian',document:{name:'report.pdf',mimeType:'application/pdf',buffer:Buffer.from('%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF')}},maxRedirects:0});ok(r.status()===303,'PDF assignment saved');
  r=await alice.get(base+'/portal.php?view=learning&batch='+f.batch);const body=await r.text();const sub=body.match(/download.php\?kind=submission&amp;id=(\d+)/)[1];r=await bob.get(base+'/download.php?kind=submission&id='+sub);ok(r.status()===404,'submission IDOR denied');
  const png=Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jSuoAAAAASUVORK5CYII=','base64');
  r=await alice.post(base+'/portal.php?view=profile',{multipart:{csrf_token:await token(alice,'portal.php?view=profile'),action:'profile',full_name:'Peserta Uji',email:'alice@example.test',phone:'+628123456789',company:'Perusahaan Uji',photo:{name:'avatar.png',mimeType:'image/png',buffer:png}},maxRedirects:0});ok(r.status()===303,'profile and image update saved');
  r=await alice.get(base+'/download.php?kind=photo');ok(r.status()===200&&r.headers()['content-type']==='image/png','photo served privately');
  php('close-assessment');r=await alice.get(base+'/portal.php?view=assessments&assessment='+f.assessment);ok((await r.text()).includes('Jawaban benar:'),'answer explanation allowed after deadline');php('open-assessment');
  php('certificate-revoke');r=await alice.get(base+'/verify.php?token='+f.verification_token);ok(r.status()===404,'revoked certificate verification rejected');r=await alice.get(base+'/download.php?kind=certificate&id='+f.certificate);ok(r.status()===404,'revoked certificate download rejected');php('certificate-restore');
  const reset=await request.newContext();const email='reset-'+Date.now()+'@example.test';
  await reset.post(base+'/auth.php?mode=register',{form:{csrf_token:await token(reset,'auth.php?mode=register'),full_name:'Reset Test',email,password:'Testing-Portal-2026!',password_confirmation:'Testing-Portal-2026!',consent:'1'}});
  const resetToken='f'.repeat(64);php('reset',email,resetToken);
  r=await reset.post(base+'/auth.php?mode=reset&token='+resetToken,{form:{csrf_token:await token(reset,'auth.php?mode=reset&token='+resetToken),password:'New-Testing-Portal-2026!',password_confirmation:'New-Testing-Portal-2026!'},maxRedirects:0});ok(r.status()===303,'password reset succeeds');
  r=await reset.post(base+'/auth.php?mode=reset&token='+resetToken,{form:{csrf_token:await token(reset,'auth.php?mode=reset&token='+resetToken),password:'New-Testing-Portal-2026!',password_confirmation:'New-Testing-Portal-2026!'}});ok((await r.text()).includes('sudah kedaluwarsa atau sudah digunakan'),'reset token cannot be reused');
  r=await login(reset,email);ok(r.status()===200,'old password rejected');r=await login(reset,email,'New-Testing-Portal-2026!');ok(r.status()===303,'new password works');
  const expiredToken='e'.repeat(64);php('reset',email,expiredToken,'expired');r=await reset.post(base+'/auth.php?mode=reset&token='+expiredToken,{form:{csrf_token:await token(reset,'auth.php?mode=reset&token='+expiredToken),password:'Other-Testing-Portal-2026!',password_confirmation:'Other-Testing-Portal-2026!'}});ok((await r.text()).includes('sudah kedaluwarsa atau sudah digunakan'),'expired reset rejected');
  r=await reset.post(base+'/auth.php?mode=forgot',{form:{csrf_token:await token(reset,'auth.php?mode=forgot'),email}});ok((await r.text()).includes('Pemulihan email belum diaktifkan'),'unconfigured SMTP is reported honestly');
  const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});const ctx=await browser.newContext({storageState:await bob.storageState(),viewport:{width:390,height:850}});const page=await ctx.newPage();await page.goto(base+'/enroll.php?batch='+batch);
  const submit=page.locator('button[type=submit]');ok(await submit.isDisabled(),'enroll button disabled before consent');await page.locator('input[name=consent]').check();ok(await submit.isEnabled(),'enroll button enabled after consent');
  await page.goto(base+'/programs.php?q=Vibration#catalog-search');ok(await page.locator('#catalog-search .catalog-card').count()===1,'catalog search filters cards');
  await browser.close();await alice.dispose();await bob.dispose();await reset.dispose();console.log('Flow checks:',count,'passed');
})().catch(e=>{console.error(e);process.exit(1);});
