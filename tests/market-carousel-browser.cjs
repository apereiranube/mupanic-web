const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const {execFileSync}=require('node:child_process'),{chromium}=require('playwright');
const template=path.resolve(__dirname,'../overlay/templates/mupanic');
const html=execFileSync('php',[path.join(__dirname,'fixtures/market.php')],{encoding:'utf8'});
const summary=JSON.parse(html.match(/id="vessara-initial">([\s\S]*?)<\/script>/)[1]);
const featuredUntil='2099-01-01T12:00:00-03:00',expiresAt='2099-01-07T12:00:00-03:00';
const all=Array.from({length:13},(_,i)=>({
 id:String(i+1),seller:'Jugador'+i,item:'Espada '+(i+1),itemIndex:500+i,level:15,
 currency:i%2,price:String(1000+i),expiresAt,
 featuredUntil:i<10?featuredUntil:null,featured:i<10,
 details:{skill:true,luck:true,additional:16,durability:255,excellent:['Daño +2%'],excellentCount:1,ancient:false,harmony:false,option380:false,sockets:[],lot:1}
}));
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 try{
  for(const width of [1366,390]){
   const page=await browser.newPage({viewport:{width,height:900},reducedMotion:width===390?'reduce':'no-preference'});
   const errors=[];let apiCount=0;
   page.on('pageerror',e=>errors.push(e.message));
   await page.route('**/*',async route=>{
    const u=new URL(route.request().url());
    if(u.searchParams.has('market_listings')){
     apiCount++;
     const currency=u.searchParams.get('currency'),search=u.searchParams.get('search')||'',sort=u.searchParams.get('sort');
     let items=all.filter(i=>(currency==='all'||String(i.currency)===currency)
        &&(u.searchParams.get('featured')!=='1'||i.featured)
        &&i.item.toLowerCase().includes(search.toLowerCase()));
     if(sort==='price-desc')items.reverse();
     const count=items.length,pages=Math.max(1,Math.ceil(count/8));
     const requested=+(u.searchParams.get('listing_page')||'1'),currentPage=Math.min(Math.max(1,requested),pages);
     return route.fulfill({json:{schema:1,available:true,updated:Date.now(),page:currentPage,pages,pageSize:8,
       total:String(count),items:items.slice((currentPage-1)*8,currentPage*8),featured:all.slice(0,10),itemOptions:true}});
    }
    if(u.searchParams.has('market_data'))return route.fulfill({json:summary});
    if(u.pathname==='/market/')return route.fulfill({contentType:'text/html',body:html});
    if(u.pathname.startsWith('/templates/mupanic/')){
     const pathname=path.join(template,u.pathname.slice('/templates/mupanic/'.length));
     if(fs.existsSync(pathname))return route.fulfill({path:pathname});
    }
    return route.abort();
   });
   await page.goto('https://market.test/market/');
   await page.addScriptTag({path:path.join(template,'js/market-listings.js')});
   await page.waitForFunction(()=>document.querySelectorAll('[data-listings-grid] .vessara-offer').length===8);
   assert.equal(await page.locator('[data-listings-featured] .vessara-offer').count(),10,'ten featured items exist without extra vertical rows');
   assert.equal(await page.locator('[data-listings-count]').innerText(),'13 publicaciones','real total remains intact');
   const bounds=await page.locator('[data-listings-featured-wrap]').boundingBox();
   assert.ok(bounds&&bounds.height<385,'featured row remains compact even with ten items');
   const track=page.locator('[data-listings-featured]');
   const overflow=await track.evaluate(el=>el.scrollWidth>el.clientWidth+15);
   assert.equal(overflow,true,'featured items scroll horizontally on '+width);
   assert.equal(await page.locator('[data-featured-controls]').isVisible(),true,'featured controls available');
   // The previous V2 rebuilt all cards on every 30-second heartbeat, interrupting tooltips.
   await page.locator('[data-listings-grid] .vessara-offer').first().evaluate(e=>e.dataset.kept='yes');
   await page.evaluate(()=>document.dispatchEvent(new Event('visibilitychange')));
   for(let i=0;i<20 && apiCount<2;i++)await page.waitForTimeout(100);
   assert.ok(apiCount>=2,'unchanged poll happened');
   assert.equal(await page.locator('[data-listings-grid] .vessara-offer').first().getAttribute('data-kept'),'yes','identical polls preserve DOM cards');
   await page.locator('[data-listings-featured-wrap]').scrollIntoViewIfNeeded();
   await page.mouse.move(1,1);
   if(width===1366){
    await page.waitForFunction(()=>document.querySelector('[data-listings-featured]').scrollLeft>5,null,{timeout:11000});
    assert.ok((await page.locator('[data-featured-status]').innerText()).includes('de 10'),'autoplay moves the featured row left');
   }else{
    assert.equal(await page.locator('[data-featured-pause]').isDisabled(),true,'reduced motion disables automatic movement');
   }
   const before=await track.evaluate(el=>el.scrollLeft);
   await page.locator('[data-featured-next]').click();
   if(width===1366)await page.waitForTimeout(800);
   assert.notEqual(await track.evaluate(el=>el.scrollLeft),before,'manual next navigates horizontal carousel');
   assert.ok((await page.locator('[data-featured-status]').innerText()).includes('de 10'),'carousel position indicator matches ten items');
   if(width===1366){
    await page.locator('[data-featured-pause]').click();
    assert.equal(await page.locator('[data-featured-pause]').getAttribute('aria-pressed'),'true','manual pause works');
   }
   assert.equal(await page.locator('[data-listings-page]').innerText(),'Página 1 de 2','8 of 13 produces two pages');
   await page.locator('[data-listings-next]').click();
   await page.waitForFunction(()=>document.querySelectorAll('[data-listings-grid] .vessara-offer').length===5);
   assert.equal(await page.locator('[data-listings-page]').innerText(),'Página 2 de 2','second page contains the remaining five items');
   assert.equal(await page.locator('[data-listings-featured] .vessara-offer').count(),10,'all featured items remain visible across pagination');
   const noHorizontalOverflow=await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth);
   assert.equal(noHorizontalOverflow,true,'document never overflows at '+width);
   assert.deepEqual(errors,[],'no browser exceptions');
   await page.close();
   console.log('PASS Vessara V3 carousel + compact 8-item pages '+width);
  }
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exit(1);});
