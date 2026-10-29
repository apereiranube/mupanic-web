// Taller (#taller): recipe cards, one recipe at a time, rates from the synced mixRates, stable height on level changes.
const fs=require('fs'),path=require('path'),assert=require('node:assert/strict');
const {execFileSync}=require('child_process'),{chromium}=require('playwright');
const root=path.resolve(__dirname,'../overlay/templates/mupanic');
const html=execFileSync(process.env.PHP_BIN||'php',[path.join(__dirname,'fixtures/atlas-rewards.php')],{encoding:'utf8',maxBuffer:32e6});
const recipes=JSON.parse(fs.readFileSync(path.join(root,'inc/crafting-recipes.json'),'utf8'));
const balance=JSON.parse(fs.readFileSync(path.join(root,'inc/public-balance.json'),'utf8'));
// Data contract: every rate is a mixRates key (never a hardcoded %), every image exists.
for(const r of recipes){
 const keys=[r.rateKey,...r.levels.map(l=>l.rateKey)].filter(Boolean);
 for(const k of keys)assert.ok(balance.crafting.mixRates[k],'Fallback snapshot lacks '+k+' for '+r.id);
 if(!keys.length)assert.ok(['always','stack','none'].includes(r.rateMode),r.id+' needs a rateKey');
 for(const l of r.levels)assert.equal(l.materials.length,r.materials.length,r.id+' level materials');
 for(const img of [r.art,...r.materials.map(m=>m.image)].filter(Boolean))assert.ok(fs.existsSync(path.join(root,img)),'Missing '+img);
 assert.ok(!/\d+\s*%/.test(JSON.stringify(r.steps)+r.note+r.summary),'Hardcoded rate in '+r.id);
}
for(const id of ['devil-square','blood-castle','illusion-temple','kalima','doppelganger','imperial-gaion','imperial-secromicon'])assert.ok(recipes.some(r=>r.id===id&&r.group==='Entradas'),'Missing entry recipe '+id);
const rateText=(key,acc)=>{const v=balance.crafting.mixRates[key][acc];return v===-1?'Variable':v+'%';};
(async()=>{
 const browser=await chromium.launch({executablePath:process.env.CHROMIUM_BIN||undefined,headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
 for(const [width,height] of [[1440,900],[1024,768],[390,844]]){
  const page=await browser.newPage({viewport:{width,height},reducedMotion:'reduce'}),errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',route=>{const name=new URL(route.request().url()).pathname;
   if(name==='/info/')return route.fulfill({contentType:'text/html',body:html});
   if(name.startsWith('/templates/mupanic/')){const file=path.join(root,name.slice(19));if(fs.existsSync(file))return route.fulfill({path:file});}
   return route.abort();});
  const overflow=()=>page.evaluate(()=>document.documentElement.scrollWidth>innerWidth);
  await page.goto('https://atlas.test/info/#taller');
  assert.equal(await page.locator('[data-recipe-nav]:visible').count(),recipes.length);
  assert.equal(await page.locator('[data-recipe-id]:visible').count(),0);
  assert.equal(await overflow(),false,'grid overflow '+width);
  await page.locator('[data-recipe-group="Entradas"]').click();
  assert.equal(await page.locator('[data-recipe-group="Entradas"]').getAttribute('aria-pressed'),'true');
  assert.equal(await page.locator('[data-recipe-nav]:visible').count(),recipes.filter(r=>r.group==='Entradas').length);
  await page.locator('[data-recipe-group="Todas"]').click();
  await page.locator('[data-recipe-search]').fill('fenrir');
  assert.ok(await page.locator('[data-recipe-nav]:visible').count()>=4);
  await page.locator('[data-recipe-search]').fill('');
  await page.locator('[data-recipe-account]').selectOption('1');
  for(const id of ['arma-chaos','fruta','blood-castle','kalima']){
   const r=recipes.find(x=>x.id===id);
   await page.locator('[data-recipe-nav="'+id+'"]').click();await page.waitForFunction(h=>location.hash===h,'#crear-'+id);
   const article=page.locator('#crear-'+id);
   assert.equal(await page.locator('[data-recipe-id]:visible').count(),1);
   const key=r.levels.length?r.levels[0].rateKey:r.rateKey;
   assert.equal(await article.locator('[data-recipe-rate]').textContent(),key?rateText(key,1):'Se arma sola');
   assert.equal(await overflow(),false,'recipe overflow '+id+' '+width);
   if(r.levels.length>1){
    const h0=await article.evaluate(el=>el.offsetHeight),last=r.levels.length-1;
    await article.locator('[data-tl-level="'+last+'"]').click();
    assert.equal(await article.locator('[data-tl-level="'+last+'"]').getAttribute('aria-pressed'),'true');
    assert.equal(await article.locator('[data-tl-result]').textContent(),r.levels[last].result);
    if(r.levels[last].rateKey)assert.equal(await article.locator('[data-recipe-rate]').textContent(),rateText(r.levels[last].rateKey,1));
    assert.equal(await article.evaluate(el=>el.offsetHeight),h0,'height changed on level '+id+' '+width);
   }
   await article.locator('.tl-back').click();await page.waitForFunction(()=>location.hash==='#taller');
  }
  // Deep link straight into a recipe, plus the next chapters still render.
  await page.goto('https://atlas.test/info/#crear-illusion-temple');
  assert.equal(await page.locator('#crear-illusion-temple').isVisible(),true);
  await page.goto('https://atlas.test/info/#mejoras');assert.equal(await page.locator('#mejoras').isVisible(),true);
  assert.deepEqual(errors,[]);await page.close();console.log('PASS taller',width);
 }
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1);});
