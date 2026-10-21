const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const {execFileSync}=require('node:child_process'),{chromium}=require('playwright');
const template=path.resolve(__dirname,'../overlay/templates/mupanic');
const html=execFileSync('php',[path.join(__dirname,'fixtures/market.php')],{encoding:'utf8'});
const snapshot=JSON.parse(html.match(/id="vessara-initial">([\s\S]*?)<\/script>/)[1]);
const images=JSON.parse(fs.readFileSync(path.join(template,'inc/market-item-images.json'),'utf8')).items;
const indexes=[6159,7181,7182,7184,7190,7209,7221,7264];
const names=['Jewel of Chaos','Jewel of Bless','Jewel of Soul','Jewel of Life','Jewel of Creation','Gemstone','Talisman of Luck','Chaos Assembly'];
const offer=(id,index,name,featured)=>({id:String(id),seller:'Jugador'+id,item:name,itemIndex:index,level:index<6000?15:0,currency:id%2,price:id%2?'1250':'1500000',expiresAt:'2099-01-01T12:00:00-03:00',featured,featuredUntil:featured?'2099-01-01T12:00:00-03:00':null,photo:images[index]?{url:'/templates/mupanic/'+images[index].src,type:images[index].source,width:images[index].width,height:images[index].height}:null,details:{skill:false,luck:false,excellent:[],excellentCount:0,lot:index>6000?5:1}});
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 const dir=process.env.MARKET_SCREENSHOT_DIR;if(dir)fs.mkdirSync(dir,{recursive:true});
 try{for(const width of [1440,390,320]){
  const page=await browser.newPage({viewport:{width,height:950},reducedMotion:'reduce'});const errors=[];let empty=false;
  page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',route=>{
   const u=new URL(route.request().url());
   if(u.searchParams.has('market_listings'))return route.fulfill({json:{schema:1,available:true,page:1,pages:1,pageSize:8,total:empty?'0':'8',items:empty?[]:indexes.map((x,i)=>offer(i+20,x,names[i],false)),featured:[offer(1,6159,'Jewel of Chaos',true)],updated:1760000000}});
   if(u.searchParams.has('market_data'))return route.fulfill({json:snapshot});
   if(u.pathname==='/market/')return route.fulfill({contentType:'text/html',body:html});
   if(u.pathname.startsWith('/templates/mupanic/')){const f=path.join(template,u.pathname.slice('/templates/mupanic/'.length));if(fs.existsSync(f))return route.fulfill({path:f});}
   return route.abort();
  });
  async function open(){await page.goto('https://market.test/market/');await page.addScriptTag({path:path.join(template,'js/market-listings.js')});await page.waitForFunction(()=>document.querySelector('[data-listings-count]').textContent.includes('publicaciones comunes'));}
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
  if(dir){await page.locator('.vessara-listings').screenshot({path:path.join(dir,'hall-listings-'+width+'.png')});await page.evaluate(()=>scrollTo({top:0,behavior:'instant'}));await page.screenshot({path:path.join(dir,'hall-page-'+width+'.png'),fullPage:true});}
  empty=true;await open();
  assert.equal(await page.locator('[data-listings-grid] .vessara-offer').count(),0);
  assert.match(await page.locator('[data-listings-state]').textContent(),/Todavía no hay publicaciones comunes/);
  assert.equal(await page.locator('[data-listings-featured-wrap]').isVisible(),true,'Empty ordinary section keeps featured visible');
  assert.deepEqual(errors,[]);await page.close();console.log('PASS Vessara merchant hall, eight real-image fixtures and featured-only state '+width);
 }}finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1)});
