const fs=require('fs'),path=require('path'),assert=require('node:assert/strict');
const {execFileSync}=require('child_process'),{chromium}=require('playwright');
const root=path.resolve(__dirname,'../overlay/templates/mupanic');
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 const output=process.env.PROFILE_SCREENSHOT_DIR;if(output)fs.mkdirSync(output,{recursive:true});
 for(const [width,height] of [[1920,1080],[1366,768],[1024,768],[390,844],[320,700]]){
  for(const mode of ['online','offline','unknown','minimal']){
   const html=execFileSync(process.env.PHP_BIN||'php',[path.join(__dirname,'fixtures/profiles.php')],{encoding:'utf8',env:{...process.env,PROFILE_FIXTURE:mode}});
   const page=await browser.newPage({viewport:{width,height},reducedMotion:'reduce'}),errors=[];
   page.on('pageerror',e=>errors.push(e.message));
   await page.route('**/*',route=>{
    const pathname=new URL(route.request().url()).pathname;
    if(pathname.startsWith('/profile/'))return route.fulfill({contentType:'text/html',body:html});
    if(pathname.startsWith('/rankings/'))return route.fulfill({contentType:'text/html',body:'<h1>Ranking</h1>'});
    if(pathname.startsWith('/templates/mupanic/')){const file=path.join(root,pathname.slice('/templates/mupanic/'.length));if(fs.existsSync(file))return route.fulfill({path:file});}
    errors.push('Missing asset: '+pathname);return route.abort();
   });
   await page.goto('https://profile.test/profile/player/req/fixture/');await page.evaluate(()=>document.fonts.ready);
   assert.equal(await page.locator('.profile-name').textContent(),'FixtureHero');
   assert.equal(await page.locator('h1').textContent(),'Perfil de personaje');
   assert.equal(await page.locator('.profile-progress .profile-value').count(),mode==='minimal'?3:4);
   assert.deepEqual(await page.locator('.profile-progress dd').allTextContents(),mode==='minimal'?['400','150','15']:['400','150','15','3']);
   assert.deepEqual(await page.locator('.profile-attributes dd').allTextContents(),['65,000','65,000','65,000','65,000']);
   assert.equal(await page.locator('.profile-details a').getAttribute('href'),'/profile/guild/FixtureGuild/');
   assert.equal(await page.locator('.profile-presence').textContent(),mode==='offline'?'Desconectado':mode==='unknown'?'Not available':'En línea');
   if(mode==='unknown')assert.ok((await page.locator('.profile-details').textContent()).includes('Custom metric123'));
   const metrics=await page.evaluate(()=>({overflow:document.documentElement.scrollWidth>innerWidth,title:document.querySelector('h1').getBoundingClientRect().top,header:document.querySelector('.site-header').getBoundingClientRect().bottom,bottom:document.querySelector('.profile-sections').getBoundingClientRect().bottom,bg:getComputedStyle(document.body).backgroundColor}));
   assert.equal(metrics.overflow,false,'No horizontal overflow '+width);assert.ok(metrics.title>=metrics.header,'Heading visible');assert.equal(metrics.bg,'rgb(11, 16, 18)');
   if(output&&mode==='online'&&[1920,390,320].includes(width))await page.screenshot({path:path.join(output,`profile-${width}.png`),fullPage:true});
   if(width===1920)assert.ok(metrics.bottom<1080,'Profile fits desktop viewport '+JSON.stringify(metrics));
   assert.deepEqual(errors,[]);
   await page.close();console.log('PASS profile',mode,width);
  }
 }
 // Return to the category the visitor opened, preserving the encrypted CMS profile URL.
 const page=await browser.newPage();const html=execFileSync(process.env.PHP_BIN||'php',[path.join(__dirname,'fixtures/profiles.php')],{encoding:'utf8'});
 await page.route('**/*',route=>{const u=new URL(route.request().url());if(u.pathname.startsWith('/templates/mupanic/'))return route.fulfill({path:path.join(root,u.pathname.slice('/templates/mupanic/'.length))});return route.fulfill({contentType:'text/html',body:u.pathname.startsWith('/rankings/')?'<a href="/profile/player/req/fixture/">Personaje</a>':html});});
 await page.goto('https://profile.test/rankings/duels/');await page.locator('a').click();
 assert.equal(await page.locator('.profile-back').first().getAttribute('href'),'https://profile.test/rankings/duels/');await page.locator('.profile-back').first().click();await page.waitForURL('**/rankings/duels/');
 await browser.close();console.log('PASS ranking return');
})().catch(e=>{console.error(e);process.exit(1)});
