(() => {
'use strict';
const root=document.querySelector('.vessara-page');if(!root)return;
let data=null,currency='all',request=null,nextPoll=Date.now()+15000,tapeKey='',newIds=new Set(),noticeTimer=null;
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
function metric(el,value){
 const valid=/^\d+$/.test(String(value)),previous=el.dataset.metricValue;
 if(!valid){cancelAnimationFrame(animations.get(el));delete el.dataset.metricValue;el.textContent='—';return;}
 value=String(value);if(previous===value)return;
 cancelAnimationFrame(animations.get(el));el.dataset.metricValue=value;
 if(previous===undefined||motion.matches){el.textContent=fmt(value);fitMetric(el);return;}
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
function tickerSale(s){
 const el=document.createElement('span');el.className='vessara-ticker-sale'+(s.currency===1?' is-eryns':'');el.append(text('strong',title(s)),text('b',money(s)),text('small',s.buyer+' ← '+s.seller),text('small',when(s)));return el;
}
function tradingTape(){
 const sales=data?.sales||[],key=JSON.stringify(sales);if(tapeKey===key)return;tapeKey=key;
 const feed=q('[data-market-feed]');feed.replaceChildren();
 for(const s of sales.slice(0,6)){
  const entry=document.createElement('article');entry.className='vessara-feed-entry'+(s.currency===1?' is-eryns':'')+(newIds.has(s.id)?' is-new':'');
  const line=document.createElement('p');line.append(text('strong',s.buyer),document.createTextNode(' compró '+title(s)));entry.append(line,text('b',money(s)),text('small',when(s)+' · Vendedor: '+s.seller));feed.append(entry);
 }
 if(!sales.length)feed.append(text('p',data?'El próximo trato empieza en el juego.':'Esperando conexión con el registro.','vessara-feed-empty'));
 const ticker=q('[data-market-ticker]');ticker.replaceChildren();ticker.classList.toggle('is-static',!sales.length);
 if(!sales.length){ticker.append(text('span',data?'Todavía no hay tratos sellados.':'Esperando el registro del mercado.','vessara-ticker-empty'));return;}
 const group=document.createElement('div');group.className='vessara-ticker-group';const recent=sales.slice(0,8);recent.forEach(s=>group.append(tickerSale(s)));ticker.append(group);
 for(let i=0;group.scrollWidth<q('.vessara-ticker-window').clientWidth+100&&i<8;i++)recent.forEach(s=>{const copy=tickerSale(s);copy.setAttribute('aria-hidden','true');group.append(copy);});
 const duplicate=group.cloneNode(true);duplicate.setAttribute('aria-hidden','true');ticker.append(duplicate);
}
function connection(stale){
 const badge=q('[data-market-connection]');badge.classList.toggle('is-stale',stale);badge.textContent=stale?'Registro en espera':'Conectado al mercado';
}
function clock(){
 q('[data-market-clock]').textContent=new Intl.DateTimeFormat('es-AR',{timeZone:'America/Argentina/Buenos_Aires',hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false}).format(new Date());
 q('[data-market-countdown]').textContent=String(Math.max(0,Math.ceil((nextPoll-Date.now())/1000)));
}
function render(){
 root.querySelectorAll('[data-market-metric]').forEach(el=>metric(el,data?.metrics?.[el.dataset.marketMetric]));
 const last=data?.sales?.[0];q('[data-market-last]').textContent=last?title(last):'—';q('[data-market-last-price]').textContent=last?fmt(last.price)+' '+(last.currency===0?'Zen':'Eryns'):'Esperando un nuevo trato';
 q('[data-market-last-actors]').textContent=last?last.buyer+' compró a '+last.seller+' · '+when(last):'';
 spark('zen');spark('eryns');tradingTape();connection(!data||data.stale);
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
 try{const res=await fetch(root.dataset.marketUrl,{headers:{Accept:'application/json'},cache:'no-store',signal:request.signal});if(!res.ok)throw Error('Unavailable');const next=await res.json();if(next?.schema!==1||!Array.isArray(next.sales)||!Array.isArray(next.days)||!next.metrics)throw Error('Invalid');const previousIds=new Set((data?.sales||[]).map(s=>s.id));const latest=data?.sales?.[0]?new Date(data.sales[0].soldAt).getTime():0;newIds=new Set(data&&!next.stale?next.sales.filter(s=>!previousIds.has(s.id)&&new Date(s.soldAt).getTime()>=latest).map(s=>s.id):[]);data=next;render();if(newIds.size){const notice=q('[data-market-notification]');notice.textContent=newIds.size===1?'Nuevo trato sellado en Vessara ↗':fmt(newIds.size)+' nuevos tratos sellados en Vessara ↗';notice.hidden=false;clearTimeout(noticeTimer);noticeTimer=setTimeout(()=>{notice.hidden=true;root.querySelectorAll('.is-new').forEach(el=>el.classList.remove('is-new'));newIds.clear();},6000);}}
 catch{q('[data-market-status]').textContent=data?'Último registro disponible':'Datos no disponibles';q('[data-market-status]').classList.add('is-stale');connection(true);}
 finally{clearTimeout(timeout);request=null;nextPoll=Date.now()+15000;clock();}
}
render();document.fonts?.ready.then(()=>root.querySelectorAll('strong[data-market-metric]').forEach(fitMetric));if(typeof ResizeObserver!=='undefined')new ResizeObserver(()=>root.querySelectorAll('strong[data-market-metric]').forEach(fitMetric)).observe(root);clock();setInterval(()=>{if(!document.hidden){clock();if(Date.now()>=nextPoll&&!request)refresh();}},1000);document.addEventListener('visibilitychange',()=>{if(!document.hidden)refresh();});
})();
