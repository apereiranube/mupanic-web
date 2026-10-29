const fs=require('fs'),path=require('path'),assert=require('node:assert/strict');
const {execFileSync}=require('child_process'),{chromium}=require('playwright');
const root=path.resolve(__dirname,'../overlay/templates/mupanic');
const render=mode=>execFileSync(process.env.PHP_BIN||'php',[path.join(__dirname,'fixtures/profiles.php')],{encoding:'utf8',env:{...process.env,PROFILE_FIXTURE:mode}});
async function open(browser,mode,width,height){
 const html=render(mode),page=await browser.newPage({viewport:{width,height},reducedMotion:'reduce'}),errors=[];
 page.on('pageerror',e=>errors.push(e.message));
 await page.route('**/*',route=>{
  const pathname=new URL(route.request().url()).pathname;
  if(pathname.startsWith('/profile/'))return route.fulfill({contentType:'text/html',body:html});
  if(pathname.startsWith('/rankings/'))return route.fulfill({contentType:'text/html',body:'<h1>Ranking</h1>'});
  if(pathname.startsWith('/templates/mupanic/')){const file=path.join(root,pathname.slice('/templates/mupanic/'.length));if(fs.existsSync(file))return route.fulfill({path:file});}
  errors.push('Missing asset: '+pathname);return route.abort();
 });
 await page.goto('https://profile.test/profile/player/req/fixture/');await page.evaluate(()=>document.fonts.ready);
 await page.evaluate(()=>Promise.all([...document.images].map(i=>i.decode().catch(()=>{}))));
 return {page,errors,html};
}
async function common(page,width,label){
 const m=await page.evaluate(()=>({overflow:document.documentElement.scrollWidth>innerWidth,title:document.querySelector('h1').getBoundingClientRect().top,header:document.querySelector('.site-header').getBoundingClientRect().bottom,bg:getComputedStyle(document.body).backgroundColor,
  h1:getComputedStyle(document.querySelector('h1')).fontFamily,h2:[...document.querySelectorAll('.pf h2')].map(h=>getComputedStyle(h).fontFamily),
  oldHero:!!document.querySelector('.inner-hero'),broken:[...document.images].filter(i=>!i.naturalWidth).map(i=>i.src)}));
 assert.equal(m.overflow,false,'No horizontal overflow '+label+' '+width);
 assert.ok(m.title>=m.header,'Heading below header '+label);
 assert.equal(m.bg,'rgb(15, 25, 35)');
 assert.match(m.h1,/^Anton/);m.h2.forEach(f=>assert.match(f,/^Anton/));
 assert.equal(m.oldHero,false);assert.deepEqual(m.broken,[]);
 // Nothing changes height on interaction.
 const target=page.locator('.pf-member, .pf-back').first(),before=await target.boundingBox();
 await target.hover();const after=await target.boundingBox();assert.equal(Math.round(after.height),Math.round(before.height));
}
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 const output=process.env.PROFILE_SCREENSHOT_DIR;if(output)fs.mkdirSync(output,{recursive:true});
 for(const [width,height] of [[1920,1080],[1440,900],[1024,768],[390,844],[320,700]]){
  for(const mode of ['online','offline','unknown','minimal','dl','rf']){
   const {page,errors,html}=await open(browser,mode,width,height);
   const name={dl:'Lordia',rf:'Puños'}[mode]||'FixtureHero';
   assert.equal(await page.locator('h1.profile-name').textContent(),name);
   assert.equal(await page.locator('.pf-hero-fig img').count(),1,'Class art in hero');
   const art=await page.locator('.pf-hero-fig img').getAttribute('src');
   assert.match(art,{dl:/clase-dl-/,rf:/clase-rf-/}[mode]||/clase-bk-/);
   const progress={minimal:['400','150','15'],dl:['380','90','12','0'],rf:['350','0','20','1']}[mode]||['400','150','15','3'];
   assert.deepEqual(await page.locator('.profile-progress dd').allTextContents(),progress);
   const attrs=await page.locator('.profile-attributes dt').allTextContents();
   assert.deepEqual(attrs,mode==='dl'?['Fuerza','Agilidad','Vitalidad','Energía','Comando']:['Fuerza','Agilidad','Vitalidad','Energía']);
   if(mode==='dl'){const bars=await page.locator('.pf-bar i').evaluateAll(b=>b.map(i=>i.style.width));assert.deepEqual(bars,['100%','58%','28%','38%','100%']);}
   if(mode==='rf')assert.equal(await page.locator('.profile-details .pf-tile--guild dd').textContent(),'Sin guild');
   else assert.equal(await page.locator('.profile-details a').getAttribute('href'),'/profile/guild/FixtureGuild/');
   const presence=await page.locator('.pf-hero .profile-presence').textContent();
   assert.equal(presence,['offline','dl'].includes(mode)?'Desconectado':mode==='unknown'?'Not available':'En línea');
   if(['online','rf'].includes(mode))assert.equal(await page.locator('.pf-hero .profile-presence.is-online').count(),1);
   if(mode==='unknown')assert.ok((await page.locator('.profile-details').textContent()).includes('Custom metric123'));
   assert.equal(await page.locator('.profile-back').count(),2);
   await common(page,width,mode);
   if(output&&['online','dl','rf','offline'].includes(mode)&&[1440,1024,390].includes(width))await page.screenshot({path:path.join(output,`profile-${mode}-${width}.png`),fullPage:true});
   assert.deepEqual(errors,[]);await page.close();console.log('PASS profile',mode,width);
  }
  for(const mode of ['guild','guild-nomark']){
   const {page,errors}=await open(browser,mode,width,height);
   assert.equal(await page.locator('h1.profile-name').textContent(),'FixtureGuild');
   if(mode==='guild'){assert.equal(await page.locator('.pf-hero-mark img').evaluate(i=>getComputedStyle(i).imageRendering),'pixelated');}
   else assert.match(await page.locator('.pf-hero-fig img').getAttribute('src'),/clase-dl-/,'Master class art when no mark');
   assert.deepEqual(await page.locator('.pf-member-name').allTextContents(),['Lordia','FixtureHero','Puños','Brujo','Novata','SinDatos']);
   assert.equal(await page.locator('.pf-member').first().locator('.pf-lead').textContent(),'Líder');
   assert.equal(await page.locator('a.pf-member').nth(1).getAttribute('href'),'/profile/player/FixtureHero/');
   assert.equal(await page.locator('.pf-member.is-online').count(),2);
   assert.equal(await page.locator('.pf-tile--online dd').textContent(),'2');
   assert.match(await page.locator('.pf-member-img').first().getAttribute('src'),/character-avatars\/dl\.jpg$/);
   await common(page,width,mode);
   if(output&&mode==='guild'&&[1440,1024,390].includes(width))await page.screenshot({path:path.join(output,`profile-guild-${width}.png`),fullPage:true});
   assert.deepEqual(errors,[]);await page.close();console.log('PASS profile',mode,width);
  }
  const {page,errors}=await open(browser,'error',width,height);
  assert.equal(await page.locator('h1').textContent(),'No encontramos este perfil');
  assert.ok((await page.locator('.pf-sub').textContent()).includes('could not be completed'));
  await common(page,width,'error');assert.deepEqual(errors,[]);await page.close();console.log('PASS profile error',width);
 }
 // Privacy: the public markup carries character names only (fixture account login never appears).
 for(const mode of ['online','dl','guild']){const html=render(mode);assert.ok(!/account|cuenta|login|AccountID|memb___id/i.test(html.replace(/<header[\s\S]*?<\/header>/,'')),'No account data in '+mode);}
 // Return to the category the visitor opened, preserving the encrypted CMS profile URL.
 const page=await browser.newPage();const html=render('online');
 await page.route('**/*',route=>{const u=new URL(route.request().url());if(u.pathname.startsWith('/templates/mupanic/')){const f=path.join(root,u.pathname.slice('/templates/mupanic/'.length));return fs.existsSync(f)?route.fulfill({path:f}):route.abort();}return route.fulfill({contentType:'text/html',body:u.pathname.startsWith('/rankings/')?'<a href="/profile/player/req/fixture/">Personaje</a>':html});});
 await page.goto('https://profile.test/rankings/duels/');await page.locator('a').click();
 for(const href of await page.locator('.profile-back').evaluateAll(a=>a.map(x=>x.href)))assert.equal(href,'https://profile.test/rankings/duels/');
 await page.locator('.profile-back').first().click();await page.waitForURL('**/rankings/duels/');
 await browser.close();console.log('PASS ranking return');
})().catch(e=>{console.error(e);process.exit(1)});
