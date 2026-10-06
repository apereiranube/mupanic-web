const fs=require('fs'),path=require('path'),assert=require('node:assert/strict');
const {execFileSync}=require('child_process');const {chromium}=require('playwright');
const root=path.resolve(__dirname,'../overlay/templates/mupanic');
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 const output=process.env.RANKINGS_SCREENSHOT_DIR;if(output)fs.mkdirSync(output,{recursive:true});
 for(const [width,height] of [[1920,1080],[1366,768],[1024,768],[390,844]]){
  for(const mode of ['level','guilds','empty']){
   const html=execFileSync(process.env.PHP_BIN||'php',[path.join(__dirname,'fixtures/rankings.php')],{encoding:'utf8',env:{...process.env,RANKINGS_FIXTURE:mode}});
   const page=await browser.newPage({viewport:{width,height}});const errors=[];
   page.on('pageerror',e=>errors.push(e.message));
   await page.route('**/*',async route=>{
    const pathname=new URL(route.request().url()).pathname;
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
    assert.equal(await page.locator('.rankings-leader').count(),3);
    assert.equal(await page.locator('.rankings-table tr[data-rank-position]:visible').count(),20);
    assert.equal(await page.locator('.rankings-leader').first().locator('.rankings-leader-name').textContent(),mode==='guilds'?'Guild1':'Hero1');
    assert.equal(await page.locator('.rankings-leader-score').first().textContent(),mode==='guilds'?'Puntos: 599':'Nivel: 599');
    assert.equal(await page.locator('.rankings-stage h2').textContent(),mode==='guilds'?'Guilds':'Nivel');
    assert.equal(await page.locator('.rankings-leader-name').first().getAttribute('target'),null);
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
    await page.getByRole('searchbox').fill('no-such-name');assert.equal(await page.locator('.rankings-empty').isVisible(),true);await page.getByRole('searchbox').fill('');
   }
   await page.evaluate(()=>window.scrollTo(0,0));
   if(output&&(width===1920||width===390))await page.screenshot({path:path.join(output,`${mode}-${width}.png`),fullPage:true});
   assert.deepEqual(errors,[]);await page.close();console.log(`PASS ${width}x${height} ${mode}: layout, data, filters, navigation and assets.`);
  }
 }
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
