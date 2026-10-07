const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const root = path.resolve(__dirname, '..');
const html = execFileSync('php', ['tests/fixtures/launch.php'], { cwd: root, encoding: 'utf8' });
const out = process.env.LAUNCH_SCREENSHOTS || path.join(root, '.tmp/launch-campaign');
fs.mkdirSync(out, { recursive: true });
const routePage = (page, content = html) => page.route('**/*', async route => {
 const url = new URL(route.request().url());
 if(url.pathname==='/') return route.fulfill({contentType:'text/html',body:content});
 const file=path.join(root,'overlay',url.pathname);
 if(fs.existsSync(file)&&fs.statSync(file).isFile()) return route.fulfill({path:file});
 return route.fulfill({status:404,body:'missing'});
});
(async()=>{
 const options = process.env.CHROMIUM_EXECUTABLE ? {executablePath:process.env.CHROMIUM_EXECUTABLE,args:['--no-sandbox','--disable-dev-shm-usage','--disable-gpu']} : {};
 const browser = await chromium.launch(options);
 for(const width of [1920,1366,1024,768,390,320]) {
  const page = await browser.newPage({viewport:{width,height:width>=1600?1080:width>700?768:844}});
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await routePage(page);await page.goto('https://beta.mupanic.com.ar/?preview=launch');await page.evaluate(()=>document.fonts.ready);
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,`overflow ${width}`);
  assert.equal(await page.locator('meta[name="robots"]').getAttribute('content'),'noindex, nofollow');
  assert.equal(await page.locator('[data-launch-at]').count(),0);
  assert.match(await page.locator('.campaign-opening').innerText(), /PRÓXIMAMENTE/);
  assert(!/31 OCTUBRE/.test(await page.locator('body').innerText()));
  const destinations=await page.locator('a[href]').evaluateAll(links=>links.map(a=>a.getAttribute('href')));
  assert(destinations.every(h=>h.startsWith('#')||h==='https://discord.gg/fP4Mxcsee'));
  const rates=await page.locator('.campaign-ticker').innerText();assert.match(rates,/15X/);assert.match(rates,/10X/);assert.match(rates,/25%/);
  assert.match(await page.locator('#filosofia').innerText(), /PAY TO WIN/);
  assert.match(await page.locator('#zen').innerText(), /JOYAS/);
  assert.match(await page.locator('#hub').innerText(), /CLIENTE PERSONALIZADO POR NOSOTROS/);
  assert.equal(await page.locator('.campaign-cut').count(), 4);
  assert.equal(await page.locator('#hub .campaign-art').count(), 0);
  assert.equal(await page.locator('.campaign-fx').evaluate(el=>el.width>0&&el.height>0),true);
  await page.waitForFunction(()=>getComputedStyle(document.querySelector('h1')).opacity==='1');
  await page.locator('.campaign-motion').click();assert.equal(await page.locator('.campaign-motion').getAttribute('aria-pressed'),'true');
  assert.equal(await page.locator('.campaign-hero-art').evaluate(el=>getComputedStyle(el).animationPlayState),'paused');
  await page.locator('.campaign-motion').press('Enter');assert.equal(await page.locator('.campaign-motion').getAttribute('aria-pressed'),'false');
  await page.evaluate(()=>{document.activeElement?.blur();window.scrollTo({top:0,behavior:'instant'});});
  await page.screenshot({path:path.join(out,`campaign-${width}-hero.png`)});
  for(const id of ['filosofia','zen','hub','eventos']) {
   await page.locator(`#${id}`).scrollIntoViewIfNeeded();
   await page.waitForFunction(()=>[...document.images].filter(i=>i.getBoundingClientRect().height>0 && i.getBoundingClientRect().top<innerHeight && i.getBoundingClientRect().bottom>0).every(i=>i.complete&&i.naturalWidth>0));
   await page.waitForFunction(id=>{const copy=document.querySelector(`#${id} .campaign-copy`);return !copy || getComputedStyle(copy).opacity==='1';}, id);
   if (id === 'hub') {
    for (const name of ['entry','hub']) {
     await page.locator(`#client-tab-${name}`).click();
     assert.equal(await page.locator('.campaign-client-panel:visible').count(),1);
     await page.waitForFunction(name=>{const i=document.querySelector(`#client-${name} img`);return i.complete&&i.naturalWidth>0;},name);
    }
    await page.locator('#client-tab-hub').press('Home');
    assert.equal(await page.locator('#client-tab-entry').getAttribute('aria-selected'),'true');
    await page.locator('#client-tab-entry').press('ArrowRight');
    assert.equal(await page.locator('#client-tab-hub').getAttribute('aria-selected'),'true');
    const dimensions = await page.locator('#client-hub img').evaluate(image => ({width:image.clientWidth,height:image.clientHeight,ratio:image.naturalWidth/image.naturalHeight,fit:getComputedStyle(image).objectFit}));
    assert.equal(dimensions.fit, 'contain');
    assert(Math.abs(dimensions.width/dimensions.height-dimensions.ratio)<.02, 'Hub preview must preserve its complete proportions');
   }
   await page.screenshot({path:path.join(out,`campaign-${width}-${id}.png`)});
  }
  for(const id of ['classics','invasions','arenas']) {
   await page.locator(`#tab-${id}`).click();assert.equal(await page.locator(`#tab-${id}`).getAttribute('aria-selected'),'true');
   assert.equal(await page.locator('.campaign-event:visible').count(),1);
   assert.notEqual(await page.locator(`#tab-${id}`).evaluate(el=>getComputedStyle(el).clipPath),'none');
   await page.waitForFunction(id=>{const i=document.querySelector(`#event-${id} img`);return i.complete&&i.naturalWidth>0},id);
  }
  await page.locator('#tab-arenas').press('Home');assert.equal(await page.locator('#tab-classics').getAttribute('aria-selected'),'true');
  await page.locator('#tab-classics').press('ArrowRight');assert.equal(await page.locator('#tab-invasions').getAttribute('aria-selected'),'true');
  await page.locator('#tab-invasions').press('End');assert.equal(await page.locator('#tab-arenas').getAttribute('aria-selected'),'true');
  await page.locator('.campaign-community').scrollIntoViewIfNeeded();
  await page.waitForFunction(()=>[...document.images].filter(i=>i.getBoundingClientRect().height>0).every(i=>i.complete&&i.naturalWidth>0));
  assert.deepEqual(errors,[]);assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
  await page.screenshot({path:path.join(out,`campaign-${width}-full.png`),fullPage:true});
  await page.emulateMedia({reducedMotion:'reduce'});assert.equal(await page.locator('.campaign-hero-art').evaluate(el=>getComputedStyle(el).animationName),'none');
  await page.close();console.log(`Campaign ${width}: responsive, images, tabs, keyboard, effects, countdown passed`);
 }
 const clockHtml=execFileSync('php',['tests/fixtures/launch.php'],{cwd:root,encoding:'utf8',env:{...process.env,LAUNCH_TEST_DATE:'1'}});
 const clockPage=await browser.newPage();
 await clockPage.clock.install({time:new Date('2026-10-31T22:59:50Z')});await clockPage.clock.pauseAt(new Date('2026-10-31T22:59:58Z'));await routePage(clockPage,clockHtml);await clockPage.goto('https://beta.mupanic.com.ar/?preview=launch');
 assert.equal(await clockPage.locator('[data-clock="seconds"]').innerText(),'02');
 await clockPage.clock.runFor(1000);assert.equal(await clockPage.locator('[data-clock="seconds"]').innerText(),'01');
 await clockPage.clock.runFor(2000);assert.equal(await clockPage.locator('[data-clock="seconds"]').innerText(),'00');
 assert.equal(await clockPage.locator('[data-clock="days"]').innerText(),'00');await clockPage.close();
 const page=await browser.newPage({javaScriptEnabled:false});await routePage(page);await page.goto('https://beta.mupanic.com.ar/?preview=launch');
 assert.equal(await page.locator('h1').isVisible(),true);assert.equal(await page.locator('.campaign-motion').isVisible(),false);assert.equal(await page.locator('.campaign-event:visible').count(),3);assert.equal(await page.locator('.campaign-client-panel:visible').count(),2);
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
