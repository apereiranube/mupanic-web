const fs=require('fs'),path=require('path'),cp=require('child_process'),{chromium}=require('playwright');
const php=process.env.PHP_BIN||'php',fixture=path.join(__dirname,'fixtures/economy.php');
const render=(query,extra={})=>cp.execFileSync(php,['-n',fixture],{encoding:'utf8',env:{...process.env,ECO_QUERY:JSON.stringify({eco:'1',pruebas:'1',...query}),...extra}});
(async()=>{
 const browser=await chromium.launch({executablePath:process.env.CHROME_BIN,headless:true,args:['--no-sandbox']});
 for(const width of [1440,390])for(const tab of ['resumen','juegos','vessara','tienda-vip','recargas','control']){
  const html=render({eco_tab:tab,juego:tab==='juegos'?'RULETA':'',nivel:tab==='control'?'REVISAR':''});
  if(html.includes('No se pudieron consultar')||html.includes('Fatal error'))throw Error('Render failed: '+tab);
  const errors=[];const page=await browser.newPage({viewport:{width,height:1000}});page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',async route=>{const u=new URL(route.request().url());if(u.pathname==='/beta/usercp/shopadmin/')return route.fulfill({contentType:'text/html',body:html});if(u.pathname==='/jquery.js')return route.fulfill({path:process.env.ECO_JQUERY_FIXTURE});const prefix='/beta/templates/mupanic/';if(u.pathname.startsWith(prefix)){const file=path.join(__dirname,'../overlay/templates/mupanic',u.pathname.slice(prefix.length));if(fs.existsSync(file))return route.fulfill({path:file});}return route.abort();});
  await page.goto('https://preview.test/beta/usercp/shopadmin/?eco=1',{waitUntil:'networkidle'});
  if(errors.length)throw Error(tab+': '+errors.join(';'));
  if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw Error('Overflow '+tab+' '+width);
  if(await page.locator('.eco-tabs a').count()!==6)throw Error('Tabs missing');
  const canvases=await page.locator('canvas').count();if(canvases&&!(await page.evaluate(()=>[...document.querySelectorAll('canvas')].every(c=>!!Chart.getChart(c)))))throw Error('Chart missing');
  if(await page.locator('[data-eco-table]').count()&&await page.locator('.dt-search').count()===0)throw Error('DataTables missing');
  if(tab==='resumen'){await page.locator('input[name="desde"]').fill('2026-10-01');await page.locator('input[name="desde"]').dispatchEvent('change');if(await page.locator('select[name="rango"]').inputValue()!=='personalizado')throw Error('Date did not activate custom range');}
  if(await page.locator('script').evaluateAll(nodes=>nodes.some(n=>n.textContent==='jugador<script>')))throw Error('XSS');
  await page.screenshot({path:'/tmp/panic-economy-'+tab+'-'+width+'.png',fullPage:true});await page.close();
  console.log(tab+' '+width+'px: tablas, gráficos, navegación y ancho OK');
 }
 for(const tab of ['resumen','juegos','vessara','tienda-vip','recargas','control']){const empty=render({eco_tab:tab},{ECO_EMPTY:'1'});if(empty.includes('No se pudieron consultar')||empty.includes('Fatal error')||!empty.includes('Sin datos en este período'))throw Error('Empty render '+tab);}
 if(!render({}, {ECO_STALE:'1'}).includes('La carga de datos está atrasada'))throw Error('Stale warning missing');
 if(!render({}, {ECO_FAIL:'1'}).includes('No se pudo leer el estado'))throw Error('Query failure hidden');
 for(const user of ['guest','jugador']){const denied=render({}, {ECO_USER:user});if(!denied.includes('Acceso no autorizado')||denied.includes('El pulso'))throw Error('Unauthorized access');}
 if(!render({}, {ECO_METHOD:'POST'}).includes('solo admite consultas'))throw Error('POST allowed');
 if(!render({eco_tab:'../config'}).includes('Filtro inválido'))throw Error('Path traversal');
 await browser.close();console.log('Vacíos, carga atrasada, errores, XSS, invitados, cuentas comunes, POST y rutas inválidas OK');
})().catch(e=>{console.error(e);process.exit(1)});
