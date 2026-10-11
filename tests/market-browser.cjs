const fs=require('fs'),path=require('path'),assert=require('node:assert/strict');
const {execFileSync}=require('node:child_process'),{chromium}=require('playwright');
const root=path.resolve(__dirname,'../overlay/templates/mupanic');
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 const dir=process.env.MARKET_SCREENSHOT_DIR;if(dir)fs.mkdirSync(dir,{recursive:true});
 try{for(const [width,height] of [[1920,1080],[1366,900],[1024,768],[901,768],[390,844]]){
  const html=execFileSync('php',[path.join(__dirname,'fixtures/market.php')],{encoding:'utf8'});
  const page=await browser.newPage({viewport:{width,height},reducedMotion:'reduce'}),errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',async route=>{
   const url=new URL(route.request().url());
   if(url.pathname==='/market/')return route.fulfill({contentType:'text/html',body:html});
   if(url.pathname.startsWith('/templates/mupanic/')){const file=path.join(root,url.pathname.slice('/templates/mupanic/'.length));if(fs.existsSync(file))return route.fulfill({path:file});}
   return route.abort();
  });
  await page.goto('https://market.test/market/');
  assert.equal(await page.locator('h1').count(),1);
  assert.equal(await page.locator('[data-market-metric=zen]').textContent(),'9.007.199.254.740.993');
  assert.equal(await page.locator('[data-market-rows] tr').count(),2);
  assert.equal(await page.locator('.vessara-day').count(),7);
  const layout=await page.evaluate(()=>({overflow:document.documentElement.scrollWidth>innerWidth,title:document.querySelector('h1').getBoundingClientRect().top,header:document.querySelector('.site-header').getBoundingClientRect().bottom,navRight:document.querySelector('.main-nav').getBoundingClientRect().right,accountLeft:document.querySelector('.nav-actions').getBoundingClientRect().left}));
  assert.equal(layout.overflow,false,'viewport overflow at '+width);assert.ok(layout.title>=layout.header,'heading overlaps header');
  if(width>900)assert.ok(layout.navRight<=layout.accountLeft,'navigation overlaps account actions at '+width);
  await page.locator('[data-market-currency="1"]').click();assert.equal(await page.locator('[data-market-rows] tr').count(),1);
  await page.locator('[data-market-currency="all"]').click();await page.locator('[data-market-search]').fill('hurricane');assert.equal(await page.locator('[data-market-rows] tr').count(),1);
  await page.locator('[data-market-search]').fill('zzzzz');assert.equal(await page.locator('[data-market-rows] tr').count(),0);assert.equal(await page.locator('[data-market-empty]').isVisible(),true);
  await page.locator('[data-market-search]').fill('');await page.locator('summary').click();assert.equal(await page.locator('details').getAttribute('open'),'');
  assert.deepEqual(errors,[]);
  if(dir)await page.screenshot({path:path.join(dir,'vessara-'+width+'.png'),fullPage:true});
  await page.close();console.log('PASS market UI '+width);
 }}finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
