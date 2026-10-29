// Mejorar mi equipo (#mejoras) and Tipos de equipo (#equipo): calculator rates come from the synced crafting data, fixed height, no overflow.
const fs=require('fs'),path=require('path'),assert=require('node:assert/strict');
const {execFileSync}=require('child_process'),{chromium}=require('playwright');
const root=path.resolve(__dirname,'../overlay/templates/mupanic');
const html=execFileSync(process.env.PHP_BIN||'php',[path.join(__dirname,'fixtures/atlas-rewards.php')],{encoding:'utf8',maxBuffer:32e6});
const source=fs.readFileSync(path.join(root,'inc/workshop.php'),'utf8');
const mejoras=source.slice(source.indexOf('<section id="mejoras"'));
// Rates are printed from $wiki['crafting'], never typed in (100% Bless is the only fixed value).
assert.ok(!/\b(?!100)\d{1,2}%/.test(mejoras.replace(/<\?php[\s\S]*?\?>/g,'')),'Hardcoded rate in #mejoras/#equipo');
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
  await page.goto('https://atlas.test/info/#mejoras');
  const data=await page.evaluate(()=>JSON.parse(document.querySelector('#wiki-data').textContent).crafting);
  const calc=page.locator('[data-upgrade-calculator]'),h0=await calc.evaluate(el=>el.offsetHeight);
  assert.equal(await page.locator('[data-mj-step]').count(),15);
  assert.equal(await page.locator('[data-chain-rate]').textContent(),data.mixRates.PlusItemLevelMixRate1[0]+'%');
  for(const sel of ['[data-upgrade-type]','[data-upgrade-account]','[data-upgrade-from]','[data-upgrade-to]','[data-upgrade-luck]'])assert.equal(await page.locator(sel).evaluate(el=>!!el.closest('label')),true,'label for '+sel);
  // +9 → +15, VIP 1, Socket, Luck.
  await page.selectOption('[data-upgrade-account]','1');await page.selectOption('[data-upgrade-type]','PlusItemSocketLevelMixRate');
  await page.selectOption('[data-upgrade-from]','9');await page.selectOption('[data-upgrade-to]','15');await page.check('[data-upgrade-luck]');
  let chain=1;for(let n=1;n<=6;n++){const r=Math.min(100,data.mixRates['PlusItemSocketLevelMixRate'+n][1]+data.jewels.AddLuckSuccessRate2[1]);chain*=r/100;
   assert.equal(await page.locator('[data-mj-step="'+(9+n)+'"] [data-mj-step-rate]').textContent(),r+'%');
   assert.equal(await page.locator('[data-mj-step="'+(9+n)+'"]').evaluate(el=>el.classList.contains('is-on')),true);}
  assert.equal(await page.locator('[data-chain-rate]').textContent(),(chain*100).toLocaleString('es-AR',{maximumFractionDigits:4})+'%');
  assert.equal(await page.locator('[data-mj-need="bless"]').textContent(),'×21');
  assert.equal(await page.locator('[data-mj-need="soul"]').textContent(),'×21');
  assert.equal(await page.locator('[data-mj-need="chaos"]').textContent(),'×6');
  assert.equal(await calc.evaluate(el=>el.offsetHeight),h0,'calculator height changed '+width);
  // +0 → +15 lights every slot and keeps the height; an invalid target shows the dash.
  await page.selectOption('[data-upgrade-from]','0');
  assert.equal(await page.locator('[data-mj-step].is-on').count(),15);
  assert.equal(await page.locator('[data-mj-step="8"] [data-mj-step-rate]').textContent(),Math.min(100,data.jewels.SoulSuccessRate[1]+data.jewels.AddLuckSuccessRate1[1])+'%');
  assert.equal(await calc.evaluate(el=>el.offsetHeight),h0,'calculator height changed (all steps) '+width);
  await page.selectOption('[data-upgrade-from]','12');await page.selectOption('[data-upgrade-to]','11');
  assert.equal(await page.locator('[data-chain-rate]').textContent(),'—');
  assert.equal(await page.locator('[data-mj-step].is-on').count(),0);
  assert.equal(await calc.evaluate(el=>el.offsetHeight),h0,'calculator height changed (invalid) '+width);
  assert.equal(await overflow(),false,'mejoras overflow '+width);
  await page.goto('https://atlas.test/info/#equipo');
  assert.equal(await page.locator('#equipo').isVisible(),true);
  assert.equal(await page.locator('#equipo .atl-letter').count(),0,'letter badges gone');
  assert.equal(await page.locator('#equipo-harmony .eq-rate b').first().textContent(),data.mixRates.JewelOfHarmonyItemPurityMixRate[0]+'%');
  for(const id of ['crear-pluma-condor','crear-seed','crear-seed-sphere'])assert.equal(await page.locator('#equipo a[href="#'+id+'"]').count(),1,id);
  assert.equal(await overflow(),false,'equipo overflow '+width);
  assert.deepEqual(errors,[]);await page.close();console.log('PASS mejoras/equipo',width);
 }
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1);});
