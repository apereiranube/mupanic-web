const fs=require('fs'),path=require('path'),cp=require('child_process'),{chromium}=require('playwright');
const php=process.env.PHP_BIN||'php',fixture=path.join(__dirname,'fixtures/economy.php');
const render=(query,extra={})=>cp.execFileSync(php,['-n',fixture],{encoding:'utf8',env:{...process.env,ECO_QUERY:JSON.stringify({eco:'1',pruebas:'1',...query}),...extra}});
(async()=>{
 const browser=await chromium.launch({executablePath:process.env.CHROME_BIN,headless:true,args:['--no-sandbox']});
 for(const width of [1440,390].filter(w=>!process.env.ECO_TEST_WIDTH||w===Number(process.env.ECO_TEST_WIDTH)))for(const tab of ['resumen','juegos','vessara','tienda-vip','recargas','control'].filter(t=>!process.env.ECO_TEST_TAB||t===process.env.ECO_TEST_TAB)){
  const query={eco_tab:tab};const html=render(query);
  if(html.includes('No se pudieron consultar')||html.includes('Fatal error')||html.includes('Warning:'))throw Error('Render failed: '+tab);
  const errors=[];const page=await browser.newPage({viewport:{width,height:1000}});page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',async route=>{const u=new URL(route.request().url());if(u.pathname==='/beta/usercp/shopadmin/')return route.fulfill({contentType:'text/html',body:html});if(u.pathname==='/jquery.js')return route.fulfill({path:process.env.ECO_JQUERY_FIXTURE});const prefix='/beta/templates/mupanic/';if(u.pathname.startsWith(prefix)){const file=path.join(__dirname,'../overlay/templates/mupanic',u.pathname.slice(prefix.length));if(fs.existsSync(file))return route.fulfill({path:file});}return route.abort();});
  await page.goto('https://preview.test/beta/usercp/shopadmin/?eco=1',{waitUntil:'networkidle'});
  if(errors.length)throw Error(tab+': '+errors.join(';'));
  if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw Error('Overflow '+tab+' '+width);
  if(await page.locator('.eco-tabs a').count()!==6||!await page.locator('.eco-tabs a').last().textContent().then(x=>x==='Revisiones'))throw Error('Friendly tabs missing');
  if(await page.locator('.eco-detail[open]').count())throw Error('Details should start closed: '+tab);
  if(await page.locator('[data-eco-table]').evaluateAll(tables=>tables.some(t=>!t.closest('.eco-detail:not([open])'))))throw Error('Table visible before opening detail');
  if(await page.locator('.eco-story').evaluate(el=>parseFloat(getComputedStyle(el.querySelector('p')).fontSize))<18)throw Error('Story too small');
  if(width===390&&await page.locator('.eco-metric').evaluateAll(cards=>cards.filter(c=>!c.closest('details:not([open])')).some(c=>c.getBoundingClientRect().width<240)))throw Error('Metrics not one column');
  if(tab==='juegos'&&await page.locator('.eco-game-card').count()!==8)throw Error('Eight games missing');
  const help=page.locator('.eco-help-button:visible').first();await help.click();
  if(await help.getAttribute('aria-expanded')!=='true')throw Error('Tap help did not open');
  const tip=help.locator('..').locator('.eco-tooltip');if(!await tip.isVisible())throw Error('Help not visible');
  const bounds=await tip.boundingBox();if(bounds.x<0||bounds.x+bounds.width>width+1)throw Error('Help outside screen');await page.keyboard.press('Escape');await page.mouse.move(0,0);
  const displayedCharts=await page.locator('canvas').evaluateAll(cs=>cs.filter(c=>!c.closest('details:not([open])')).length);if(displayedCharts!==(tab==='resumen'||tab==='recargas'?1:0))throw Error('Too many main charts '+tab);
  await page.screenshot({path:'/tmp/panic-economy-friendly-'+tab+'-'+width+'.png',fullPage:true});
  await page.locator('.eco-detail').evaluateAll(details=>details.forEach(d=>d.open=true));await page.waitForTimeout(100);
  if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw Error('Detail overflow '+tab+' '+width);
  if(await page.locator('canvas').count()&&!(await page.evaluate(()=>[...document.querySelectorAll('canvas')].every(c=>!!Chart.getChart(c)))))throw Error('Lazy chart missing');
  if(await page.locator('[data-eco-table]').count()&&await page.locator('.dt-search').count()===0)throw Error('Lazy DataTables missing');
  if(tab==='resumen'){await page.locator('input[name="desde"]').fill('2026-10-01');await page.locator('input[name="desde"]').dispatchEvent('change');if(await page.locator('select[name="rango"]').inputValue()!=='personalizado')throw Error('Date did not activate custom range');}
  if(errors.length)throw Error(tab+': '+errors.join(';'));await page.close();console.log(tab+' '+width+'px: frases, detalles cerrados, ayudas, columna móvil y apertura de gráficos/tablas OK');
 }
 for(const tab of ['resumen','juegos','vessara','tienda-vip','recargas','control']){const empty=render({eco_tab:tab,pruebas:'0'},{ECO_EMPTY:'1'});if(empty.includes('No se pudieron consultar')||empty.includes('Fatal error')||empty.includes('Warning:'))throw Error('Empty render '+tab);if(tab!=='control'&&!empty.includes('Ver también mis pruebas'))throw Error('Empty state not actionable '+tab);}
 const game=render({eco_tab:'juegos',juego:'RULETA'});if(!game.includes('id="eco-game-detail"')||game.includes('No se pudieron consultar'))throw Error('Selected game detail failed');
 if(!render({}, {ECO_STALE:'1'}).includes('La carga de datos está atrasada'))throw Error('Stale warning missing');
 if(!render({}, {ECO_FAIL:'1'}).includes('No se pudo leer el estado'))throw Error('Query failure hidden');
 for(const user of ['guest','jugador']){const denied=render({}, {ECO_USER:user});if(!denied.includes('Acceso no autorizado')||denied.includes('Tu economía'))throw Error('Unauthorized access');}
 if(!render({}, {ECO_METHOD:'POST'}).includes('solo admite consultas'))throw Error('POST allowed');
 if(!render({eco_tab:'../config'}).includes('Filtro inválido'))throw Error('Path traversal');
 await browser.close();console.log('Vacíos amables, carga atrasada, errores, invitados, cuentas comunes, POST, rutas y carga en función del CMS OK');
})().catch(e=>{console.error(e);process.exit(1)});
