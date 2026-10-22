const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const {execFileSync}=require('node:child_process'),{chromium}=require('playwright');
const template=path.resolve(__dirname,'../overlay/templates/mupanic');
const html=execFileSync('php',[path.join(__dirname,'fixtures/market.php')],{encoding:'utf8'});
const summary=JSON.parse(html.match(/id="vessara-initial">([\s\S]*?)<\/script>/)[1]);
const expiry='2099-01-01T12:00:00-03:00';
const offer=(id,featured)=>({
 id:String(id),seller:'Jugador'+id,item:'Chromatic Staff',itemIndex:2593,level:15,
 currency:id%2,price:'2000',expiresAt:expiry,featured,featuredUntil:featured?expiry:null,
 details:{skill:true,luck:true,additional:28,durability:255,excellent:['Daño +2%'],excellentCount:1,
 ancient:false,harmony:false,option380:false,sockets:[],lot:1}
});
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 try{
  for(const width of [1366,1920]){
   const page=await browser.newPage({viewport:{width,height:800},reducedMotion:'no-preference'});
   const errors=[];
   page.on('pageerror',e=>errors.push(e.message));
   await page.route('**/*',async route=>{
    const url=new URL(route.request().url());
    if(url.searchParams.has('market_listings')){
     return route.fulfill({json:{schema:1,available:true,page:1,pages:1,pageSize:10,total:'8',
      items:Array.from({length:8},(_,i)=>offer(i+20,false)),
      featured:Array.from({length:10},(_,i)=>offer(i+1,true)),
      itemOptions:true,updated:1760000000}});
    }
    if(url.searchParams.has('market_data'))return route.fulfill({json:summary});
    if(url.pathname==='/market/')return route.fulfill({contentType:'text/html',body:html});
    if(url.pathname.startsWith('/templates/mupanic/')){
     const name=path.join(template,url.pathname.slice('/templates/mupanic/'.length));
     if(fs.existsSync(name))return route.fulfill({path:name});
    }
    return route.abort();
   });
   await page.goto('https://market.test/market/');
   await page.addScriptTag({path:path.join(template,'js/market-listings.js')});
   await page.waitForFunction(()=>document.querySelectorAll('[data-listings-grid] .vessara-offer').length===8);
   // Bolsa de Vessara: no full-screen canvas; each scene animates only while it is on screen.
   assert.equal(await page.locator('canvas.vessara-atmosphere,[data-market-atmosphere]').count(),0,'no full-screen particle canvas');
   await page.waitForFunction(()=>document.querySelector('.vs-sc-1').classList.contains('is-live'));
   // The real trade ticker never freezes on hover or focus. Explicit manual pause remains accessible.
   const ticker=page.locator('[data-market-ticker]');
   await page.waitForFunction(()=>document.querySelector('[data-market-ticker]').getAnimations().length>0);
   await page.locator('.vessara-ticker').hover();
   assert.equal(await ticker.evaluate(x=>getComputedStyle(x).animationPlayState),'running','hovering over latest trades does not stop the ticker');
   await page.locator('[data-market-pause]').focus();
   assert.equal(await ticker.evaluate(x=>getComputedStyle(x).animationPlayState),'running','focus within latest trades does not pause it');
   await page.locator('[data-market-pause]').click();
   assert.equal(await ticker.evaluate(x=>getComputedStyle(x).animationPlayState),'paused','an intentional pause button still works');
   await page.locator('[data-market-pause]').click();
   assert.equal(await ticker.evaluate(x=>getComputedStyle(x).animationPlayState),'running','explicit resume immediately runs the tape');
   await page.evaluate(()=>{document.documentElement.style.scrollBehavior='auto';document.querySelector('[data-listings-grid]').scrollIntoView({block:'center',behavior:'instant'});});
   await page.waitForFunction(()=>!document.querySelector('.vs-sc-1').classList.contains('is-live'));
   assert.equal(await page.locator('.vs-sc-1 .vs-stick img').evaluate(x=>getComputedStyle(x).animationPlayState),'paused','offscreen cover drift pauses');
   assert.equal(await page.locator('.vs-sc-4 .vs-board-chg.is-up,.vs-sc-2 .vs-bulbs').first().evaluate(x=>getComputedStyle(x).animationPlayState),'running','the live market keeps animating on screen');
   const firstTime=await ticker.evaluate(x=>x.getAnimations()[0]?.currentTime??0);
   await page.waitForTimeout(360);
   const secondTime=await ticker.evaluate(x=>x.getAnimations()[0]?.currentTime??0);
   assert.ok(secondTime>firstTime+220,'ticker animation progresses continuously');
   await page.evaluate(()=>document.querySelector('.vessara-promoted').scrollIntoView({block:'center',behavior:'instant'}));
   await page.waitForFunction(()=>!document.querySelector('.vessara-promoted').classList.contains('is-offscreen'));
   assert.equal(await page.locator('.vessara-marquee-motion').evaluate(x=>getComputedStyle(x).animationPlayState),'running','featured carousel moves while on screen');
   // Only a change in WIDTH should re-fit the metric cards, not content inserted lower on the page.
   await page.evaluate(()=>{
    const metric=document.querySelector('strong[data-market-metric="today"]');
    window.__marketMetricMutations=0;
    new MutationObserver(m=>window.__marketMetricMutations+=m.length).observe(metric,{attributes:true,attributeFilter:['style']});
    const block=document.createElement('div');block.style.height='280px';block.dataset.scrollProbe='true';
    document.querySelector('.vessara-listings-board').append(block);
   });
   await page.waitForTimeout(220);
   assert.equal(await page.evaluate(()=>window.__marketMetricMutations),0,'adding height in listings does not re-fit the metric cards');
   await page.evaluate(()=>{document.querySelector('[data-scroll-probe]')?.remove();window.scrollTo(0,0);});
   await page.waitForFunction(()=>document.querySelector('.vs-sc-1').classList.contains('is-live'));
   assert.equal(await page.locator('.vs-sc-1 .vs-stick img').evaluate(x=>getComputedStyle(x).animationPlayState),'running','returning to the top resumes the cover');
   assert.deepEqual(errors,[],'no JavaScript errors');
   await page.close();
   console.log('PASS Vessara desktop scrolling pauses offscreen scenes '+width);
  }
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
