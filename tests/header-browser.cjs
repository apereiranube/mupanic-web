// Compare the production header across page themes and real Bootstrap 3.4.1.
const fs=require('fs'),path=require('path'),assert=require('node:assert/strict');
const {execFileSync}=require('node:child_process');
const {chromium}=require('playwright');
const repo=path.resolve(__dirname,'..'),template=path.join(repo,'overlay/templates/mupanic');
const output=process.env.HEADER_SCREENSHOT_DIR;
if(output)fs.mkdirSync(output,{recursive:true});
for(const css of ['home-launch','information','atlas','rankings','profiles']){
 const source=fs.readFileSync(path.join(template,'css',css+'.css'),'utf8');
 assert.ok(!/\.(site-header|nav-shell|main-nav|nav-actions|menu-toggle|nav-cta|account-link)\b/.test(source),css+' must not define a page-specific header');
}
const bootstrap=fs.readFileSync(path.join(__dirname,'fixtures/bootstrap-3.4.1.min.css'),'utf8');
const themes=[
 ['inicio','is-home-launch is-home',['home-launch']],
 ['servidor','is-inner is-server-info',['atlas','information']],
 ['mercado','is-inner is-market',['atlas','market']],
 ['fractura','is-inner is-fractura',['atlas','fractura']],
 ['atlas','is-inner is-wiki',['atlas','atlas-vessara']],
 ['rankings','is-inner is-rankings',['atlas','rankings']],
 ['perfil','is-inner is-profile',['atlas','profiles']],
 ['cuenta','is-inner is-account',['atlas','account']]
];
const render=logged=>execFileSync(process.env.PHP_BIN||'php',['-n',path.join(__dirname,'fixtures/home-launch.php'),'',...(logged?['--logged']:[])],{encoding:'utf8'});
function documentFor(name,classes,styles,logged){
 const source=render(logged),header=source.match(/<header class="site-header">[\s\S]*?<\/header>/)[0];
 const fallback=source.match(/<noscript>[\s\S]*?<\/noscript>/g).join('');
 return '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'+
 ['style',...styles].map(css=>'<link rel="stylesheet" href="/templates/mupanic/css/'+css+'.css">').join('')+fallback+
 '</head><body class="'+classes+'">'+header+'<main style="min-height:200vh;padding-top:160px;background:#101719"><h1>MU PANIC</h1></main><script src="/templates/mupanic/js/main.js"></script></body></html>';
}
async function metrics(page){return page.locator('.site-header').evaluate(root=>{
 const properties=['fontFamily','fontSize','fontWeight','lineHeight','letterSpacing','color','backgroundColor','backgroundImage','borderTopWidth','borderBottomWidth','borderBottomColor','borderLeftWidth','boxSizing','paddingTop','paddingRight','paddingBottom','paddingLeft','marginTop','marginRight','marginBottom','marginLeft','height','width','minHeight','gap','display','alignItems','justifyContent','position','top','left','right','boxShadow','backdropFilter','textTransform','textDecorationLine','outlineStyle','outlineWidth','outlineOffset','borderRadius','clipPath'];
 return [root,...root.querySelectorAll('*')].map(el=>{
  const cs=getComputedStyle(el),r=el.getBoundingClientRect();
  return {tag:el.tagName,class:el.className,rect:[r.x,r.y,r.width,r.height].map(x=>Math.round(x*100)/100),style:Object.fromEntries(properties.map(p=>[p,cs[p]]))};
 });
});}
async function withBootstrap(page){await page.evaluate(css=>{const style=document.createElement('style');style.dataset.testBootstrap='3.4.1';style.textContent=css;document.head.prepend(style);},bootstrap);}
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
 for(const width of [1920,1366,1101,1024,390])for(const logged of [false,true]){
  let baseline,openBaseline,pixelBaseline;
  for(const [name,classes,styles] of themes){
   const page=await browser.newPage({viewport:{width,height:1000},reducedMotion:'reduce'});
   const errors=[];page.on('pageerror',e=>errors.push(e.message));
   await page.route('**/*',route=>{
    const url=new URL(route.request().url());
    if(url.pathname==='/')return route.fulfill({contentType:'text/html',body:documentFor(name,classes,styles,logged)});
    if(url.pathname.startsWith('/templates/mupanic/')){
     const file=path.join(template,url.pathname.slice('/templates/mupanic/'.length));
     if(fs.existsSync(file))return route.fulfill({path:file});
    }
    return route.abort();
   });
   await page.goto('https://header.test/',{waitUntil:'networkidle'});
   await page.evaluate(()=>document.fonts.ready);
   await page.locator('.brand-logo').evaluate(im=>im.decode());
   const plain=await metrics(page);
   await withBootstrap(page);
   assert.deepEqual(await metrics(page),plain,`${name}/${width}/${logged}: Bootstrap must not change the header`);
   if(baseline)assert.deepEqual(plain,baseline,`${name}/${width}/${logged}: page CSS must not change the header`);
   baseline=plain;
   const sizes=await page.locator('.main-nav .nav-main-link').first().evaluate(el=>({font:parseFloat(getComputedStyle(el).fontSize),weight:getComputedStyle(el).fontWeight}));
   assert.equal(sizes.font,width>1100&&width<=1350?14:15,'Menu labels must remain readable instead of dropping to 10 or 11 pixels');
   assert.equal(sizes.weight,'600');
   const actionFont=await page.locator('.nav-cta').evaluate(el=>parseFloat(getComputedStyle(el).fontSize));
   assert.ok(actionFont>=(width<=760?11:12),'Account actions must grow with the main navigation');

   const pixels=await page.locator('.site-header').screenshot();
   if(pixelBaseline)assert.ok(pixels.equals(pixelBaseline),'The rendered header must match pixel for pixel across page themes');
   pixelBaseline=pixels;
   assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
   const header=await page.locator('.site-header').boundingBox();
   assert.equal(header.height,width<=760?70:width<=1100?76:86);
   if(width>1100){
    const links=await page.locator('.main-nav a:visible').all();
    assert.equal(await page.locator('.menu-toggle').isVisible(),false);
    const shell=await page.locator('.nav-shell').boundingBox(),actions=await page.locator('.nav-actions').boundingBox(),nav=await page.locator('.main-nav').boundingBox();
    assert.ok(nav.x+nav.width<=actions.x-4 && actions.x+actions.width<=shell.x+shell.width+1,'Desktop menu/actions must fit');
    assert.ok(links.length>=8);
   }else{
    assert.equal(await page.locator('.main-nav').isVisible(),false);
    await page.locator('.menu-toggle').click();
    assert.equal(await page.locator('.menu-toggle').getAttribute('aria-expanded'),'true');
    assert.equal(await page.locator('.main-nav a:visible').count(),10);
    await page.mouse.move(0,900);
    const opened=await metrics(page);
    if(openBaseline)assert.deepEqual(opened,openBaseline,'Open mobile menu must match every page');
    openBaseline=opened;
    // Remove Bootstrap while open, to exercise the dropdown and button resets too.
    await page.locator('[data-test-bootstrap]').evaluate(el=>el.remove());
    assert.deepEqual(await metrics(page),opened,'Open dropdown must be independent of Bootstrap');
    await withBootstrap(page);
    if(output && !logged){const menu=await page.locator('.main-nav').boundingBox();await page.screenshot({path:path.join(output,name+'-'+width+'-menu.png'),clip:{x:0,y:0,width,height:Math.ceil(menu.y+menu.height)}});}
    await page.keyboard.press('Escape');
    assert.equal(await page.locator('.menu-toggle').getAttribute('aria-expanded'),'false');
    assert.equal(await page.locator('.main-nav').isVisible(),false);
   }
   await page.locator('.account-link:visible,.nav-cta').last().focus();
   const focus=await metrics(page);
   await page.locator('[data-test-bootstrap]').evaluate(el=>el.remove());
   assert.deepEqual(await metrics(page),focus,'Focused buttons must match with/without Bootstrap');
   await page.evaluate(()=>document.activeElement.blur());
   await page.mouse.move(0,900);
   if(output && !logged)await page.locator('.site-header').screenshot({path:path.join(output,name+'-'+width+'.png')});
   assert.deepEqual(errors,[]);
   await page.close();
  }
  console.log(`PASS ${width}px ${logged?'account':'guest'}: eight page themes, Bootstrap parity, geometry, menu and keyboard`);
 }
 const nojs=await browser.newPage({javaScriptEnabled:false,viewport:{width:390,height:844}});
 await nojs.route('**/*',route=>{
  const url=new URL(route.request().url());
  if(url.pathname==='/')return route.fulfill({contentType:'text/html',body:documentFor(...themes[0],false)});
  const file=path.join(template,url.pathname.replace('/templates/mupanic/',''));
  return fs.existsSync(file)?route.fulfill({path:file}):route.abort();
 });
 await nojs.goto('https://header.test/');
 assert.equal(await nojs.locator('.main-nav').isVisible(),true);
 assert.equal(await nojs.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
 await nojs.close();await browser.close();
})().catch(e=>{console.error(e);process.exit(1);});
