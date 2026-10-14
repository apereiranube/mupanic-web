(() => {
'use strict';
const root=document.querySelector('.vessara-page');
if(!root || !root.dataset.marketListingsUrl)return;
const q=s=>root.querySelector(s);
const el=(tag,value,cls)=>{const node=document.createElement(tag);if(value!==null && value!==undefined)node.textContent=String(value);if(cls)node.className=cls;return node;};
const fmt=value=>{try{return new Intl.NumberFormat('es-AR').format(BigInt(value));}catch{return '—';}};
const title=item=>item.item+(item.level?' +'+item.level:'');
const money=item=>fmt(item.price)+' '+(item.currency===1?'Eryns':'Zen');
const rarityClass=item=>{
 const d=item.details;
 if(d?.ancient)return 'is-ancient';
 if(Array.isArray(d?.sockets)&&d.sockets.length>0)return 'is-socket';
 if((d?.excellentCount||0)>0)return 'is-excellent';
 if(d?.harmony)return 'is-harmony';
 return 'is-common';
};
const levelClass=item=>{
 const n=Number(item.level)||0;
 return n>=15?'is-max':n>=13?'is-high':n>=10?'is-strong':n>=7?'is-medium':'is-low';
};
function heading(tag,item){
 const node=el(tag,null,'vessara-item-title '+rarityClass(item));
 node.append(document.createTextNode(item.item));
 if(Number(item.level)>0)node.append(el('span',' +'+item.level,'vessara-item-level '+levelClass(item)));
 return node;
}
const active=item=>!item.expiresAt || new Date(item.expiresAt).getTime()>Date.now();
const promoted=item=>item.featured===true && item.featuredUntil && new Date(item.featuredUntil).getTime()>Date.now() && active(item);
let current=null,abort=null,requestSeq=0,page=1,currency='all',search='',sort='recent',nextPoll=0,typing=0,shown=null,pinned=false,openedAt=0;
const popup=el('div',null,'vessara-tooltip');
popup.id='vessara-item-tooltip';popup.hidden=true;popup.setAttribute('role','dialog');popup.setAttribute('aria-label','Detalle del objeto publicado');
document.body.append(popup);
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
 const itemTitle=heading('h3',item);
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
 // No internal item number or expiry text: the server removes expired offers.
 const foot=el('div',null,'vessara-tooltip-footer');
 foot.append(el('span','La compra se realiza dentro del juego.'));
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
 dismiss();shown=item;pinned=stick || innerWidth<=720;openedAt=Date.now();buildPopup(item);popup.hidden=false;
 document.body.classList.toggle('vessara-tooltip-open',innerWidth<=720);
 anchor.setAttribute('aria-expanded','true');
 position(anchor);
}
function offerCard(item,hero=false) {
 const isPromoted=promoted(item);
 const card=el('article',null,'vessara-offer'+(isPromoted?' is-featured':'')+(hero?' is-hero-offer':'')+' '+rarityClass(item)+' '+levelClass(item));
 card.dataset.vessaraOffer=item.id;card.tabIndex=0;card.setAttribute('role','button');
 card.setAttribute('aria-expanded','false');card.setAttribute('aria-controls',popup.id);
 card.setAttribute('aria-label','Ver opciones de '+title(item)+' · '+money(item));
 card.append(media(item,hero));
 const body=el('div',null,'vessara-offer-body');
 const tags=el('div',null,'vessara-offer-tags');
 tags.append(el('span',item.currency===1?'◆ ERYNS':'● ZEN','vessara-offer-currency'+(item.currency===1?' is-eryns':'')));
 if(isPromoted)tags.append(el('span','✦ DESTACADO','vessara-offer-badge'));
 body.append(tags,heading('h4',item));
 body.append(el('p','Vendedor: '+(item.seller||'—'),'vessara-offer-seller'));
 body.append(el('strong',money(item),'vessara-offer-price'+(item.currency===1?' is-eryns':'')));
 // Keep the card focused on item identity, seller, currency and price.
 card.append(body);
 // Let native scroll-into-view/snap finish before showing the hover panel.
 let hoverDelay=0;
 card.addEventListener('pointerenter',event=>{
   if(event.pointerType==='touch'||pinned)return;
   clearTimeout(hoverDelay);
   hoverDelay=setTimeout(()=>{
     if(!pinned && card.isConnected && card.matches(':hover'))show(item,card,false);
   },110);
 });
 card.addEventListener('pointerleave',()=>{
   clearTimeout(hoverDelay);
   if(!pinned && shown?.id===item.id)dismiss();
 });
 card.addEventListener('focus',()=>{if(innerWidth>720 && !pinned)show(item,card,false);});
 card.addEventListener('blur',()=>{if(!pinned)dismiss();});
 card.addEventListener('click',()=>{if(shown&&shown.id===item.id&&pinned)dismiss();else show(item,card,true);});
 card.addEventListener('keydown',event=>{
   if(event.key==='Enter'||event.key===' '){event.preventDefault();if(shown&&shown.id===item.id&&pinned)dismiss();else show(item,card,true);}
 });
 return card;
}

// Seamless constant-speed featured marquee. Only CSS animates; JS rebuilds on
// a real promotion change or meaningful viewport-width change, never every frame.
const featuredArea=q('[data-listings-featured-wrap]');
const featuredViewport=q('[data-listings-featured]');
const featuredPause=q('[data-featured-pause]');
const featuredMotion=matchMedia('(prefers-reduced-motion: reduce)');
let featuredItems=[],featuredKey='',featuredWidth=0,featuredResize=0;
let featuredManuallyPaused=false,lastDisplayKey='';
const displayKey=v=>JSON.stringify([v.page,v.pages,v.total,v.pageSize,v.itemOptions,v.items,v.featured]);
function marqueeGroup(items,repeatCount,isDuplicate){
 const group=el('div',null,'vessara-marquee-group');
 for(let i=0;i<repeatCount;i++){
   const card=offerCard(items[i%items.length],true);
   if(isDuplicate || i>=items.length){
     card.tabIndex=-1;
     card.setAttribute('aria-hidden','true');
   }else card.dataset.marqueeOriginal='true';
   group.append(card);
 }
 if(isDuplicate)group.setAttribute('aria-hidden','true');
 return group;
}
function rebuildFeatured(){
 if(!featuredItems.length || featuredArea.hidden)return;
 // Native scrolling is retained when reduced motion is selected.
 const reduced=featuredMotion.matches;
 featuredViewport.replaceChildren();
 const belt=el('div',null,'vessara-marquee-motion');
 const first=marqueeGroup(featuredItems,featuredItems.length,false);
 belt.append(first);featuredViewport.append(belt);
 const card=first.querySelector('.vessara-offer');
 const width=card?.getBoundingClientRect().width || 315;
 const viewport=featuredViewport.getBoundingClientRect().width || 920;
 // Fill the whole visible width so wrapping never creates an empty gap.
 const repeats=reduced?featuredItems.length:Math.max(featuredItems.length,Math.ceil((viewport+width+15)/(width+14)));
 if(repeats!==featuredItems.length)first.replaceWith(marqueeGroup(featuredItems,repeats,false));
 const group=belt.firstElementChild;
 if(!reduced){
   const duplicate=marqueeGroup(featuredItems,repeats,true);
   belt.append(duplicate);
   const travel=group.getBoundingClientRect().width;
   belt.style.setProperty('--vessara-travel',travel.toFixed(2)+'px');
   belt.style.setProperty('--vessara-duration',(travel/43).toFixed(2)+'s');
 }
 featuredWidth=viewport;
 featuredArea.classList.toggle('is-reduced-motion',reduced);
 featuredPause.disabled=reduced;
 featuredPause.textContent=reduced?'Movimiento reducido':(featuredManuallyPaused?'▶ Reanudar':'Ⅱ Pausar');
 featuredPause.setAttribute('aria-pressed',String(featuredManuallyPaused));
 featuredArea.classList.toggle('is-user-paused',featuredManuallyPaused);
}
function renderFeatured(items){
 featuredItems=items;
 featuredArea.hidden=items.length===0;
 if(!items.length){featuredKey='';featuredViewport.replaceChildren();return;}
 const key=JSON.stringify(items);
 if(key===featuredKey && featuredViewport.childElementCount>0)return;
 featuredKey=key;
 // Use current layout dimensions after the section becomes visible.
 requestAnimationFrame(()=>{if(featuredKey===key && !featuredArea.hidden)rebuildFeatured();});
}
featuredPause.addEventListener('click',()=>{
 if(featuredMotion.matches)return;
 featuredManuallyPaused=!featuredManuallyPaused;
 featuredArea.classList.toggle('is-user-paused',featuredManuallyPaused);
 featuredPause.setAttribute('aria-pressed',String(featuredManuallyPaused));
 featuredPause.setAttribute('aria-label',featuredManuallyPaused?'Reanudar destacados':'Pausar desplazamiento de destacados');
 featuredPause.textContent=featuredManuallyPaused?'▶ Reanudar':'Ⅱ Pausar';
 if(!featuredManuallyPaused)featuredPause.blur();
});
featuredMotion.addEventListener('change',()=>{if(!featuredArea.hidden)rebuildFeatured();});
addEventListener('resize',()=>{
 clearTimeout(featuredResize);
 featuredResize=setTimeout(()=>{
   if(!featuredArea.hidden && Math.abs(featuredViewport.getBoundingClientRect().width-featuredWidth)>8)rebuildFeatured();
 },190);
},{passive:true});
if('IntersectionObserver' in window){
 new IntersectionObserver(entries=>{
   featuredArea.classList.toggle('is-offscreen',!entries.some(x=>x.isIntersecting));
 },{threshold:.02}).observe(featuredArea);
}
document.addEventListener('visibilitychange',()=>{
 featuredArea.classList.toggle('is-page-hidden',document.hidden);
});

function render() {
 dismiss();
 const grid=q('[data-listings-grid]'),notice=q('[data-listings-state]'),pagination=q('[data-listings-pagination]');
 grid.replaceChildren();
 const okay=current&&current.schema===1&&current.available===true&&Array.isArray(current.items)&&Array.isArray(current.featured);
 if(!okay){
   renderFeatured([]);pagination.hidden=true;q('[data-listings-count]').textContent='—';notice.hidden=false;
   notice.textContent='No se pudieron consultar las publicaciones. El registro de ventas sigue disponible más abajo.';
   return;
 }
 const top=current.featured.filter(x=>active(x)&&promoted(x));
 renderFeatured(top);
 // Defensive exclusion: a stale or older API response cannot duplicate featured
 // publications in the ordinary browse results.
 current.items.filter(x=>active(x)&&!promoted(x)).forEach(x=>grid.append(offerCard(x)));
 q('[data-listings-count]').textContent=fmt(current.total)+' publicaciones comunes';
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
 url.searchParams.set('sort',sort);
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

   q('[data-listings-connection]').textContent='Publicaciones actualizadas automáticamente';
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
addEventListener('scroll',()=>{if(shown && !pinned && Date.now()-openedAt>300)dismiss();},{passive:true});
addEventListener('resize',()=>{if(shown && !pinned)dismiss();},{passive:true});
setInterval(()=>{
 if(!document.hidden && !abort && Date.now()>=nextPoll)load();
 // A promotion can expire before the next 30-second poll.
 if(!document.hidden && current && !abort && Date.now()<nextPoll &&
    (current.featured.some(x=>x.featured && !promoted(x)) || current.items.some(x=>!active(x))))load();
},5000);
load();
})();
