const fs=require('fs'),path=require('path'),assert=require('node:assert/strict');
const {execFileSync}=require('node:child_process');
const {chromium}=require('playwright');
const template=path.resolve(__dirname,'../overlay/templates/mupanic');
(async()=>{
 const html=execFileSync('php',[path.join(__dirname,'fixtures/market.php')],{encoding:'utf8'});
 const snapshot=JSON.parse(html.match(/id="vessara-initial">([\s\S]*?)<\/script>/)[1]);
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 const featured={id:'21',seller:'VessaraTest',item:'Deadly Staff',itemIndex:2593,level:15,currency:1,price:'9007199254740993',expiresAt:'2027-01-05T12:00:00-03:00',featuredUntil:'2027-01-01T11:00:00-03:00',featured:true,details:{skill:true,luck:true,additional:28,durability:255,excellent:['Daño +2%','Probabilidad de daño Excellent +10%'],excellentCount:2,ancient:false,harmony:false,option380:false,sockets:[],lot:1}};
 const ordinary={id:'22',seller:'PANIC',item:'Hurricane Armor',itemIndex:4129,level:13,currency:0,price:'1000000',expiresAt:'2027-01-05T12:00:00-03:00',featuredUntil:null,featured:false,details:{skill:false,luck:true,additional:16,durability:200,excellent:['Reducción de daño +4%'],excellentCount:1,ancient:true,ancientType:'Set Ancient',harmony:false,option380:false,sockets:[],lot:1}};
 try{for(const width of [1366,390]){
  const page=await browser.newPage({viewport:{width,height:900},reducedMotion:'reduce'}),errors=[],requests=[];
  page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',async route=>{
   const url=new URL(route.request().url());
   if(url.searchParams.has('market_listings')){
    const currency=url.searchParams.get('currency'),onlyFeatured=url.searchParams.get('featured')==='1',search=(url.searchParams.get('search')||'').toLowerCase(),pg=+(url.searchParams.get('listing_page')||'1');
    requests.push({currency,search,pg});
    let items=[ordinary].filter(i=>(currency==='all'||String(i.currency)===currency)&&i.item.toLowerCase().includes(search));
    return route.fulfill({json:{schema:1,available:true,page:pg,pages:pg===1?2:2,total:String(items.length),items:pg===1?items:[],featured:[featured],updated:1760000000}});
   }
   if(url.searchParams.has('market_data'))return route.fulfill({json:snapshot});
   if(url.pathname==='/market/')return route.fulfill({contentType:'text/html',body:html});
   if(url.pathname.startsWith('/templates/mupanic/')){
    const file=path.join(template,url.pathname.slice('/templates/mupanic/'.length));
    if(fs.existsSync(file))return route.fulfill({path:file});
   }
   return route.abort();
  });
  await page.goto('https://market.test/market/');
  await page.addScriptTag({path:path.join(template,'js/market-listings.js')});
  await page.waitForFunction(()=>document.querySelectorAll('[data-listings-grid] .vessara-offer').length===1);
  // The featured strip is built on the next animation frame, once its width is known.
  await page.waitForFunction(()=>document.querySelectorAll('[data-listings-featured] .vessara-offer').length>0);
  assert.equal(await page.locator('[data-listings-featured] .is-featured').count(),1,'promoted area contains only promoted item');
  assert.equal(await page.locator('[data-listings-grid] .vessara-offer.is-featured').count(),0,'paid featured offers never duplicate in normal listing');
  assert.equal(await page.locator('[data-listings-grid] .vessara-item-title.is-ancient').count(),1,'ancient item has a distinct title color');
  assert.equal(await page.locator('[data-listings-featured] .vessara-item-title.is-excellent').count()>0,true,'excellent item uses green title');
  assert.equal(await page.locator('[data-listings-featured] .vessara-item-level.is-max').count()>0,true,'item +15 displays max-tier highlight');
  assert.ok((await page.locator('[data-listings-featured]').innerText()).includes('9.007.199.254.740.993 Eryns'),'BIGINT displayed exactly');
  assert.ok(!(await page.locator('.vessara-listings').innerText()).includes('AccountID'),'private fields never rendered');
  const promotedCard=page.locator('[data-listings-featured] .vessara-offer').first();
  if(width>720)await promotedCard.hover();else await promotedCard.click();
  await page.waitForFunction(()=>!document.querySelector('.vessara-tooltip').hidden);
  assert.ok((await page.locator('.vessara-tooltip').innerText()).includes('Luck'),'tooltip shows actual Luck option');
  assert.ok((await page.locator('.vessara-tooltip').innerText()).includes('Daño +2%'),'tooltip shows Excellent option name');
  assert.ok((await page.locator('.vessara-tooltip').innerText()).includes('9.007.199.254.740.993 Eryns'),'tooltip preserves exact bigint');
  assert.equal(await page.locator('.vessara-tooltip').evaluate(x=>x.scrollWidth<=x.clientWidth),true,'tooltip fits without overflow');
  assert.ok(!(await page.locator('.vessara-tooltip').innerText()).includes('Ítem #'),'no internal item index on tooltip');
  assert.ok(!(await page.locator('.vessara-listings').innerText()).includes('Vigente hasta'),'no confusing expiration date on cards');
  assert.ok(!(await page.locator('.vessara-listings').innerText()).includes('Ver opciones del objeto'),'no redundant hover instruction');
  await page.keyboard.press('Escape');
  assert.equal(await page.locator('.vessara-tooltip').isVisible(),false,'Escape closes tooltip');
  await page.locator('[data-listings-currency="0"]').click();
  await page.waitForFunction(()=>document.querySelectorAll('[data-listings-grid] .vessara-offer').length===1);
  assert.ok((await page.locator('[data-listings-grid]').innerText()).includes('Hurricane Armor'),'Zen filter works');
  await page.locator('[data-listings-currency="1"]').click();
  await page.waitForFunction(()=>document.querySelectorAll('[data-listings-grid] .vessara-offer').length===0);
  assert.equal(await page.locator('[data-listings-state]').isVisible(),true,'empty search results explained');
  await page.locator('[data-listings-currency="all"]').click();
  await page.waitForFunction(()=>document.querySelectorAll('[data-listings-grid] .vessara-offer').length===1);
  await page.locator('[data-listings-next]').click();
  await page.waitForFunction(()=>document.querySelector('[data-listings-page]').textContent.includes('Página 2'));
  assert.equal(requests.at(-1).pg,2,'pagination uses listing_page, not CMS route page');
  assert.equal(await page.locator('.vessara-listings').evaluate(el=>el.scrollWidth<=el.clientWidth),true,'no listings overflow at '+width);
  assert.deepEqual(errors,[],'no browser errors '+width);
  await page.close();console.log('PASS Vessara active listings '+width);
 }}finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
