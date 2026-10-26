const fs=require('fs'),path=require('path'),assert=require('node:assert/strict');
const {execFileSync}=require('child_process'),{chromium}=require('playwright');
const root=path.resolve(__dirname,'../overlay/templates/mupanic');
const html=execFileSync(process.env.PHP_BIN||'php',[path.join(__dirname,'fixtures/atlas-rewards.php')],{encoding:'utf8',maxBuffer:16e6});
(async()=>{
 const browser=await chromium.launch({executablePath:process.env.CHROMIUM_BIN||undefined,headless:true,args:['--no-sandbox','--disable-dev-shm-usage','--use-gl=angle','--use-angle=swiftshader','--enable-unsafe-swiftshader']});
 for(const [width,height] of [[1366,768],[390,844]]){
  const page=await browser.newPage({viewport:{width,height},reducedMotion:'reduce'}),errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',route=>{const n=new URL(route.request().url()).pathname;if(n==='/info/')return route.fulfill({contentType:'text/html',body:html});if(n.startsWith('/templates/mupanic/')){const p=path.join(root,n.slice('/templates/mupanic/'.length));if(fs.existsSync(p))return route.fulfill({path:p});}errors.push('Missing '+n);return route.abort();});
  await page.goto('https://atlas.test/info/#mapa-7');
  const wiki=JSON.parse(await page.locator('#wiki-data').textContent());
  assert.equal(await page.locator('.atlas-pin--super').count(),29);
  const map=page.locator('#mapa-7');await map.locator('.atlas-spot-picker a').filter({hasText:'114 / 90'}).click();
  const panel=map.locator('[data-atlas-panel]:visible');await panel.locator('[data-atlas-mob-select]').filter({hasText:'Vepar elite'}).click();
  const elite=panel.locator('[data-atlas-mob]:visible');assert.ok((await elite.textContent()).includes('ÉLITE'));assert.ok((await elite.textContent()).includes('14.000'));assert.ok((await elite.textContent()).includes('no cuentan bajas'));
  await elite.locator('img.atlas-mob-portrait').evaluate(img=>img.decode());
  assert.equal(await elite.locator('[data-atlas-rates]').count(),0);
  await map.locator('[data-atlas-account]').selectOption('1');
  await elite.locator('.atlas-loot>summary').click();
  await elite.locator('[data-atlas-rates]').first().waitFor({state:'visible'});
  assert.ok(await elite.locator('[data-atlas-rates]').evaluateAll(rs=>rs.every(r=>r.textContent===JSON.parse(r.dataset.atlasRates)[1].toLocaleString('es-AR',{maximumFractionDigits:6})+'%')));
  await elite.locator('.atlas-loot>summary').click();
  await page.waitForFunction(()=>document.querySelector('[data-atlas-mob]:not([hidden]) .atlas-loot')!==null);

  const output=process.env.ATLAS_SCREENSHOT_DIR;if(output){fs.mkdirSync(output,{recursive:true});await elite.screenshot({path:path.join(output,'atlas-elite-'+width+'.png')});}
  await page.goto('https://atlas.test/info/#drops');await page.locator('[data-drop-category=joyeria]').click();
  assert.equal(await page.locator('[data-drop-card]:visible').count(),4);
  await page.locator('[data-drop-card="-1-219"]').click();const pool=page.locator('[data-drop-item="-1-219"]');
  await pool.getByText('Ver objetos y opciones de esta selección ↗︎').first().click();const reward=page.locator('#recompensa-219');await reward.waitFor({state:'visible'});
  assert.ok((await reward.textContent()).includes('No necesitás conseguir ni abrir una caja'));
  assert.ok((await reward.textContent()).includes('1 opción'));
  await page.goto('https://atlas.test/info/#buscar');await page.locator('[data-atlas-query]').fill('Kris');await page.locator('[data-atlas-type]').selectOption('object');await page.locator('[data-atlas-map]').selectOption('0');
  assert.ok(await page.locator('.atlas-find-card:visible').count());assert.ok((await page.locator('.atlas-find-card:visible').allTextContents()).join(' ').includes('Drop directo'));
  assert.equal(await page.locator('#recompensa-70,#recompensa-71,#recompensa-72,#recompensa-100,#recompensa-101').count(),0);
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
  assert.ok(wiki.drops.filter(d=>d.id===7199).every(d=>d.name==='Jewel of Guardian'));
  assert.deepEqual(errors,[]);await page.close();console.log('PASS hunting routes and elites',width);
 }
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
