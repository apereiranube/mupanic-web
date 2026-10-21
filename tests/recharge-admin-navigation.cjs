'use strict';
const fs=require('fs'),path=require('path'),assert=require('node:assert/strict'),{execFileSync}=require('child_process'),{chromium}=require('playwright');
const render=(query)=>{
 const view={inicio:'home',compras:'purchases',transferencias:'bank',configuracion:'settings',economia:'economy',referidos:'referrals'}[query.vista]||(query.eco?'economy':query.ref?'referrals':'home');
 const html=execFileSync(process.env.PHP_BIN||'php',['tests/fixtures/recharge-admin.php'],{encoding:'utf8',env:{...process.env,RECHARGE_ADMIN_VIEW:view,RECHARGE_ADMIN_GET:JSON.stringify(query)}});
 assert(!/Warning:|Fatal error:/.test(html),'PHP render must not emit warnings');return html;
};
(async()=>{
 const b=await chromium.launch({headless:true,args:['--no-sandbox'],...(process.env.CHROME_BIN?{executablePath:process.env.CHROME_BIN}:{})});
 for(const width of [1440,390,320]){
  const page=await b.newPage({viewport:{width,height:1000}}),errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',r=>{const u=new URL(r.request().url());if(u.pathname==='/beta/usercp/shopadmin/')return r.fulfill({contentType:'text/html',body:render(Object.fromEntries(u.searchParams))});const prefix='/beta/templates/mupanic/';if(u.pathname.startsWith(prefix)){const f=path.resolve('overlay/templates/mupanic',u.pathname.slice(prefix.length));if(fs.existsSync(f))return r.fulfill({path:f});}return r.abort();});
  await page.goto('https://preview.test/beta/usercp/shopadmin/',{waitUntil:'networkidle'});
  assert.deepEqual(await page.locator('.admin-console-menu a').allTextContents(),['Inicio','Transferencias por aprobar','Compras','Economía','Referidos','Configuración']);
  assert.equal(await page.locator('.admin-console-menu [aria-current]').textContent(),'Inicio');
  assert.equal(await page.locator('.admin-overview-metric').count(),4);
  await page.screenshot({path:'/tmp/admin-v3-Inicio-'+width+'.png'});
  for(const [label,selector] of [['Transferencias por aprobar','.bank-review-page'],['Compras','.admin-console-content'],['Configuración','#shop-catalogue'],['Economía','.eco'],['Referidos','#referidos-admin']]){
   await page.locator('.admin-console-menu a').getByText(label,{exact:true}).click();await page.waitForLoadState('networkidle');
   assert.equal(await page.locator('.admin-console-menu [aria-current]').textContent(),label);assert.equal(await page.locator(selector).count(),1);
   if(label==='Transferencias por aprobar'){assert.equal(await page.locator('.recharge-order').count(),1);assert.equal(await page.locator('[name="bank[holder]"]').count(),0);}
   if(label==='Compras'){
    assert.equal(await page.locator('[data-order-provider=bank_transfer]').count(),3);assert.equal(await page.locator('[data-order-provider=uala_bis]').count(),1);
    assert.equal(await page.locator('[data-order-provider=bank_transfer][data-order-state=approved]').count(),1);
    assert.equal(await page.locator('[data-order-provider=bank_transfer][data-order-state=credited]').count(),1);
    await page.locator('[name=medio]').selectOption('bank_transfer');await page.locator('[name=estado]').selectOption('credited');await page.getByRole('button',{name:'Aplicar filtros'}).click();await page.waitForLoadState('networkidle');
    assert.equal(await page.locator('.recharge-order').count(),1);assert.equal(await page.locator('[data-order-provider=bank_transfer][data-order-state=credited]').count(),1);
    assert.equal(await page.locator('[name="bank[holder]"]').count(),0);
   }
   if(label==='Configuración'){assert.equal(await page.locator('[name="bank[holder]"]').count(),1);assert.equal(await page.locator('[data-order-provider]').count(),0);}
   assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),true,label+' overflow at '+width);
   if(['Compras','Configuración'].includes(label))await page.screenshot({path:'/tmp/admin-v3-'+label.normalize('NFD').replace(/[\u0300-\u036f]/g,'')+'-'+width+'.png'});
  }
  assert.deepEqual(errors,[]);console.log(width+'px: six separate screens, approved/credited bank history, mixed purchases and filters passed.');await page.close();
 }
 await b.close();
})().catch(e=>{console.error(e);process.exitCode=1});
