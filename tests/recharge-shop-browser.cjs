const fs=require('fs');const path=require('path');
const {chromium}=require('playwright');
const {execFileSync}=require('child_process');
const html=execFileSync(process.env.PHP_BIN||'php',[path.join(__dirname,'fixtures/recharge-shop.php')],{encoding:'utf8'});
(async()=>{
 const browser=await chromium.launch({...(process.env.CHROME_BIN?{executablePath:process.env.CHROME_BIN}:{}),headless:true,args:['--no-sandbox']});
 const root=path.resolve(__dirname,'../overlay/templates/mupanic');
 for(const width of [1440,1024,390]) {
  const page=await browser.newPage({viewport:{width,height:1100}});const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',async route=>{
   const u=new URL(route.request().url());
   if(u.searchParams.get('shop_status')==='1')return route.fulfill({contentType:'application/json',body:JSON.stringify({orders:{['PANIC-'+ 'd'.repeat(32)]:['credited','Monedas acreditadas','Ya podés usar tus WCoin C.']}})});
   if(u.pathname==='/beta/usercp/recharge/')return route.fulfill({contentType:'text/html',body:html});
   const prefix='/beta/templates/mupanic/';
   if(u.pathname.startsWith(prefix)){const file=path.join(root,u.pathname.slice(prefix.length));if(fs.existsSync(file)&&fs.statSync(file).isFile())return route.fulfill({path:file});}
   return route.abort();
  });
  await page.clock.install();
  await page.goto('https://preview.test/beta/usercp/recharge/',{waitUntil:'networkidle'});
  const initial=await page.locator('[data-total-price]').textContent();if(initial!=='$ 100.000 ARS')throw Error('Five packages total '+initial);
  if(await page.locator('[data-total-coins]').textContent()!=='100.000 WCoin C')throw Error('Coin total');
  await page.locator('#quantity-wcoin-20000').fill('2');await page.locator('#quantity-wcoin-5000').fill('3');
  if(await page.locator('[data-total-price]').textContent()!=='$ 55.000 ARS')throw Error('Mixed total');
  await page.locator('#quantity-wcoin-1000').fill('1.5');if(!await page.locator('[data-cart-submit]').isDisabled())throw Error('Fractional quantity enabled');
  await page.locator('#quantity-wcoin-1000').fill('0');await page.locator('#quantity-wcoin-20000').fill('99');if(!await page.locator('[data-cart-submit]').isDisabled())throw Error('Over limit enabled');
  await page.locator('#quantity-wcoin-20000').fill('5');await page.locator('#quantity-wcoin-5000').fill('0');
  const measured=await page.evaluate(()=>({overflow:document.documentElement.scrollWidth>innerWidth,qty:[...document.querySelectorAll('[data-quantity-change]')].every(b=>b.getBoundingClientRect().height>=44)}));
  if(measured.overflow||!measured.qty||errors.length)throw Error(JSON.stringify({width,...measured,errors}));
  if(process.env.RECHARGE_SCREENSHOT_DIR)await page.screenshot({path:path.join(process.env.RECHARGE_SCREENSHOT_DIR,'recharge-shop-'+width+'.png'),fullPage:true});
  await page.clock.runFor(21000);
  await page.waitForFunction(()=>document.querySelector('[data-order-id="PANIC-'+ 'd'.repeat(32)+'"]').dataset.orderState==='credited');
  if(await page.locator('[data-order-pay]').isVisible())throw Error('Paid checkout link remains visible');
  console.log('Shop '+width+'px: totals, limits, mobile tap targets and no overflow passed.');await page.close();
 }
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
