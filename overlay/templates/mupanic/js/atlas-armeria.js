(function(){'use strict';
var root=document.getElementById('armeria'),src=document.getElementById('arm-data');if(!root||!src)return;
var D;try{D=JSON.parse(src.textContent)}catch(e){return}
var ART=JSON.parse(root.getAttribute('data-art')),ITEMS=root.getAttribute('data-items');
const CLASSES=[
 {k:'DK',id:'dk',n:'Dark Knight',s:'BK',img:'bk',c:'#29c8ff',evo:'Blade Knight'},
 {k:'DW',id:'dw',n:'Dark Wizard',s:'SM',img:'sm',c:'#9b6bff',evo:'Soul Master'},
 {k:'FE',id:'elfa',n:'Elfa',s:'Elfa',img:'elfa',c:'#36e07f',evo:'Muse Elf'},
 {k:'MG',id:'mg',n:'Magic Gladiator',s:'MG',img:'mg',c:'#ff3fa4',evo:''},
 {k:'DL',id:'dl',n:'Dark Lord',s:'DL',img:'dl',c:'#ff4655',evo:''},
 {k:'SU',id:'su',n:'Summoner',s:'SU',img:'su',c:'#c58bff',evo:'Bloody Summoner'},
 {k:'RF',id:'rf',n:'Rage Fighter',s:'RF',img:'rf',c:'#ff7a1a',evo:''}
];
const TIERS=[{n:'Inicial',max:39,c:'#b9c3cb'},{n:'Medio',max:79,c:'#6fb4ff'},{n:'Alto',max:119,c:'#ffc24a'},{n:'Endgame',max:9999,c:'#ff4655'}];
const MAGIC={'Bastones':1,'Libros':1};
const tierOf=lv=>TIERS.find(t=>lv<=t.max);
const SVG={7:'<svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 18a10 10 0 0 1 20 0v6h-6v-5h-8v5H6z"/></svg>',8:'<svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5l5 3 5-3 6 4-2 6-2-1v13H9V14l-2 1-2-6z"/></svg>',9:'<svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5h14l2 22h-6l-3-14-3 14H7z"/></svg>',10:'<svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 27V14l-2-4 2-1 3 4V5h2v8V4h2v9V5h2v9l2-5 2 1-3 8v9z"/></svg>',11:'<svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 4h8v14l8 4v5H6v-5l4-2z"/></svg>'};
const $=id=>document.getElementById('arm-'+id), esc=s=>String(s).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const img=(k,i)=>ITEMS+k+'-'+i+'.webp';
let cls=CLASSES[0], mode='sets', fam=null, list=[], sel=0;
CLASSES.forEach(c=>{const b=document.createElement('button');b.className='arm-pick';b.type='button';b.setAttribute('role','tab');b.id='arm-p-'+c.id;b.style.setProperty('--c',c.c);
  b.innerHTML='<img src="'+ART[c.img]+'" alt="" loading="lazy"><span><b class="arm-ln">'+c.n+'</b><b class="arm-sn">'+c.s+'</b></span>';b.onclick=()=>{cls=c;sel=0;fam=null;try{history.replaceState(null,'','#armeria-'+c.id)}catch(e){}build()};$('picker').appendChild(b)});
function famsFor(c){const f=[];D.weap.forEach(w=>{const x=w.fm[c.k];if(x&&!f.includes(x))f.push(x)});const order=['Espadas','Hachas','Mazas','Lanzas','Arcos y ballestas','Bastones','Libros','Cetros','Guantes de combate'];return f.sort((a,b)=>order.indexOf(a)-order.indexOf(b))}
const val=x=>mode==='sets'?x.def:(MAGIC[fam]?x.mg:Math.round((x.mn+x.mx)/2));
function items(){
  const c=cls;
  if(mode==='sets')return D.sets.filter(s=>s.c[c.k]&&(c.k!=='MG'||!(s.c.DW||s.c.DK))).filter(s=>$('show380').checked||!s.r380).sort((a,b)=>a.lv-b.lv||a.def-b.def);
  return D.weap.filter(w=>w.fm[c.k]===fam).sort((a,b)=>a.lv-b.lv||val(a)-val(b));
}
function count(el,to){const from=parseInt(el.dataset.v||'0',10);el.dataset.v=to;if(matchMedia('(prefers-reduced-motion: reduce)').matches){el.textContent=to;return}const t0=performance.now();(function f(t){const k=Math.min(1,(t-t0)/500),e=1-Math.pow(1-k,3);el.textContent=Math.round(from+(to-from)*e);if(k<1)requestAnimationFrame(f)})(t0)}
function build(){
  const c=cls; document.documentElement.style.setProperty('--cls',c.c);
  CLASSES.forEach(x=>$('p-'+x.id).setAttribute('data-on',x===c?'true':'false'));
  $('clsWord').textContent=c.n; $('clsArt').textContent=c.k==='FE'||c.k==='SU'?'de la':'del'; $('heroWord').textContent=c.s;
  $('hWhat').textContent=mode==='sets'?'Sets':'Armas';
  $('mSets').setAttribute('data-on',mode==='sets');$('mWeap').setAttribute('data-on',mode==='weap');
  const hi=$('heroImg'); if(hi.getAttribute('src')!==ART[c.img]){hi.classList.add('arm-out');setTimeout(()=>{hi.src=ART[c.img];hi.onload=()=>hi.classList.remove('arm-out')},220)}
  $('mgNote').hidden=!(c.k==='MG'&&mode==='sets'); $('opt380').hidden=mode!=='sets';
  const F=famsFor(c); if(mode==='weap'){if(!F.includes(fam))fam=F[0];$('fams').hidden=F.length<2;$('fams').innerHTML=F.map(f=>'<button type="button" data-on="'+(f===fam)+'" data-f="'+esc(f)+'">'+esc(f)+'</button>').join('');[...$('fams').children].forEach(b=>b.onclick=()=>{fam=b.dataset.f;sel=0;build()})}else $('fams').hidden=true;
  list=items(); const max=Math.max(...list.map(val),1);
  const st=$('stairs'); st.innerHTML='';
  list.forEach((s,i)=>{const b=document.createElement('button');b.type='button';b.className='arm-step';b.style.setProperty('--t',tierOf(s.lv).c);b.style.setProperty('--h',Math.max(8,val(s)/max*86)+'%');b.setAttribute('aria-label',(i+1)+'. '+s.n);b.innerHTML='<i></i><b>'+(i+1)+'</b>';b.onclick=()=>pick(i);st.appendChild(b)});
  const tr=$('tiers'); tr.innerHTML=''; TIERS.forEach((t,ti)=>{const min=ti?TIERS[ti-1].max+1:0,n=list.filter(s=>s.lv>=min&&s.lv<=t.max).length;if(!n)return;const d=document.createElement('div');d.style.setProperty('--t',t.c);d.style.setProperty('--n',n);d.textContent=t.n;tr.appendChild(d)});
  const what=mode==='sets'?'sets':(fam||'armas').toLowerCase(), metric=mode==='sets'?'defensa':(MAGIC[fam]?'poder mágico':'daño');
  $('stairsSub').textContent=list.length+' '+what+', de '+(list[0]?list[0].n:'')+' a '+(list[list.length-1]?list[list.length-1].n:'')+'. Más alta la columna, más '+metric+'.';
  $('foot').textContent=mode==='sets'?'La defensa es la del set completo en +0, sin opciones excelentes. Al subir cada pieza (+1, +2…) suben la defensa y los requisitos de Fuerza y Agilidad. Los sets de jefes piden nivel 380.':'El daño es el del arma en +0, sin opciones excelentes. Al subirla (+1, +2…) sube el daño y también los requisitos. En bastones y libros, el poder mágico suma porcentaje al daño de tus skills.';
  $('scrub').max=Math.max(0,list.length-1); pick(Math.min(sel,list.length-1));
}
function ancHTML(ids,mineSec,mineIdx){
  if(!ids||!ids.length)return '';
  return '<div class="arm-anc-head"><h3>Versiones Ancient</h3><p>Ancient es la versión dorada de las piezas. Cuantas más piezas del mismo Ancient llevás puestas, más bonus suma.</p></div><div class="arm-anc">'+ids.map(id=>{const a=D.anc[id];if(!a)return'';
    const it=a.it.map(x=>'<span'+(x[1]===mineIdx&&(mineSec==null?x[0]>=7&&x[0]<=11:x[0]===mineSec)?' class="arm-mine"':'')+'>'+esc(x[2])+'</span>').join('');
    const lad=a.b.map(b=>'<li><b>'+b[0]+' piezas</b><span>'+esc(b[1].join(' · '))+'</span></li>').join('')+(a.f.length?'<li class="arm-full"><b>Completo</b><span>'+esc(a.f.join(' · '))+'</span></li>':'');
    return '<div class="arm-ancard"><div class="arm-ancard-l"><h4>'+esc(a.n)+'</h4><small>Piezas del Ancient ('+a.it.length+')</small><div class="arm-anc-items">'+it+'</div></div><ol class="arm-ladder">'+lad+'</ol></div>'}).join('')+'</div>';
}
var IDX=null,WD=null;
function srcIndex(){
  if(IDX!==null)return IDX; IDX=false;
  try{var el=document.getElementById('wiki-data');if(el&&window.PanicAtlasSearch){WD=JSON.parse(el.textContent);var m=new Map();window.PanicAtlasSearch.build(WD).forEach(function(e){if(e.type==='object')m.set(e.id,e)});IDX=m}}catch(e){IDX=false}
  return IDX;
}
function sources(ids){
  var ix=srcIndex(); if(!ix)return null;
  var maps=new Map(),bosses=new Map(),boxes=new Map();
  ids.forEach(function(id){var o=ix.get(id);if(!o)return;o.routes.forEach(function(r){
    var h=String(r.hash||'');
    if(r.kind==='drop'&&h.indexOf('#drop-')===0){var drop=WD.drops[+h.slice(6)];(r.maps||[]).forEach(function(mid){var map=WD.maps.find(function(x){return x.id===mid});if(!map||!drop)return;var e=maps.get(mid);if(!e){e={map:map,mobs:new Map()};maps.set(mid,e)}map.monsters.forEach(function(mob){if(window.PanicAtlasSearch.compatible(drop,map,mob))e.mobs.set(mob.id,mob)})})}
    else if(r.kind==='reward'&&h.indexOf('#recompensa-')===0){var bid=+h.slice(12),bag=(WD.eventBags||[]).find(function(b){return b.id===bid});if(bag)(bag.monster>=0?bosses:boxes).set(bid,bag)}
  })});
  return {maps:maps,bosses:bosses,boxes:boxes};
}
function srcHTML(ids,what){
  var s=sources(ids); if(!s)return '';
  var rows=Array.from(s.maps.values()).filter(function(e){return e.mobs.size}).map(function(e){var mobs=Array.from(e.mobs.values()).sort(function(a,b){return a.level-b.level});return {e:e,mobs:mobs,min:mobs[0].level,max:mobs[mobs.length-1].level}}).sort(function(a,b){return a.min-b.min});
  if(!rows.length&&!s.bosses.size&&!s.boxes.size)return '';
  var h='<div class="arm-src"><h3>Dónde se consigue</h3><p class="arm-src-sub">'+esc(what)+'. Tocá un mapa o un monstruo para ir a su ficha del Atlas.</p>';
  if(rows.length){h+='<div class="arm-src-group"><h4>Cae de monstruos</h4><div class="arm-src-maps">'+rows.map(function(r){
    var shown=r.mobs.slice(0,8),more=r.mobs.length-shown.length;
    return '<div class="arm-src-map"><a class="arm-src-mapname" href="#mapa-'+r.e.map.id+'">'+esc(r.e.map.name)+'<small>Lv. '+r.min+(r.max!==r.min?'–'+r.max:'')+'</small></a><div class="arm-src-mobs">'+shown.map(function(m){return '<a href="#mob-'+r.e.map.id+'-'+m.id+'">'+esc(m.name)+' <small>'+m.level+'</small></a>'}).join('')+(more>0?'<span>+'+more+' más</span>':'')+'</div></div>'}).join('')+'</div></div>'}
  if(s.bosses.size){h+='<div class="arm-src-group"><h4>Jefes</h4><div class="arm-src-mobs">'+Array.from(s.bosses.values()).map(function(b){return '<a href="#recompensa-'+b.id+'">'+esc(b.monsterName||b.name)+'</a>'}).join('')+'</div></div>'}
  if(s.boxes.size){h+='<div class="arm-src-group"><h4>Cajas y eventos</h4><div class="arm-src-mobs">'+Array.from(s.boxes.values()).map(function(b){return '<a href="#recompensa-'+b.id+'">'+esc(b.name)+'</a>'}).join('')+'</div></div>'}
  return h+'</div>';
}
function pick(i){
  sel=Math.max(0,Math.min(i,list.length-1)); const s=list[sel]; if(!s){$('ancBox').innerHTML='';return} const t=tierOf(s.lv), prev=list[sel-1];
  [...$('stairs').children].forEach((b,j)=>b.setAttribute('data-on',j===sel?'true':'false')); $('scrub').value=sel;
  const card=$('card'); card.style.setProperty('--t',t.c); card.classList.remove('arm-swap'); void card.offsetWidth; card.classList.add('arm-swap');
  $('cNum').textContent=sel+1; $('cTier').textContent=t.n; $('cName').textContent=s.n;
  const k=mode==='sets'?8:s.k, ix=s.i, box=$('cImgBox'), im=$('cImg'); box.classList.remove('arm-empty'); im.onerror=()=>box.classList.add('arm-empty'); im.src=mode==='sets'?img(8,ix):img(s.k,s.i); im.alt=s.n;
  const ch=[]; if(s.c[cls.k]===2)ch.push('<span class="arm-chip arm-evo">★ Pide '+esc(cls.evo||'2ª evolución')+'</span>');
  if(mode==='sets'){
    if(s.r380)ch.push('<span class="arm-chip arm-boss">Jefe: '+s.src+'</span>');
    if(s.a.length)ch.push('<span class="arm-chip arm-boss">Tiene Ancient</span>');
    ch.push('<span class="arm-chip">'+s.p.length+' piezas</span>');
    $('cStats').innerHTML='<div class="arm-stat"><small>Defensa del set</small><strong id="arm-cVal">0</strong></div><div class="arm-stat"><small>Contra el anterior</small><strong id="arm-cDelta">—</strong></div><div class="arm-stat"><small>Nivel del objeto</small><strong>'+s.lv+'</strong></div>';
    $('cReqs').innerHTML='';
    $('cHow').textContent=s.r380?('No cae de monstruos comunes: sale del jefe '+(s.src==='Kundun'?'Kundun, en Kalima':'Selupan, en Raklion')+'. Pide nivel 380.'):'Cae de monstruos. Cuanto más alto el nivel del objeto, más fuertes los monstruos que lo sueltan.'+(prev?' Mejora a '+prev.n+'.':'');
    $('cPieces').innerHTML='<h4>Piezas</h4>'+s.p.map(p=>'<div class="arm-piece"><span class="arm-thumb"><img src="'+img(p.k,ix)+'" alt="" loading="lazy" onerror="this.outerHTML=\''+SVG[p.k].replace(/"/g,'&quot;')+'\'"></span><div><strong>'+p.s+'</strong><span>Fza '+p.f+' · Agi '+p.g+'</span></div><em>'+p.d+'<small>DEF</small></em></div>').join('');
    $('cPieces').hidden=false;
    var srcS=srcHTML(s.p.map(function(p){return p.k*512+ix}),'Cualquier pieza del set: casco, armadura, pantalones, guantes o botas');
    $('ancBox').innerHTML=srcS+ancHTML(s.a,null,ix);
    if(srcS)$('cHow').textContent=s.r380?'Pide nivel 380 para equiparlo.':(prev?'Mejora a '+prev.n+'.':'El primer set de la clase.');
  } else {
    if(!s.d)ch.push('<span class="arm-chip arm-boss">No cae de monstruos comunes</span>');
    if(s.a.length)ch.push('<span class="arm-chip arm-boss">Tiene Ancient</span>');
    ch.push('<span class="arm-chip">'+esc(fam)+'</span>');
    const mag=MAGIC[fam];
    $('cStats').innerHTML=(mag?'<div class="arm-stat"><small>Poder mágico</small><strong><span id="arm-cVal">0</span><span>%</span></strong></div>':'<div class="arm-stat"><small>Daño</small><strong>'+s.mn+'<span> – </span>'+s.mx+'</strong></div>')+'<div class="arm-stat"><small>Contra el anterior</small><strong id="arm-cDelta">—</strong></div><div class="arm-stat"><small>Velocidad</small><strong>'+s.sp+'</strong></div>';
    const r=[]; if(s.rq)r.push('Nivel <b>'+s.rq+'</b>'); if(s.f)r.push('Fza <b>'+s.f+'</b>'); if(s.g)r.push('Agi <b>'+s.g+'</b>'); if(s.e)r.push('Ene <b>'+s.e+'</b>'); if(mag&&s.mx)r.push('Daño físico <b>'+s.mn+'–'+s.mx+'</b>');
    $('cReqs').innerHTML=r.length?'<span>Pide</span> '+r.join(' · '):'';
    $('cHow').textContent=(s.d?'Cae de monstruos. Cuanto más alto el nivel del objeto ('+s.lv+'), más fuertes los monstruos que la sueltan.':'Arma especial: no cae de monstruos comunes.')+(prev?' Mejora a '+prev.n+'.':'');
    $('cPieces').hidden=true; $('cPieces').innerHTML='';
    var srcW=srcHTML([s.k*512+s.i],'Esta arma');
    $('ancBox').innerHTML=srcW+ancHTML(s.a,s.k,s.i);
    if(srcW)$('cHow').textContent=prev?'Mejora a '+prev.n+'.':'La primera de la lista.';
  }
  $('cChips').innerHTML=ch.join('');
  const v=$('cVal'); if(v)count(v,mode==='sets'?s.def:s.mg);
  const d=$('cDelta'); if(prev&&val(prev)>0){const p=Math.round((val(s)-val(prev))/val(prev)*100);d.innerHTML=(p>=0?'+':'')+p+'<span>%</span>';d.className=p>0?'arm-up':''}else{d.textContent='—';d.className=''}
  $('card').classList.toggle('arm-one',mode!=='sets');
  $('prev').disabled=sel===0; $('next').disabled=sel===list.length-1;
}
$('scrub').addEventListener('input',e=>pick(+e.target.value));
$('prev').onclick=()=>pick(sel-1); $('next').onclick=()=>pick(sel+1);
$('mSets').onclick=()=>{if(mode!=='sets'){mode='sets';sel=0;build()}}; $('mWeap').onclick=()=>{if(mode!=='weap'){mode='weap';sel=0;build()}};
$('stairs').addEventListener('keydown',e=>{if(e.key==='ArrowRight'||e.key==='ArrowLeft'){pick(sel+(e.key==='ArrowRight'?1:-1));$('stairs').children[sel].focus();e.preventDefault()}});
$('show380').addEventListener('change',build);
function fromHash(){const h=(location.hash||'').slice(1);const m=h.indexOf('armeria-')===0?h.slice(8):'';const c=CLASSES.find(x=>x.id===m)||(list.length?cls:CLASSES[0]);if(c!==cls||!list.length){cls=c;sel=0;fam=null}build()}
window.addEventListener('hashchange',fromHash); fromHash();
})();
