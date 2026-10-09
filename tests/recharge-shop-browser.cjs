const fs=require('fs');const path=require('path');
const {chromium}=require('playwright');
const {execFileSync}=require('child_process');
const html=execFileSync(process.env.PHP_BIN||'php',[path.join(__dirname,'fixtures/recharge-shop.php')],{encoding:'utf8'});
(async()=>{
 const browser=await chromium.launch({...(process.env.CHROME_BIN?{executablePath:process.env.CHROME_BIN}:{}),headless:true,args:['--no-sandbox']});
 const root=path.resolve(__dirname,'../overlay/templates/mupanic');
 for(const width of [1440,1024,768,390,320]) {
  const page=await browser.newPage({viewport:{width,height:1100}});const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',async route=>{
   const u=new URL(route.request().url());
   if(u.searchParams.get('shop_status')==='1')return route.fulfill({contentType:'application/json',body:JSON.stringify({orders:{['PANIC-'+ 'd'.repeat(32)]:['credited','Eryns acreditados','Ya podés usar tus Eryns.']}})});
   if(u.pathname==='/beta/usercp/recharge/')return route.fulfill({contentType:'text/html',body:html});
   const prefix='/beta/templates/mupanic/';
   if(u.pathname.startsWith(prefix)){const file=path.join(root,u.pathname.slice(prefix.length));if(fs.existsSync(file)&&fs.statSync(file).isFile())return route.fulfill({path:file});}
   errors.push('Missing asset: '+u.pathname);return route.abort();
  });
  await page.clock.install();
  await page.goto('https://preview.test/beta/usercp/recharge/',{waitUntil:'networkidle'});
  if(/wcoin/i.test(await page.locator('[data-recharge-shop]').textContent()))throw Error('Legacy currency label in storefront');
  if(width<=540){if(!await page.locator('[data-mobile-summary]').isVisible())throw Error('Mobile summary missing');if(await page.locator('[data-mobile-coins]').textContent()!=='100.000 Eryns')throw Error('Mobile summary differs');}
  const initial=await page.locator('[data-total-price]').textContent();if(initial!=='$ 100.000 ARS')throw Error('Five packages total '+initial);
  if(await page.locator('[data-total-coins]').textContent()!=='100.000 Eryns')throw Error('Coin total');
  await page.locator('#quantity-wcoin-20000').fill('2');await page.locator('#quantity-wcoin-5000').fill('3');
  if(await page.locator('[data-total-price]').textContent()!=='$ 55.000 ARS')throw Error('Mixed total');
  await page.locator('#quantity-wcoin-1000').fill('1.5');if(!await page.locator('[data-cart-submit]').isDisabled())throw Error('Fractional quantity enabled');
  await page.locator('#quantity-wcoin-1000').fill('0');await page.locator('#quantity-wcoin-20000').fill('99');if(!await page.locator('[data-cart-submit]').isDisabled())throw Error('Over limit enabled');
  await page.locator('#quantity-wcoin-20000').fill('5');await page.locator('#quantity-wcoin-5000').fill('0');
  const measured=await page.evaluate(()=>({overflow:document.documentElement.scrollWidth>innerWidth,qty:[...document.querySelectorAll('[data-quantity-change]')].every(b=>b.getBoundingClientRect().height>=44)}));
  if(measured.overflow||!measured.qty||errors.length)throw Error(JSON.stringify({width,...measured,errors}));
  if(process.env.RECHARGE_SCREENSHOT_DIR){fs.mkdirSync(process.env.RECHARGE_SCREENSHOT_DIR,{recursive:true});await page.screenshot({path:path.join(process.env.RECHARGE_SCREENSHOT_DIR,'recharge-shop-'+width+'.png'),fullPage:true});await page.locator('#recharge-packages').scrollIntoViewIfNeeded();await page.screenshot({path:path.join(process.env.RECHARGE_SCREENSHOT_DIR,'packages-'+width+'.png')});await page.locator('#recharge-cart').scrollIntoViewIfNeeded();await page.screenshot({path:path.join(process.env.RECHARGE_SCREENSHOT_DIR,'cart-'+width+'.png')});}
  await page.clock.runFor(21000);
  await page.waitForFunction(()=>document.querySelector('[data-order-id="PANIC-'+ 'd'.repeat(32)+'"]').dataset.orderState==='credited');
  if(await page.locator('[data-order-pay]').isVisible())throw Error('Paid checkout link remains visible');
  console.log('Shop '+width+'px: totals, limits, mobile tap targets and no overflow passed.');await page.close();
 }
 for(const mode of ['closed','ready','empty']){
  const stateHtml=execFileSync(process.env.PHP_BIN||'php',[path.join(__dirname,'fixtures/recharge-shop.php')],{encoding:'utf8',env:{...process.env,RECHARGE_FIXTURE:mode}});
  const page=await browser.newPage({viewport:{width:390,height:844}});
  let submitted=null;
  await page.route('**/*',route=>{const u=new URL(route.request().url());if(u.pathname==='/beta/usercp/recharge/'){if(route.request().method()==='POST')submitted=new URLSearchParams(route.request().postData());return route.fulfill({contentType:'text/html',body:stateHtml});}const prefix='/beta/templates/mupanic/';if(u.pathname.startsWith(prefix)){const file=path.join(root,u.pathname.slice(prefix.length));if(fs.existsSync(file))return route.fulfill({path:file});}return route.abort();});
  await page.goto('https://preview.test/beta/usercp/recharge/');
  if(mode==='closed'){if(!await page.locator('[data-cart-submit]').isDisabled())throw Error('Closed shop can submit');await page.locator('#quantity-wcoin-1000').fill('1');if(!await page.locator('[data-cart-submit]').isDisabled())throw Error('Selection bypasses closed shop');}
  if(mode==='ready'){if(!await page.locator('.recharge-ready').isVisible())throw Error('Prepared checkout missing');if(await page.locator('.recharge-ready a').getAttribute('href')!=='https://stage.uala-checkout.com/fixture')throw Error('Checkout URL changed');if(!(await page.locator('.recharge-ready').textContent()).includes('100.000 Eryns'))throw Error('Prepared amount missing');}
  if(mode==='empty'){if(!await page.locator('.recharge-history-empty').isVisible())throw Error('Empty history missing');if(!await page.locator('[data-cart-submit]').isDisabled())throw Error('Empty cart can submit');await page.locator('#quantity-wcoin-1000').fill('1');await page.locator('[data-cart-submit]').click();await page.waitForLoadState('networkidle');if(!submitted||submitted.get('quantity[wcoin-1000]')!=='1'||submitted.get('recharge_action')!=='create'||submitted.get('recharge_csrf')!=='a'.repeat(64)||submitted.get('recharge_nonce')!=='b'.repeat(32))throw Error('Checkout form contract changed');}
  if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth))throw Error('State overflow '+mode);
  console.log('Shop state '+mode+': passed.');await page.close();
 }
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
