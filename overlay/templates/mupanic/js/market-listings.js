(() => {
'use strict';
const root=document.querySelector('.vessara-page');
if(!root || !root.dataset.marketListingsUrl)return;
const q=s=>root.querySelector(s);
const el=(tag,value,cls)=>{const node=document.createElement(tag);if(value!==null && value!==undefined)node.textContent=String(value);if(cls)node.className=cls;return node;};
const fmt=value=>{try{return new Intl.NumberFormat('es-AR').format(BigInt(value));}catch{return '—';}};
const date=value=>{
 if(!value)return '—';
 const d=new Date(value);
 return Number.isNaN(d.getTime())?'—':new Intl.DateTimeFormat('es-AR',{timeZone:'America/Argentina/Buenos_Aires',day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}).format(d);
};
const title=item=>item.item+(item.level?' +'+item.level:'');
const money=item=>fmt(item.price)+' '+(item.currency===1?'Eryns':'Zen');
const active=item=>!item.expiresAt || new Date(item.expiresAt).getTime()>Date.now();
const promoted=item=>item.featured===true && item.featuredUntil && new Date(item.featuredUntil).getTime()>Date.now() && active(item);
const photoBase=(root.dataset.marketPhotoBase||'').replace(/\/?$/,'/');
let current=null,abort=null,requestSeq=0,page=1,currency='all',onlyFeatured=false,search='',sort='recent',nextPoll=0,typing=0,shown=null,pinned=false;
const popup=el('div',null,'vessara-tooltip');
popup.id='vessara-item-tooltip';popup.hidden=true;popup.setAttribute('role','dialog');popup.setAttribute('aria-label','Detalle del objeto publicado');
document.body.append(popup);
function countdown(until) {
 const delta=new Date(until).getTime()-Date.now();
 if(!Number.isFinite(delta)||delta<=0)return 'Finalizado';
 const minutes=Math.ceil(delta/60000);
 const d=Math.floor(minutes/1440),h=Math.floor(minutes%1440/60),m=minutes%60;
 return d>0?d+' d '+h+' h':h>0?h+' h '+String(m).padStart(2,'0')+' min':m+' min';
}
function sources(item) {
 const index=Number(item.itemIndex);
 if(!Number.isInteger(index)||index<0||index>8191)return item.photo?.url?[item.photo.url]:[];
 const group=Math.floor(index/512),sub=index%512;
 const list=[];
 if(item.photo?.url && /^\/|^https?:\/\//.test(item.photo.url))list.push(item.photo.url);
 // Game's original 2D inventory sprites, following the source format already
 // documented in MU PANIC's Atlas image-source manifests.
 list.push('https://mureforge.com/item-images/'+group+'/'+sub+'.webp');
 list.push('https://bless.gs/images/items/'+group+'-'+sub+'.webp');
 return Array.from(new Set(list));
}
function media(item,large) {
 const frame=el('div',null,'vessara-item-media'+(large?' is-large':''));
 const img=document.createElement('img');img.alt='Imagen 2D de '+item.item;img.loading='lazy';img.decoding='async';img.className='vessara-item-photo';
 let urls=sources(item),i=0;
 const failed=()=>{if(i<urls.length){img.src=urls[i++];return;}
     img.remove();frame.classList.add('is-missing');
     frame.append(el('span','Imagen no disponible','vessara-photo-missing'));
 };
 img.addEventListener('error',failed);
 img.addEventListener('load',()=>frame.classList.add('has-photo'));
 frame.append(img);
 failed();
 return frame;
}
function rowLine(value,kind) {
 const line=el('li',value,kind?'vessara-tooltip-'+kind:'');
 return line;
}
function buildPopup(item) {
 popup.replaceChildren();
 popup.classList.toggle('is-featured',promoted(item));
 const top=el('div',null,'vessara-tooltip-top');
 top.append(el('span','FICHA REAL DEL OBJETO · VESSARA','vessara-tooltip-eyebrow'));
 const close=el('button','✕','vessara-tooltip-close');close.type='button';close.setAttribute('aria-label','Cerrar detalle');
 close.addEventListener('click',dismiss);top.append(close);popup.append(top);
 const intro=el('div',null,'vessara-tooltip-intro');
 intro.append(media(item,true));
 const details=el('div',null,'vessara-tooltip-name');
 if(promoted(item))details.append(el('span','✦ PUBLICACIÓN DESTACADA','vessara-tooltip-promoted'));
 const opts=item.details;
 const itemTitle=el('h3',title(item),opts?.ancient?'is-ancient':(opts?.excellentCount>0?'is-excellent':''));
 details.append(itemTitle,el('p','Publicado por '+(item.seller||'—')));
 intro.append(details);popup.append(intro);
 const purchase=el('div',null,'vessara-tooltip-pricebar');
 purchase.append(el('span','PRECIO EN EL JUEGO'));
 purchase.append(el('strong',money(item),item.currency===1?'is-eryns':''));
 popup.append(purchase);
 if(!opts) {
   popup.append(el('p','Las opciones completas estarán disponibles al actualizar el módulo de lectura del servidor. No se muestran valores supuestos.','vessara-tooltip-not-ready'));
 } else {
   const standard=[];
   if(opts.skill)standard.push('Skill');
   if(opts.luck)standard.push('Luck');
   if(opts.additional>0)standard.push('Opción adicional +'+opts.additional);
   if(typeof opts.durability==='number')standard.push('Durabilidad '+opts.durability);
   if(opts.lot>1)standard.push('Lote de '+opts.lot+' unidades');
   const section=el('section',null,'vessara-tooltip-section');
   section.append(el('h4','ATRIBUTOS'));
   const list=el('ul',null,'vessara-tooltip-attributes');
   for(const value of standard)list.append(rowLine(value));
   if(!standard.length)list.append(rowLine('Sin atributos adicionales identificados'));
   section.append(list);popup.append(section);
   if(opts.excellentCount>0 && Array.isArray(opts.excellent)){
     const exc=el('section',null,'vessara-tooltip-section is-excellent');
     exc.append(el('h4','EXCELLENT · '+opts.excellentCount+' OPCIONES'));
     const list=el('ul');opts.excellent.forEach(value=>list.append(rowLine(value,'excellent')));
     exc.append(list);popup.append(exc);
   }
   if(opts.ancient || opts.harmony || opts.option380 || (Array.isArray(opts.sockets) && opts.sockets.length)){
     const rare=el('section',null,'vessara-tooltip-section is-rare');
     rare.append(el('h4','OPCIONES ESPECIALES'));
     const list=el('ul');
     if(opts.ancient)list.append(rowLine(opts.ancientType||'Ancient · bonificación de set','ancient'));
     if(opts.harmony)list.append(rowLine(opts.harmonyText||'Opción Harmony aplicada','harmony'));
     if(opts.option380)list.append(rowLine('Opción 380 registrada','special'));
     if(Array.isArray(opts.sockets))opts.sockets.forEach(s=>{
       list.append(rowLine('Socket '+s.slot+': '+(s.state==='vacío'?'vacío':'gema insertada (ID '+s.code+')'),'socket'));
     });
     rare.append(list);popup.append(rare);
   }
 }
 const foot=el('div',null,'vessara-tooltip-footer');
 foot.append(el('span','Ítem #'+item.itemIndex+' · '+(promoted(item)?'Destacado '+countdown(item.featuredUntil):'Vigencia '+date(item.expiresAt))));
 foot.append(el('span','Compra y confirmación únicamente en el juego.'));
 popup.append(foot);
}
function dismiss(){
 shown=null;pinned=false;popup.hidden=true;document.body.classList.remove('vessara-tooltip-open');
 root.querySelectorAll('[aria-expanded][data-vessara-offer]').forEach(node=>node.setAttribute('aria-expanded','false'));
}
function position(anchor){
 if(popup.hidden)return;
 const r=anchor.getBoundingClientRect(),n=popup.getBoundingClientRect();
 if(innerWidth<=720){
   popup.style.left='0px';popup.style.top='auto';popup.style.bottom='0px';return;
 }
 const margin=14;
 let left=r.right+14;
 if(left+n.width>innerWidth-margin)left=r.left-n.width-14;
 if(left<margin)left=Math.min(innerWidth-n.width-margin,margin);
 let top=Math.max(margin,Math.min(r.top,innerHeight-n.height-margin));
 popup.style.left=Math.round(left)+'px';popup.style.top=Math.round(top)+'px';popup.style.bottom='auto';
}
function show(item,anchor,stick=false){
 if(!active(item))return;
 if(shown && shown.id===item.id && pinned && !stick)return;
 dismiss();shown=item;pinned=stick || innerWidth<=720;buildPopup(item);popup.hidden=false;
 document.body.classList.toggle('vessara-tooltip-open',innerWidth<=720);
 anchor.setAttribute('aria-expanded','true');
 position(anchor);
}
function offerCard(item,hero=false) {
 const isPromoted=promoted(item);
 const card=el('article',null,'vessara-offer'+(isPromoted?' is-featured':'')+(hero?' is-hero-offer':''));
 card.dataset.vessaraOffer=item.id;card.tabIndex=0;card.setAttribute('role','button');
 card.setAttribute('aria-expanded','false');card.setAttribute('aria-controls',popup.id);
 card.setAttribute('aria-label','Ver opciones de '+title(item)+' · '+money(item));
 card.append(media(item,hero));
 const body=el('div',null,'vessara-offer-body');
 const tags=el('div',null,'vessara-offer-tags');
 tags.append(el('span',item.currency===1?'◆ ERYNS':'● ZEN','vessara-offer-currency'+(item.currency===1?' is-eryns':'')));
 if(isPromoted)tags.append(el('span','✦ DESTACADO','vessara-offer-badge'));
 body.append(tags,el('h4',title(item)));
 body.append(el('p','Vendedor: '+(item.seller||'—'),'vessara-offer-seller'));
 body.append(el('strong',money(item),'vessara-offer-price'+(item.currency===1?' is-eryns':'')));
 if(isPromoted){
   body.append(el('div','TERMINA EN '+countdown(item.featuredUntil),'vessara-feature-clock'));
 } else body.append(el('small','Vigente hasta '+date(item.expiresAt),'vessara-offer-expiry'));
 body.append(el('span','Ver opciones del objeto ↗','vessara-offer-detail-hint'));
 card.append(body);
 card.addEventListener('pointerenter',event=>{if(event.pointerType==='touch'||pinned)return;show(item,card,false);});
 card.addEventListener('pointerleave',()=>{if(!pinned)dismiss();});
 card.addEventListener('focus',()=>{if(innerWidth>720 && !pinned)show(item,card,false);});
 card.addEventListener('blur',()=>{if(!pinned)dismiss();});
 card.addEventListener('click',()=>{if(shown&&shown.id===item.id&&pinned)dismiss();else show(item,card,true);});
 card.addEventListener('keydown',event=>{
   if(event.key==='Enter'||event.key===' '){event.preventDefault();if(shown&&shown.id===item.id&&pinned)dismiss();else show(item,card,true);}
 });
 return card;
}

// A capped, native horizontal carousel. No frame-by-frame JS animation.
// Desktop shows up to three offers; narrow screens allow finger swipes.
const featuredArea=q('[data-listings-featured-wrap]');
const featuredTrack=q('[data-listings-featured]');
const featuredControls=q('[data-featured-controls]');
const featuredStatus=q('[data-featured-status]');
const featuredPause=q('[data-featured-pause]');
const featuredMotion=matchMedia('(prefers-reduced-motion: reduce)');
let featuredIndex=0,featuredMax=0,featuredVisibleCount=1;
let featuredHovered=false,featuredInView=false,featuredUserPaused=false;
let featuredQuietUntil=0,featuredProgrammatic=false,featuredMoveTimer=0;
let lastDisplayKey='';
const displayKey=v=>JSON.stringify([v.page,v.pages,v.total,v.pageSize,v.itemOptions,v.items,v.featured]);
function updateFeaturedStatus(){
 const count=featuredTrack.children.length;
 if(!count){featuredStatus.textContent='0 de 0';return;}
 const first=Math.min(count,featuredIndex+1);
 const last=Math.min(count,first+featuredVisibleCount-1);
 featuredStatus.textContent=(first===last?String(first):first+'–'+last)+' de '+count;
}
function measureFeatured(){
 const cards=featuredTrack.children;
 const count=cards.length;
 if(!count){featuredMax=0;featuredIndex=0;featuredControls.hidden=true;return;}
 const computed=getComputedStyle(featuredTrack);
 const gap=parseFloat(computed.columnGap||computed.gap)||0;
 const width=cards[0].getBoundingClientRect().width;
 featuredVisibleCount=width>0?Math.max(1,Math.min(count,Math.floor((featuredTrack.clientWidth+gap)/(width+gap)+0.025))):1;
 const overflowing=featuredTrack.scrollWidth>featuredTrack.clientWidth+2;
 featuredMax=overflowing?Math.max(0,count-featuredVisibleCount):0;
 featuredIndex=Math.min(featuredIndex,featuredMax);
 featuredControls.hidden=!overflowing || featuredMax===0;
 featuredPause.disabled=featuredMotion.matches;
 if(featuredMotion.matches){featuredPause.textContent='Ⅱ';featuredPause.title='Movimiento automático reducido';}
 const first=cards[0],selected=cards[featuredIndex];
 featuredTrack.scrollTo({left:(selected.offsetLeft-first.offsetLeft),behavior:'instant'});
 updateFeaturedStatus();
}
function moveFeatured(direction,manual=false){
 if(featuredArea.hidden || featuredControls.hidden || featuredMax===0)return;
 if(manual){featuredQuietUntil=Date.now()+9000;if(shown)dismiss();}
 featuredIndex+=direction;
 const wrapped=featuredIndex>featuredMax || featuredIndex<0;
 if(featuredIndex>featuredMax)featuredIndex=0;
 else if(featuredIndex<0)featuredIndex=featuredMax;
 const first=featuredTrack.firstElementChild,card=featuredTrack.children[featuredIndex];
 if(!first||!card)return;
 featuredProgrammatic=true;clearTimeout(featuredMoveTimer);
 // At the end, snap to the beginning instead of animating backwards.
 featuredTrack.scrollTo({left:card.offsetLeft-first.offsetLeft,behavior:wrapped||featuredMotion.matches?'instant':'smooth'});
 featuredMoveTimer=setTimeout(()=>{featuredProgrammatic=false;},550);
 updateFeaturedStatus();
}
function featuredShouldAdvance(){
 return !document.hidden && !featuredArea.hidden && featuredInView && !featuredControls.hidden &&
   !featuredMotion.matches && !featuredUserPaused && !featuredHovered && !shown &&
   Date.now()>=featuredQuietUntil && !featuredArea.contains(document.activeElement);
}
q('[data-featured-prev]').addEventListener('click',()=>moveFeatured(-1,true));
q('[data-featured-next]').addEventListener('click',()=>moveFeatured(1,true));
featuredPause.addEventListener('click',()=>{
 featuredUserPaused=!featuredUserPaused;
 featuredPause.setAttribute('aria-pressed',String(featuredUserPaused));
 featuredPause.textContent=featuredUserPaused?'▶':'Ⅱ';
 featuredPause.setAttribute('aria-label',featuredUserPaused?'Reanudar carrusel de destacados':'Pausar carrusel de destacados');
 featuredQuietUntil=Date.now()+3000;
});
featuredArea.addEventListener('pointerenter',event=>{if(event.pointerType!=='touch')featuredHovered=true;});
featuredArea.addEventListener('pointerleave',()=>{featuredHovered=false;});
featuredTrack.addEventListener('pointerdown',()=>{featuredQuietUntil=Date.now()+12000;featuredProgrammatic=false;});
let featuredScrollTimer=0;
featuredTrack.addEventListener('scroll',()=>{
 if(featuredProgrammatic)return;
 clearTimeout(featuredScrollTimer);
 featuredScrollTimer=setTimeout(()=>{
   const first=featuredTrack.firstElementChild;
   const second=featuredTrack.children[1];
   if(!first||!second)return;
   const step=second.offsetLeft-first.offsetLeft;
   if(step>0){featuredIndex=Math.min(featuredMax,Math.max(0,Math.round(featuredTrack.scrollLeft/step)));updateFeaturedStatus();}
 },100);
},{passive:true});
if('IntersectionObserver' in window){
 new IntersectionObserver(entries=>{featuredInView=entries.some(e=>e.isIntersecting);},{threshold:0.1}).observe(featuredArea);
}else featuredInView=true;
addEventListener('resize',()=>{if(!featuredArea.hidden)measureFeatured();},{passive:true});
featuredMotion.addEventListener('change',()=>{if(!featuredArea.hidden)measureFeatured();});
setInterval(()=>{if(featuredShouldAdvance())moveFeatured(1);},6500);

function render() {
 dismiss();
 const highlights=q('[data-listings-featured]'),section=q('[data-listings-featured-wrap]'),grid=q('[data-listings-grid]'),notice=q('[data-listings-state]'),pagination=q('[data-listings-pagination]');
 highlights.replaceChildren();grid.replaceChildren();
 const okay=current&&current.schema===1&&current.available===true&&Array.isArray(current.items)&&Array.isArray(current.featured);
 if(!okay){
   section.hidden=true;pagination.hidden=true;q('[data-listings-count]').textContent='—';notice.hidden=false;
   notice.textContent='No se pudieron consultar las publicaciones. El registro de ventas sigue disponible más abajo.';
   return;
 }
 const top=current.featured.filter(x=>active(x)&&promoted(x));
 section.hidden=top.length===0;
 section.classList.toggle('is-duo',top.length===2);
 section.classList.toggle('is-single',top.length===1);
 // The ten available promotion booths remain in one horizontal row.
 featuredIndex=Math.min(featuredIndex,Math.max(0,top.length-1));
 top.forEach(x=>highlights.append(offerCard(x,true)));
 current.items.filter(active).forEach(x=>grid.append(offerCard(x)));
 if(top.length)requestAnimationFrame(measureFeatured);
 else {featuredControls.hidden=true;featuredIndex=0;featuredMax=0;}
 q('[data-listings-count]').textContent=fmt(current.total)+' publicaciones';
 notice.hidden=grid.children.length>0;
 notice.textContent=current.total==='0'?'Todavía no hay publicaciones activas.':'No hay coincidencias con estos filtros.';
 pagination.hidden=current.pages<=1;
 q('[data-listings-page]').textContent='Página '+current.page+' de '+current.pages;
 q('[data-listings-prev]').disabled=current.page<=1;
 q('[data-listings-next]').disabled=current.page>=current.pages;
}
async function load() {
 if(document.hidden)return;
 const n=++requestSeq;if(abort)abort.abort();
 const ctl=new AbortController();abort=ctl;
 const url=new URL(root.dataset.marketListingsUrl,location.href);
 url.searchParams.set('listing_page',String(page));
 url.searchParams.set('currency',currency);url.searchParams.set('search',search);
 url.searchParams.set('featured',onlyFeatured?'1':'0');url.searchParams.set('sort',sort);
 q('[data-listings-connection]').textContent='Consultando el mercado…';
 const timeout=setTimeout(()=>ctl.abort(),10000);
 try{
   const response=await fetch(url.toString(),{headers:{Accept:'application/json'},cache:'no-store',signal:ctl.signal});
   if(!response.ok)throw Error('Unavailable');
   const value=await response.json();
   if(value.schema!==1||value.available!==true||!Array.isArray(value.items)||!Array.isArray(value.featured)||!Number.isInteger(value.page)||!Number.isInteger(value.pages))throw Error('Invalid');
   if(n!==requestSeq)return;
   current=value;page=value.page;
   const signature=displayKey(value);
   if(signature!==lastDisplayKey){lastDisplayKey=signature;render();}

   q('[data-listings-connection]').textContent=value.itemOptions?'Opciones reales del juego · Actualización cada 30 segundos':'Actualización cada 30 segundos · Opciones pendientes de sincronizar';
 }catch(error){
   if(n!==requestSeq)return;
   current=null;lastDisplayKey='';render();q('[data-listings-connection]').textContent='Consulta temporalmente no disponible';
 }finally{
   clearTimeout(timeout);
   if(n===requestSeq){abort=null;nextPoll=Date.now()+30000;}
 }
}
function changed(){page=1;load();}
root.querySelectorAll('[data-listings-currency]').forEach(button=>button.addEventListener('click',()=>{
 currency=button.dataset.listingsCurrency;
 root.querySelectorAll('[data-listings-currency]').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));
 changed();
}));
q('[data-listings-onlyfeatured]').addEventListener('click',()=>{
 onlyFeatured=!onlyFeatured;q('[data-listings-onlyfeatured]').setAttribute('aria-pressed',String(onlyFeatured));changed();
});
q('[data-listings-sort]').addEventListener('change',event=>{sort=event.target.value;changed();});
q('[data-listings-search]').addEventListener('input',event=>{
 clearTimeout(typing);const value=event.target.value.slice(0,60);
 typing=setTimeout(()=>{search=value;changed();},300);
});
q('[data-listings-prev]').addEventListener('click',()=>{if(page>1){page--;load();}});
q('[data-listings-next]').addEventListener('click',()=>{if(current && page<current.pages){page++;load();}});
document.addEventListener('visibilitychange',()=>{if(document.hidden)dismiss();else load();});
document.addEventListener('keydown',event=>{if(event.key==='Escape')dismiss();});
document.addEventListener('pointerdown',event=>{
 if(pinned && shown && !popup.contains(event.target) && !event.target.closest('[data-vessara-offer]'))dismiss();
});
addEventListener('scroll',()=>{if(shown && !pinned)dismiss();},{passive:true});
addEventListener('resize',()=>{if(shown && !pinned)dismiss();},{passive:true});
setInterval(()=>{
 if(!document.hidden && !abort && Date.now()>=nextPoll)load();
 // A promotion can expire before the next 30-second poll.
 if(!document.hidden && current && !abort && Date.now()<nextPoll &&
    current.featured.some(x=>x.featured && !promoted(x)))load();
},5000);
load();
})();
