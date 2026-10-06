const fs=require('fs'),path=require('path'),assert=require('node:assert/strict');
const {execFileSync}=require('child_process'),{chromium}=require('playwright');
const root=path.resolve(__dirname,'../overlay/templates/mupanic');
const html=execFileSync(process.env.PHP_BIN||'php',[path.join(__dirname,'fixtures/atlas-rewards.php')],{encoding:'utf8',maxBuffer:16e6});
const output=process.env.ATLAS_SCREENSHOT_DIR;
const search=require('../overlay/templates/mupanic/js/atlas-search.js');
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 for(const [width,height] of [[1920,1080],[1366,768],[1024,768],[390,844],[320,700]]){
  const page=await browser.newPage({viewport:{width,height},reducedMotion:'reduce'}),errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/*',route=>{const n=new URL(route.request().url()).pathname;if(n==='/info/')return route.fulfill({contentType:'text/html',body:html});if(n.startsWith('/templates/mupanic/')){const p=path.join(root,n.slice('/templates/mupanic/'.length));if(fs.existsSync(p))return route.fulfill({path:p});}errors.push('Missing '+n);return route.abort();});
  await page.goto('https://atlas.test/info/#drops');await page.evaluate(()=>document.fonts.ready);
  const drops=page.locator('#drops'),data=JSON.parse(await page.locator('#wiki-data').textContent());
  const cards=drops.locator('[data-drop-card]');assert.equal(await cards.count(),30);assert.equal(await cards.locator('img').count(),30);assert.equal(await drops.locator('[data-drop-row]').count(),82);assert.equal(await drops.locator('table').count(),0);
  await cards.locator('img').evaluateAll(async imgs=>Promise.all(imgs.map(img=>img.decode())));
  async function overflow(){assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Horizontal overflow at '+width);}
  async function shot(name){if(output&&[1920,390].includes(width)){fs.mkdirSync(output,{recursive:true});await page.screenshot({path:path.join(output,name+'-'+width+'.png')});}}
  await overflow();await shot('atlas-drops-catalogue');
  if(width===1920){
   for(const key of await cards.evaluateAll(cs=>cs.map(c=>c.getAttribute('data-drop-card')))){
    await drops.locator('[data-drop-card="'+key+'"]').click();assert.equal(await drops.locator('[data-drop-item]:visible').getAttribute('data-drop-item'),key);assert.equal(await drops.locator('[data-drop-catalogue]').isVisible(),false);assert.ok(await drops.locator('[data-drop-item]:visible [data-drop-row]:visible').count());await drops.locator('[data-drop-back]:visible').click();
   }
  }
  await drops.locator('[data-drop-category=joyas]').click();assert.equal(await cards.filter({visible:true}).count(),6);
  await drops.locator('[data-drop-card="6159-0"]').click();const chaos=drops.locator('#drop-item-6159-0');await chaos.waitFor({state:'visible'});assert.equal(await chaos.isVisible(),true);assert.equal(await chaos.locator('[data-drop-row]:visible').count(),3);
  assert.deepEqual(await chaos.locator('[data-drop-rate]').allTextContents(),['0,3%','0,008%','0,035%']);
  await drops.locator('[data-drop-account]').selectOption('1');assert.ok((await chaos.locator('[data-account-name]').allTextContents()).every(n=>n==='VIP'));
  await chaos.locator('#drop-0 [data-drop-routes]').click();const route=chaos.locator('#drop-routes-0');assert.equal(await route.isVisible(),true);assert.deepEqual(await route.locator('[data-drop-route-map] option').allTextContents(),['Dungeon']);
  const dungeon=data.maps.find(m=>m.id===1),eligible=dungeon.monsters.filter(m=>search.compatible(data.drops[0],dungeon,m));assert.ok((await route.locator('.atlas-drop-mob').count())<=6);assert.ok((await route.locator('.atlas-drop-mob strong').allTextContents()).every(n=>eligible.some(m=>m.name===n)));
  await overflow();await shot('atlas-drops-object');if(output&&[1920,390].includes(width))await route.locator('.atlas-drop-mob-grid').screenshot({path:path.join(output,'atlas-drops-mobs-'+width+'.png')});
  await route.locator('button',{}).filter({hasText:'Cerrar'}).click();assert.equal(await route.isVisible(),false);assert.equal(await chaos.locator('#drop-0 [data-drop-routes]').getAttribute('aria-expanded'),'false');
  // General rules list actual compatible maps and paginate mobs instead of creating a huge list.
  await chaos.locator('#drop-1 [data-drop-routes]').click();const general=chaos.locator('#drop-routes-1');const options=await general.locator('[data-drop-route-map] option').evaluateAll(os=>os.map(o=>Number(o.value)));assert.deepEqual(options,data.maps.filter(m=>m.monsters.some(mob=>search.compatible(data.drops[1],m,mob))).map(m=>m.id));
  await general.locator('[data-drop-route-map]').selectOption('1');if(await general.getByRole('button',{name:'Siguiente →'}).isEnabled()){await general.getByRole('button',{name:'Siguiente →'}).click();assert.ok((await general.locator('.atlas-drop-pager span').textContent()).startsWith('7–'));assert.equal(await general.locator('.atlas-drop-pager button').evaluateAll(bs=>bs.some(b=>!b.disabled&&b===document.activeElement)),true,'Paging keeps focus on an enabled navigation button');}
  const mobLink=general.locator('.atlas-drop-mob>a').first(),hash=await mobLink.getAttribute('href');await mobLink.click();await page.locator(hash).waitFor({state:'visible'});assert.equal(await page.locator(hash).isVisible(),true,'Mob link opens correct map card');
  await page.goto('https://atlas.test/info/#drop-21');const bone=drops.locator('#drop-item-6673');await bone.waitFor({state:'visible'});assert.equal(await bone.isVisible(),true);assert.equal(await bone.locator('[data-drop-row]:visible').count(),8);assert.equal(await drops.locator('#drop-21').isVisible(),true,'Legacy indexed rule deep link survives');
  await bone.locator('[data-drop-variant]').selectOption('7');assert.equal(await bone.locator('[data-drop-row]:visible').count(),1);assert.ok((await bone.locator('[data-drop-row]:visible').textContent()).includes('85–101'));await overflow();await shot('atlas-drops-variant');
  await drops.locator('[data-drop-back]:visible').click();await drops.locator('[data-drop-filter]').fill('Blood Bone 8');assert.equal(await cards.filter({visible:true}).count(),1);await drops.locator('[data-drop-card="6673"]').click();await bone.waitFor({state:'visible'});assert.equal(await bone.locator('[data-drop-row]:visible').count(),1,'Numeric search matches original rule names');
  await drops.locator('[data-drop-reset]').first().click();await drops.locator('[data-drop-category=alas]').click();assert.equal(await cards.filter({visible:true}).count(),3,'Feather, Crest and Flame remain distinct');
  await drops.locator('[data-drop-reset]').first().click();await drops.locator('[data-drop-map]').selectOption('2');await drops.locator('[data-drop-monster]').selectOption('25');const expected=data.drops.filter(d=>data.maps.find(m=>m.id===2).monsters.some(m=>m.id===25&&search.compatible(d,data.maps.find(m=>m.id===2),m))).length;assert.equal(await drops.locator('[data-drop-row]:not([hidden])').count(),expected,'Map/mob filters use levels of the actual population');
  await drops.locator('[data-drop-filter]').fill('no existe este objeto');assert.equal(await drops.locator('[data-drop-empty]').isVisible(),true);await drops.locator('[data-drop-empty] [data-drop-reset]').click();assert.equal(await cards.filter({visible:true}).count(),30);await overflow();
  await drops.locator('[data-drop-card="6159-0"]').focus();await page.keyboard.press('Enter');await chaos.waitFor({state:'visible'});assert.equal(await chaos.isVisible(),true);assert.equal(await chaos.locator('h3').evaluate(el=>document.activeElement===el),true,'Keyboard navigation focuses item heading');
  assert.deepEqual(errors,[]);await page.close();console.log('PASS drop catalogue',width);
 }
 // Distinct synthetic VIP rate proves index selection even while this server uses equal rates.
 const changed=html.replace('"rates":[0.3,0.3,0.3,0.3]','"rates":[0.3,0.77,0.3,0.3]');assert.notEqual(changed,html);
 const p=await browser.newPage();await p.route('**/*',r=>{const n=new URL(r.request().url()).pathname;if(n==='/info/')return r.fulfill({contentType:'text/html',body:changed});return r.fulfill({path:path.join(root,n.slice('/templates/mupanic/'.length))});});await p.goto('https://atlas.test/info/#drop-0');await p.locator('[data-drop-account]').selectOption('1');assert.equal(await p.locator('#drop-0 [data-drop-rate]').textContent(),'0,77%');await p.close();
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
