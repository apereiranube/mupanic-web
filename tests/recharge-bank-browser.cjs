const fs=require('fs'),path=require('path'),assert=require('node:assert/strict'),{execFileSync}=require('child_process'),{chromium}=require('playwright');
const html=execFileSync(process.env.PHP_BIN||'php',['tests/fixtures/recharge-shop.php'],{encoding:'utf8',env:{...process.env,RECHARGE_FIXTURE:'bank'}});
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox'],...(process.env.CHROME_BIN?{executablePath:process.env.CHROME_BIN}:{})});
 const root=path.resolve('overlay/templates/mupanic');
 for(const width of [1440,390,320]) {
  const page=await browser.newPage({viewport:{width,height:1000}});const errors=[];let posted=null;page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',route=>{
   const req=route.request(),u=new URL(req.url());
   if(req.method()==='POST'){posted=new URLSearchParams(req.postData());return route.fulfill({contentType:'text/html',body:'<p>Compra recibida en la prueba</p>'});}
   if(u.searchParams.has('shop_status'))return route.fulfill({json:{orders:{}}});
   if(u.pathname==='/beta/usercp/recharge/')return route.fulfill({contentType:'text/html',body:html});
   const prefix='/beta/templates/mupanic/';
   if(u.pathname.startsWith(prefix)){const f=path.join(root,u.pathname.slice(prefix.length));if(fs.existsSync(f))return route.fulfill({path:f});}
   return route.abort();
  });
  await page.goto('https://preview.test/beta/usercp/recharge/',{waitUntil:'networkidle'});
  const before=await page.locator('[data-total-price]').textContent();
  await page.locator('[name=payment_method][value=bank_transfer]').check();
  assert.equal(await page.locator('[name=buyer_cuit]').isVisible(),true);assert.equal(await page.locator('[name=buyer_cuit]').getAttribute('required'),'');
  assert.equal(await page.locator('[data-total-price]').textContent(),before);assert.match(await page.locator('[data-cart-submit]').textContent(),/Ver datos de transferencia/);
  assert.equal(await page.locator('[name=payment_method][value=uala_bis]').count(),0);
  assert.match(await page.locator('[data-payment-explanation]').textContent(),/48 horas/);
  assert.equal(await page.locator('[name=buyer_cuit]').evaluate(e=>e.checkValidity()),false);
  await page.locator('[name=buyer_cuit]').fill('20123456786');
  assert.equal(await page.locator('[name=buyer_cuit]').evaluate(e=>e.checkValidity()),true);
  assert.match(await page.locator('#bank-transfers').textContent(),/mu.panic.prueba/);
  assert.match(await page.locator('.bank-ticket-code').textContent(),/\/recarga/);
  assert.equal(await page.locator('#bank-transfers [name=recharge_action]').getAttribute('value'),'bank_ready');
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),true,'No horizontal overflow at '+width);
  fs.mkdirSync('/tmp/bank-qa',{recursive:true});await page.locator('#recharge-cart').screenshot({path:'/tmp/bank-qa/cart-'+width+'.png'});
  const consent=page.locator('[name=accept_terms]');assert.equal(await consent.isChecked(),false);
  await page.locator('[data-cart-submit]').click();assert.equal(posted,null,'No checkout without acceptance');
  await consent.check();
  await page.locator('[data-cart-submit]').click();await page.waitForTimeout(200);
  assert.equal(posted.get('accept_terms'),'1');assert.equal(posted.get('terms_version'),'2026-10-21.2');
  assert.equal(posted.get('payment_method'),'bank_transfer');assert.equal(posted.get('buyer_cuit'),'20123456786');assert.equal(posted.get('recharge_action'),'create');
  assert.deepEqual(errors,[]);await page.close();
 }
 await browser.close();console.log('Bank checkout, CUIT, unchanged prices, Discord instructions and mobile layout passed at 1440/390/320px.');
})().catch(e=>{console.error(e);process.exitCode=1});
