const fs=require('fs'),path=require('path'),assert=require('node:assert/strict');
const {execFileSync}=require('child_process');const {chromium}=require('playwright');
const root=path.resolve(__dirname,'../overlay/templates/mupanic');
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 const output=process.env.RANKINGS_SCREENSHOT_DIR;if(output)fs.mkdirSync(output,{recursive:true});
 for(const [width,height] of [[1920,1080],[1366,768],[1024,768],[390,844]]){
  for(const mode of ['level','killers','guilds','master','resets','grandresets','bloodcastle','devilsquare','duels','empty']){
   const total=mode==='killers'?1:45;
   const titles={level:'Nivel',killers:'Asesinatos',guilds:'Guilds',master:'Nivel Master',resets:'Resets',grandresets:'Master Resets',bloodcastle:'Blood Castle',devilsquare:'Devil Square',duels:'Duelos'};
   const scores={level:'Nivel',killers:'Asesinatos',guilds:'Puntos',master:'Nivel Master',resets:'Resets',grandresets:'Master Resets',bloodcastle:'Puntos',devilsquare:'Puntos',duels:'Victorias'};
   const html=execFileSync(process.env.PHP_BIN||'php',[path.join(__dirname,'fixtures/rankings.php')],{encoding:'utf8',env:{...process.env,RANKINGS_FIXTURE:mode}});
   const page=await browser.newPage({viewport:{width,height},reducedMotion:'reduce'});const errors=[];
   page.on('pageerror',e=>errors.push(e.message));
   await page.route('**/*',async route=>{
    const pathname=new URL(route.request().url()).pathname;
    if(pathname.startsWith('/status/')) return route.fulfill({contentType:'image/svg+xml',body:'<svg xmlns="http://www.w3.org/2000/svg" width="9" height="9"><circle cx="4" cy="4" r="4" fill="green"/></svg>'});
    if(pathname.startsWith('/rankings/'))return route.fulfill({contentType:'text/html',body:html});
    if(pathname.startsWith('/templates/mupanic/')){
     const file=path.join(root,pathname.slice('/templates/mupanic/'.length));if(fs.existsSync(file))return route.fulfill({path:file});
    }
    errors.push('Missing asset: '+pathname);return route.abort();
   });
   await page.goto('https://ranking.test/rankings/'+(mode==='empty'?'bloodcastle':mode)+'/');await page.evaluate(()=>document.fonts.ready);
   assert.equal(await page.locator('.inner-hero').count(),0,'Old cropped hero removed');
   assert.equal(await page.locator('h1').count(),1);
   const metrics=await page.evaluate(()=>({overflow:document.documentElement.scrollWidth>innerWidth,hero:document.querySelector('.rankings-hero').getBoundingClientRect().height,title:document.querySelector('h1').getBoundingClientRect().top,header:document.querySelector('.site-header').getBoundingClientRect().bottom}));
   assert.equal(metrics.overflow,false,'Page horizontal overflow');assert.ok(metrics.title>=metrics.header,'Heading covered by header');
   assert.ok(metrics.hero<(width>700?440:450),'Hero is compact: '+JSON.stringify(metrics));
   assert.equal(await page.locator('.rankings_menu a').count(),9);assert.equal(await page.locator('[aria-current=page]').count(),2);
   assert.equal(await page.locator('#rankings-results').count(),1);
   if(mode!=='empty'){
    assert.equal(await page.locator('.rankings-leader').count(),Math.min(3,total));
    assert.equal(await page.locator('.rankings-identity').count(),total);
    assert.equal(await page.locator('.rankings-place-medal').count(),total);
    assert.equal(await page.locator('.rankings-profile-link').count(),total);
    assert.equal(await page.locator('.rankings-score-value').first().textContent(),'599');
    assert.equal(await page.locator('.rankings-identity-name a').first().getAttribute('href'),mode==='guilds'?'/profile/guild/Guild1/':'/profile/player/Hero1/');
    if(width<=700) assert.equal(await page.locator('.rankings-table tr[data-rank-position]').first().evaluate(el=>getComputedStyle(el).display),'grid','Mobile roster cards');
    assert.equal(await page.locator('.rankings-table tr[data-rank-position]:visible').count(),Math.min(20,total));
    assert.equal(await page.locator('.rankings-leader').first().locator('.rankings-leader-name').textContent(),mode==='guilds'?'Guild1':'Hero1');
    assert.equal(await page.locator('.rankings-leader-score').first().textContent(),scores[mode]+': 599');
    assert.equal(await page.locator('.rankings-stage h2').textContent(),titles[mode]);
    assert.equal(await page.locator('.rankings-leader-name').first().getAttribute('target'),null);
    if(total>20){
    await page.getByRole('searchbox').fill(mode==='guilds'?'Guild45':'Hero45');
    assert.equal(await page.locator('.rankings-table tr[data-rank-position]:visible .rankings-table-place').textContent(),'45');
    await page.locator('.rankings-inspect').first().click();assert.equal(await page.locator('.rankings-row-focus .rankings-table-place').textContent(),'1');
    assert.equal(await page.getByRole('searchbox').inputValue(),'');
    if(mode==='level'){
     await page.getByRole('button',{name:'Guerreros',exact:true}).click();assert.equal(await page.locator('.rankings-table tr[data-rank-position="2"]').isVisible(),false);
     await page.getByRole('button',{name:'Todas',exact:true}).click();
    }
    await page.getByRole('button',{name:'Siguiente →'}).click();assert.equal(await page.locator('.rankings-table tr[data-rank-position]:visible .rankings-table-place').first().textContent(),'21');
    await page.getByRole('button',{name:'← Anterior'}).click();
    }
    if(mode!=='guilds') assert.equal(await page.locator('.rankings-presence.is-online').first().textContent(),'En línea');
    if(mode==='killers'){assert.equal(await page.locator('.rankings-detail-cell[data-label="Estado PK"]').textContent(),'Warning');assert.equal(await page.locator('.rankings-detail-cell[data-label="Nivel"]').textContent(),'7');}
    if(mode==='duels') assert.equal(await page.locator('.rankings-detail-cell[data-label="Derrotas"]').first().textContent(),'44');
    await page.getByRole('searchbox').fill('no-such-name');assert.equal(await page.locator('.rankings-empty').isVisible(),true);await page.getByRole('searchbox').fill('');
   }
   await page.evaluate(()=>window.scrollTo({top:0,behavior:'instant'}));
   if(output&&['level','killers','guilds','duels','empty'].includes(mode)&&(width===1920||width===390))await page.screenshot({path:path.join(output,`${mode}-${width}.png`),fullPage:true});
   if(output&&mode==='level'&&(width===1920||width===390)){await page.locator('#rankings-results').scrollIntoViewIfNeeded();await page.screenshot({path:path.join(output,`roster-${width}.png`)});}
   assert.deepEqual(errors,[]);await page.close();console.log(`PASS ${width}x${height} ${mode}: layout, data, filters, navigation and assets.`);
  }
 }
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
