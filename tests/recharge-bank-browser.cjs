const fs=require('fs'),path=require('path'),assert=require('node:assert/strict'),{execFileSync}=require('child_process'),{chromium}=require('playwright');
const html=execFileSync(process.env.PHP_BIN||'php',['tests/fixtures/recharge-shop.php'],{encoding:'utf8',env:{...process.env,RECHARGE_FIXTURE:'bank'}});
const render=mode=>execFileSync(process.env.PHP_BIN||'php',['tests/fixtures/recharge-shop.php'],{encoding:'utf8',env:{...process.env,RECHARGE_FIXTURE:mode}});
const createdHtml=render('bank-created'),errorHtml=render('bank-error');
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox'],...(process.env.CHROME_BIN?{executablePath:process.env.CHROME_BIN}:{})});
 const root=path.resolve('overlay/templates/mupanic');
 for(const width of [1440,390,320]) {
  const page=await browser.newPage({viewport:{width,height:1000}});const errors=[];let posted=null;page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',route=>{
   const req=route.request(),u=new URL(req.url());
   if(req.method()==='POST'){posted=new URLSearchParams(req.postData());return route.fulfill({contentType:'text/html',body:posted.get('recharge_action')==='bank_ready'?html:createdHtml});}
   if(u.searchParams.has('shop_status'))return route.fulfill({json:{orders:{}}});
   if(u.pathname==='/beta/usercp/recharge/')return route.fulfill({contentType:'text/html',body:u.searchParams.has('fixture_error')?errorHtml:html});
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
  assert.equal(new URL(await page.locator('[data-cart-form]').getAttribute('action')).hash,'#bank-transfers');
  await page.locator('[data-cart-submit]').click();await page.waitForLoadState('networkidle');
  assert.equal(posted.get('accept_terms'),'1');assert.equal(posted.get('terms_version'),'2026-10-21.2');
  assert.equal(posted.get('payment_method'),'bank_transfer');assert.equal(posted.get('buyer_cuit'),'20123456786');assert.equal(posted.get('recharge_action'),'create');
  async function landsAt(prefix) {
   const target=page.locator('[data-checkout-target]');const id=await target.getAttribute('data-checkout-target');assert.ok(id.startsWith(prefix));
   assert.equal(new URL(page.url()).hash,'#'+id);
   const bounds=await page.locator('#'+id).boundingBox();
   const offset=await page.locator('.eryns-store-top').evaluate(e=>e.getBoundingClientRect().bottom);
   const atBottom=await page.evaluate(()=>Math.abs(scrollY+innerHeight-document.documentElement.scrollHeight)<3);
   assert.ok(bounds.y>=offset-1 && (bounds.y<offset+40 || (atBottom && bounds.y<500)),'Payment step appears below navigation at '+width+': '+JSON.stringify(await page.locator('#'+id).evaluate(e=>({y:e.getBoundingClientRect().y,margin:getComputedStyle(e).scrollMarginTop,scroll:scrollY,height:innerHeight,full:document.documentElement.scrollHeight,nav:document.querySelector('.eryns-store-top').getBoundingClientRect().bottom,offset:getComputedStyle(e.closest('[data-recharge-shop]')).getPropertyValue('--eryns-header-offset')}))));
  }
  await landsAt('bank-order-');
  assert.equal(await page.locator('[data-mobile-summary]').isVisible(),false,'No new-payment bar while following an existing transfer');
  assert.equal(await page.locator('.bank-payment-step').count(),3);
  await page.context().grantPermissions(['clipboard-read','clipboard-write']);
  await page.locator('[data-copy-label=alias]').click();
  assert.equal(await page.evaluate(()=>navigator.clipboard.readText()),'mu.panic.prueba');
  await page.locator('[data-copy-label="CBU / CVU"]').click();assert.equal(await page.evaluate(()=>navigator.clipboard.readText()),'0140000701100008921240');
  await page.locator('#bank-transfers').screenshot({path:'/tmp/bank-qa/steps-'+width+'.png'});
  await page.locator('#bank-transfers [name=recharge_action]').click();await page.waitForLoadState('networkidle');await landsAt('bank-ticket-');
  await page.screenshot({path:'/tmp/bank-qa/continue-'+width+'.png'});
  await page.locator('[data-copy-label=código]').click();assert.equal(await page.evaluate(()=>navigator.clipboard.readText()),'a'.repeat(24));
  await page.locator('#bank-transfers').screenshot({path:'/tmp/bank-qa/discord-'+width+'.png'});
  await page.goto('https://preview.test/beta/usercp/recharge/?fixture_error=1#bank-transfers',{waitUntil:'networkidle'});await landsAt('recharge-checkout');
  assert.deepEqual(errors,[]);await page.close();
 }
 await browser.close();console.log('Bank checkout, consent, payment-step navigation, errors, Discord instructions and mobile layout passed at 1440/390/320px.');
})().catch(e=>{console.error(e);process.exit(1)});
