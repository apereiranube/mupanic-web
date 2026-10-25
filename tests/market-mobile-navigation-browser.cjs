const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const {execFileSync}=require('node:child_process'),{chromium}=require('playwright');
const template=path.resolve(__dirname,'../overlay/templates/mupanic');
const html=execFileSync('php',[path.join(__dirname,'fixtures/market.php')],{encoding:'utf8'});
const saleSummary=JSON.parse(html.match(/id="vessara-initial">([\s\S]*?)<\/script>/)[1]);
const expiry='2099-07-01T12:00:00-03:00';
const item=(id)=>({
 id:String(id),seller:'Vendedor'+id,item:id===1?'Hurricane Armor':'Chromatic Staff',itemIndex:id===1?4129:2593,
 level:15,currency:1,price:'1500',expiresAt:expiry,featuredUntil:null,featured:false,
 details:{skill:true,luck:true,additional:28,durability:245,
  excellent:['Daño +2%','Daño + nivel/20','Velocidad de ataque +7','Probabilidad de daño Excellent +10%','Recuperación de maná','Recuperación de vida'],
  excellentCount:6,ancient:true,ancientType:'Set Ancient',harmony:true,
  harmonyText:'Opción Harmony aplicada',option380:true,
  sockets:[{slot:1,state:'colocado',code:12},{slot:2,state:'vacío'},
   {slot:3,state:'colocado',code:34},{slot:4,state:'vacío'},{slot:5,state:'colocado',code:56}],
  lot:5}
});
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 try{
  for(const [width,height] of [[390,844],[360,640],[320,568],[844,390]]){
   const page=await browser.newPage({
    viewport:{width,height},isMobile:true,hasTouch:true,deviceScaleFactor:2,reducedMotion:width===390?'no-preference':'reduce'
   });
   const errors=[];
   page.on('pageerror',e=>errors.push(e.message));
   await page.route('**/*',async route=>{
    const url=new URL(route.request().url());
    if(url.searchParams.has('market_listings'))return route.fulfill({json:{
     schema:1,available:true,page:1,pages:1,pageSize:8,total:'2',items:[item(1),item(2)],
     featured:[],updated:1760000000,itemOptions:true
    }});
    if(url.searchParams.has('market_data'))return route.fulfill({json:saleSummary});
    if(url.pathname==='/market/')return route.fulfill({contentType:'text/html',body:html});
    if(url.pathname.startsWith('/templates/mupanic/')){
     const name=path.join(template,url.pathname.slice('/templates/mupanic/'.length));
     if(fs.existsSync(name))return route.fulfill({path:name});
    }
    return route.abort();
   });
   await page.goto('https://market.test/market/');
   await page.addScriptTag({path:path.join(template,'js/market-listings.js')});
   await page.waitForFunction(()=>document.querySelectorAll('[data-listings-grid] .vessara-offer').length===2);
   await page.locator('[data-listings-grid] .vessara-offer').first().scrollIntoViewIfNeeded();
   const initial=await page.evaluate(()=>({
    x:scrollX,y:scrollY,innerWidth,docWidth:document.documentElement.scrollWidth,
    bodyWidth:document.body.scrollWidth,htmlOverflow:getComputedStyle(document.documentElement).overflowX,
    bodyOverflow:getComputedStyle(document.body).overflowX
   }));
   assert.ok(initial.docWidth<=initial.innerWidth+1,'document is no wider than viewport at '+width);
   assert.ok(initial.bodyWidth<=initial.innerWidth+1,'body is no wider than viewport at '+width);
   assert.equal(initial.x,0,'starts with no sideways displacement');
   assert.equal(initial.bodyOverflow,'clip','mobile market body clips lateral overscroll');
   if(width===390){
    assert.ok(initial.y>200,'test starts below the top of the actual marketplace');
    assert.equal(await page.evaluate(()=>getComputedStyle(document.documentElement).scrollBehavior),'smooth',
      'regression runs with the site-wide smooth-scroll setting enabled');
   }
   await page.mouse.wheel(300,0);
   await page.waitForTimeout(120);
   assert.equal(await page.evaluate(()=>scrollX),0,'horizontal wheel does not move the whole page');
   // Touch works from the visible offer with mobile emulation.
   await page.locator('[data-listings-grid] .vessara-offer').first().tap();
   await page.waitForFunction(()=>!document.querySelector('.vessara-tooltip').hidden);
   const visible=await page.evaluate(()=>{
    const p=document.querySelector('.vessara-tooltip'),r=p.getBoundingClientRect();
    return {
     top:r.top,bottom:r.bottom,left:r.left,right:r.right,
     clientHeight:p.clientHeight,scrollHeight:p.scrollHeight,
     viewportHeight:innerHeight,viewportWidth:innerWidth,
     bodyPosition:document.body.style.position,scrollLocked:document.body.classList.contains('vessara-tooltip-open'),
     scrimVisible:!document.querySelector('.vessara-tooltip-backdrop').hidden,
     topPosition:getComputedStyle(p).top,modal:p.getAttribute('aria-modal')
    };
   });
   assert.ok(visible.top>=7 && visible.top<=55,'item popup begins near top of visible mobile viewport: '+JSON.stringify(visible));
   assert.ok(visible.bottom<=visible.viewportHeight-6,'popup is fully contained vertically at '+width+'x'+height);
   assert.ok(visible.left>=7 && visible.right<=visible.viewportWidth-7,'popup is fully contained horizontally');
   assert.equal(visible.bodyPosition,'fixed','background page is frozen while modal is open');
   assert.equal(visible.scrollLocked,true,'mobile scroll lock class active');
   assert.equal(visible.scrimVisible,true,'separate scrim is visible');
   assert.equal(visible.modal,'true','mobile popup announces modal role');
   assert.ok(visible.scrollHeight>visible.clientHeight+3,'long option details can scroll within popup');
   await page.locator('.vessara-tooltip').evaluate(node=>{node.scrollTop=node.scrollHeight;});
   const scrolled=await page.evaluate(()=>{
    const p=document.querySelector('.vessara-tooltip'),c=p.querySelector('.vessara-tooltip-close');
    const pr=p.getBoundingClientRect(),cr=c.getBoundingClientRect();
    return {scrollTop:p.scrollTop,closeTop:cr.top,popupTop:pr.top,closeBottom:cr.bottom,popupBottom:pr.bottom};
   });
   assert.ok(scrolled.scrollTop>20,'the detail scrolls independently');
   assert.ok(scrolled.closeTop>=scrolled.popupTop && scrolled.closeBottom<scrolled.popupBottom,
    'close control remains available at the top while scrolling');
   await page.locator('.vessara-tooltip-close').tap();
   assert.equal(await page.locator('.vessara-tooltip').isVisible(),false,'close button hides popup');
   const afterClose=await page.evaluate(()=>({
     position:document.body.style.position,top:document.body.style.top,y:scrollY,x:scrollX,
     active:document.body.classList.contains('vessara-tooltip-open'),
     shade:document.querySelector('.vessara-tooltip-backdrop').hidden
   }));
   assert.equal(afterClose.position,'','closing restores the body position');
   assert.equal(afterClose.top,'','closing clears the fixed offset');
   assert.equal(afterClose.active,false,'closing releases lock class');
   assert.equal(afterClose.shade,true,'closing hides backdrop');
   assert.ok(Math.abs(afterClose.y-initial.y)<=2,'closing restores original scroll position');
   assert.equal(afterClose.x,0,'closing never shifts page horizontally');
   if(width===390){
    const positions=await page.evaluate(async()=>{
     const points=[scrollY];
     for(let i=0;i<24;i++){
      await new Promise(resolve=>requestAnimationFrame(resolve));
      points.push(scrollY);
     }
     return points;
    });
    assert.ok(positions.every(y=>Math.abs(y-initial.y)<=2),
      'closing returns immediately without a downward smooth-scroll journey: '+JSON.stringify(positions));
    assert.equal(await page.evaluate(()=>getComputedStyle(document.documentElement).scrollBehavior),'smooth',
      'normal site smooth scrolling is restored after closing');
   }
   // Touching the backdrop closes the dialog instead of triggering the card beneath.
   await page.locator('[data-listings-grid] .vessara-offer').first().tap();
   await page.waitForFunction(()=>!document.querySelector('.vessara-tooltip').hidden);
   await page.mouse.click(4,Math.floor(height/2));
   assert.equal(await page.locator('.vessara-tooltip').isVisible(),false,'tapping outside closes modal');
   assert.equal(await page.locator('.vessara-tooltip-backdrop').isVisible(),false,'overlay fully disappears');
   await page.locator('[data-listings-grid] .vessara-offer').first().tap();
   await page.waitForFunction(()=>!document.querySelector('.vessara-tooltip').hidden);
   await page.keyboard.press('Escape');
   assert.equal(await page.locator('.vessara-tooltip').isVisible(),false,'Escape closes mobile popup');
   assert.equal(await page.evaluate(()=>document.body.style.position),'','Escape also unlocks scroll');
   assert.deepEqual(errors,[],'no page errors at '+width+'x'+height);
   await page.close();
   console.log('PASS Vessara mobile viewport + top modal '+width+'x'+height);
  }
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exit(1);});
