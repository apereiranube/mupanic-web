(() => {
'use strict';
const root=document.querySelector('.vessara-page');if(!root)return;
let data=null,currency='all',request=null;
try{data=JSON.parse(document.getElementById('vessara-initial').textContent);}catch{}
const q=s=>root.querySelector(s),fmt=v=>{try{return new Intl.NumberFormat('es-AR').format(BigInt(v));}catch{return '—';}};
const text=(tag,value,cls)=>{const el=document.createElement(tag);el.textContent=value;if(cls)el.className=cls;return el;};
const title=s=>s.item+(s.level?' +'+s.level:'');
function rows(){
 const query=q('[data-market-search]').value.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim();
 const list=(data?.sales||[]).filter(s=>(currency==='all'||String(s.currency)===currency)&&[title(s),s.buyer,s.seller].join(' ').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().includes(query));
 const body=q('[data-market-rows]');body.replaceChildren();
 for(const s of list){
  const tr=document.createElement('tr'),item=document.createElement('td');item.append(text('span','◇','vessara-item-mark'),text('strong',title(s)));tr.append(item,text('td',s.buyer),text('td',s.seller),text('td',fmt(s.price)+' '+(s.currency===0?'Zen':'Eryns'),'vessara-price'+(s.currency===1?' is-eryns':'')));
  const td=document.createElement('td'),time=text('time',new Intl.DateTimeFormat('es-AR',{timeZone:'America/Argentina/Buenos_Aires',day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}).format(new Date(s.soldAt)));time.dateTime=s.soldAt;td.append(time);tr.append(td);body.append(tr);
 }
 const empty=q('[data-market-empty]');empty.hidden=!!list.length;empty.textContent=!data?'El registro del mercado no está disponible en este momento.':(!data.sales.length?'Todavía no hay tratos sellados.':'No hay coincidencias entre los últimos tratos.');
}
const motion=matchMedia('(prefers-reduced-motion: reduce)'),animations=new WeakMap();
function fitMetric(el){
 if(el.tagName!=='STRONG')return;el.style.fontSize='';
 const width=el.clientWidth;if(width&&el.scrollWidth>width)el.style.fontSize=(parseFloat(getComputedStyle(el).fontSize)*width/el.scrollWidth)+'px';
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
function render(){
 root.querySelectorAll('[data-market-metric]').forEach(el=>metric(el,data?.metrics?.[el.dataset.marketMetric]));
 const last=data?.sales?.[0];q('[data-market-last]').textContent=last?title(last):'—';q('[data-market-last-price]').textContent=last?fmt(last.price)+' '+(last.currency===0?'Zen':'Eryns'):'Esperando un nuevo trato';
 const status=q('[data-market-status]');status.classList.toggle('is-stale',!data||data.stale);status.textContent=data?(data.stale?'Último registro disponible':'Actualizado '+new Intl.DateTimeFormat('es-AR',{timeZone:'America/Argentina/Buenos_Aires',hour:'2-digit',minute:'2-digit'}).format(new Date(data.updated*1000))):'Datos no disponibles';
 const bars=q('[data-market-days]');bars.replaceChildren();const days=data?.days||[],max=Math.max(1,...days.map(d=>Number(d.trades)));
 for(const d of days){const col=document.createElement('div');col.className='vessara-day';col.setAttribute('aria-label',d.day+': '+fmt(d.trades)+' tratos');col.title=fmt(d.trades)+' tratos · '+fmt(d.zen)+' Zen · '+fmt(d.eryns)+' Eryns';col.append(text('strong',fmt(d.trades)));const bar=document.createElement('i');bar.style.height=(Number(d.trades)/max*100)+'%';const track=document.createElement('div');track.append(bar);col.append(track,text('span',d.day.slice(8)+'/'+d.day.slice(5,7)));bars.append(col);}
 if(!data)bars.append(text('p','Esperando datos del mercado.'));
 rows();
}
root.querySelectorAll('[data-market-currency]').forEach(button=>button.addEventListener('click',()=>{currency=button.dataset.marketCurrency;root.querySelectorAll('[data-market-currency]').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));rows();}));
q('[data-market-search]').addEventListener('input',rows);
async function refresh(){
 if(document.hidden||request)return;request=new AbortController();const timeout=setTimeout(()=>request?.abort(),10000);
 try{const res=await fetch(root.dataset.marketUrl,{headers:{Accept:'application/json'},cache:'no-store',signal:request.signal});if(!res.ok)throw Error('Unavailable');const next=await res.json();if(next?.schema!==1||!Array.isArray(next.sales)||!Array.isArray(next.days)||!next.metrics)throw Error('Invalid');data=next;render();}
 catch{q('[data-market-status]').textContent=data?'Último registro disponible':'Datos no disponibles';q('[data-market-status]').classList.add('is-stale');}
 finally{clearTimeout(timeout);request=null;}
}
render();if(typeof ResizeObserver!=='undefined')new ResizeObserver(()=>root.querySelectorAll('strong[data-market-metric]').forEach(fitMetric)).observe(root);setInterval(refresh,60000);document.addEventListener('visibilitychange',()=>{if(!document.hidden)refresh();});
})();
