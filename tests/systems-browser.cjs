// Launch redesign: complete production templates, public data and responsive navigation.
const fs = require('fs');
const path = require('path');
const assert = require('node:assert/strict');
const {execFileSync} = require('child_process');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '../overlay/templates/mupanic');
const fixture = path.join(__dirname,'fixtures/experience.php');
const render = page => execFileSync(process.env.PHP_BIN || 'php',['-n',fixture,page],{encoding:'utf8'});
const pages = {'/':render(''),'/information/':render('server')};
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 const output=process.env.SYSTEMS_SCREENSHOT_DIR;
 if(output)fs.mkdirSync(output,{recursive:true});
 for(const width of [1920,1440,1100,1024,768,760,390,320]) {
  const page=await browser.newPage({viewport:{width,height:960}});
  const errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*', async route=>{
   const url=new URL(route.request().url());
   if(pages[url.pathname])return route.fulfill({contentType:'text/html',body:pages[url.pathname]});
   if(url.pathname==='/api/launcher.php')return route.fulfill({json:{online:13,servidor:'online',eventos:[
    {nombre:'Evento futuro',proximo:'2099-10-20T18:00:00-03:00'},
    {nombre:'<img src=x onerror=alert(1)>',proximo:'2099-10-20T19:00:00-03:00'}
   ]}});
   if(url.pathname.startsWith('/templates/mupanic/')) {
    const file=path.join(root,url.pathname.slice('/templates/mupanic/'.length));
    if(fs.existsSync(file))return route.fulfill({path:file});
   }
   errors.push('Missing resource '+url.pathname);return route.abort();
  });
  for(const url of ['/','/information/']) {
   await page.goto('https://experience.test'+url,{waitUntil:'networkidle'});
   await page.evaluate(()=>document.fonts.ready);
   assert.equal(await page.locator('h1').count(),1);
   // Force lazy images to load before checking dimensions and layout stability.
   await page.locator('img[loading=lazy]').evaluateAll(images=>images.forEach(im=>im.loading='eager'));
   await page.waitForFunction(()=>Array.from(document.images).every(im=>im.complete&&im.naturalWidth>0));
   assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Horizontal overflow '+width+' '+url);
   if(width<=1100){
    await page.locator('.menu-toggle').click();
    assert.equal(await page.locator('.menu-toggle').getAttribute('aria-expanded'),'true');
    await page.keyboard.press('Escape');
    assert.equal(await page.locator('.menu-toggle').getAttribute('aria-expanded'),'false');
   }
   if(url==='/'){
    assert.equal(await page.locator('[data-home-online]').textContent(),'13');
    assert.equal(await page.locator('[data-home-state]').textContent(),'Online');
    assert.equal(await page.locator('[data-home-events] img').count(),0,'API names rendered as text');
    const games=await page.locator('.px-games').boundingBox();
    const factions=await page.locator('.px-fractura').boundingBox();
    assert.ok(games.y>factions.y,'Games remain secondary');
    await page.route('**/api/launcher.php',route=>route.abort());
    await page.evaluate(()=>window.dispatchEvent(new Event('online')));
    await page.waitForFunction(()=>document.querySelector('[data-home-state]').textContent==='Sin verificar');
    assert.equal(await page.locator('[data-home-online]').textContent(),'13','Last known count retained with stale label');
   }else{
    assert.equal(await page.locator('.px-detail').count(),4);
    assert.equal(await page.locator('.px-games-index li').count(),8);
    for(const detail of await page.locator('.px-detail').all()){
     await detail.locator('summary').click();assert.equal(await detail.getAttribute('open'),'');
     await detail.locator('summary').press('Enter');assert.equal(await detail.getAttribute('open'),null);
    }
   }
   if(output && [1440,390].includes(width)){
    await page.evaluate(()=>scrollTo(0,0));
    await page.screenshot({path:path.join(output,(url==='/'?'home':'server')+'-'+width+'.png'),fullPage:true});
   }
   assert.deepEqual(errors,[]);
  }
  await page.emulateMedia({reducedMotion:'reduce'});
  assert.equal(await page.locator('.px-hero').count(),0);
  console.log('PASS launch experience '+width+'px: resources, data, navigation, text safety and document flow.');
  await page.close();
 }
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
