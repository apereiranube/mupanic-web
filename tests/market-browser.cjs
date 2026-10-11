const fs=require('fs'),path=require('path'),assert=require('node:assert/strict');
const {execFileSync}=require('node:child_process'),{chromium}=require('playwright');
const root=path.resolve(__dirname,'../overlay/templates/mupanic');
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 const dir=process.env.MARKET_SCREENSHOT_DIR;if(dir)fs.mkdirSync(dir,{recursive:true});
 try{for(const [width,height] of [[1920,1080],[1366,900],[1024,768],[901,768],[390,844]]){
  const html=execFileSync('php',[path.join(__dirname,'fixtures/market.php')],{encoding:'utf8'});
  let apiCalls=0;const snapshot=JSON.parse(html.match(/id="vessara-initial">([\s\S]*?)<\/script>/)[1]);const page=await browser.newPage({viewport:{width,height},reducedMotion:width===1366?'no-preference':'reduce'}),errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',async route=>{
   const url=new URL(route.request().url());
   if(url.searchParams.has('market_data')){apiCalls++;const next=structuredClone(snapshot);next.metrics.zen=(BigInt(next.metrics.zen)+1n).toString();next.metrics.today='13';if(apiCalls>=3){next.sales.unshift({...next.sales[0],id:'3',buyer:'JugadorNuevo',price:150,soldAt:'2026-10-10T20:03:35-03:00'});next.metrics.eryns='25550';}return route.fulfill({json:next});}
   if(url.pathname==='/market/')return route.fulfill({contentType:'text/html',body:html});
   if(url.pathname.startsWith('/templates/mupanic/')){const file=path.join(root,url.pathname.slice('/templates/mupanic/'.length));if(fs.existsSync(file))return route.fulfill({path:file});}
   return route.abort();
  });
  await page.goto('https://market.test/market/');await page.evaluate(()=>document.fonts.ready);await page.waitForFunction(()=>[...document.querySelectorAll('.vessara-stats strong[data-market-metric]')].every(e=>e.scrollWidth<=e.clientWidth));
  assert.equal(await page.locator('h1').count(),1);
  assert.equal(await page.locator('[data-market-metric=zen]').textContent(),'9.007.199.254.740.993');
  assert.equal(await page.locator('[data-market-rows] tr').count(),2);
  assert.equal(await page.locator('.vessara-day').count(),7);assert.equal(await page.locator('.vessara-feed-entry').count(),2);assert.equal(await page.locator('[data-market-notification]').isVisible(),false,'initial historical sales do not announce new trades');assert.equal(await page.locator('[data-market-ticker]').innerText().then(t=>t.includes('Juanita')),true);await page.locator('[data-market-pause]').click();assert.equal(await page.locator('.vessara-ticker').evaluate(el=>el.classList.contains('is-paused')),true);await page.locator('[data-market-pause]').click();assert.equal(await page.locator('[data-market-spark=zen] polyline').count(),1);
  const layout=await page.evaluate(()=>({overflow:document.documentElement.scrollWidth>innerWidth,title:document.querySelector('h1').getBoundingClientRect().top,header:document.querySelector('.site-header').getBoundingClientRect().bottom,navRight:document.querySelector('.main-nav').getBoundingClientRect().right,accountLeft:document.querySelector('.nav-actions').getBoundingClientRect().left}));
  assert.equal(await page.locator('.vessara-stats strong[data-market-metric]').evaluateAll(els=>els.every(e=>e.scrollWidth<=e.clientWidth)),true,'metric fits its card');assert.equal(layout.overflow,false,'viewport overflow at '+width);assert.ok(layout.title>=layout.header,'heading overlaps header');
  if(width>900)assert.ok(layout.navRight<=layout.accountLeft,'navigation overlaps account actions at '+width);
  await page.locator('[data-market-currency="1"]').click();assert.equal(await page.locator('[data-market-rows] tr').count(),1);
  await page.locator('[data-market-currency="all"]').click();await page.locator('[data-market-search]').fill('hurricane');assert.equal(await page.locator('[data-market-rows] tr').count(),1);
  await page.locator('[data-market-search]').fill('zzzzz');assert.equal(await page.locator('[data-market-rows] tr').count(),0);assert.equal(await page.locator('[data-market-empty]').isVisible(),true);
  await page.locator('[data-market-search]').fill('');await page.locator('summary').click();assert.equal(await page.locator('details').getAttribute('open'),'');
  await page.evaluate(()=>document.dispatchEvent(new Event('visibilitychange')));await page.waitForFunction(()=>document.querySelector('[data-market-metric=zen]').dataset.metricValue==='9007199254740994');if(width===1366)assert.equal(await page.locator('.vessara-stat-zen').evaluate(el=>el.classList.contains('is-updating')),true);await page.waitForTimeout(950);assert.equal(await page.locator('[data-market-metric=zen]').textContent(),'9.007.199.254.740.994');await page.evaluate(()=>document.dispatchEvent(new Event('visibilitychange')));await page.waitForTimeout(100);assert.equal(await page.locator('.is-updating').count(),0,'unchanged values do not animate');assert.equal(apiCalls,2);assert.equal(await page.locator('[data-market-notification]').isVisible(),false,'unchanged sales stay quiet');await page.locator('[data-market-search]').fill('hurricane');await page.evaluate(()=>document.dispatchEvent(new Event('visibilitychange')));await page.waitForFunction(()=>!document.querySelector('[data-market-notification]').hidden);assert.equal(await page.locator('[data-market-search]').inputValue(),'hurricane','live update preserves search');assert.equal(await page.locator('[data-market-rows] tr').count(),1);assert.equal(await page.locator('.vessara-feed-entry').count(),3);assert.equal(await page.locator('.vessara-feed-entry.is-new').count(),1);await page.locator('[data-market-search]').fill('');assert.equal(await page.locator('[data-market-rows] tr.is-new').count(),1);await page.waitForTimeout(900);assert.deepEqual(errors,[]);
  await page.evaluate(()=>scrollTo(0,0));if(dir)await page.screenshot({path:path.join(dir,'vessara-'+width+'.png'),fullPage:true});
  await page.close();console.log('PASS market UI '+width);
 }}finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
