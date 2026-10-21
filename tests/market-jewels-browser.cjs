const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const {execFileSync}=require('node:child_process'),{chromium}=require('playwright');

const template=path.resolve(__dirname,'../overlay/templates/mupanic');
const html=execFileSync('php',[path.join(__dirname,'fixtures/market.php')],{encoding:'utf8'});
const summary=JSON.parse(html.match(/id="vessara-initial">([\s\S]*?)<\/script>/)[1]);
const map=JSON.parse(fs.readFileSync(path.join(template,'inc/market-item-images.json'),'utf8')).items;
const names={6159:'Jewel of Chaos',7181:'Jewel of Bless',7182:'Jewel of Soul',
 7184:'Jewel of Life',7190:'Jewel of Creation',7209:'Gemstone',
 7221:'Talisman of Luck',7264:'Chaos Assembly'};
const offers=Object.entries(names).map(([key,name],i)=>{
 const image=map[key];assert.equal(image.source,'atlas_renovado','expected HD Atlas mapping: '+key);
 return {id:'jewel-'+key,item:name,itemIndex:Number(key),seller:'PruebaVessara',
  level:0,currency:i%2,price:String(3000+i),featured:false,
  featuredUntil:null,expiresAt:'2099-01-01T00:00:00-03:00',
  photo:{url:'/templates/mupanic/'+image.src,type:image.source,width:image.width,height:image.height},
  details:null};
});
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 try{
  for(const width of [1366,390]){
   const page=await browser.newPage({viewport:{width,height:920},reducedMotion:'reduce'}),errors=[],missing=[];
   page.on('pageerror',e=>errors.push(e.message));
   await page.route('**/*',async route=>{
    const url=new URL(route.request().url());
    if(url.searchParams.has('market_listings')){
     return route.fulfill({json:{schema:1,available:true,updated:1760000000,page:1,pages:1,
       pageSize:8,total:'8',items:offers,featured:[],itemOptions:false}});
    }
    if(url.searchParams.has('market_data'))return route.fulfill({json:summary});
    if(url.pathname==='/market/')return route.fulfill({contentType:'text/html',body:html});
    if(url.pathname.startsWith('/templates/mupanic/')){
     const filename=path.join(template,url.pathname.slice('/templates/mupanic/'.length));
     if(fs.existsSync(filename))return route.fulfill({path:filename});
     missing.push(filename);
    }
    return route.abort();
   });
   await page.goto('https://market.test/market/');
   await page.addScriptTag({path:path.join(template,'js/market-listings.js')});
   await page.waitForFunction(()=>document.querySelectorAll('[data-listings-grid] .vessara-item-photo').length===8);
   // Tall showroom cards load their images as each stall enters the viewport.
   for(const card of await page.locator('[data-listings-grid] .vessara-offer').all())await card.scrollIntoViewIfNeeded();
   await page.waitForFunction(()=>Array.from(document.querySelectorAll('[data-listings-grid] .vessara-item-photo'))
      .every(img=>img.complete && img.naturalWidth>=512),null,{timeout:20000});
   assert.equal(await page.locator('[data-listings-grid] .vessara-offer').count(),8,'all eight renewed assets have cards');
   assert.equal(await page.locator('[data-listings-grid] .vessara-item-media.is-renewed-art').count(),8,'high-res art styling is active');
   assert.equal(await page.locator('[data-listings-grid] img[src*="/img/atlas/"]').count(),8,'every sprite loads from local Atlas');
   assert.equal(missing.length,0,'no local image missing');
   const imageData=await page.locator('[data-listings-grid] .vessara-item-photo').evaluateAll(imgs=>imgs.map(img=>({
     url:img.currentSrc,width:img.naturalWidth,height:img.naturalHeight
   })));
   for(let i=0;i<offers.length;i++){
    const item=offers[i];
    assert.ok(imageData[i].url.endsWith(map[String(item.itemIndex)].src),'correct ItemIndex image: '+item.item);
    assert.ok(imageData[i].width>=512 && imageData[i].height>=512,'no pixelated original sprite: '+item.item);
   }
   const distinct=await page.evaluate(()=>{
    const board=getComputedStyle(document.querySelector('.vessara-listings-board'));
    const table=getComputedStyle(document.querySelector('.vessara-table-wrap'));
    const card=getComputedStyle(document.querySelector('.vessara-offer-grid .vessara-offer'));
    return {background:board.backgroundImage!==table.backgroundImage,
      border:board.borderTopColor!==table.borderTopColor,
      cardColor:card.backgroundImage!==table.backgroundImage};
   });
   assert.deepEqual(distinct,{background:true,border:true,cardColor:true},'warm active-shop palette is distinct from historical sales ledger');
   const chaos=page.locator('[data-listings-grid] .vessara-offer').first();
   if(width>720)await chaos.hover();else await chaos.click();
   await page.waitForFunction(()=>!document.querySelector('.vessara-tooltip').hidden);
   const tooltipImage=page.locator('.vessara-tooltip .vessara-item-photo');
   await tooltipImage.waitFor();
   await page.waitForFunction(()=>document.querySelector('.vessara-tooltip .vessara-item-photo').naturalWidth===512);
   assert.ok((await tooltipImage.getAttribute('src')).includes('jewel-chaos-'),'Chaos tooltip uses same updated Atlas art');
   await page.keyboard.press('Escape');
   assert.equal(await page.locator('.vessara-tooltip').isVisible(),false,'Escape closes item details');
   assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),true,'no horizontal page overflow');
   assert.deepEqual(errors,[],'no browser errors');
   await page.close();
   console.log('PASS Vessara V6 renewed Atlas jewels and contrasted shop colors '+width);
  }
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exit(1);});
