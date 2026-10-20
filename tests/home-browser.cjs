const fs=require('fs');
const path=require('path');
const assert=require('node:assert/strict');
const {execFileSync}=require('node:child_process');
const {chromium}=require('playwright');
const root=path.resolve(__dirname,'../overlay/templates/mupanic');
const render=(flags=[])=>execFileSync(process.env.PHP_BIN||'php',['-n',path.join(__dirname,'fixtures/home-launch.php'),'',...flags],{encoding:'utf8'});
const html=render(['--missing-cache']);
const output=process.env.HOME_SCREENSHOT_DIR;
if(output)fs.mkdirSync(output,{recursive:true});
const fixture={online:17,servidor:'online',eventos:[
 {nombre:'Castillo Sangriento',proximo:'2099-10-20T18:00:00-03:00'},
 {nombre:'Plaza del Diablo',proximo:'2099-10-20T19:00:00-03:00'},
 {nombre:'Dragón Rojo',proximo:'2099-10-20T20:00:00-03:00'}
]};
async function routes(page,body=html){
 await page.route('**/*',route=>{
  const url=new URL(route.request().url());
  if(url.pathname==='/')return route.fulfill({contentType:'text/html',body});
  if(url.pathname==='/api/launcher.php')return route.fulfill({json:fixture});
  if(url.pathname.startsWith('/templates/mupanic/')){
   const file=path.join(root,url.pathname.slice('/templates/mupanic/'.length));
   if(fs.existsSync(file))return route.fulfill({path:file});
  }
  return route.abort();
 });
}
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
 for(const width of [1920,1440,1100,1024,768,760,390,320]){
  const page=await browser.newPage({viewport:{width,height:1000},deviceScaleFactor:1});
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  page.on('requestfailed',r=>errors.push('Failed resource: '+r.url()));
  await routes(page);
  await page.goto('https://home.test/',{waitUntil:'networkidle'});
  await page.evaluate(()=>document.fonts.ready);
  await page.locator('img').evaluateAll(images=>images.forEach(im=>im.loading='eager'));
  await page.waitForFunction(()=>[...document.images].every(im=>im.complete&&im.naturalWidth));
  if(output){
   await page.screenshot({path:path.join(output,'home-'+width+'.png'),fullPage:true});
   if([1440,390].includes(width))for(const scene of ['hero','fractura','companions','workshop','events','games'])await page.locator('.ph-'+scene).screenshot({path:path.join(output,scene+'-'+width+'.png')});
  }
  const metrics=await page.locator('.panic-home .ph-art img').evaluateAll(images=>images.map(im=>{
   const rect=im.getBoundingClientRect();return{src:im.currentSrc,width:rect.width,height:rect.height,naturalWidth:im.naturalWidth,naturalHeight:im.naturalHeight,fit:getComputedStyle(im).objectFit};
  }));
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Horizontal overflow at '+width);
  assert.equal(metrics.length,6,'Exactly six unique artwork compositions');
  for(const im of metrics){assert.equal(im.fit,'contain');assert.ok(Math.abs(im.height-im.width*im.naturalHeight/im.naturalWidth)<2,'Artwork cropped: '+JSON.stringify(im));}
  if(width<=760){
   for(const scene of ['hero','fractura','companions','games']){
    const section=page.locator('.ph-'+scene), art=await section.locator('.ph-art').boundingBox(), copy=await section.locator('.ph-copy').boundingBox();
    assert.ok(copy.y>=art.y+art.height-22,'Mobile text overlaps artwork '+scene);
   }
  }
  assert.equal(await page.locator('[data-home-online]').textContent(),'17');
  assert.equal(await page.locator('[data-home-state]').textContent(),'Online');
  assert.equal(await page.locator('[data-home-events] li').count(),3);
  assert.equal(await page.locator('.ambient-embers').count(),0);
  await page.mouse.move(100,100);assert.equal(await page.locator('.cursor-ember').count(),0);
  if(width<=1100){await page.locator('.menu-toggle').click();assert.equal(await page.locator('.main-nav').isVisible(),true);await page.keyboard.press('Escape');assert.equal(await page.locator('.menu-toggle').getAttribute('aria-expanded'),'false');}
  const summary=page.locator('.ph-games-details summary');
  await summary.click();assert.equal(await page.locator('.ph-games-details').getAttribute('open'),'');
  assert.equal(await page.locator('.ph-games-disclosure li:visible').count(),8);
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Expanded games overflow');
  if(output&&width===390)await page.locator('.ph-games').screenshot({path:path.join(output,'games-open-390.png')});
  await summary.press('Enter');assert.equal(await page.locator('.ph-games-details').getAttribute('open'),null);
  assert.deepEqual(errors,[]);
  // Public event names are plain text; failed refresh keeps a clearly marked last count.
  await page.route('**/api/launcher.php',route=>route.fulfill({json:{...fixture,eventos:[{nombre:'<img src=x onerror=alert(1)>',proximo:'2099-10-20T21:00:00-03:00'}]}}));
  await page.evaluate(()=>window.dispatchEvent(new Event('online')));
  await page.waitForFunction(()=>document.querySelector('[data-home-events]').textContent.includes('<img'));
  assert.equal(await page.locator('[data-home-events] img').count(),0);
  await page.route('**/api/launcher.php',route=>route.fulfill({status:503,body:'unavailable'}));
  await page.evaluate(()=>window.dispatchEvent(new Event('online')));
  await page.waitForFunction(()=>document.querySelector('[data-home-state]').textContent==='Sin verificar');
  assert.equal(await page.locator('[data-home-online]').textContent(),'17');
  console.log('PASS '+width+'px: artwork ratios, complete resources, no overflow, menu, eight games and live data states.');
  await page.close();
 }
 const nojs=await browser.newPage({javaScriptEnabled:false,viewport:{width:390,height:844}});
 await routes(nojs,render());await nojs.goto('https://home.test/');
 assert.equal(await nojs.locator('.main-nav').isVisible(),true);
 assert.equal(await nojs.locator('h1').isVisible(),true);
 assert.ok(await nojs.locator('[data-home-events] li').count()>0);
 assert.equal(await nojs.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
 await nojs.close();await browser.close();
})().catch(error=>{console.error(error);process.exit(1)});
