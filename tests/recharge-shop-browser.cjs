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
  if(await page.locator('[data-recharge-shop] a[href*="/info/"]').count())throw Error('Store links to Atlas');
  if(/wcoin/i.test(await page.locator('[data-recharge-shop]').textContent()))throw Error('Legacy currency label in storefront');
  if(width<=540){if(!await page.locator('[data-mobile-summary]').isVisible())throw Error('Mobile summary missing');if(await page.locator('[data-mobile-coins]').textContent()!=='100.000 Eryns')throw Error('Mobile summary differs');}
  if(await page.locator('.eryns-mount-product').count()!==4)throw Error('Fourth mystery card missing');
  if(await page.locator('.eryns-mount-product.is-sealed').count()!==3)throw Error('Unreleased mounts must remain veiled');
  if(!(await page.locator('.eryns-vault').textContent()).includes('La bóveda de Agustin'))throw Error('Personal vault missing');
  const visualOrder=await page.evaluate(()=>document.querySelector('.eryns-wishlist').compareDocumentPosition(document.querySelector('#eryns-vip')) & Node.DOCUMENT_POSITION_FOLLOWING && document.querySelector('#eryns-vip').compareDocumentPosition(document.querySelector('#recharge-packages')) & Node.DOCUMENT_POSITION_FOLLOWING);
  if(!visualOrder)throw Error('VIP should sit between mounts and packs');
  const beforeVip=await page.locator('[data-total-price]').textContent();
  if(await page.locator('.eryns-vip-single-plan').count()!==1 || await page.locator('[data-vip-duration]').count())throw Error('VIP must be a single membership');
  await page.locator('[data-vip-cta]').click();
  if(!await page.locator('#eryns-vip-plan-details').evaluate(e=>e.open)||await page.locator('[data-total-price]').textContent()!==beforeVip)throw Error('Pending VIP interaction modifies recharge');
  const initial=await page.locator('[data-total-price]').textContent();if(initial!=='$ 100.000 ARS')throw Error('Five packages total '+initial);
  if(await page.locator('[data-total-coins]').textContent()!=='100.000 Eryns')throw Error('Coin total');
  await page.locator('[data-choose-package="wcoin-1000"]').click();
  if(await page.locator('[data-total-price]').textContent()!=='$ 1.000 ARS')throw Error('Package CTA changes wrong amount');
  await page.locator('.eryns-combine summary').click();
  await page.locator('#quantity-wcoin-1000').fill('0');
  await page.locator('#quantity-wcoin-20000').fill('2');await page.locator('#quantity-wcoin-5000').fill('3');
  if(await page.locator('[data-total-price]').textContent()!=='$ 55.000 ARS')throw Error('Mixed total');
  await page.locator('#quantity-wcoin-1000').fill('1.5');if(!await page.locator('[data-cart-submit]').isDisabled())throw Error('Fractional quantity enabled');
  await page.locator('#quantity-wcoin-1000').fill('0');await page.locator('#quantity-wcoin-20000').fill('99');if(!await page.locator('[data-cart-submit]').isDisabled())throw Error('Over limit enabled');
  await page.locator('#quantity-wcoin-20000').fill('5');await page.locator('#quantity-wcoin-5000').fill('0');
  const measured=await page.evaluate(()=>({overflow:document.documentElement.scrollWidth>innerWidth,qty:[...document.querySelectorAll('[data-quantity-change]')].every(b=>b.getBoundingClientRect().height>=44)}));
  if(measured.overflow||!measured.qty||errors.length)throw Error(JSON.stringify({width,...measured,errors}));
  await page.locator('.eryns-combine summary').click();
  if(process.env.RECHARGE_SCREENSHOT_DIR){fs.mkdirSync(process.env.RECHARGE_SCREENSHOT_DIR,{recursive:true});await page.screenshot({path:path.join(process.env.RECHARGE_SCREENSHOT_DIR,'recharge-shop-'+width+'.png'),fullPage:true});await page.locator('#eryns-vip').scrollIntoViewIfNeeded();await page.locator('.eryns-vip-portrait>img').evaluate(img=>img.decode());await page.screenshot({path:path.join(process.env.RECHARGE_SCREENSHOT_DIR,'vip-'+width+'.png')});await page.locator('#recharge-packages').scrollIntoViewIfNeeded();await page.screenshot({path:path.join(process.env.RECHARGE_SCREENSHOT_DIR,'packages-'+width+'.png')});await page.locator('#recharge-cart').scrollIntoViewIfNeeded();await page.screenshot({path:path.join(process.env.RECHARGE_SCREENSHOT_DIR,'cart-'+width+'.png')});}
  if(await page.locator('.eryns-mount-shelf [data-target-price]:not(:disabled)').count())throw Error('Unpriced mount enabled');
  await page.locator('[data-open-creature="eryns-creature-nerathys"]').click();
  if(!await page.locator('#eryns-creature-nerathys').isVisible()||page.url()!=='https://preview.test/beta/usercp/recharge/')throw Error('Creature preview leaves store');
  await page.keyboard.press('Escape');
  if(await page.locator('#eryns-creature-nerathys').isVisible())throw Error('Creature modal did not close');
  await page.locator('[data-open-creature="eryns-creature-theryon"]').click();
  await page.locator('#eryns-creature-theryon [data-return-to-buy]').click();
  if(await page.locator('#eryns-creature-theryon').isVisible())throw Error('Return to purchase did not close modal');
  if(await page.locator('[data-cart-submit]').isDisabled())throw Error('Preview changed purchase state');
  await page.locator('.eryns-store-top a[href="#recharge-history"]').click();
  await page.clock.runFor(21000);
  await page.waitForFunction(()=>document.querySelector('[data-order-id="PANIC-'+ 'd'.repeat(32)+'"]').dataset.orderState==='credited');
  if(await page.locator('[data-order-pay]').isVisible())throw Error('Paid checkout link remains visible');
  console.log('Shop '+width+'px: totals, limits, mobile tap targets and no overflow passed.');await page.close();
 }
 for(const mode of ['closed','ready','empty','zero','bonus','vip','vip-unknown','mount-normal','mount-vip','vip-priced','vip-priced-active','vip-priced-bonus','vip-priced-closed']){
  const stateHtml=execFileSync(process.env.PHP_BIN||'php',[path.join(__dirname,'fixtures/recharge-shop.php')],{encoding:'utf8',env:{...process.env,RECHARGE_FIXTURE:mode}});
  const page=await browser.newPage({viewport:{width:390,height:844}});
  let submitted=null;
  await page.route('**/*',route=>{const u=new URL(route.request().url());if(u.pathname==='/beta/usercp/recharge/'){if(route.request().method()==='POST')submitted=new URLSearchParams(route.request().postData());return route.fulfill({contentType:'text/html',body:stateHtml});}const prefix='/beta/templates/mupanic/';if(u.pathname.startsWith(prefix)){const file=path.join(root,u.pathname.slice(prefix.length));if(fs.existsSync(file))return route.fulfill({path:file});}return route.abort();});
  await page.goto('https://preview.test/beta/usercp/recharge/'+(mode.startsWith('vip-priced')?'?recharge_goal=vip#recharge-cart':''));
  if(mode.startsWith('vip-priced') && await page.locator('[data-total-coins]').textContent()!=='25.000 Eryns')throw Error('VIP page link does not prepare recharge');
  await page.locator('.eryns-combine summary').click();
  if(mode==='closed'){if(!await page.locator('[data-cart-submit]').isDisabled())throw Error('Closed shop can submit');await page.locator('#quantity-wcoin-1000').fill('1');if(!await page.locator('[data-cart-submit]').isDisabled())throw Error('Selection bypasses closed shop');}
  if(mode==='ready'){if(!await page.locator('.recharge-ready').isVisible())throw Error('Prepared checkout missing');if(await page.locator('.recharge-ready a').getAttribute('href')!=='https://stage.uala-checkout.com/fixture')throw Error('Checkout URL changed');if(!(await page.locator('.recharge-ready').textContent()).includes('100.000 Eryns'))throw Error('Prepared amount missing');}
  if(mode==='empty'){await page.locator('#recharge-history>summary').click();if(!await page.locator('.recharge-history-empty').isVisible())throw Error('Empty history missing');await page.locator('#quantity-wcoin-5000').fill('0');if(!await page.locator('[data-cart-submit]').isDisabled())throw Error('Empty cart can submit');await page.locator('#quantity-wcoin-1000').fill('1');await page.locator('[data-cart-submit]').click();await page.waitForLoadState('networkidle');if(!submitted||submitted.get('quantity[wcoin-1000]')!=='1'||submitted.get('recharge_action')!=='create'||submitted.get('recharge_csrf')!=='a'.repeat(64)||submitted.get('recharge_nonce')!=='b'.repeat(32))throw Error('Checkout form contract changed');}
  if(mode==='mount-normal'||mode==='mount-vip'){
    const goal=page.locator('[data-target-name="Theryon"]');const expected=mode==='mount-vip'?'90000':'100000';
    if(await goal.getAttribute('data-target-price')!==expected||await goal.isDisabled())throw Error('Manual price does not match account state');
    if((await page.locator('.eryns-mount-product--theryon').getAttribute('class')).includes('is-sealed'))throw Error('Published mount remains sealed');
    await goal.click();const delivered=Number((await page.locator('[data-total-coins]').textContent()).replace(/[^0-9]/g,''));if(delivered<Number(expected))throw Error('Goal reload does not reach manual product price');
  }
  if(mode.startsWith('vip-priced')) {
    const cta=page.locator('[data-vip-cta]');if(await cta.getAttribute('data-target-price')!=='25000')throw Error('VIP price not connected');
    if(!(await cta.textContent()).includes(mode==='vip-priced-active'?'Extender VIP':'Activá tu VIP'))throw Error('VIP purchase label incorrect');
    await cta.click();
    const received=Number((await page.locator('[data-total-coins]').textContent()).replace(/[^0-9]/g,''));if(received!==25000)throw Error('VIP recharge should reach 25k with existing packs');
    if(mode==='vip-priced-bonus' && (received!==25000 || await page.locator('[data-total-price]').textContent()!=='$ 20.000 ARS'))throw Error('VIP ignores real pack bonus');
    if((await page.locator('[data-cart-submit]').isDisabled())!==(mode==='vip-priced-closed'))throw Error('VIP bypasses payment availability');
    if(!(await page.locator('[data-target-message]').textContent()).includes('dentro del juego'))throw Error('VIP recharge implies activation');
    if(await page.locator('#eryns-vip-plan-details').evaluate(e=>e.open))throw Error('Priced VIP only opens information');
  }
  if(mode==='vip'){if(await page.locator('[data-vip-state]').getAttribute('data-vip-state')!=='active'||!(await page.locator('[data-vip-state]').textContent()).includes('12 días restantes')||!(await page.locator('[data-vip-cta]').textContent()).includes('Conocé el VIP'))throw Error('Active VIP status incorrect');}
  if(mode==='vip-unknown'){if(await page.locator('[data-vip-state]').getAttribute('data-vip-state')!=='unknown'||!(await page.locator('[data-vip-cta]').textContent()).includes('Conocé el VIP'))throw Error('Unknown VIP treated as normal');}
  if(mode==='zero'){if(await page.locator('[data-total-coins]').textContent()!=='5.000 Eryns'||await page.locator('[data-cart-submit]').isDisabled())throw Error('All-zero GET did not select recommendation');}
  if(mode==='bonus'){
    if(await page.locator('[data-total-coins]').textContent()!=='22.000 Eryns'||await page.locator('[data-total-price]').textContent()!=='$ 20.000 ARS')throw Error('Bonus pack amount changed');
    if(await page.locator('[data-bonus-rate]').textContent()!=='+10%'||await page.locator('[data-bonus-breakdown]').textContent()!=='20.000 + 2.000 de regalo')throw Error('Real bonus missing');
    if(!(await page.locator('[data-choose-package="wcoin-20000"]').textContent()).includes('MEJOR VALOR'))throw Error('Best value label missing');
    await page.locator('[data-choose-package="wcoin-1000"]').click();if(!await page.locator('[data-upgrade-package]').isVisible())throw Error('Real bonus upgrade missing');
    await page.locator('[data-upgrade-package]').click();if(await page.locator('[data-total-coins]').textContent()!=='22.000 Eryns')throw Error('Upgrade changes amount');
    if(process.env.RECHARGE_SCREENSHOT_DIR){await page.locator('.eryns-combine summary').click();await page.screenshot({path:path.join(process.env.RECHARGE_SCREENSHOT_DIR,'bonus-390.png'),fullPage:true});}
    await page.evaluate(()=>{const button=document.querySelector('[data-target-price]');button.disabled=false;button.dataset.targetPrice='12000';button.click();});
    if(await page.locator('[data-total-coins]').textContent()!=='12.000 Eryns'||await page.locator('[data-total-price]').textContent()!=='$ 12.000 ARS')throw Error('Goal should choose cheapest sufficient quantity');
  }
  if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth))throw Error('State overflow '+mode);
  console.log('Shop state '+mode+': passed.');await page.close();
 }
 const reduced=await browser.newPage({viewport:{width:390,height:844},reducedMotion:'reduce'});
 await reduced.route('**/*',route=>{const u=new URL(route.request().url());if(u.pathname==='/beta/usercp/recharge/')return route.fulfill({contentType:'text/html',body:html});const prefix='/beta/templates/mupanic/';if(u.pathname.startsWith(prefix)){const file=path.join(root,u.pathname.slice(prefix.length));if(fs.existsSync(file))return route.fulfill({path:file});}return route.abort();});
 await reduced.goto('https://preview.test/beta/usercp/recharge/');await reduced.locator('[data-choose-package="wcoin-1000"]').click();
 if(await reduced.evaluate(()=>document.querySelector('[data-recharge-shop]').getAnimations({subtree:true}).length))throw Error('Reduced motion still animates');
 await reduced.locator('.eryns-combine summary').click();await reduced.locator('#quantity-wcoin-1000').fill('2');
 if(await reduced.locator('[data-total-coins]').textContent()!=='2.000 Eryns')throw Error('Reduced-motion checkout differs');
 console.log('Reduced motion: animation-free selection and canonical totals passed.');await reduced.close();
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
