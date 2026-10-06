const fs=require('fs'),path=require('path'),assert=require('node:assert/strict');
const {execFileSync}=require('child_process'),{chromium}=require('playwright');
const root=path.resolve(__dirname,'../overlay/templates/mupanic');
const html=execFileSync(process.env.PHP_BIN||'php',[path.join(__dirname,'fixtures/atlas-rewards.php')],{encoding:'utf8',maxBuffer:16e6});
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 const output=process.env.ATLAS_SCREENSHOT_DIR;if(output)fs.mkdirSync(output,{recursive:true});
 for(const [width,height] of [[1920,1080],[1024,768],[390,844],[320,700]]){
  const page=await browser.newPage({viewport:{width,height},reducedMotion:'reduce'}),errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',route=>{
   const name=new URL(route.request().url()).pathname;
   if(name==='/info/')return route.fulfill({contentType:'text/html',body:html});
   if(name.startsWith('/templates/mupanic/')){const file=path.join(root,name.slice('/templates/mupanic/'.length));if(fs.existsSync(file))return route.fulfill({path:file});}
   errors.push('Missing asset: '+name);return route.abort();
  });
  await page.goto('https://atlas.test/info/#recompensas');await page.evaluate(()=>document.fonts.ready);
  await page.locator('[data-reward-filter]').fill('Medusa');
  const medusa=page.locator('#recompensa-106');await medusa.locator('summary').click();
  assert.equal(await medusa.locator('[data-reward-item]').count(),157);
  assert.ok(!(await medusa.textContent()).includes('requiere verificación'));
  assert.ok((await medusa.textContent()).includes('Hasta 3 selecciones'));
  assert.ok((await medusa.textContent()).includes('Ancient'));
  await medusa.locator('[data-reward-item-filter]').fill('Hyon');
  assert.equal(await medusa.locator('[data-reward-item]:visible').count(),4);
  assert.ok((await medusa.locator('[data-reward-item]:visible').allTextContents()).every(t=>t.includes('Hyon')));
  for(const options of await medusa.locator('[data-reward-item]:visible td:last-child').allTextContents()){
   const labels=options.split(' · ');assert.equal(new Set(labels).size,labels.length,'Repeated option labels');
  }
  if(output&&[1920,390].includes(width)){await medusa.scrollIntoViewIfNeeded();await page.screenshot({path:path.join(output,`medusa-${width}.png`)});}
  await medusa.locator('[data-reward-item-filter]').fill('Harmony');assert.equal(await medusa.locator('[data-reward-item]:visible').count(),0,'Old Medusa loot must not appear');
  await page.locator('[data-reward-filter]').fill('Nightmare');const nightmare=page.locator('#recompensa-48');await nightmare.locator('summary').click();
  assert.deepEqual(await nightmare.locator('[data-reward-item] td:first-child').allTextContents(),['Jewel of Harmony','Higher refining stone','Lower refining stone']);
  await page.locator('[data-reward-filter]').fill('Selupan');const selupan=page.locator('#recompensa-67');await selupan.locator('summary').click();assert.ok((await selupan.textContent()).includes('Socket'));
  await page.locator('[data-reward-filter]').fill('Chaos mix');assert.ok((await page.locator('#recompensa-1014').textContent()).includes('requiere verificación'));
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Horizontal overflow');assert.deepEqual(errors,[]);
  await page.close();console.log('PASS Atlas rewards',width);
 }
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
