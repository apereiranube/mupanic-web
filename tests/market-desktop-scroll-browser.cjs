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
     return route.fulfill({json:{schema:1,available:true,page:1,pages:1,pageSize:8,total:'8',
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
   // The compact statistics can place the showroom near the initial viewport.
   const initiallyNear=await page.locator('.vessara-listings').evaluate(x=>x.getBoundingClientRect().top<=innerHeight*1.3);
   await page.waitForFunction(paused=>document.querySelector('.vessara-atmosphere').dataset.running===(paused?'false':'true'),initiallyNear);
   assert.equal(await page.locator('.vessara-page').evaluate(x=>x.classList.contains('is-browsing-market')),initiallyNear,'initial ambience follows proximity to the showroom');
   // The real trade ticker never freezes on hover/focus or when visual
   // decoration is disabled. Explicit manual pause remains accessible.
   const ticker=page.locator('[data-market-ticker]');
   await page.waitForFunction(()=>document.querySelector('[data-market-ticker]').getAnimations().length>0);
   await page.locator('.vessara-ticker').hover();
   assert.equal(await ticker.evaluate(x=>getComputedStyle(x).animationPlayState),'running',
      'hovering over latest trades no longer stops the ticker');
   await page.locator('[data-market-pause]').focus();
   assert.equal(await ticker.evaluate(x=>getComputedStyle(x).animationPlayState),'running',
      'focus within latest trades does not pause it');
   await page.locator('[data-market-pause]').click();
   assert.equal(await ticker.evaluate(x=>getComputedStyle(x).animationPlayState),'paused',
      'an intentional pause button still works');
   await page.locator('[data-market-pause]').click();
   assert.equal(await ticker.evaluate(x=>getComputedStyle(x).animationPlayState),'running',
      'explicit resume immediately runs the tape');
   await page.locator('[data-market-effects]').click();
   assert.equal(await ticker.evaluate(x=>getComputedStyle(x).animationPlayState),'running',
      'pausing expensive decorative effects never pauses actual trades');
   await page.locator('[data-market-effects]').click();
   // The global theme uses smooth scrolling; move instantly in this test so
   // observed jank or observer changes cannot be caused by the test itself.
   await page.evaluate(()=>{
    document.documentElement.style.scrollBehavior='auto';
    const listings=document.querySelector('.vessara-listings');
    window.scrollTo(0,listings.getBoundingClientRect().top+scrollY-innerHeight*.90);
   });
   await page.waitForFunction(()=>document.querySelector('.vessara-page').classList.contains('is-browsing-market'));
   await page.waitForFunction(()=>document.querySelector('.vessara-atmosphere').dataset.running==='false');
   const reading=await page.evaluate(()=>({
    canvasHidden:getComputedStyle(document.querySelector('.vessara-atmosphere')).visibility==='hidden',
    atmosphereStopped:document.querySelector('.vessara-atmosphere').dataset.running==='false',
    auroraPaused:getComputedStyle(document.querySelector('.vessara-aurora i')).animationPlayState==='paused',
    coinSparkPaused:getComputedStyle(document.querySelector('.vessara-spark polyline')).animationPlayState==='paused',
    listingTop:document.querySelector('.vessara-listings').getBoundingClientRect().top
   }));
   assert.equal(reading.canvasHidden,true,'full-screen background canvas becomes hidden before market arrives');
   assert.equal(reading.atmosphereStopped,true,'atmosphere painting stops when browsing the marketplace');
   assert.equal(reading.auroraPaused,true,'offscreen large aurora effects pause');
   assert.equal(reading.coinSparkPaused,true,'offscreen coin sparklines pause');
   assert.equal(await ticker.evaluate(x=>getComputedStyle(x).animationPlayState),'running',
      'latest trades still move when the desktop market optimization is active');
   const firstTime=await ticker.evaluate(x=>x.getAnimations()[0]?.currentTime??0);
   await page.waitForTimeout(360);
   const secondTime=await ticker.evaluate(x=>x.getAnimations()[0]?.currentTime??0);
   assert.ok(secondTime>firstTime+220,
      'ticker animation progresses continuously instead of freezing near the market');
   await page.evaluate(()=>document.querySelector('.vessara-listings').scrollIntoView({block:'start',behavior:'instant'}));
   await page.waitForFunction(()=>!document.querySelector('.vessara-promoted').classList.contains('is-offscreen'));
   assert.equal(await page.locator('.vessara-marquee-motion').evaluate(x=>getComputedStyle(x).animationPlayState),'running',
    'real featured carousel keeps moving while the decorative header is suspended');
   // Reproduce async listing height expansion. Only a change in WIDTH should
   // cause metric resizing, not insertion lower on the page.
   await page.evaluate(()=>{
    const metric=document.querySelector('strong[data-market-metric="today"]');
    window.__marketMetricMutations=0;
    const observer=new MutationObserver(m=>window.__marketMetricMutations+=m.length);
    observer.observe(metric,{attributes:true,attributeFilter:['style']});
    const block=document.createElement('div');block.style.height='280px';
    block.dataset.scrollProbe='true';
    document.querySelector('.vessara-listings-board').append(block);
   });
   await page.waitForTimeout(220);
   assert.equal(await page.evaluate(()=>window.__marketMetricMutations),0,
    'adding height in listings does not force re-fitting above-the-fold metric cards');
   await page.evaluate(()=>{
    document.querySelector('[data-scroll-probe]')?.remove();
    window.scrollTo(0,0);
   });
   await page.waitForFunction(paused=>document.querySelector('.vessara-page').classList.contains('is-browsing-market')===paused,initiallyNear);
   await page.waitForFunction(paused=>document.querySelector('.vessara-atmosphere').dataset.running===(paused?'false':'true'),initiallyNear);
   assert.equal(await page.locator('.vessara-aurora i').first().evaluate(x=>getComputedStyle(x).animationPlayState),initiallyNear?'paused':'running',
    'returning to the top restores the initial ambience state');
   assert.deepEqual(errors,[],'no JavaScript errors');
   await page.close();
   console.log('PASS Vessara desktop scrolling suspends offscreen effects '+width);
  }
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
