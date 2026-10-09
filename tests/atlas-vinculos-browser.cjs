const fs=require('fs'),path=require('path'),assert=require('node:assert/strict');
const {execFileSync}=require('child_process'),{chromium}=require('playwright');
const root=path.resolve(__dirname,'../overlay/templates/mupanic');
const html=execFileSync(process.env.PHP_BIN||'php',[path.join(__dirname,'fixtures/atlas-rewards.php')],{encoding:'utf8',maxBuffer:16e6});
const catalog=JSON.parse(fs.readFileSync(path.join(root,'inc/atlas-vinculos.json')));
const {build,filter}=require('../overlay/templates/mupanic/js/atlas-search.js');
const data=JSON.parse(html.match(/<script type="application\/json" id="wiki-data">([\s\S]*?)<\/script>/)[1]);
const index=build(data);
for(const entry of catalog.entries.concat(catalog.materials)){
 const found=filter(index,{query:entry.name,type:'',map:'',level:''});
 assert.equal(found.filter(x=>x.id===entry.id).length,1);
 assert.ok(found.find(x=>x.id===entry.id).routes.some(x=>x.hash==='#vinculo-'+entry.slug));
 if(entry.kind==='exclusiva'||entry.kind==='montura')assert.equal(found.find(x=>x.id===entry.id).routes.length,1,'Exclusive companion cannot claim drops');
 if(entry.image)assert.ok(fs.existsSync(path.join(root,entry.image)));
}
assert.equal(filter(index,{query:'',type:'mount',map:'',level:''}).length,3);
assert.equal(filter(index,{query:'',type:'pet',map:'',level:''}).length,11);
for(const entry of catalog.entries.filter(x=>x.stage))assert.ok(entry.story.length===2 && entry.story.join(' ').length>500 && entry.role.length>100,'Complete PDF story: '+entry.name);
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath:process.env.CHROMIUM_PATH||undefined,args:['--no-sandbox']});
 for(const [width,height] of [[1920,1080],[1440,1000],[390,844],[320,700]]){
  const page=await browser.newPage({viewport:{width,height},reducedMotion:'reduce'}),errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',route=>{
   const name=new URL(route.request().url()).pathname;
   if(name==='/info/')return route.fulfill({contentType:'text/html',body:html});
   if(name.startsWith('/templates/mupanic/')){const file=path.join(root,name.slice('/templates/mupanic/'.length));if(fs.existsSync(file))return route.fulfill({path:file});}
   errors.push('Missing asset: '+name);return route.abort();
  });
  await page.goto('https://atlas.test/info/#vinculos');await page.evaluate(()=>document.fonts.ready);
  assert.equal(await page.locator('#vinculos').isVisible(),true);
  assert.equal(await page.locator('#inicio').isVisible(),false);
  assert.equal(await page.locator('[data-vinculo]').count(),14);
  await page.evaluate(async()=>Promise.all(Array.from(document.querySelectorAll('#vinculos img')).map(image=>new Promise((resolve,reject)=>{const probe=new Image();probe.onload=resolve;probe.onerror=()=>reject(new Error('Invalid image: '+image.src));probe.src=image.src;}))));
  await page.locator('#vinculos img').evaluateAll(async images=>{images.forEach(image=>image.loading='eager');await Promise.all(images.map(image=>image.decode()));});
  for(const slug of ['nerathys','luck','assembly'])assert.ok(await page.locator(`#vinculo-${slug} img`).evaluate(im=>im.naturalWidth>=1024&&im.naturalHeight>=1024),'High-resolution asset: '+slug);
  const clipped=await page.locator('.vinculo-portrait').evaluateAll(portraits=>portraits.filter(portrait=>{const box=portrait.getBoundingClientRect(),image=portrait.querySelector('img').getBoundingClientRect();return image.top<box.top||image.bottom>box.bottom||image.left<box.left||image.right>box.right;}).map(portrait=>portrait.closest('[data-vinculo]').id));
  assert.deepEqual(clipped,[],'Companion images fit fully inside their portrait frames');
  const heights=await page.locator('[data-vinculo]>summary').evaluateAll(cards=>cards.map(card=>Math.round(card.getBoundingClientRect().height)));
  assert.ok(Math.max(...heights)-Math.min(...heights)<=1,'All collapsed companion cards use the same height: '+JSON.stringify(heights));
  await page.locator('#vinculo-aelira').scrollIntoViewIfNeeded();
  if(process.env.ATLAS_SCREENSHOT_DIR){fs.mkdirSync(process.env.ATLAS_SCREENSHOT_DIR,{recursive:true});await page.screenshot({path:path.join(process.env.ATLAS_SCREENSHOT_DIR,`mascotas-${width}.png`)});}
  if(process.env.ATLAS_SCREENSHOT_DIR){await page.locator('#vinculo-nerathys').scrollIntoViewIfNeeded();await page.screenshot({path:path.join(process.env.ATLAS_SCREENSHOT_DIR,`nerathys-${width}.png`)});}
  await page.locator('.vinculos-chapters a[href="#vinculo-materiales"]').click();
  assert.equal(await page.locator('#vinculo-materiales').isVisible(),true);
  for(const selector of ['#vinculo-fragmentos img','#vinculo-nucleos img','#vinculo-luck img','#vinculo-assembly img'])assert.equal(await page.locator(selector).evaluate(im=>im.complete&&im.naturalWidth>0),true);
  if(width>=540){const sizes=await page.locator('.vinculo-material').evaluateAll(cards=>cards.map(card=>Math.round(card.getBoundingClientRect().height)));assert.ok(Math.max(...sizes)-Math.min(...sizes)<=1,'Material cards share one height');}
  if(process.env.ATLAS_SCREENSHOT_DIR){fs.mkdirSync(process.env.ATLAS_SCREENSHOT_DIR,{recursive:true});await page.screenshot({path:path.join(process.env.ATLAS_SCREENSHOT_DIR,`materiales-${width}.png`)});}
  if(process.env.ATLAS_SCREENSHOT_DIR){await page.locator('#vinculo-luck').scrollIntoViewIfNeeded();await page.screenshot({path:path.join(process.env.ATLAS_SCREENSHOT_DIR,`talismanes-${width}.png`)});}
  if(data.drops.some(rule=>rule.id===7369)){
   await page.locator('#vinculo-nucleos .vinculo-drop-rules summary').click();
   const link=page.locator('#vinculo-nucleos .vinculo-drop-rules a').first();
   const hash=await link.getAttribute('href');await link.click();await page.locator(hash).waitFor({state:'visible'});
  }
  await page.evaluate(()=>location.hash='vinculo-aelira');
  await page.waitForFunction(()=>document.querySelector('#vinculo-aelira').open);
  assert.ok((await page.locator('#vinculo-aelira').textContent()).includes('Custodia los comienzos.'));
  await page.evaluate(()=>location.hash='buscar');await page.locator('[data-atlas-query]').fill('Vaelkar');
  const result=page.locator('[data-atlas-find-results]');await page.waitForFunction(()=>document.querySelector('[data-atlas-find-results]').textContent.includes('Vaelkar'));
  assert.equal(await result.locator('img').count(),1);
  await result.locator('a[href="#vinculo-vaelkar"]').click();await page.waitForFunction(()=>document.querySelector('#vinculo-vaelkar').open);
  assert.equal(await page.locator('#vinculos').isVisible(),true);
  await page.evaluate(()=>location.hash='vinculos');
  await page.locator('.vinculos-hero').scrollIntoViewIfNeeded();
  const output=process.env.ATLAS_SCREENSHOT_DIR;
  if(output){fs.mkdirSync(output,{recursive:true});await page.screenshot({path:path.join(output,`vinculos-${width}.png`)});}
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Horizontal overflow');
  assert.deepEqual(errors,[]);await page.close();console.log('PASS Vínculos',width);
 }
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
