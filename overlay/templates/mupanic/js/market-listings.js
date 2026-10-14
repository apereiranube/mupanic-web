(() => {
'use strict';
const root=document.querySelector('.vessara-page');
if(!root || !root.dataset.marketListingsUrl)return;
const one=s=>root.querySelector(s);
const txt=(tag,value,cls)=>{const e=document.createElement(tag);if(value!=null)e.textContent=String(value);if(cls)e.className=cls;return e;};
const fmt=s=>{try{return new Intl.NumberFormat('es-AR').format(BigInt(s));}catch{return '—';}};
const date=s=>{if(!s)return '—';const d=new Date(s);return Number.isNaN(d.getTime())?'—':new Intl.DateTimeFormat('es-AR',{timeZone:'America/Argentina/Buenos_Aires',day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}).format(d);};
let current=null,abort=null,seq=0,page=1,currency='all',onlyFeatured=false,search='',sort='recent',nextPoll=0,typing=0;
function emblem(s){
 s=String(s).toLowerCase();
 if(/jewel|joya|gem|stone|crystal|bless|soul|life/.test(s))return 'jewel';
 if(/shield|escudo/.test(s))return 'shield';
 if(/wing|ala /.test(s))return 'wings';
 if(/staff|bast[oó]n|scepter|cetro/.test(s))return 'staff';
 if(/armor|glove|helm|boot|pants|armadura|guante/.test(s))return 'armor';
 if(/sword|axe|bow|mace|crossbow|spear|espada|hacha|arco/.test(s))return 'weapon';
 return 'relic';
}
function card(item){
 const el=txt('article',null,'vessara-offer'+(item.featured?' is-featured':''));el.dataset.listingId=item.id;
 const symbol=txt('div',null,'vessara-offer-crest'),img=document.createElement('img');
 img.src=root.dataset.marketEmblemsUrl+emblem(item.item)+'.svg';img.alt='';img.loading='lazy';img.setAttribute('aria-hidden','true');symbol.append(img);el.append(symbol);
 const body=txt('div',null,'vessara-offer-body'),tags=txt('div',null,'vessara-offer-tags');
 tags.append(txt('span',item.currency===1?'◆ ERYNS':'● ZEN','vessara-offer-currency'+(item.currency===1?' is-eryns':'')));
 if(item.featured)tags.append(txt('span','✦ DESTACADO','vessara-offer-badge'));
 body.append(tags,txt('h4',item.item+(item.level?' +'+item.level:'')));
 body.append(txt('p','Vendedor: '+(item.seller||'—'),'vessara-offer-seller'));
 body.append(txt('strong',fmt(item.price)+' '+(item.currency===1?'Eryns':'Zen'),'vessara-offer-price'+(item.currency===1?' is-eryns':'')));
 body.append(txt('small',item.featured?'Destacado hasta '+date(item.featuredUntil):'Publicado hasta '+date(item.expiresAt),'vessara-offer-expiry'));
 el.append(body);return el;
}
function render(){
 const promos=one('[data-listings-featured]'),area=one('[data-listings-featured-wrap]'),grid=one('[data-listings-grid]'),notice=one('[data-listings-state]'),nav=one('[data-listings-pagination]');
 promos.replaceChildren();grid.replaceChildren();
 const ready=current&&current.schema===1&&current.available===true&&Array.isArray(current.items)&&Array.isArray(current.featured);
 if(!ready){
  area.hidden=true;nav.hidden=true;one('[data-listings-count]').textContent='—';notice.hidden=false;
  notice.textContent='No se pudieron consultar las publicaciones del juego. El historial de ventas sigue disponible más abajo.';return;
 }
 area.hidden=current.featured.length===0;
 current.featured.forEach(x=>promos.append(card(x)));
 current.items.forEach(x=>grid.append(card(x)));
 one('[data-listings-count]').textContent=fmt(current.total)+' publicaciones';
 notice.hidden=current.items.length>0;
 notice.textContent=current.total==='0'?'Todavía no hay publicaciones activas.':'No hay coincidencias con los filtros elegidos.';
 nav.hidden=current.pages<=1;
 one('[data-listings-page]').textContent='Página '+current.page+' de '+current.pages;
 one('[data-listings-prev]').disabled=current.page<=1;one('[data-listings-next]').disabled=current.page>=current.pages;
}
async function load(){
 if(document.hidden)return;
 const n=++seq;if(abort)abort.abort();abort=new AbortController();const signal=abort.signal;
 const url=new URL(root.dataset.marketListingsUrl,location.href);
 url.searchParams.set('listing_page',String(page));url.searchParams.set('currency',currency);url.searchParams.set('search',search);
 url.searchParams.set('featured',onlyFeatured?'1':'0');url.searchParams.set('sort',sort);
 one('[data-listings-connection]').textContent='Consultando el mercado…';
 const timeout=setTimeout(()=>abort?.abort(),10000);
 try{
  const res=await fetch(url.toString(),{headers:{Accept:'application/json'},cache:'no-store',signal});
  if(!res.ok)throw Error('Unavailable');
  const json=await res.json();
  if(json.schema!==1||json.available!==true||!Array.isArray(json.items)||!Array.isArray(json.featured)||!Number.isInteger(json.page)||!Number.isInteger(json.pages))throw Error('Invalid');
  if(n!==seq)return;
  current=json;page=json.page;render();one('[data-listings-connection]').textContent='Publicaciones vigentes · Lectura cada 30 segundos';
 }catch(e){
  if(n!==seq)return;
  current=null;render();one('[data-listings-connection]').textContent='Consulta temporalmente no disponible';
 }finally{clearTimeout(timeout);if(n===seq){abort=null;nextPoll=Date.now()+30000;}}
}
function changed(){page=1;load();}
root.querySelectorAll('[data-listings-currency]').forEach(button=>button.addEventListener('click',()=>{
 currency=button.dataset.listingsCurrency;
 root.querySelectorAll('[data-listings-currency]').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));changed();
}));
one('[data-listings-onlyfeatured]').addEventListener('click',()=>{
 onlyFeatured=!onlyFeatured;one('[data-listings-onlyfeatured]').setAttribute('aria-pressed',String(onlyFeatured));changed();
});
one('[data-listings-sort]').addEventListener('change',e=>{sort=e.target.value;changed();});
one('[data-listings-search]').addEventListener('input',e=>{
 clearTimeout(typing);const next=e.target.value.slice(0,60);typing=setTimeout(()=>{search=next;changed();},300);
});
one('[data-listings-prev]').addEventListener('click',()=>{if(page>1){page--;load();}});
one('[data-listings-next]').addEventListener('click',()=>{if(current&&page<current.pages){page++;load();}});
document.addEventListener('visibilitychange',()=>{if(!document.hidden)load();});
setInterval(()=>{if(!document.hidden&&!abort&&Date.now()>=nextPoll)load();},5000);
load();
})();
