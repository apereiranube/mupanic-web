const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const {execFileSync}=require('node:child_process'),{chromium}=require('playwright');
const template=path.resolve(__dirname,'../overlay/templates/mupanic');
const html=execFileSync('php',[path.join(__dirname,'fixtures/market.php')],{encoding:'utf8'});
const snapshot=JSON.parse(html.match(/id="vessara-initial">([\s\S]*?)<\/script>/)[1]);
snapshot.metrics.zen='22000100';snapshot.metrics.eryns='4080';snapshot.metrics.today='12';
const images=JSON.parse(fs.readFileSync(path.join(template,'inc/market-item-images.json'),'utf8')).items;
const indexes=[6159,7181,7182,7184,7190,7209,7221,7264];
const names=['Jewel of Chaos','Jewel of Bless','Jewel of Soul','Jewel of Life','Jewel of Creation','Gemstone','Talisman of Luck','Chaos Assembly'];
const offer=(id,index,name,featured)=>({id:String(id),seller:'Jugador'+id,item:name,itemIndex:index,level:index<6000?15:0,currency:id%2,price:id%2?'1250':'1500000',expiresAt:'2099-01-01T12:00:00-03:00',featured,featuredUntil:featured?'2099-01-01T12:00:00-03:00':null,photo:images[index]?{url:'/templates/mupanic/'+images[index].src,type:images[index].source,width:images[index].width,height:images[index].height}:null,details:{skill:false,luck:false,excellent:[],excellentCount:0,lot:index>6000?5:1}});
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 const dir=process.env.MARKET_SCREENSHOT_DIR;if(dir)fs.mkdirSync(dir,{recursive:true});
 try{for(const width of [2560,1920,1440,390,320]){
  const page=await browser.newPage({viewport:{width,height:950},reducedMotion:'reduce'});const errors=[];let mode='full';
  page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',route=>{
   const u=new URL(route.request().url());
   if(u.searchParams.has('market_listings'))return route.fulfill({json:{schema:1,available:true,page:1,pages:1,pageSize:8,total:mode==='empty'?'0':mode==='single'?'1':'8',items:mode==='empty'?[]:indexes.slice(0,mode==='single'?1:8).map((x,i)=>offer(i+20,x,names[i],false)),featured:mode==='single'?[]:[offer(1,6159,'Jewel of Chaos',true)],updated:1760000000}});
   if(u.searchParams.has('market_data'))return route.fulfill({json:snapshot});
   if(u.pathname==='/market/')return route.fulfill({contentType:'text/html',body:html});
   if(u.pathname.startsWith('/templates/mupanic/')){const f=path.join(template,u.pathname.slice('/templates/mupanic/'.length));if(fs.existsSync(f))return route.fulfill({path:f});}
   return route.abort();
  });
  async function open(){await page.goto('https://market.test/market/');await page.addScriptTag({path:path.join(template,'js/market-listings.js')});await page.waitForFunction(()=>document.querySelector('[data-listings-count]').textContent.includes('publicaciones comunes'));await page.evaluate(()=>document.dispatchEvent(new Event('visibilitychange')));await page.waitForFunction(()=>document.querySelector('[data-market-metric=zen]').dataset.metricValue==='22000100');}
  await open();
  assert.equal(await page.locator('[data-listings-grid] .vessara-offer').count(),8);
  assert.equal(await page.locator('[data-listings-grid] .is-featured').count(),0,'Featured never enter the ordinary display');
  assert.equal(await page.locator('[data-listings-featured-wrap]').isVisible(),true);
  assert.equal(await page.locator('[data-listings-grid]').evaluate(e=>getComputedStyle(e).gridTemplateColumns.split(' ').length),width>1000?4:1);
  await page.locator('[data-listings-grid]').scrollIntoViewIfNeeded();
  for(const card of await page.locator('[data-listings-grid] .vessara-offer').all())await card.scrollIntoViewIfNeeded();
  await page.waitForFunction(()=>{const images=[...document.querySelectorAll('[data-listings-grid] img')];return images.length===8&&images.every(e=>e.complete&&e.naturalWidth>0)});
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),true,'No horizontal overflow '+width);
  const cards=await page.locator('[data-listings-grid] .vessara-offer').evaluateAll(es=>es.map(e=>({width:e.clientWidth,content:e.scrollWidth})));
  assert.ok(cards.every(e=>e.content<=e.width+1),'Card content fits '+width);
  if(dir){if(width===1440){await page.evaluate(()=>scrollTo({top:0,behavior:'instant'}));const previewHeight=await page.locator('.vessara-listings').evaluate(e=>Math.ceil(e.getBoundingClientRect().bottom+24));await page.setViewportSize({width,height:previewHeight});await page.evaluate(()=>scrollTo({top:0,behavior:'instant'}));await page.screenshot({path:path.join(dir,'bazaar-desktop.png')});await page.locator('.vessara-listings').evaluate(e=>scrollTo({top:scrollY+e.getBoundingClientRect().top-90,behavior:'instant'}));await page.screenshot({path:path.join(dir,'bazaar-listings.png')});await page.setViewportSize({width,height:950});}await page.locator('.vessara-listings').screenshot({path:path.join(dir,'hall-listings-'+width+'.png')});await page.evaluate(()=>scrollTo({top:0,behavior:'instant'}));await page.screenshot({path:path.join(dir,'hall-page-'+width+'.png'),fullPage:true});}
  const geometry=await page.locator('.vessara-hero').evaluate(e=>{const r=e.getBoundingClientRect(),t=e.querySelector('.vessara-intro').getBoundingClientRect(),img=e.querySelector('.vessara-hero-art'),ir=img.getBoundingClientRect();return {hero:r.width,copyOffset:t.left-r.left,imageRatio:ir.width/ir.height,natural:img.naturalWidth/img.naturalHeight,repeat:getComputedStyle(e.closest('.vessara-page'),'::before').backgroundRepeat}});
  assert.ok(Math.abs(geometry.hero-width)<2,'Hero artwork spans the full desktop composition');
  assert.ok(await page.locator('.vessara-page').evaluate(e=>Math.abs(e.getBoundingClientRect().width-innerWidth)<2),'Scenery spans the full window');
  assert.equal(await page.locator('.vessara-page').evaluate(e=>getComputedStyle(e,'::before').position),'fixed','One static scenery layer fills the window');
  assert.match(await page.locator('.vessara-stats').evaluate(e=>getComputedStyle(e).borderImageSource),/vessara-bronze-frame/,'Statistics use the cut bronze frame');
  assert.match(geometry.repeat,/^no-repeat/,'Hall artwork never repeats');
  if(width>900){assert.ok(Math.abs(geometry.copyOffset-geometry.hero*.105)<2,'Copy stays at left edge');assert.ok(Math.abs(geometry.imageRatio-geometry.natural)<.01,'Complete illustration keeps its proportions');}
  mode='single';await open();
  assert.equal(await page.locator('[data-listings-grid] .vessara-offer').count(),1);
  assert.equal(await page.locator('[data-listings-featured-wrap]').isVisible(),false);
  assert.equal(await page.locator('.vessara-listings-board').evaluate(e=>getComputedStyle(e).backgroundImage),'none','A single item has no empty giant backdrop');
  assert.ok(await page.locator('[data-listings-grid]').evaluate(e=>{const a=e.getBoundingClientRect(),b=e.firstElementChild.getBoundingClientRect();return Math.abs((a.left+a.width/2)-(b.left+b.width/2))<2 && b.width<=a.width}),'One publication is centered within the showcase');
  if(dir){await page.evaluate(()=>scrollTo({top:0,behavior:'instant'}));await page.screenshot({path:path.join(dir,'hall-top-'+width+'.png')});await page.locator('.vessara-listings').evaluate(e=>scrollTo({top:scrollY+e.getBoundingClientRect().top-90,behavior:'instant'}));await page.waitForFunction(()=>{const img=document.querySelector('[data-listings-grid] img');return img?.complete&&img.naturalWidth>0});await page.locator('[data-listings-grid] img').evaluate(img=>img.decode());await page.locator('[data-listings-grid] img').screenshot({path:path.join(dir,'single-icon-'+width+'.png')});await page.screenshot({path:path.join(dir,'hall-middle-'+width+'.png')});await page.evaluate(()=>scrollTo({top:0,behavior:'instant'}));await page.screenshot({path:path.join(dir,'hall-single-'+width+'.png'),fullPage:true});await page.locator('.vessara-hero').screenshot({path:path.join(dir,'hero-'+width+'.png')});}
  mode='empty';await open();
  assert.equal(await page.locator('[data-listings-grid] .vessara-offer').count(),0);
  assert.match(await page.locator('[data-listings-state]').textContent(),/Todavía no hay publicaciones comunes/);
  assert.equal(await page.locator('[data-listings-featured-wrap]').isVisible(),true,'Empty ordinary section keeps featured visible');
  assert.deepEqual(errors,[]);await page.close();console.log('PASS Vessara merchant hall, eight real-image fixtures and featured-only state '+width);
 }}finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1)});
