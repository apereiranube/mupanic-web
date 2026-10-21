const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const {execFileSync}=require('node:child_process'),{chromium}=require('playwright');
const template=path.resolve(__dirname,'../overlay/templates/mupanic');
const html=execFileSync('php',[path.join(__dirname,'fixtures/market.php')],{encoding:'utf8'});
const summary=JSON.parse(html.match(/id="vessara-initial">([\s\S]*?)<\/script>/)[1]);
const featuredUntil='2099-01-01T12:00:00-03:00',expiresAt='2099-01-07T12:00:00-03:00';
function options(i){
 const type=i%4;
 return {skill:true,luck:i%2===0,additional:16,durability:255,
   excellent:type===0?['Daño +2%']:[],excellentCount:type===0?1:0,
   ancient:type===2,ancientType:type===2?'Set Ancient':null,
   harmony:false,option380:false,sockets:type===1?[{slot:1,state:'vacío'}]:[],lot:1};
}
const all=Array.from({length:23},(_,i)=>({
 id:String(i+1),seller:'Jugador'+i,item:'Espada '+(i+1),itemIndex:500+i,
 level:i<10?15:(i===10?3:13),currency:i%2,price:String(1000+i),expiresAt,
 featuredUntil:i<10?featuredUntil:null,featured:i<10,details:options(i)
}));
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 try {
  for(const width of [1366,390]) {
   const page=await browser.newPage({viewport:{width,height:900},reducedMotion:width===390?'reduce':'no-preference'});
   const errors=[];let apiCount=0,twoFeatured=false;
   page.on('pageerror',e=>errors.push(e.message));
   await page.route('**/*',async route=>{
    const u=new URL(route.request().url());
    if(u.searchParams.has('market_listings')){
     apiCount++;
     const currency=u.searchParams.get('currency'),search=u.searchParams.get('search')||'',sort=u.searchParams.get('sort');
     let items=all.filter(i=>!i.featured&&(currency==='all'||String(i.currency)===currency)
        &&i.item.toLowerCase().includes(search.toLowerCase()));
     if(sort==='price-desc')items.reverse();
     const count=items.length,pages=Math.max(1,Math.ceil(count/8));
     const requested=+(u.searchParams.get('listing_page')||'1'),currentPage=Math.min(Math.max(1,requested),pages);
     return route.fulfill({json:{schema:1,available:true,updated:Date.now(),page:currentPage,pages,pageSize:8,
       total:String(count),items:items.slice((currentPage-1)*8,currentPage*8),
       featured:all.slice(0,twoFeatured?2:10),itemOptions:true}});
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
   await page.waitForFunction(()=>document.querySelectorAll('[data-marquee-original]').length===10);
   const regular=page.locator('[data-listings-grid] .vessara-offer');
   const marquee=page.locator('[data-listings-featured]');
   const belt=page.locator('.vessara-marquee-motion');
   assert.equal(await regular.count(),8,'normal browse has exactly eight cards');
   assert.equal(await regular.filter({has:page.locator('.vessara-offer-badge')}).count(),0,'ordinary rows exclude paid highlights');
   assert.equal(await page.locator('[data-marquee-original]').count(),10,'ten actual promotions are shown');
   assert.equal(await page.locator('[data-listings-count]').innerText(),'13 publicaciones comunes','ordinary count excludes ten promoted offers');
   assert.equal(await page.locator('[data-listings-onlyfeatured]').count(),0,'redundant featured-only filter is gone');
   assert.equal(await page.locator('[data-listings-sort]').inputValue(),'recent','recent sort defaults');
   assert.ok((await marquee.innerText()).includes('Espada 1'),'featured offer is visible');
   assert.ok(!(await page.locator('.vessara-listings').innerText()).includes('Vigente hasta'),'no expiry dates on cards');
   assert.ok(!(await page.locator('.vessara-listings').innerText()).includes('TERMINA EN'),'no featured expiry countdown');
   assert.ok(!(await page.locator('.vessara-listings').innerText()).includes('Ver opciones del objeto'),'no redundant options link');
   assert.equal(await marquee.locator('.vessara-item-title.is-excellent').count()>0,true,'excellent name has rarity class');
   assert.equal(await marquee.locator('.vessara-item-title.is-socket').count()>0,true,'socket name has distinct rarity class');
   assert.equal(await marquee.locator('.vessara-item-title.is-ancient').count()>0,true,'ancient name has distinct rarity class');
   assert.equal(await marquee.locator('.vessara-item-level.is-max').count()>0,true,'+15 is highest tier');
   assert.equal(await regular.locator('.vessara-item-level.is-low').count()>0,true,'+3 remains lower tier');
   assert.equal(await regular.locator('.vessara-item-level.is-high').count()>0,true,'+13 has a distinct tier');
   const box=await page.locator('[data-listings-featured-wrap]').boundingBox();
   assert.ok(box && box.height<375,'even ten promoted cards stay in a compact single-row block');
   assert.equal(await page.locator('.vessara-marquee-group').count(),width===390?1:2,'CSS carousel duplicates only when motion is enabled');
   await regular.first().evaluate(node=>node.dataset.stable='1');
   await page.evaluate(()=>document.dispatchEvent(new Event('visibilitychange')));
   await page.waitForFunction(()=>document.querySelector('[data-listings-connection]')?.textContent==='Publicaciones actualizadas automáticamente');
   assert.ok(apiCount>=2,'poll request occurred');
   assert.equal(await regular.first().getAttribute('data-stable'),'1','unchanged polling does not rebuild the list');
   await page.locator('[data-listings-featured-wrap]').scrollIntoViewIfNeeded();
   await page.mouse.move(1,1);
   assert.equal(await page.locator('[data-featured-pause]').count(),0,'manual pause control was removed');
   assert.equal(await page.locator('.vessara-promoted-star').count(),0,'decorative circle was removed');
   assert.equal((await page.locator('.vessara-promoted-heading h3').innerText()).trim(),'Destacados de Vessara','featured heading identifies the separate showroom');
   if(width===1366){
    await page.waitForFunction(()=>!document.querySelector('[data-listings-featured-wrap]').classList.contains('is-offscreen'));
    const before=await belt.evaluate(node=>node.getAnimations()[0]?.currentTime||0);
    await page.waitForTimeout(460);
    const after=await belt.evaluate(node=>node.getAnimations()[0]?.currentTime||0);
    assert.ok(after>before+150,'featured strip still moves continuously');
    await marquee.hover({position:{x:5,y:5}});
    assert.equal(await belt.evaluate(node=>getComputedStyle(node).animationPlayState),'paused','hover pauses the moving items for inspecting tooltips');
    await page.waitForTimeout(120);
    const stopped=await belt.evaluate(node=>getComputedStyle(node).transform);
    await page.waitForTimeout(240);
    const held=await belt.evaluate(node=>getComputedStyle(node).transform);
    assert.equal(held,stopped,'hover keeps the strip stationary');
    await page.mouse.move(1,1);
    assert.equal(await belt.evaluate(node=>getComputedStyle(node).animationPlayState),'running','leaving the strip resumes automatic movement');
   }else{
    assert.equal(await belt.evaluate(node=>getComputedStyle(node).animationName),'none','reduced-motion carousel does not animate');
    assert.equal(await marquee.evaluate(node=>getComputedStyle(node).overflowX),'auto','reduced-motion users can manually scroll');
   }
   assert.equal(await page.locator('[data-listings-page]').innerText(),'Página 1 de 2','13 ordinary items across two eight-item pages');
   await page.locator('[data-listings-next]').click();
   await page.waitForFunction(()=>document.querySelectorAll('[data-listings-grid] .vessara-offer').length===5);
   assert.equal(await page.locator('[data-listings-page]').innerText(),'Página 2 de 2','page two contains five ordinary offers');
   assert.equal(await page.locator('[data-marquee-original]').count(),10,'promotions persist across pagination');
   twoFeatured=true;
   const beforeChange=apiCount;
   await page.evaluate(()=>document.dispatchEvent(new Event('visibilitychange')));
   await page.waitForFunction(()=>document.querySelectorAll('[data-marquee-original]').length===2);
   assert.ok(apiCount>beforeChange,'promotional refresh requested');
   if(width===1366){
    assert.equal(await page.locator('.vessara-marquee-group').count(),2,'two promotions still create continuous loop');
    assert.ok((await page.locator('.vessara-marquee-group').first().locator('.vessara-offer').count())>=2,'marquee automatically fills viewport without empty spaces');
   }
   assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),true,'no document horizontal overflow');
   assert.deepEqual(errors,[],'no browser errors '+width);
   await page.close();
   console.log('PASS Vessara V4 continuous marquee + rare levels + isolated eight-item catalog '+width);
  }
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exit(1);});
