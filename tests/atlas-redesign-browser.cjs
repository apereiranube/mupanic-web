const fs=require('fs'),path=require('path'),os=require('os'),assert=require('node:assert/strict');
const {execFileSync}=require('child_process'),{chromium}=require('playwright');
const root=path.resolve(__dirname,'../overlay/templates/mupanic');
const html=execFileSync(process.env.PHP_BIN||'php',[path.join(__dirname,'fixtures/atlas-rewards.php')],{encoding:'utf8',maxBuffer:16e6});
const output=process.env.ATLAS_SCREENSHOT_DIR;
const eventSnapshot=JSON.parse(fs.readFileSync(path.join(root,'inc/public-events.json'),'utf8'));
const enabledEvents=eventSnapshot.events.filter(e=>e.enabled);
const imagePaths=[...html.matchAll(/<img[^>]+src="([^"]+)"/g)].map(m=>m[1]);
for(const src of imagePaths){assert.ok(src.startsWith('/templates/mupanic/'));assert.ok(fs.existsSync(path.join(root,src.slice('/templates/mupanic/'.length))),'Missing image '+src);}
function setup(page,errors,body=html){
 page.on('pageerror',e=>errors.push(e.message));
 return page.route('**/*',route=>{
  const name=new URL(route.request().url()).pathname;
  if(name==='/info/')return route.fulfill({contentType:'text/html',body});
  if(name.startsWith('/templates/mupanic/')){const file=path.join(root,name.slice('/templates/mupanic/'.length));if(fs.existsSync(file))return route.fulfill({path:file});}
  errors.push('Missing asset '+name);return route.abort();
 });
}
async function noOverflow(page,label){assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Horizontal overflow '+label);}
async function shot(page,name){if(output)await page.screenshot({path:path.join(output,name+'.png')});}
(async()=>{
 if(output)fs.mkdirSync(output,{recursive:true});
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 for(const [width,height] of [[1920,1080],[1366,768],[1024,768],[390,844],[320,700]]){
  const page=await browser.newPage({viewport:{width,height},reducedMotion:'reduce'}),errors=[];
  await setup(page,errors);await page.goto('https://atlas.test/info/');await page.evaluate(()=>document.fonts.ready);
  assert.equal(await page.locator('.wiki-intro').count(),0);
  assert.equal(await page.locator('.wiki-section:visible').count(),1);
  assert.equal(await page.locator('.atlas-home-card').count(),4);
  assert.equal(await page.locator('.atlas-boot').count(),0);
  assert.equal(await page.evaluate(()=>scrollY),0,'Fresh Atlas visit keeps the hero in view');
  if(width<=900){assert.equal(await page.locator('.main-nav').isVisible(),false,'Mobile navigation is collapsed');await page.locator('.menu-toggle').click();assert.equal(await page.locator('.main-nav').isVisible(),true);await page.keyboard.press('Escape');assert.equal(await page.locator('.main-nav').isVisible(),false);}
  assert.equal(await page.evaluate(()=>getComputedStyle(document.body).backgroundColor),'rgb(27, 34, 35)');
  assert.deepEqual(await page.locator('[data-recipe-account] option').allTextContents(),['Free','VIP']);
  assert.deepEqual(await page.locator('[data-recipe-account] option').evaluateAll(options=>options.map(o=>o.value)),['0','1']);
  assert.equal(await page.locator('#rates tbody').first().locator('tr').count(),2);
  await noOverflow(page,'home');if([1920,390].includes(width))await shot(page,'atlas-home-'+width);
  await page.locator('.wiki-nav a[href="#progresion"]').click();
  assert.equal(await page.locator('#progresion .wiki-map').count(),30);
  await page.locator('[data-map-filter]').fill('Lorencia');
  assert.equal(await page.locator('#progresion .wiki-map:visible').count(),1);
  const lorencia=page.locator('#mapa-0');await lorencia.locator('>summary').click();
  await lorencia.locator('[data-atlas-select="1"]').last().click();
  assert.equal(await lorencia.locator('[data-atlas-panel="1"]').isVisible(),true);
  await lorencia.locator('[data-atlas-panel="1"] .atlas-loot>summary').first().click();
  await lorencia.locator('[data-atlas-account]').selectOption('1');
  assert.equal(await lorencia.locator('[data-atlas-account]').inputValue(),'1');
  assert.equal(await lorencia.locator('[data-atlas-panel="1"] [data-atlas-rates]').evaluateAll(rows=>rows.every(row=>row.textContent===JSON.parse(row.dataset.atlasRates)[1].toLocaleString('es-AR',{maximumFractionDigits:6})+'%')),true,'Display configured VIP rates');
  await noOverflow(page,'map');if([1920,390].includes(width)){await lorencia.evaluate(el=>el.scrollIntoView({block:'start',behavior:'instant'}));await shot(page,'atlas-map-'+width);}
  await page.locator('.wiki-nav a[href="#recompensas"]').click();
  await page.locator('[data-reward-kind="boss"]').click();
  assert.ok(await page.locator('[data-reward-list]:visible').count()>0);
  assert.equal(await page.locator('[data-reward-list][data-reward-type="box"]:visible').count(),0);
  await page.locator('[data-reward-filter]').fill('Medusa');
  const medusa=page.locator('#recompensa-106');await medusa.locator('summary').click();
  await medusa.locator('[data-reward-item-filter]').fill('Hyon');assert.equal(await medusa.locator('[data-reward-item]:visible').count(),4);
  await noOverflow(page,'boss');if([1920,390].includes(width)){await medusa.scrollIntoViewIfNeeded();await shot(page,'atlas-boss-'+width);}
  await page.locator('.wiki-nav a[href="#buscar"]').click();await page.locator('[data-atlas-query]').fill('Harmony');
  assert.ok(await page.locator('.atlas-find-card').count()>0);await noOverflow(page,'finder');
  await page.locator('.wiki-nav a[href="#eventos"]').click();
  assert.equal(await page.locator('.atlas-event-card').count(),enabledEvents.length);if([1920,390].includes(width)){await page.locator('#eventos').scrollIntoViewIfNeeded();await shot(page,'atlas-events-'+width);}
  await page.locator('[data-event-group=staff]').click();assert.equal(await page.locator('.atlas-event-card:visible').count(),6);await page.locator('[data-event-group=all]').click();
  await page.locator('[data-event-search]').fill('Maldito');assert.equal(await page.locator('.atlas-event-card:visible').count(),1);await page.locator('[data-event-search]').fill('');
  assert.equal(await page.locator('.atlas-event-card [data-atlas-event-open=auction]').count(),0);
  const timeResult=await page.evaluate(()=>document.querySelector('[data-wiki]').atlasNextOccurrence([[-1,-1,-1,-1,-1,50,0]],new Date('2026-10-06T13:51:00Z')).toISOString());assert.equal(timeResult,'2026-10-06T14:50:00.000Z');
  for(const id of (width>=1024?enabledEvents.map(e=>e.id):['pandora','blood-castle','devil-square'])){
   const trigger=page.locator('.atlas-event-card [data-atlas-event-open="'+id+'"]');await trigger.click();
   const modal=page.locator('[data-atlas-event-dialog]');assert.equal(await modal.evaluate(el=>el.open),true);
   assert.equal(await page.evaluate(()=>getComputedStyle(document.documentElement).overflowY),'hidden');
   assert.equal(await modal.locator('h3').count(),1);
   assert.equal(await modal.evaluate(el=>el.getBoundingClientRect().width<=innerWidth),true);
   if(width>=1024){assert.equal(await modal.evaluate(el=>el.scrollHeight>el.clientHeight+1),false,'Desktop modal overflow '+id+' '+width);assert.ok((await modal.boundingBox()).height<=height*.85);}
   if(id==='blood-castle'&&[1920,390].includes(width))await shot(page,'atlas-event-modal-'+width);
   if(id==='pandora'){assert.ok((await modal.textContent()).includes('19:15'));await page.keyboard.press('Escape');}
   else if(id==='blood-castle')await modal.locator('[data-atlas-event-close]').click();
   else await page.mouse.click(3,3);
   assert.equal(await modal.evaluate(el=>el.open),false);
   assert.equal(await trigger.evaluate(el=>document.activeElement===el),true,'Return event focus');
  }
  await page.locator('.atlas-event-card [data-atlas-event-open="blood-castle"]').click();await page.locator('[data-atlas-event-dialog] a[href="#recompensa-12"]').click();
  await page.locator('#recompensa-12').waitFor({state:'visible'});assert.equal(await page.locator('#recompensa-12').isVisible(),true,'Reward link clears filters');
  assert.equal(await page.locator('[data-atlas-event-dialog]').evaluate(el=>el.open),false);
  // Keyboard focus stays inside the dossier.
  await page.locator('.wiki-nav a[href="#eventos"]').click();await page.locator('.atlas-event-card [data-atlas-event-open="devil-square"]').click();
  await page.keyboard.press('Shift+Tab');assert.equal(await page.evaluate(()=>!!document.activeElement.closest('dialog')),true);await page.keyboard.press('Escape');
  // All chapter links still resolve without manufacturing data or changing IDs.
  await page.goto('https://atlas.test/info/#taller');await page.locator('[data-recipe-search]').fill('Fenrir');assert.ok(await page.locator('[data-recipe-nav]:visible').count()>0);await noOverflow(page,'workshop');
  await page.locator('[data-recipe-search]').fill('');await page.locator('[data-recipe-account]').selectOption('1');
  const recipeId=await page.locator('[data-recipe-id]:visible').first().getAttribute('data-recipe-id');
  assert.equal(await page.locator('[data-recipe-id]:visible [data-recipe-rate]').first().textContent(),await page.evaluate(id=>{const d=JSON.parse(document.querySelector('#wiki-data').textContent),r=d.recipes.find(r=>r.id===id),v=d.crafting.mixRates[r.rateKey][1];return v===-1?'Variable':v+'%';},recipeId));
  for(const chapter of ['primeros-pasos','rates','sistemas','mejoras','equipo']){
   await page.goto('https://atlas.test/info/#'+chapter);await page.evaluate(()=>document.fonts.ready);
   await page.locator('#'+chapter).evaluate(el=>el.scrollIntoView({block:'start',behavior:'instant'}));
   await noOverflow(page,chapter);if([1920,390].includes(width))await shot(page,'atlas-'+chapter+'-'+width);
  }
  await page.goto('https://atlas.test/info/#taller');await page.locator('#taller .recipe-reference summary').click();await page.locator('#taller .recipe-reference').evaluate(el=>el.scrollIntoView({block:'start',behavior:'instant'}));await noOverflow(page,'recipe reference');if([1920,390].includes(width))await shot(page,'atlas-recipe-rates-'+width);
  assert.deepEqual(errors,[]);await page.close();console.log('PASS redesigned Atlas',width);
 }
 // A new server catalogue removes disabled events without replacing cached chapter nodes.
 const runtime=fs.mkdtempSync(path.join(os.tmpdir(),'atlas-events-test-'));
 const changed=JSON.parse(JSON.stringify(eventSnapshot));changed.sourceHash='e'.repeat(64);changed.events.find(e=>e.id==='pandora').enabled=false;changed.events.filter(e=>e.id.startsWith('arena-')||e.id.startsWith('event-drop-')).forEach(e=>e.enabled=true);
 fs.writeFileSync(path.join(runtime,'public-events.json'),JSON.stringify(changed));
 const refreshed=execFileSync(process.env.PHP_BIN||'php',[path.join(__dirname,'fixtures/atlas-rewards.php')],{encoding:'utf8',env:{...process.env,PANIC_ATLAS_RUNTIME_DIR:runtime},maxBuffer:16e6});
 const live=await browser.newPage({viewport:{width:1920,height:1080},reducedMotion:'reduce'}),liveErrors=[];
 await live.clock.install({time:new Date('2026-10-06T13:00:00Z')});await setup(live,liveErrors);let refreshCalls=0;
 await live.route('**/api/atlas-events.php',route=>{refreshCalls++;return route.fulfill({contentType:'application/json',body:JSON.stringify({version:changed.sourceHash,html:refreshed.match(/<section id="eventos"[\s\S]*?<\/section>/)[0]})});});
 await live.goto('https://atlas.test/info/#eventos');
 await live.locator('.atlas-event-card [data-atlas-event-open="pandora"]').click();
 await live.clock.fastForward(61000);assert.equal(refreshCalls,0,'Refreshing defers while a dossier is open');
 await live.keyboard.press('Escape');await live.clock.fastForward(61000);
 await live.waitForFunction(()=>document.querySelector('#eventos').dataset.eventsVersion==='e'.repeat(64));
 assert.equal(await live.locator('.atlas-event-card [data-atlas-event-open="pandora"]').count(),0);
 assert.equal(await live.locator('.atlas-event-card').count(),changed.events.filter(e=>e.enabled).length);
 await live.locator('.wiki-nav a[href="#inicio"]').click();await live.locator('#inicio').waitFor({state:'visible'});await live.locator('.wiki-nav a[href="#eventos"]').click();await live.locator('#eventos').waitFor({state:'visible'});
 assert.equal(await live.locator('#eventos').isVisible(),true,'Chapter references survive refresh');
 await live.locator('.atlas-event-card [data-atlas-event-open="blood-castle"]').click();assert.equal(await live.locator('[data-atlas-event-dialog]').evaluate(el=>el.open),true);await live.keyboard.press('Escape');
 for(const e of changed.events.filter(e=>e.id.startsWith('arena-')||e.id.startsWith('event-drop-'))){await live.locator('.atlas-event-card [data-atlas-event-open="'+e.id+'"]').click();assert.equal(await live.locator('[data-atlas-event-dialog]').evaluate(el=>el.scrollHeight>el.clientHeight+1),false);await live.keyboard.press('Escape');}
 assert.deepEqual(liveErrors,[]);await live.close();fs.rmSync(runtime,{recursive:true,force:true});
 // Hold the late app script to verify the first paint without a disappearing header.
 const boot=await browser.newPage({viewport:{width:1920,height:1080},reducedMotion:'reduce'}),bootErrors=[];
 await setup(boot,bootErrors);
 let releaseMain;const mainGate=new Promise(resolve=>{releaseMain=resolve;});
 await boot.route('**/js/main.js',async route=>{await mainGate;return route.fulfill({path:path.join(root,'js/main.js')});});
 await boot.goto('https://atlas.test/info/',{waitUntil:'commit'});
 await boot.locator('#wiki-data').waitFor({state:'attached'});await boot.evaluate(()=>document.fonts.ready);
 assert.equal(await boot.locator('.atlas-boot').count(),1);
 assert.equal(await boot.locator('.wiki-section:visible').count(),1,'First paint exposes one chapter');
 const firstTop=await boot.locator('#inicio').evaluate(el=>el.getBoundingClientRect().top);
 releaseMain();await boot.waitForFunction(()=>!document.querySelector('.atlas-boot'));
 assert.ok(Math.abs((await boot.locator('#inicio').evaluate(el=>el.getBoundingClientRect().top))-firstTop)<2,'No header layout shift after app init');
 assert.deepEqual(bootErrors,[]);await boot.close();
 const fallback=await browser.newPage({viewport:{width:390,height:844},javaScriptEnabled:false}),errors=[];
 await setup(fallback,errors);await fallback.goto('https://atlas.test/info/');
 assert.ok(await fallback.locator('#progresion').isVisible());assert.equal(await fallback.locator('.atlas-event-full:visible').count(),enabledEvents.length);await noOverflow(fallback,'no JS');assert.deepEqual(errors,[]);await fallback.close();
 await browser.close();
})().catch(error=>{console.error(error);process.exit(1)});
