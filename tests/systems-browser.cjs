const fs = require('fs');
const path = require('path');
const assert = require('node:assert/strict');
const {createHash} = require('node:crypto');
const {execFileSync} = require('child_process');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '../overlay/templates/mupanic');
const html = execFileSync(process.env.PHP_BIN || 'php', [path.join(__dirname, 'fixtures/systems.php')], {encoding:'utf8'});
const ids = ['chronicles','hero-path','daily','fortune','vault','vip','nexus'];
// Artwork URLs must change with the bytes, including clients with old assets cached.
for (const id of ids) {
 const name = fs.readdirSync(path.join(root,'img/server/systems')).find(name=>name.startsWith(id+'-'));
 assert.ok(name, 'Missing versioned artwork: '+id);
 const digest = createHash('sha256').update(fs.readFileSync(path.join(root,'img/server/systems',name))).digest('hex').slice(0,12);
 assert.equal(name,id+'-'+digest+'.webp');
 assert.ok(html.includes('/systems/'+name));
 assert.ok(!html.includes('/systems/'+id+'.webp'));
}
const hubName=fs.readdirSync(path.join(root,'img/server/systems')).find(name=>name.startsWith('hub-f11-'));
assert.ok(hubName,'Missing client capture');
assert.equal(hubName,'hub-f11-'+createHash('sha256').update(fs.readFileSync(path.join(root,'img/server/systems',hubName))).digest('hex').slice(0,12)+'.webp');
(async () => {
 const browser = await chromium.launch({headless:true,args:['--no-sandbox']});
 const output = process.env.SYSTEMS_SCREENSHOT_DIR;
 if (output) fs.mkdirSync(output,{recursive:true});
 for (const [width,height,columns] of [[1920,1080,3],[1366,768,3],[1024,768,2],[390,844,1],[360,640,1]]) {
  const page = await browser.newPage({viewport:{width,height}});
  const errors = []; page.on('pageerror', error => errors.push(error.message));
  await page.route('**/*', async route => {
   const pathname = new URL(route.request().url()).pathname;
   if (pathname === '/information/') return route.fulfill({contentType:'text/html',body:html});
   if (pathname.startsWith('/templates/mupanic/')) {
    const file = path.join(root, pathname.slice('/templates/mupanic/'.length));
    if (fs.existsSync(file)) return route.fulfill({path:file});
   }
   errors.push('Missing asset: '+pathname); return route.abort();
  });
  await page.goto('https://systems.test/information/',{waitUntil:'networkidle'});
  await page.evaluate(() => document.fonts.ready);
  assert.equal(await page.locator('[data-system-open]').count(),7);
  assert.equal(await page.locator('details').count(),0);
  assert.equal(await page.locator('.server-features-grid').evaluate(el => getComputedStyle(el).gridTemplateColumns.split(' ').length),columns);
  const shading = await page.locator('.server-feature-card').first().evaluate(el => ({card:el.clientHeight,shade:parseFloat(getComputedStyle(el,'::after').height),padding:getComputedStyle(el).padding}));
  assert.ok(Math.abs(shading.card-shading.shade)<=2, 'Card shading must cover the artwork: '+JSON.stringify(shading));
  assert.equal(shading.padding,'0px');
  for (const id of ids) {
   const button = page.locator(`[data-system-open="${id}"]`);
   const dialog = page.locator('[data-system-modal]');
   await button.click();
   await page.waitForTimeout(250);
   assert.equal(await page.locator('[data-system-panel]:visible').getAttribute('data-system-panel'),id);
   assert.equal(await dialog.getAttribute('aria-labelledby'),'system-title-'+id);
   assert.equal(await page.evaluate(()=>document.activeElement.hasAttribute('data-system-close')),true);
   const metrics = await dialog.evaluate(el => ({height:el.getBoundingClientRect().height,width:el.getBoundingClientRect().width,overflow:el.scrollHeight>el.clientHeight+1,bg:getComputedStyle(document.documentElement).overflow}));
   assert.equal(metrics.bg,'hidden');
   if (width>700) { assert.equal(metrics.overflow,false,JSON.stringify({width,id,metrics})); assert.ok(metrics.height<=height*.85+2); assert.ok(metrics.width<=1050); }
   await page.locator('[data-system-close]').press('Tab');
   assert.equal(await page.evaluate(()=>document.activeElement.hasAttribute('data-system-close')),true,'Focus trap');
   assert.equal(await page.locator('[data-system-panel]:visible img').evaluate(im=>im.complete&&im.naturalWidth===Number(im.getAttribute('width'))&&im.naturalHeight===Number(im.getAttribute('height'))),true);
   if(id!=='nexus') assert.equal(await page.locator('[data-system-panel]:visible img').getAttribute('src'), await page.locator('[data-system-open="'+id+'"]').locator('xpath=ancestor::article').locator('img').getAttribute('src'));
   if(id==='nexus') {
    assert.ok((await page.locator('[data-system-panel]:visible img').getAttribute('src')).includes('hub-f11-'));
    assert.ok(!(await button.locator('xpath=ancestor::article').locator('img').getAttribute('src')).includes('hub-f11-'));
    if(width>700) assert.ok(await button.locator('xpath=ancestor::article').evaluate(el=>el.getBoundingClientRect().height<=320),'Nexus card stays compact');
   }
   if(id==='nexus') assert.equal(await page.locator('[data-system-panel]:visible img').evaluate(im=>getComputedStyle(im).objectFit),'contain','Show the entire real Hub');
   if(output && (width===1920||width===390) && ['chronicles','fortune','nexus'].includes(id)) await page.screenshot({path:path.join(output,`${id}-${width}.png`)});
   await page.keyboard.press('Escape');
   assert.equal(await dialog.isVisible(),false);
   assert.equal(await button.evaluate(el=>el===document.activeElement),true);
   await button.click(); await page.locator('[data-system-close]').click(); assert.equal(await dialog.isVisible(),false);
   await button.click(); await page.mouse.click(2,2); assert.equal(await dialog.isVisible(),false);
   assert.equal(await page.evaluate(()=>document.documentElement.classList.contains('system-modal-open')),false);
  }
  await page.locator('.server-feature-card-nexus').scrollIntoViewIfNeeded();
  const allImages=await page.locator('.server-feature-card img').evaluateAll(ims=>ims.every(im=>im.complete&&im.naturalWidth===Number(im.getAttribute('width'))&&im.naturalHeight===Number(im.getAttribute('height'))));
  assert.equal(allImages,true,'Broken card image');
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Horizontal overflow');
  if(output && (width===1920||width===390)) await page.locator('#sistemas').screenshot({path:path.join(output,`systems-${width}.png`)});
  assert.deepEqual(errors,[]);
  console.log(`PASS ${width}x${height}: seven systems, images, X/Escape/backdrop, focus, layout and scroll.`);
  await page.close();
 }
 await browser.close();
})().catch(error=>{console.error(error);process.exit(1)});
