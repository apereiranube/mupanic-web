(() => {
'use strict';
const root=document.querySelector('.vessara-page');if(!root)return;
let data=null,currency='all',request=null,nextPoll=Date.now()+15000,tapeKey='',newIds=new Set(),noticeTimer=null,effectsPaused=false;
try{data=JSON.parse(document.getElementById('vessara-initial').textContent);}catch{}
const q=s=>root.querySelector(s),fmt=v=>{try{return new Intl.NumberFormat('es-AR').format(BigInt(v));}catch{return '—';}};
const text=(tag,value,cls)=>{const el=document.createElement(tag);el.textContent=value;if(cls)el.className=cls;return el;};
const title=s=>s.item+(s.level?' +'+s.level:'');
function rows(){
 const query=q('[data-market-search]').value.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim();
 const list=(data?.sales||[]).filter(s=>(currency==='all'||String(s.currency)===currency)&&[title(s),s.buyer,s.seller].join(' ').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().includes(query));
 const body=q('[data-market-rows]');body.replaceChildren();
 for(const s of list){
  const tr=document.createElement('tr'),item=document.createElement('td');if(newIds.has(s.id))tr.classList.add('is-new');item.append(text('span','◇','vessara-item-mark'),text('strong',title(s)));tr.append(item,text('td',s.buyer),text('td',s.seller),text('td',fmt(s.price)+' '+(s.currency===0?'Zen':'Eryns'),'vessara-price'+(s.currency===1?' is-eryns':'')));
  const td=document.createElement('td'),time=text('time',new Intl.DateTimeFormat('es-AR',{timeZone:'America/Argentina/Buenos_Aires',day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}).format(new Date(s.soldAt)));time.dateTime=s.soldAt;td.append(time);tr.append(td);body.append(tr);
 }
 const empty=q('[data-market-empty]');empty.hidden=!!list.length;empty.textContent=!data?'El registro del mercado no está disponible en este momento.':(!data.sales.length?'Todavía no hay tratos sellados.':'No hay coincidencias entre los últimos tratos.');
}
const motion=matchMedia('(prefers-reduced-motion: reduce)'),animations=new WeakMap();
function fitMetric(el){
 if(el.tagName!=='STRONG')return;el.style.fontSize='';
 const width=el.clientWidth;for(let i=0;width&&el.scrollWidth>width&&i<3;i++)el.style.fontSize=(parseFloat(getComputedStyle(el).fontSize)*width/el.scrollWidth*.98)+'px';
}
// Contador tipo bolsa: cada dígito es una ficha que gira como un odómetro.
function odo(el,value){
 if(!/^\d+$/.test(String(value))){el.textContent='—';delete el.dataset.metricValue;delete el.dataset.odoLen;return;}
 value=String(value);if(el.dataset.metricValue===value)return;
 const first=el.dataset.metricValue===undefined,txt=fmt(value);el.dataset.metricValue=value;el.setAttribute('aria-label',txt);
 let sr=el.querySelector('.sr-only');
 if(el.dataset.odoLen!==String(txt.length)){
  el.replaceChildren();
  for(const ch of txt){if(/\d/.test(ch)){const d=document.createElement('span');d.className='od-d';d.setAttribute('aria-hidden','true');const col=document.createElement('span');col.className='od-col';for(let n=0;n<20;n++)col.append(text('i',String(n%10)));d.append(col);el.append(d);}else{const sep=text('span',ch,'od-sep');sep.setAttribute('aria-hidden','true');el.append(sep);}}
  el.dataset.odoLen=String(txt.length);sr=null;
 }
 if(!sr){sr=text('span','','sr-only');el.prepend(sr);}sr.textContent=txt;
 const digits=txt.replace(/\D/g,'');
 el.querySelectorAll('.od-col').forEach((c,k)=>{c.style.transitionDelay=(first?k*90:k*30)+'ms';c.style.setProperty('--n',digits[k]);});
 if(!first&&!motion.matches&&!effectsPaused){const card=el.closest('article');card?.classList.add('is-updating');setTimeout(()=>card?.classList.remove('is-updating'),900);}
 fitMetric(el);
}
function metric(el,value){
 if(el.matches('.vessara-stats strong'))return odo(el,value);
 const valid=/^\d+$/.test(String(value)),previous=el.dataset.metricValue;
 if(!valid){cancelAnimationFrame(animations.get(el));delete el.dataset.metricValue;el.textContent='—';return;}
 value=String(value);if(previous===value)return;
 cancelAnimationFrame(animations.get(el));el.dataset.metricValue=value;
 if(previous===undefined||motion.matches||effectsPaused){el.textContent=fmt(value);fitMetric(el);return;}
 const from=BigInt(previous),to=BigInt(value),start=performance.now(),card=el.closest('.vessara-stats article');
 card?.classList.add('is-updating');
 function frame(now){const t=Math.min(1,(now-start)/800),e=BigInt(Math.round((1-Math.pow(1-t,3))*1000000));el.textContent=fmt(from+(to-from)*e/1000000n);fitMetric(el);if(t<1)animations.set(el,requestAnimationFrame(frame));else{el.textContent=fmt(value);card?.classList.remove('is-updating');}}
 animations.set(el,requestAnimationFrame(frame));
}
const money=s=>fmt(s.price)+' '+(s.currency===0?'Zen':'Eryns');
const when=s=>new Intl.DateTimeFormat('es-AR',{timeZone:'America/Argentina/Buenos_Aires',day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}).format(new Date(s.soldAt));
function spark(key){
 const svg=q('[data-market-spark="'+key+'"]'),values=(data?.days||[]).map(d=>BigInt(d[key]||'0'));svg.replaceChildren();if(!values.length)return;
 const max=values.reduce((a,b)=>a>b?a:b,1n),points=values.map((v,i)=>[i*280/Math.max(1,values.length-1),52-Number(v*4500n/max)/100]);
 const area=document.createElementNS('http://www.w3.org/2000/svg','polygon');area.setAttribute('points','0,55 '+points.map(p=>p.join(',')).join(' ')+' 280,55');
 const line=document.createElementNS('http://www.w3.org/2000/svg','polyline');line.setAttribute('points',points.map(p=>p.join(',')).join(' '));svg.append(area,line);
}
// Como en una bolsa: ▲/▼ frente a la venta anterior del mismo objeto, nivel y moneda.
function trend(s){
 const list=data?.sales||[],at=list.indexOf(s),prev=at<0?null:list.slice(at+1).find(x=>x.item===s.item&&x.level===s.level&&x.currency===s.currency);
 if(!prev)return null;const a=BigInt(s.price),b=BigInt(prev.price);if(b===0n)return null;
 const pct=Number((a-b)*1000n/b)/10;return {dir:a>b?'up':a<b?'down':'flat',label:(a>b?'▲ ':a<b?'▼ ':'= ')+(a===b?'':Math.abs(pct).toLocaleString('es-AR',{maximumFractionDigits:1})+'%')};
}
function tickerSale(s){
 const t=trend(s),el=document.createElement('span');el.className='vessara-ticker-sale'+(s.currency===1?' is-eryns':'')+(t?' is-'+t.dir:'');
 const price=text('b',money(s));el.append(text('strong',title(s)),price);
 if(t){const mark=text('i',t.label);mark.title='Frente a la venta anterior del mismo objeto';el.append(mark);el.style.gridTemplateColumns='auto auto auto';}
 const who=text('small',s.buyer+' ← '+s.seller),at=text('small',when(s));if(t)at.style.gridColumn='2 / 4';el.append(who,at);return el;
}
function tradingTape(){
 const sales=data?.sales||[],key=JSON.stringify(sales);if(tapeKey===key)return;tapeKey=key;
 const ticker=q('[data-market-ticker]');ticker.replaceChildren();ticker.classList.toggle('is-static',!sales.length);
 if(!sales.length){ticker.append(text('span',data?'Todavía no hay tratos sellados.':'Esperando el registro del mercado.','vessara-ticker-empty'));return;}
 const group=document.createElement('div');group.className='vessara-ticker-group';const recent=sales.slice(0,8);recent.forEach(s=>group.append(tickerSale(s)));ticker.append(group);
 for(let i=0;group.scrollWidth<q('.vessara-ticker-window').clientWidth+100&&i<8;i++)recent.forEach(s=>{const copy=tickerSale(s);copy.setAttribute('aria-hidden','true');group.append(copy);});
 const duplicate=group.cloneNode(true);duplicate.setAttribute('aria-hidden','true');ticker.append(duplicate);
}
// Tablero de cotizaciones: una fila por objeto (nombre, nivel y moneda) con su último precio.
function board(){
 const box=q('[data-market-board]');if(!box)return;
 const led=q('[data-market-led]');
 if(led){const sales=(data?.sales||[]).slice(0,12);const line=sales.length?sales.map(x=>{const t=trend(x);return title(x).toUpperCase()+'  '+money(x).toUpperCase()+(t?'  '+t.label:'');}).join('   ◆   ')+'   ◆   ':'MERCADO DE VESSARA · ZEN · ERYNS · LOREN MARKET · ';led.textContent=line+line;led.style.setProperty('--led-time',Math.max(20,line.length*0.22)+'s');}
 const groups=new Map();
 for(const s of (data?.sales||[])){const k=s.item+'|'+s.level+'|'+s.currency;if(!groups.has(k))groups.set(k,[]);groups.get(k).push(s);}
 const rows=[...groups.values()].slice(0,8);box.replaceChildren();
 if(!rows.length){box.append(text('p',data?'Todavía no hay cotizaciones. La primera venta abre el tablero.':'Esperando las primeras cotizaciones del día.','vs-board-empty'));return;}
 rows.forEach((list,i)=>{
  const last=list[0],prev=list[1],row=document.createElement('div');row.className='vs-board-row'+(last.currency===1?' is-eryns':'')+(newIds.has(last.id)?' is-new':'');row.setAttribute('role','listitem');row.style.setProperty('--i',i);
  const name=document.createElement('span');name.className='vs-board-item';name.append(text('b',title(last)),text('small',last.currency===1?'ERYNS':'ZEN'));
  let dir='flat',label='—';
  if(prev){const a=BigInt(last.price),b=BigInt(prev.price);if(b>0n){const pct=Number((a-b)*1000n/b)/10;dir=a>b?'up':a<b?'down':'flat';label=(a>b?'▲ ':a<b?'▼ ':'= ')+(a===b?'0%':Math.abs(pct).toLocaleString('es-AR',{maximumFractionDigits:1})+'%');}}
  else label='Nuevo';
  const chg=text('span',label,'vs-board-chg is-'+(prev?dir:'new'));
  const svg=document.createElementNS('http://www.w3.org/2000/svg','svg');svg.setAttribute('viewBox','0 0 100 30');svg.setAttribute('preserveAspectRatio','none');svg.setAttribute('class','vs-board-spark is-'+dir);svg.setAttribute('aria-hidden','true');
  const pts=list.slice(0,10).reverse().map(x=>Number(x.price));
  if(pts.length>1){const mx=Math.max(...pts),mn=Math.min(...pts),d=mx-mn||1;const line=document.createElementNS('http://www.w3.org/2000/svg','polyline');line.setAttribute('points',pts.map((v,k)=>(k*100/(pts.length-1)).toFixed(1)+','+(26-(v-mn)/d*22).toFixed(1)).join(' '));svg.append(line);}
  row.append(name,text('span',money(last),'vs-board-price'),chg,text('span',fmt(String(list.length)),'vs-board-n'),svg);box.append(row);
 });
 if(boardSeen)shuffleBoard([...box.querySelectorAll('.vs-board-row.is-new')]);
}
// Las celdas "barajan" caracteres antes de mostrar el valor, como un tablero que se actualiza.
const glyphs='0123456789ABCDEFGHJKLMNPQRSTUVWXYZ▲▼◆#$%';
function scramble(el,delay){
 const final=el.textContent;if(motion.matches||effectsPaused||!final.trim())return;
 const start=performance.now()+delay,dur=650;
 (function step(now){const t=(now-start)/dur;if(t<0){el.textContent=final.replace(/\S/g,()=>glyphs[Math.random()*glyphs.length|0]);requestAnimationFrame(step);return;}
  if(t>=1){el.textContent=final;return;}const keep=Math.floor(final.length*t);
  el.textContent=final.slice(0,keep)+final.slice(keep).replace(/\S/g,()=>glyphs[Math.random()*glyphs.length|0]);requestAnimationFrame(step);})(performance.now());
}
function shuffleBoard(rows){rows.forEach((row,i)=>row.querySelectorAll('.vs-board-item b,.vs-board-price,.vs-board-chg,.vs-board-n').forEach((c,k)=>scramble(c,i*90+k*60)));}
let boardSeen=false;
function highlights(){
 const stage=q('[data-market-highlights]');stage.replaceChildren();const stats=data?.highlights;
 function card(label,eryns){const el=document.createElement('article');el.className='vessara-top-card'+(eryns?' is-eryns':'');el.append(text('span',label,'vessara-top-label'));stage.append(el);return el;}
 function waiting(el){el.append(text('p',data?'El puesto está libre.':'Esperando datos del mercado.','vessara-top-empty'));}
 for(const currency of [1,0]){const el=card('TOP VENDEDOR / '+(currency===1?'ERYNS':'ZEN'),currency===1),s=stats?.sellers?.find(s=>s.currency===currency);if(s)el.append(text('strong',s.seller),text('b',fmt(s.volume)+' '+(currency===1?'Eryns':'Zen')),text('small',fmt(s.trades)+' '+(s.trades==='1'?'venta':'ventas')+' · Volumen vendido hoy'));else waiting(el);}
 const popular=stats?.popular?.[0],el=card('OBJETO MÁS COMERCIADO',false);if(popular)el.append(text('strong',title(popular)),text('b',fmt(popular.trades)+' '+(popular.trades==='1'?'trato':'tratos')),text('small','Ventas confirmadas hoy'));else waiting(el);
 for(const currency of [1,0]){const el=card('MAYOR TRATO / '+(currency===1?'ERYNS':'ZEN'),currency===1),s=stats?.records?.find(s=>s.currency===currency);if(s)el.append(text('strong',title(s)),text('b',money(s)),text('small',s.buyer+' ← '+s.seller+' · '+when(s)));else waiting(el);}
}
function connection(stale){
 const badge=q('[data-market-connection]');badge.classList.toggle('is-stale',stale);badge.textContent=stale?'Conexión en espera':'Conectado al mercado';const label=q('[data-market-live-label]');label.classList.toggle('is-stale',stale);label.querySelector('span').textContent=stale?'MERCADO EN ESPERA':'MERCADO EN VIVO';
}
function clock(){
 const clk=q('[data-market-clock]');if(clk)clk.textContent=new Intl.DateTimeFormat('es-AR',{timeZone:'America/Argentina/Buenos_Aires',hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false}).format(new Date());
 q('[data-market-countdown]').textContent=String(Math.max(0,Math.ceil((nextPoll-Date.now())/1000)));
}
// Efectos decorativos livianos: sin lienzo de partículas a pantalla completa.
// Las animaciones solo corren con la sección en pantalla y la pestaña visible.
function ambientState(){
 const paused=effectsPaused||motion.matches;
 root.classList.toggle('effects-paused',paused);root.classList.toggle('effects-background',document.hidden);
 const button=q('[data-market-effects]');if(button){button.disabled=motion.matches;button.setAttribute('aria-pressed',String(paused));button.textContent=motion.matches?'Efectos reducidos':effectsPaused?'Activar efectos ▷':'Pausar efectos Ⅱ';}
 if(paused)root.querySelectorAll('[data-market-metric]:not(strong)').forEach(el=>{cancelAnimationFrame(animations.get(el));if(el.dataset.metricValue)el.textContent=fmt(el.dataset.metricValue);fitMetric(el);el.closest('article')?.classList.remove('is-updating');});
 deal.sync();
}
// Entrada suave de cada bloque al llegar.
const reveal=[...root.querySelectorAll('.vs-rv')];
if('IntersectionObserver' in window && !motion.matches){
 root.classList.add('vs-js');
 const seen=new IntersectionObserver(entries=>entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('is-in');seen.unobserve(e.target);}}),{rootMargin:'0px 0px -8% 0px',threshold:.08});
 reveal.forEach(el=>seen.observe(el));
}
// Cada escena anima solo mientras está en pantalla.
if('IntersectionObserver' in window){
 const live=new IntersectionObserver(entries=>entries.forEach(e=>e.target.classList.toggle('is-live',e.isIntersecting)),{threshold:0});
 root.querySelectorAll('.vs-sc').forEach(el=>live.observe(el));
}
// "Así se sella un trato": cinco pasos que avanzan solos hasta que el jugador elige uno.
const deal=(()=>{
 const stage=q('[data-deal-stage]'),list=q('[data-deal-steps]');
 if(!stage||!list)return{sync(){}};
 const items=[...list.children],caption=q('[data-deal-caption]');
 const captions=['Kaelor publica su Jewel of Chaos en Loren Market.','Vessara guarda el objeto bajo su sello.','La publicación aparece en el juego y en la web.','Nyxara la compra: el sello cae y el trato queda hecho.','Kaelor cobra 950 Eryns, aunque esté desconectado.'];
 const span=5200;let step=0,auto=true,visible=false,timer=0;
 function show(i){step=i;stage.dataset.step=String(i+1);caption.textContent=captions[i];
  items.forEach((li,k)=>{li.classList.toggle('on',k===i);li.classList.toggle('done',k<i);const pg=li.querySelector('.vs-pg');pg.classList.remove('run');if(k===i&&auto&&running()){void pg.offsetWidth;pg.style.setProperty('--d',span+'ms');pg.classList.add('run');}});
 }
 const running=()=>visible&&!document.hidden&&!effectsPaused&&!motion.matches;
 function sync(){clearTimeout(timer);if(auto&&running()){show(step);timer=setTimeout(function next(){show((step+1)%items.length);timer=setTimeout(next,span);},span);}else items.forEach(li=>li.querySelector('.vs-pg').classList.remove('run'));}
 items.forEach((li,i)=>li.querySelector('button').addEventListener('click',()=>{auto=false;clearTimeout(timer);show(i);}));
 if('IntersectionObserver' in window)new IntersectionObserver(e=>{visible=e[0].isIntersecting;sync();},{threshold:.35}).observe(stage);
 document.addEventListener('visibilitychange',sync);
 show(0);
 return{sync};
})();
// Contador grande de la vidriera: publicaciones comunes + destacadas visibles.
(()=>{const big=q('[data-listings-count-big]'),small=q('[data-listings-count]'),feat=q('[data-listings-featured]');if(!big||!small)return;
 const update=()=>{const n=parseInt((small.textContent||'').replace(/\D/g,''),10);const f=feat?feat.querySelectorAll('[data-marquee-original]').length:0;big.textContent=Number.isFinite(n)?fmt(String(n+f)):'—';};
 const mo=new MutationObserver(update);mo.observe(small,{childList:true,characterData:true,subtree:true});if(feat)mo.observe(feat,{childList:true});update();})();
if('IntersectionObserver' in window){const bEl=q('.vs-board');if(bEl)new IntersectionObserver((e,o)=>{if(e[0].isIntersecting){o.disconnect();boardSeen=true;setTimeout(()=>shuffleBoard([...bEl.querySelectorAll('.vs-board-row')]),250);}},{threshold:.25}).observe(bEl);}
// Siempre en movimiento: cada pocos segundos una ficha da una vuelta completa y cae en el mismo dígito.
(()=>{const stats=q('.vessara-stats');if(!stats||!Element.prototype.animate)return;let visible=false;
 if('IntersectionObserver' in window)new IntersectionObserver(e=>{visible=e[0].isIntersecting;},{threshold:.2}).observe(stats);
 function spin(){if(visible&&!document.hidden&&!effectsPaused&&!motion.matches&&stats.classList.contains('is-in')){
  const cols=[...stats.querySelectorAll('.od-col')];if(cols.length){
   const picks=new Set();const count=1+(Math.random()<.45?1:0)+(Math.random()<.2?1:0);
   while(picks.size<Math.min(count,cols.length))picks.add(cols[cols.length-1-(Math.random()*Math.min(6,cols.length)|0)]);
   [...picks].forEach((c,k)=>{const n=Number(c.style.getPropertyValue('--n'))||0;c.animate([{transform:'translateY('+(-n*1.08)+'em)'},{transform:'translateY('+(-(n+10)*1.08)+'em)'}],{duration:900+Math.random()*500,delay:k*120,easing:'cubic-bezier(.3,.05,.2,1)'});c.closest('.od-d')?.animate([{boxShadow:'0 0 0 #0000'},{boxShadow:'0 0 14px #ffd38a88'},{boxShadow:'0 0 0 #0000'}],{duration:1100,delay:k*120});});
  }}
  setTimeout(spin,1800+Math.random()*1600);}
 setTimeout(spin,3000);})();
q('[data-market-effects]')?.addEventListener('click',()=>{effectsPaused=!effectsPaused;ambientState();});motion.addEventListener('change',ambientState);document.addEventListener('visibilitychange',ambientState);ambientState();
function tradeImpact(){if(motion.matches||effectsPaused)return;const burst=q('[data-market-burst]');burst.classList.remove('is-firing');void burst.offsetWidth;burst.classList.add('is-firing');setTimeout(()=>burst.classList.remove('is-firing'),1800);}
// Skip expensive DOM/canvas reconstruction on polls without real market changes.
// The timestamp updates on each refresh but is not a reason to rebuild the UI.
const tradeSnapshotKey=state=>state?JSON.stringify([
 state.metrics,state.sales,state.days,state.highlights,state.serverDay,!!state.stale
]):'';
function render(){
 root.querySelectorAll('[data-market-metric]').forEach(el=>metric(el,data?.metrics?.[el.dataset.marketMetric]));
 highlights();board();spark('zen');spark('eryns');tradingTape();connection(!data||data.stale);
 const daysForComparison=data?.days||[],yesterday=daysForComparison.at(-2);q('[data-market-comparison]').textContent=yesterday?'AYER: '+fmt(yesterday.trades)+' TRATOS':'ACTIVIDAD DEL DÍA';
 const status=q('[data-market-status]');status.classList.toggle('is-stale',!data||data.stale);status.textContent=data?(data.stale?'Último registro disponible':'Actualizado '+new Intl.DateTimeFormat('es-AR',{timeZone:'America/Argentina/Buenos_Aires',hour:'2-digit',minute:'2-digit'}).format(new Date(data.updated*1000))):'Datos no disponibles';
 const bars=q('[data-market-days]');bars.replaceChildren();const days=data?.days||[],max=Math.max(1,...days.map(d=>Number(d.trades)));
 for(const d of days){const col=document.createElement('div');col.className='vessara-day';col.setAttribute('aria-label',d.day+': '+fmt(d.trades)+' tratos');col.title=fmt(d.trades)+' tratos · '+fmt(d.zen)+' Zen · '+fmt(d.eryns)+' Eryns';col.append(text('strong',fmt(d.trades)));const bar=document.createElement('i');bar.style.height=(Number(d.trades)/max*100)+'%';const track=document.createElement('div');track.append(bar);col.append(track,text('span',d.day.slice(8)+'/'+d.day.slice(5,7)));bars.append(col);}
 if(!data)bars.append(text('p','Esperando datos del mercado.'));
 rows();
}
q('[data-market-pause]').addEventListener('click',()=>{const button=q('[data-market-pause]'),paused=button.getAttribute('aria-pressed')!=='true';button.setAttribute('aria-pressed',String(paused));button.setAttribute('aria-label',paused?'Reanudar cinta de ventas':'Pausar cinta de ventas');button.textContent=paused?'▷':'Ⅱ';q('.vessara-ticker').classList.toggle('is-paused',paused);});
root.querySelectorAll('[data-market-currency]').forEach(button=>button.addEventListener('click',()=>{currency=button.dataset.marketCurrency;root.querySelectorAll('[data-market-currency]').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));rows();}));
q('[data-market-search]').addEventListener('input',rows);
async function refresh(){
 if(document.hidden||request)return;request=new AbortController();const timeout=setTimeout(()=>request?.abort(),10000);
 try{const res=await fetch(root.dataset.marketUrl,{headers:{Accept:'application/json'},cache:'no-store',signal:request.signal});if(!res.ok)throw Error('Unavailable');const next=await res.json();if(next?.schema!==2||!Array.isArray(next.sales)||!Array.isArray(next.days)||!next.metrics||!next.highlights||!Array.isArray(next.highlights.popular)||!Array.isArray(next.highlights.records)||!Array.isArray(next.highlights.sellers))throw Error('Invalid');if(data && tradeSnapshotKey(next)===tradeSnapshotKey(data)){
    data=next;
    return;
   }
   const previousIds=new Set((data?.sales||[]).map(s=>s.id));const latest=data?.sales?.[0]?new Date(data.sales[0].soldAt).getTime():0;newIds=new Set(data&&!next.stale?next.sales.filter(s=>!previousIds.has(s.id)&&new Date(s.soldAt).getTime()>=latest).map(s=>s.id):[]);data=next;render();if(newIds.size){tradeImpact();const notice=q('[data-market-notification]');const sealed=data.sales.find(s=>newIds.has(s.id));notice.replaceChildren(text('small',newIds.size===1?'NUEVO TRATO SELLADO':fmt(newIds.size)+' NUEVOS TRATOS SELLADOS'),text('strong',title(sealed)),text('b',money(sealed)));const route=document.createElement('span');route.className='vessara-deal-route';route.append(text('span',sealed.buyer),text('i','→'),text('span',sealed.seller));notice.append(route);notice.classList.toggle('is-eryns',sealed.currency===1);notice.hidden=false;clearTimeout(noticeTimer);noticeTimer=setTimeout(()=>{notice.hidden=true;root.querySelectorAll('.is-new').forEach(el=>el.classList.remove('is-new'));newIds.clear();},6000);}}
 catch{q('[data-market-status]').textContent=data?'Último registro disponible':'Datos no disponibles';q('[data-market-status]').classList.add('is-stale');connection(true);}
 finally{clearTimeout(timeout);request=null;nextPoll=Date.now()+15000;clock();}
}
render();
document.fonts?.ready.then(()=>root.querySelectorAll('strong[data-market-metric]').forEach(fitMetric));
// Only responsive WIDTH changes require a metric re-fit. Observing root
// height caused forced reflows when async market listings/images expanded.
if(typeof ResizeObserver!=='undefined'){
 let measuredWidth=0;
 new ResizeObserver(entries=>{
  const width=entries[0]?.contentRect.width||root.clientWidth;
  if(Math.abs(width-measuredWidth)<1)return;
  measuredWidth=width;
  root.querySelectorAll('strong[data-market-metric]').forEach(fitMetric);
 }).observe(root);
}
clock();setInterval(()=>{if(!document.hidden){clock();if(Date.now()>=nextPoll&&!request)refresh();}},1000);document.addEventListener('visibilitychange',()=>{if(!document.hidden)refresh();});
})();
