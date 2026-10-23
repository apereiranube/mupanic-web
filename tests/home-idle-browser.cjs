const fs=require('fs'),path=require('path'),assert=require('node:assert/strict');
const {execFileSync}=require('node:child_process');const {chromium}=require('playwright');
const W=path.resolve(__dirname,'..'),root=path.join(W,'overlay/templates/mupanic');
const html=execFileSync('php',['-n',path.join(W,'tests/fixtures/home-launch.php'),'','--missing-cache'],{encoding:'utf8'});
const fixture={online:17,servidor:'online',rates:{experience:15,master:10,drop:25},eventos:[{nombre:'Castillo',proximo:'2099-10-20T18:00:00-03:00'}]};
(async()=>{
 const b=await chromium.launch({headless:true,args:['--no-sandbox']});
 const page=await b.newPage({viewport:{width:1440,height:900}});
 const errors=[];page.on('pageerror',e=>errors.push(e.message));
 await page.addInitScript(()=>{window.__clr=0;const o=CanvasRenderingContext2D.prototype.clearRect;CanvasRenderingContext2D.prototype.clearRect=function(){if(this.canvas.classList.contains('mp-brasas'))window.__clr++;return o.apply(this,arguments);};});
 await page.route('**/*',r=>{const u=new URL(r.request().url());if(u.pathname==='/')return r.fulfill({contentType:'text/html',body:html});if(u.pathname==='/api/launcher.php')return r.fulfill({json:fixture});if(u.pathname.startsWith('/templates/mupanic/')){const f=path.join(root,u.pathname.slice(19));if(fs.existsSync(f))return r.fulfill({path:f});}return r.abort();});
 await page.goto('https://home.test/',{waitUntil:'networkidle'});
 await page.evaluate(()=>window.dispatchEvent(new Event('focus')));
 const frames=async()=>{const a=await page.evaluate(()=>window.__clr);await page.waitForTimeout(1000);return (await page.evaluate(()=>window.__clr))-a;};
 await page.locator('.ph-furia-demo').scrollIntoViewIfNeeded();
 await page.waitForFunction(()=>[...document.querySelectorAll('[data-furia-video]')].some(v=>!v.paused));
 const f1=await frames(); console.log('con foco: brasas cuadros/s =',f1); assert.ok(f1>20);
 await page.evaluate(()=>window.dispatchEvent(new Event('blur')));
 await page.waitForTimeout(200);
 const f2=await frames(); console.log('sin foco: brasas cuadros/s =',f2); assert.ok(f2<=1);
 assert.equal(await page.evaluate(()=>document.documentElement.classList.contains('ph-idle')),true);
 assert.equal(await page.evaluate(()=>[...document.querySelectorAll('[data-furia-video]')].every(v=>v.paused)),true,'videos Furia pausados');
 assert.equal(await page.evaluate(()=>!document.querySelector('[data-furia-showcase]').classList.contains('is-animating')),true);
 await page.locator('.ph-status').scrollIntoViewIfNeeded();
 assert.equal(await page.locator('.ph-online-metric').evaluate(el=>getComputedStyle(el,'::after').animationPlayState),'paused');
 assert.equal(await page.locator('.ph-play-cta').evaluate(el=>getComputedStyle(el,'::after').animationPlayState),'paused');
 await page.evaluate(()=>window.dispatchEvent(new Event('focus')));
 await page.waitForFunction(()=>document.querySelector('.ph-status').classList.contains('is-stats-live'));
 assert.equal(await page.locator('.ph-online-metric').evaluate(el=>getComputedStyle(el,'::after').animationPlayState),'running');
 const f3=await frames(); console.log('vuelve el foco: brasas cuadros/s =',f3); assert.ok(f3>20);
 await page.locator('.ph-furia-demo').scrollIntoViewIfNeeded();
 await page.waitForFunction(()=>[...document.querySelectorAll('[data-furia-video]')].some(v=>!v.paused));
 // sin duplicar el bucle de brasas tras varias idas y vueltas
 for(let i=0;i<5;i++){await page.evaluate(()=>window.dispatchEvent(new Event('blur')));await page.waitForTimeout(50);await page.evaluate(()=>window.dispatchEvent(new Event('focus')));await page.waitForTimeout(50);}
 const f4=await frames(); console.log('tras 5 idas y vueltas: brasas cuadros/s =',f4); assert.ok(f4<=f1*1.3,'el bucle no se duplica');
 assert.deepEqual(errors.filter(e=>!/Failed/.test(e)),[]);
 console.log('PASS');await b.close();
})().catch(e=>{console.error(e);process.exit(1)});
