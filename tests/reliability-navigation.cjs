const {chromium}=require('playwright');const fs=require('fs');const assert=require('node:assert/strict');const path=require('path');
const base=process.env.MIGAS_CHECK_URL||'http://127.0.0.1:8088';
const data=JSON.parse(fs.readFileSync('config/reliability-services.json','utf8'));let checks=0;
const check=(value,label)=>{assert.ok(value,label);console.log('PASS '+label);checks++};
(async()=>{const b=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});const c=await b.newContext({viewport:{width:1440,height:1000}});const p=await c.newPage();const errors=[];p.on('pageerror',e=>errors.push(e.message));
await p.goto(base+'/index.php',{waitUntil:'domcontentloaded'});await p.locator('.nav-trigger').first().waitFor();
for(const [index,id] of ['training','services','technology','competency'].entries()){
 const button=p.locator('.nav-trigger').nth(index);await button.hover();await p.waitForTimeout(220);check(await button.getAttribute('aria-expanded')==='true','hover opens '+id);
 const panel=p.locator('#nav-'+id);check(await panel.isVisible(),'panel visible '+id);await panel.locator('a').last().hover();await p.waitForTimeout(230);check(await panel.isVisible(),'pointer can enter submenu '+id);
 await p.keyboard.press('Escape');check(await panel.isHidden(),'Escape closes '+id);
}
check(await p.locator('.reliability-nav a[href^="service.php?"]').count()===46,'all 46 desktop submenu destinations');
const urls=await p.locator('.reliability-nav a[href^="service.php?"]').evaluateAll(els=>els.map(a=>a.getAttribute('href')));check(new Set(urls).size===46,'46 distinct detail URLs');
await p.locator('.nav-trigger').nth(2).focus();await p.keyboard.press('ArrowDown');check(await p.locator('#nav-technology a').first().evaluate(a=>a===document.activeElement),'keyboard enters submenu');await p.keyboard.press('Escape');
await p.locator('.nav-trigger').nth(2).hover();await p.locator('#nav-technology a[href="service.php?slug=reliability-technology-motor"]').click();await p.waitForURL('**/service.php?slug=reliability-technology-motor');check(await p.locator('main h1').innerText()==='Reliability Technology for Motor','Motor opens its own detail');
check((await p.locator('main .service-actions a').first().getAttribute('href')).includes(encodeURIComponent('Reliability Technology for Motor')),'inquiry retains chosen service');
await p.screenshot({path:path.join(process.env.TEMP,'migas-hero-loop/service-motor-desktop.png'),fullPage:true});
for(const item of data){const r=await c.request.get(base+'/service.php?slug='+item.slug);const html=await r.text();assert.equal(r.status(),200,item.slug);assert(html.includes(item.subtitle)&&html.includes(item.intro),item.slug+' unique content');assert(!/Undefined array key|Fatal error|<b>Warning<\/b>|Notice:/.test(html),item.slug+' PHP warnings');const main=html.split('<main')[1];assert(main.includes('services.php?category='+item.category),item.slug+' correct category');}
check(true,'46 detail pages respond 200 with their own content');
for(const category of ['services','technology','competency']){const r=await c.request.get(base+'/services.php?category='+category);assert.equal(r.status(),200);const html=await r.text();const cards=(html.match(/class="service-card"/g)||[]).length;assert.equal(cards,data.filter(i=>i.category===category).length,category+' lists all own detail cards');assert(!/Undefined array key|Fatal error|<b>Warning<\/b>/.test(html));}
check(true,'three category landing pages work');
for(const key of ['engineering','plant','oil','learning']){const r=await c.request.get(base+'/assets/images/services/'+key+'.jpg');assert.equal(r.status(),200);assert(r.headers()['content-type'].startsWith('image/'));}
check(true,'all local service images available');
for(const bad of ['service.php?slug=unknown','service.php?slug[]=motor','services.php?category=unknown']){check((await c.request.get(base+'/'+bad)).status()===404,'safe 404 '+bad);}
check(errors.length===0,'no page script errors');
for(const width of [320,390,768,1024,1280,1440]){await p.setViewportSize({width,height:900});await p.goto(base+'/service.php?slug=reliability-technology-gis',{waitUntil:'domcontentloaded'});await p.waitForTimeout(350);check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'layout fits '+width);
if(width<1280){await p.locator('#menu-toggle').click();const menu=p.locator('#mobile-menu');check(await menu.isVisible(),'mobile menu opens '+width);await menu.locator('summary').filter({hasText:'Reliability Technology'}).click();check(await menu.locator('a[href="service.php?slug=reliability-technology-motor"]').isVisible(),'touch submenu opens '+width);if(width===390){await p.screenshot({path:path.join(process.env.TEMP,'migas-hero-loop/service-mobile-nav.png')});}await menu.locator('a[href="service.php?slug=reliability-technology-motor"]').click();await p.waitForURL('**/service.php?slug=reliability-technology-motor');check(await p.locator('main h1').innerText()==='Reliability Technology for Motor','mobile submenu navigates '+width);if(width===390)await p.screenshot({path:path.join(process.env.TEMP,'migas-hero-loop/service-motor-mobile.png'),fullPage:true});}
else{check(await p.locator('.reliability-nav').isVisible(),'desktop nav visible '+width);await p.locator('.nav-trigger').nth(1).hover();await p.waitForTimeout(250);const box=await p.locator('#nav-services').boundingBox();check(box.x>=0&&box.x+box.width<=width,'mega menu fits '+width);if(width===1440)await p.screenshot({path:path.join(process.env.TEMP,'migas-hero-loop/service-desktop-nav.png')});}}
await b.close();console.log(checks+' reliability checks passed');})().catch(e=>{console.error(e);process.exit(1)});
