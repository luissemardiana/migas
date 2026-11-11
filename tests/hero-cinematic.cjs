const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const base = process.env.MIGAS_CHECK_URL || 'http://127.0.0.1:8088';
const dir = process.env.TEST_SCREENSHOT_DIR || process.env.TEMP;
let checks=0;
const check=(condition,label)=>{assert.ok(condition,label);checks++;console.log('PASS',label);};
(async()=>{
  const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
  const context=await browser.newContext({viewport:{width:1440,height:900}});
  const page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.goto(base+'/index.php',{waitUntil:'domcontentloaded'});
  await page.waitForFunction(()=>{const v=document.querySelector('.hero-video');return v.readyState>=3&&!v.paused&&v.classList.contains('is-playing');},{timeout:20000});
  await page.waitForTimeout(1300);
  const media=await page.locator('.hero-video').evaluate(v=>({width:v.videoWidth,height:v.videoHeight,duration:v.duration,loop:v.loop,muted:v.muted,inline:v.playsInline,autoplay:v.autoplay,source:v.currentSrc}));
  check(media.width>=1920&&media.height>=1080&&media.duration>=8&&media.duration<=12,'real 1080p MP4, duration 8–12 seconds');
  check(media.loop&&media.muted&&media.inline&&media.autoplay,'native autoplay / muted / loop / playsinline');
  check(await page.locator('#hero-headline').innerText()==='BUILDING EXPERTISE.\nDRIVING ENERGY.','exact headline');
  check(await page.locator('.hero-description').innerText()==='Professional Oil & Gas Training for the Next Generation of Industry Experts.','exact description');
  check(await page.getByRole('link',{name:'Explore Training',exact:true}).getAttribute('href')==='programs.php','primary CTA retains catalog route');
  check(await page.getByRole('link',{name:'Discover Our Programs',exact:true}).getAttribute('href')==='#programs','secondary CTA opens program section');
  const continuity=await page.locator('.hero-video').evaluate(v=>new Promise(resolve=>{
    let previous=v.currentTime,wraps=0,hidden=0,ended=0;const begin=performance.now();
    const timer=setInterval(()=>{if(v.currentTime<previous-.5)wraps++;previous=v.currentTime;const frame=document.querySelector('.hero-frame');const visible=document.querySelector('#home').classList.contains('has-frame-surface')?frame?.classList.contains('is-ready')&&getComputedStyle(frame).visibility==='visible':getComputedStyle(v).opacity==='1';if(!visible)hidden++;if(v.ended)ended++;if(performance.now()-begin>9200){clearInterval(timer);resolve({wraps,hidden,ended});}},40);
  }));
  check(continuity.wraps>=1&&continuity.hidden===0&&continuity.ended===0,'continuous loop without opacity reset or ended state');
  const scrollContinuity = async (p, label) => {
    await p.evaluate(() => {
      const v = document.querySelector('.hero-video');
      window.heroScrollEvents = [];
      ['pause', 'emptied', 'loadstart'].forEach(name => v.addEventListener(name, () => heroScrollEvents.push(name)));
    });
    for (let i = 0; i < 3; i++) {
      await p.evaluate(() => scrollTo({top: 2000, behavior: 'instant'}));
      await p.waitForTimeout(250);
      check(await p.locator('.hero-video').evaluate(v => !v.paused && v.classList.contains('is-playing')), label+' keeps decoded video while scrolling '+i);
      await p.evaluate(() => scrollTo({top: 0, behavior: 'instant'}));
      await p.waitForTimeout(250);
      check(await p.locator('.hero-video').evaluate(v => {const frame=document.querySelector('.hero-frame');return v.readyState>=2 && (document.querySelector('#home').classList.contains('has-frame-surface')?getComputedStyle(frame).visibility==='visible':getComputedStyle(v).opacity==='1');}), label+' returns without fading '+i);
    }
    check(await p.evaluate(() => heroScrollEvents.length === 0), label+' no scroll-triggered pause or source reload');
  };
  await scrollContinuity(page, 'desktop');
  const retained = await page.locator('.hero-frame').evaluate(async canvas => {
    const video=document.querySelector('.hero-video');
    const ctx=canvas.getContext('2d');
    const sample=()=>{const pixels=ctx.getImageData(0,0,canvas.width,canvas.height).data;let sum=0;for(let i=0;i<pixels.length;i+=4)sum+=pixels[i]+pixels[i+1]+pixels[i+2];return sum/(canvas.width*canvas.height*3);};
    const before=canvas.toDataURL();
    await new Promise(r=>setTimeout(r,300));
    const animated=before!==canvas.toDataURL();
    video.pause();await new Promise(r=>requestAnimationFrame(()=>requestAnimationFrame(r)));
    const held=canvas.toDataURL();
    scrollTo({top:2000,behavior:'instant'});await new Promise(r=>setTimeout(r,100));
    scrollTo({top:0,behavior:'instant'});await new Promise(r=>setTimeout(r,100));
    const preserved=held===canvas.toDataURL();const brightness=sample();await video.play();
    return {animated,preserved,brightness};
  });
  check(retained.animated,'desktop retained surface contains moving video pixels');
  check(retained.preserved&&retained.brightness>15,'decoded refinery bitmap survives pause and scroll without clearing to black');
  await page.screenshot({path:dir+'/cinematic-desktop.png'});
  await page.getByRole('link',{name:'Discover Our Programs',exact:true}).click();await page.waitForTimeout(800);
  check(new URL(page.url()).hash==='#programs','program CTA navigation works');
  check(await page.locator('[data-program-carousel]').count()>0,'existing program carousel retained');
  check(errors.length===0,'no JavaScript exceptions');await context.close();
  for(const width of [320,390,768]){
    const ctx=await browser.newContext({viewport:{width,height:850},isMobile:width<768,hasTouch:width<768});const p=await ctx.newPage();const videos=[];p.on('request',r=>{if(r.url().endsWith('.mp4'))videos.push(r.url());});
    await p.goto(base+'/index.php',{waitUntil:'domcontentloaded'});await p.waitForTimeout(1400);
    const dimensions=await p.locator('#hero-headline').evaluate(el=>({right:el.querySelector('span').getBoundingClientRect().right,width:innerWidth,scroll:document.documentElement.scrollWidth,poster:document.querySelector('.hero-poster img').naturalWidth}));
    check(dimensions.right<=width&&dimensions.scroll<=width,'headline and layout fit '+width+'px');
    check(dimensions.poster>0,'poster available '+width+'px');
    if(width<768){
      await p.waitForFunction(()=>{const v=document.querySelector('.hero-video');return v.readyState>=3&&!v.paused&&v.classList.contains('is-playing');},null,{timeout:20000});
      const mobile=await p.locator('.hero-video').evaluate(v=>({width:v.videoWidth,height:v.videoHeight,src:v.currentSrc,display:getComputedStyle(v).display,time:v.currentTime}));
      check(mobile.width===720&&mobile.height===960&&mobile.src.includes('refinery-cinematic-mobile.mp4')&&mobile.display!=='none','mobile video plays '+width+'px');
      check(videos.every(url=>url.includes('refinery-cinematic-mobile.mp4')),'mobile only downloads optimized video '+width+'px');
      await p.waitForTimeout(500);
      check(await p.locator('.hero-video').evaluate((v,t)=>v.currentTime!==t,mobile.time),'mobile playback advances '+width+'px');
      await scrollContinuity(p, 'mobile '+width);
    }
    await p.screenshot({path:dir+'/cinematic-'+width+'.png'});await ctx.close();
  }
  for(const mode of ['reduced','failure','blocked','save-data']){
    const ctx=await browser.newContext({viewport:{width:1440,height:900},reducedMotion:mode==='reduced'?'reduce':'no-preference'});
    const p=await ctx.newPage();const videos=[];p.on('request',r=>{if(r.url().endsWith('.mp4'))videos.push(r.url());});
    if(mode==='failure')await p.route('**/refinery-cinematic.mp4',r=>r.abort());
    if(mode==='blocked')await p.addInitScript(()=>{
      // Disable native autoplay too; rejecting play() alone does not simulate a browser policy.
      new MutationObserver(()=>document.querySelectorAll('video[autoplay]').forEach(v=>v.removeAttribute('autoplay'))).observe(document,{childList:true,subtree:true});
      HTMLMediaElement.prototype.play=function(){return Promise.reject(new DOMException('blocked','NotAllowedError'));};
    });
    if(mode==='save-data')await p.addInitScript(()=>Object.defineProperty(navigator,'connection',{value:{saveData:true,addEventListener(){}}}));
    await p.goto(base+'/index.php',{waitUntil:'domcontentloaded'});await p.waitForTimeout(1500);
    check(await p.locator('.hero-video').evaluate(v=>!v.classList.contains('is-playing')),'poster fallback '+mode);
    check(await p.locator('.hero-poster img').evaluate(img=>img.complete&&img.naturalWidth>0),'loaded fallback image '+mode);
    if(mode==='reduced'||mode==='save-data')check(videos.length===0,'no video transfer '+mode);
    if(mode==='reduced')check(await p.locator('#hero-headline').evaluate(el=>getComputedStyle(el).animationName)==='none','reduced-motion text remains static');
    await ctx.close();
  }
  const cold = await browser.newContext({viewport:{width:390,height:850},isMobile:true,hasTouch:true});
  const coldPage = await cold.newPage();
  await coldPage.route('**/refinery-cinematic*.jpg', route => route.abort());
  await coldPage.route('**/refinery-cinematic*.mp4', route => route.abort());
  await coldPage.goto(base+'/index.php',{waitUntil:'domcontentloaded'});
  check(await coldPage.locator('#home').evaluate(el => getComputedStyle(el).backgroundImage.includes('data:image/jpeg;base64,')), 'embedded refinery remains when posters and video cannot load');
  await cold.close();
  const returning = await browser.newContext({viewport:{width:390,height:850},isMobile:true,hasTouch:true});
  const returnPage = await returning.newPage();
  await returnPage.goto(base+'/auth.php',{waitUntil:'domcontentloaded'});
  await returnPage.locator('main a[href="index.php?return=home"]').click();
  await returnPage.waitForFunction(()=>{const v=document.querySelector('.hero-video');return v?.classList.contains('is-playing') && !v.paused;},null,{timeout:20000});
  check(await returnPage.locator('.hero-video').evaluate(v=>v.readyState>=2&&v.currentSrc.includes('mobile.mp4')),'return from account page reveals decoded mobile video');
  await returning.close();
  const native=await browser.newContext({viewport:{width:1440,height:900}});const nativePage=await native.newPage();
  await nativePage.addInitScript(()=>{HTMLCanvasElement.prototype.getContext=function(){return null;};});
  await nativePage.goto(base+'/index.php',{waitUntil:'domcontentloaded'});
  await nativePage.waitForFunction(()=>document.querySelector('.hero-video')?.classList.contains('is-playing'),null,{timeout:20000});
  check(await nativePage.locator('.hero-video').evaluate(v=>getComputedStyle(v).opacity==='1'&&!document.querySelector('#home').classList.contains('has-frame-surface')),'native video fallback when canvas is unavailable');
  await native.close();
  await browser.close();console.log('Hero checks:',checks,'passed');
})().catch(e=>{console.error(e);process.exit(1);});
