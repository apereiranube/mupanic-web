const vm=require('node:vm'),fs=require('node:fs'),assert=require('node:assert/strict');
function el(text=''){const c=new Set();return{textContent:text,hidden:true,style:{},events:{},classList:{toggle(k,v){if(v)c.add(k);else c.delete(k)},contains(k){return c.has(k)}},addEventListener(k,v){this.events[k]=v},setAttribute(){},animate(){}}}
const nodes=new Map();function node(s){if(!nodes.has(s))nodes.set(s,el());return nodes.get(s)}
const points=[1,2].map(id=>node('[data-fractura-points="'+id+'"]'));points[0].textContent='230';points[1].textContent='100';
const root=el(),scene=el(),canvas=el(),depth=[el(),el()];depth[0].dataset={fracturaDepth:'-1'};depth[1].dataset={fracturaDepth:'1'};
scene.getBoundingClientRect=()=>({width:1200,height:800,left:0,top:0});let draws=0;
const paint={setTransform(){},clearRect(){},beginPath(){},arc(){draws++},fill(){}};canvas.getContext=()=>paint;
root.dataset={fracturaUrl:'/beta/fractura/?fractura_data=1'};
root.querySelector=s=>s==='[data-fractura-scene]'?scene:s==='[data-fractura-particles]'?canvas:node(s);
root.querySelectorAll=s=>s==='[data-fractura-points]'?points:s==='[data-fractura-depth]'?depth:[];
const document={hidden:false,querySelector:()=>root,events:{},addEventListener(k,v){this.events[k]=v}};
let mode='normal',families={1:{points:'9007199254740993',members:'1'},2:{points:'12000',members:'3'}},refresh;
const frames=new Map();let next=0;const reduced={matches:false,addEventListener(k,v){this.changed=v}};
const context={document,window:{devicePixelRatio:1,matchMedia:()=>reduced,addEventListener(){}},localStorage:{getItem:()=>null,setItem(){}},performance:{now:()=>0},Intl,AbortController,setTimeout,clearTimeout,setInterval:cb=>{refresh=cb;return 1},clearInterval(){},requestAnimationFrame:cb=>{const id=++next;frames.set(id,cb);return id},cancelAnimationFrame:id=>frames.delete(id),fetch:async()=>({ok:mode!=='failure',json:async()=>({schema:1,updated:Math.floor(Date.now()/1000),stale:mode==='stale',factions:families})})};
vm.runInNewContext(fs.readFileSync(__dirname+'/../overlay/templates/mupanic/js/fractura.js','utf8'),context);
(async()=>{
 assert.equal(frames.size,1,'canvas starts');const [id,cb]=frames.entries().next().value;frames.delete(id);cb(16);assert.ok(draws>30,'visible particles draw');
 node('[data-fractura-effects]').events.click();assert.ok(root.classList.contains('effects-paused'));assert.equal(frames.size,0,'pause cancels drawing loop');
 scene.events.pointermove({clientX:1000,pointerType:'mouse'});assert.equal(depth[0].style.transform,undefined,'parallax frozen');
 node('[data-fractura-effects]').events.click();assert.equal(frames.size,1,'resume restarts drawing');scene.events.pointermove({clientX:1000,pointerType:'mouse'});assert.match(depth[0].style.transform,/translateX/);
 await refresh();assert.equal(points[0].textContent,'9.007.199.254.740.993');assert.equal(points[1].textContent,'12.000');assert.equal(node('[data-fractura-lead]').textContent,'Nexo lleva la delantera');assert.equal(node('[data-fractura-status]').hidden,true,'healthy score has no technical status');
 mode='failure';await refresh();assert.equal(points[0].textContent,'9.007.199.254.740.993');assert.equal(node('[data-fractura-status]').hidden,false);
 mode='normal';families={1:{points:'10',members:'1'},2:{points:'30',members:'2'}};await refresh();assert.equal(node('[data-fractura-lead]').textContent,'Caos lleva la delantera');assert.equal(node('[data-fractura-share="2"]').textContent,'75%');
 mode='stale';await refresh();assert.equal(node('[data-fractura-status]').hidden,false);assert.doesNotMatch(node('[data-fractura-status]').textContent,/consulta|Argentina|2026/);
 reduced.matches=true;reduced.changed();assert.equal(frames.size,0,'reduced motion stops canvas');
 console.log('PASS: visible particle draw, full pause/resume, parallax freeze, reduced motion, native score mapping, exact totals and hidden healthy status.');
})().catch(e=>{console.error(e);process.exitCode=1});
