const fs=require('fs'),path=require('path'),{execFileSync}=require('child_process'),{chromium}=require('playwright');
(async()=>{const browser=await chromium.launch({executablePath:process.env.CHROME_BIN,headless:true,args:['--no-sandbox']});const root=path.resolve(__dirname,'../overlay/templates/mupanic');
for(const width of [1440,768,390,320])for(const mode of ['normal','active','unknown','pending']){
 const html=execFileSync(process.env.PHP_BIN||'php',[path.join(__dirname,'fixtures/vip.php')],{encoding:'utf8',env:{...process.env,VIP_FIXTURE:mode}});
 const page=await browser.newPage({viewport:{width,height:900},reducedMotion:'reduce'});const errors=[];page.on('pageerror',e=>errors.push(e.message));
 await page.route('**/*',route=>{const url=new URL(route.request().url());if(url.pathname==='/beta/usercp/vip/')return route.fulfill({contentType:'text/html',body:html});const prefix='/beta/templates/mupanic/';if(url.pathname.startsWith(prefix)){const file=path.join(root,url.pathname.slice(prefix.length));if(fs.existsSync(file))return route.fulfill({path:file});}errors.push(url.pathname);return route.abort();});
 await page.goto('https://preview.test/beta/usercp/vip/',{waitUntil:'networkidle'});
 if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth)||errors.length)throw Error(JSON.stringify({width,mode,errors}));
 if(await page.locator('.vip-salon-offer').count()!==1)throw Error('Multiple VIP offers');
 const expected=mode==='pending'?'#eryns-vip':'?recharge_goal=vip#recharge-cart';if(!(await page.locator('.vip-salon-offer a').getAttribute('href')).endsWith(expected))throw Error('VIP target not canonical');
 if(mode==='active' && !(await page.locator('.vip-salon-offer a').textContent()).includes('Extender VIP'))throw Error('Renewal missing');
 if(!(await page.locator('.vip-salon-benefit').first().textContent()).includes('10% en X'))throw Error('Configured benefit missing');
 if(!(await page.locator('.vip-salon-benefit').nth(1).textContent()).includes('<Prueba>'))throw Error('Benefit escaping changed');
 if(width<=700 && !await page.locator('.vip-salon-mobile').isVisible())throw Error('Mobile recharge bar missing');
 await page.locator('.vip-salon-benefit summary').first().click();if(!(await page.locator('.vip-salon-benefit').first().textContent()).includes('mientras tu VIP esté activo'))throw Error('Benefit detail missing');
 if(await page.evaluate(()=>document.querySelector('.panic-vip').getAnimations({subtree:true}).length))throw Error('Reduced motion animates');
 if(process.env.VIP_SCREENSHOT_DIR && mode==='normal'){fs.mkdirSync(process.env.VIP_SCREENSHOT_DIR,{recursive:true});await page.screenshot({path:path.join(process.env.VIP_SCREENSHOT_DIR,'vip-'+width+'.png'),fullPage:true});}
 console.log(`VIP ${width}px ${mode}: passed`);await page.close();
}await browser.close();})().catch(e=>{console.error(e);process.exit(1)});
