// El servidor (/information/): render the real module and check it on desktop and mobile.
const fs = require('fs');
const path = require('path');
const assert = require('node:assert/strict');
const {createHash} = require('node:crypto');
const {execFileSync} = require('child_process');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '../overlay/templates/mupanic');
const html = execFileSync(process.env.PHP_BIN || 'php', [path.join(__dirname, 'fixtures/systems.php')], {encoding:'utf8'});

// Every image the page names must exist; V3 artwork names change with their bytes.
const srcs = [...html.matchAll(/<img[^>]+src="([^"]*)"/g)].map(m=>m[1]).filter(Boolean);
assert.ok(srcs.length >= 40, 'Server page images: '+srcs.length);
for (const src of srcs) {
 assert.ok(src.startsWith('/templates/mupanic/'), 'Unexpected image path: '+src);
 assert.ok(fs.existsSync(path.join(root,src.slice('/templates/mupanic/'.length))), 'Missing page image: '+src);
}
const v3 = path.join(root,'img/server/v3');
for (const name of fs.readdirSync(v3)) {
 const digest = createHash('sha256').update(fs.readFileSync(path.join(v3,name))).digest('hex').slice(0,12);
 assert.match(name, new RegExp('^[a-z0-9-]+-'+digest+'\\.webp$'), 'Unversioned artwork: '+name);
}
const css = fs.readFileSync(path.join(root,'css/information.css'),'utf8');
for (const ref of css.matchAll(/url\(['"]?\.\.\/(img\/[^)'"]+)/g)) assert.ok(fs.existsSync(path.join(root,ref[1])), 'Missing CSS asset: '+ref[1]);
assert.ok(!/data:image|beta\.mupanic\.com\.ar/.test(html+css+fs.readFileSync(path.join(root,'js/information.js'),'utf8')), 'No embedded art or hard-coded domain');
for (const banned of [/Sello Imperial/i, /\bbeta\b/i, /Louis/i, /UP43/i]) assert.ok(!banned.test(html.replace(/<script[\s\S]*?<\/script>/g,'')), 'Text that must not be shown: '+banned);
const events = JSON.parse(html.match(/<script type="application\/json" id="srv-events">([\s\S]*?)<\/script>/)[1]);
assert.ok(events.length >= 5, 'Scheduled events from the Atlas data');
for (const e of events) {
 assert.ok(['classic','invasion','custom'].includes(e.g) && e.d > 0 && e.t.length && e.t.every(t=>/^\d\d:\d\d$/.test(t)), JSON.stringify(e));
 assert.ok(fs.existsSync(path.join(root,'img',e.i)), 'Missing event art: '+e.i);
}

let three = '';
try { three = fs.readFileSync(require.resolve('three/build/three.min.js'),'utf8'); } catch (e) {}

(async () => {
 const browser = await chromium.launch({headless:true,args:['--no-sandbox','--use-gl=swiftshader','--enable-unsafe-swiftshader']});
 const output = process.env.SYSTEMS_SCREENSHOT_DIR;
 if (output) fs.mkdirSync(output,{recursive:true});
 for (const [width,height] of [[2560,1440],[1920,1080],[1366,768],[1024,768],[390,844],[360,640]]) {
  const page = await browser.newPage({viewport:{width,height}});
  const errors = []; page.on('pageerror', error => errors.push(error.message));
  await page.route('**/*', async route => {
   const url = new URL(route.request().url());
   if (url.pathname === '/information/') return route.fulfill({contentType:'text/html',body:html});
   if (url.hostname === 'cdnjs.cloudflare.com' && url.pathname.endsWith('/three.min.js')) return route.fulfill({contentType:'text/javascript',body:three});
   if (url.hostname.startsWith('fonts.g')) return route.fulfill({contentType:'text/css',body:''});
   if (url.pathname.startsWith('/templates/mupanic/')) {
    const file = path.join(root, url.pathname.slice('/templates/mupanic/'.length));
    if (fs.existsSync(file)) return route.fulfill({path:file});
   }
   errors.push('Missing asset: '+url.href); return route.abort();
  });
  await page.goto('https://systems.test/information/',{waitUntil:'networkidle'});
  await page.waitForTimeout(400);

  assert.equal(await page.locator('#srv-page section.sc').count(),10,'Ten scenes');
  assert.equal(await page.locator('#srv-page.js').count(),1,'Page script started');
  // Sticky scene backgrounds need every ancestor without overflow clipping.
  assert.equal(await page.locator('.module-surface').evaluate(el=>getComputedStyle(el).overflowX),'visible');
  // Scenes span the whole screen, also above 1920 px.
  assert.equal(await page.locator('#srv-page section.sc').evaluateAll(els=>els.every(el=>Math.abs(el.getBoundingClientRect().width-document.documentElement.clientWidth)<2)),true,'Full-width scenes');
  assert.equal(await page.locator('#srv-page .sbg .stick').first().evaluate(el=>getComputedStyle(el).position),'sticky');
  // Live events clock reads the real schedule.
  assert.notEqual((await page.locator('#nx-name').textContent()).trim(),'—','Next event');
  assert.ok(await page.locator('#ev-list li').count()>0,'Event list');
  await page.locator('#f-invasion').click();
  assert.equal(await page.locator('#f-invasion').getAttribute('aria-pressed'),'true');
  // Classes roster.
  assert.equal(await page.locator('#roster .cls').count(),7);
  await page.locator('#roster .cls').nth(3).locator('button').click();
  assert.ok(await page.locator('#roster .cls').nth(3).evaluate(el=>el.classList.contains('on')));
  // Free / VIP toggle.
  await page.locator('#v-vip').click();
  assert.equal(await page.locator('#v-vip').getAttribute('aria-pressed'),'true');
  // Reset simulator moves.
  await page.locator('#simR').evaluate(el=>{el.value=el.max;el.dispatchEvent(new Event('input',{bubbles:true}))});
  assert.match((await page.locator('#simOut').textContent()).trim(),/20/,'Reset simulator');

  // Walk the page so every lazy image and scroll-driven demo starts.
  const total = await page.evaluate(()=>document.documentElement.scrollHeight);
  for (let y=0; y<total; y+=Math.round(height*.8)) {
   await page.evaluate(v=>window.scrollTo(0,v),y);
   await page.waitForTimeout(60);
   if (output && (width===1920||width===390) && y % (height*4) < height*.8) await page.screenshot({path:path.join(output,`server-${width}-${String(y).padStart(5,'0')}.png`)});
  }
  await page.evaluate(()=>document.querySelectorAll('#srv-page img[loading="lazy"]').forEach(im=>{im.loading='eager';}));
  await page.waitForFunction(()=>[...document.querySelectorAll('#srv-page img')].every(im=>im.complete),null,{timeout:20000});
  const broken = await page.locator('#srv-page img').evaluateAll(ims=>ims.filter(im=>im.getAttribute('src')&&!im.naturalWidth).map(im=>im.getAttribute('src')));
  assert.deepEqual(broken,[],'Broken image in the server page');
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Horizontal overflow');
  assert.deepEqual(errors,[]);
  console.log(`PASS ${width}x${height}: ten scenes, live events, classes, VIP, images and layout.`);
  await page.close();
 }
 await browser.close();
})().catch(error=>{console.error(error);process.exit(1)});
