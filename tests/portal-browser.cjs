const { chromium, request } = require('playwright');
const fs = require('fs');
const assert = require('node:assert/strict');
const base = process.env.MIGAS_CHECK_URL || 'http://127.0.0.1:8088';
if (!/^http:\/\/127\.0\.0\.1:/.test(base)) throw new Error('Write tests are restricted to localhost.');
const f = JSON.parse(fs.readFileSync(process.env.TEST_FIXTURE_FILE,'utf8'));
let checks = 0;
const ok = (value,label) => {assert.ok(value,label);checks++;console.log('PASS',label);};
const csrf = html => {const m=html.match(/name="csrf_token" value="([^"]+)"/);assert.ok(m,'csrf token present');return m[1];};
async function login(context,email) {
  const page=await context.get(base+'/auth.php');
  const response=await context.post(base+'/auth.php',{form:{csrf_token:csrf(await page.text()),email,password:'Testing-Portal-2026!'},maxRedirects:0});
  ok(response.status()===303,'login '+email);return context;
}
(async()=>{
  const alice=await login(await request.newContext(),'alice@example.test');
  const bob=await login(await request.newContext(),'bob@example.test');
  for(const view of ['overview','training','registrations','learning','schedule','attendance','assessments','certificates','notifications','profile']) {
    const response=await alice.get(base+'/portal.php?view='+view);const html=await response.text();
    ok(response.status()===200&&!/Fatal error|Warning:|Data belum dapat dimuat/.test(html),'participant page '+view);
  }
  let response=await bob.get(base+'/download.php?kind=lesson&id='+f.lesson);ok(response.status()===403,'other participant cannot download lesson');
  response=await bob.get(base+'/download.php?kind=certificate&id='+f.certificate);ok(response.status()===404,'other participant cannot download certificate');
  response=await alice.get(base+'/download.php?kind=lesson&id='+f.lesson);ok(response.status()===200&&response.headers()['content-type'].includes('pdf'),'authorized lesson PDF');
  response=await alice.get(base+'/verify.php?token='+f.verification_token+'&qr=1');ok(response.status()===200&&(await response.text()).includes('<svg'),'certificate QR route');
  response=await alice.get(base+'/portal.php?view=assessments&assessment='+f.assessment);ok(!(await response.text()).includes('Jawaban benar:'),'answer key hidden before closing time');
  response=await bob.get(base+'/portal.php?view=learning&batch='+f.batch);ok(response.status()===403|| (await response.text()).includes('Anda belum memiliki akses'),'lesson ownership enforced');
  response=await bob.get(base+'/portal.php?view=schedule');ok(!(await response.text()).includes('private-meeting'),'meeting link absent for other participant');
  response=await alice.post(base+'/portal.php?view=profile',{form:{action:'profile',csrf_token:'wrong',full_name:'Changed'}});ok(response.status()===419,'HTTP CSRF rejection');
  const anon=await request.newContext();response=await anon.get(base+'/portal.php',{maxRedirects:0});ok(response.status()===303&&response.headers().location==='auth.php?mode=login','anonymous portal redirects to login');
  response=await anon.get(base+'/auth.php?mode=register');const email='browser-'+Date.now()+'@example.test';
  response=await anon.post(base+'/auth.php?mode=register',{form:{csrf_token:csrf(await response.text()),full_name:'Browser Participant',email,password:'Testing-Portal-2026!',password_confirmation:'Testing-Portal-2026!',consent:'1'},maxRedirects:0});ok(response.status()===303,'HTTP account registration');
  response=await anon.get(base+'/portal.php');ok((await response.text()).includes('Browser Participant'),'new account session works');
  response=await anon.get(base+'/auth.php?mode=logout');response=await anon.post(base+'/auth.php?mode=logout',{form:{csrf_token:csrf(await response.text())},maxRedirects:0});ok(response.status()===303,'CSRF protected logout');
  response=await anon.get(base+'/portal.php',{maxRedirects:0});ok(response.status()===303,'logout removes authentication');
  const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
  for(const width of [390,1440]) {
    const ctx=await browser.newContext({viewport:{width,height:950},storageState:await alice.storageState()});const page=await ctx.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
    for(const path of ['index.php','programs.php','program.php?program=rcm','public-training.php','auth.php','about.php','insights.php','portal.php','portal.php?view=learning&batch='+f.batch,'portal.php?view=profile','portal.php?view=certificates']) {
      const response=await page.goto(base+'/'+path,{waitUntil:'domcontentloaded'});await page.waitForTimeout(150);
      ok(response.status()===200,'browser route '+width+' '+path);
      const size=await page.evaluate(()=>({scroll:document.documentElement.scrollWidth,width:innerWidth}));
      ok(size.scroll<=size.width+1,'no mobile overflow '+width+' '+path+' '+JSON.stringify(size));
    }
    await page.goto(base+'/portal.php');await page.screenshot({path:process.env.TEST_SCREENSHOT_DIR+'/portal-'+width+'.png',fullPage:true});
    ok(errors.length===0,'no JS exceptions '+width+': '+errors.join(','));await ctx.close();
  }
  await browser.close();await alice.dispose();await bob.dispose();await anon.dispose();console.log('Browser checks:',checks,'passed');
})().catch(e=>{console.error(e);process.exit(1);});
