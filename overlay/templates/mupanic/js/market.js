(() => {
'use strict';
const root=document.querySelector('.vessara-page');if(!root)return;
let data=null,currency='all',request=null,nextPoll=Date.now()+15000,tapeKey='',newIds=new Set(),noticeTimer=null,showcaseKey='',effectsPaused=false;
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
function itemFamily(index){
 const group=Math.floor(Number(index)/512);if(group===5)return ['staff','BASTONES'];if(group>=7&&group<=11)return ['armor','ARMADURAS'];if(group===6)return ['shield','ESCUDOS'];if(group<=4)return ['weapon','ARMAS'];if(group===12)return ['wings','ALAS / ARTEFACTOS'];if(group===14)return ['jewel','JOYAS'];return ['relic','ARTEFACTOS'];
}
function showcase(){
 const sales=data?.sales||[],key=JSON.stringify(sales);if(showcaseKey===key)return;showcaseKey=key;const stage=q('[data-market-showcase]');stage.replaceChildren();const unique=new Set();
 for(const sale of sales){const code=sale.itemIndex+':'+sale.level;if(unique.has(code))continue;unique.add(code);const [family,label]=itemFamily(sale.itemIndex),card=document.createElement('article');card.className='vessara-treasure'+(sale.currency===1?' is-eryns':'')+(newIds.has(sale.id)?' is-new':'');const art=document.createElement('div');art.className='vessara-treasure-art';const img=document.createElement('img');img.src=root.dataset.marketAssets+'emblems/'+family+'.svg';img.alt='Emblema de '+label.toLowerCase();img.width=120;img.height=128;art.append(img);card.append(art,text('small',label),text('h3',title(sale)),text('b',money(sale)),text('span',sale.buyer+' · '+when(sale)));stage.append(card);if(unique.size===4)break;}
 if(!unique.size)stage.append(text('p',data?'Los próximos tesoros se sellan en el juego.':'Esperando el registro de tesoros.','vessara-showcase-empty'));
}
const canvas=q('[data-market-atmosphere]'),ctx=canvas.getContext('2d');let ambienceFrame=0,ambienceLast=0,particles=[],cw=0,ch=0;
const sprites=['#ffc978','#cca1ff','#d4ed9c'].map(color=>{const c=document.createElement('canvas');c.width=c.height=24;const cx=c.getContext('2d'),g=cx.createRadialGradient(12,12,0,12,12,12);g.addColorStop(0,color);g.addColorStop(.12,color+'dd');g.addColorStop(.45,color+'33');g.addColorStop(1,color+'00');cx.fillStyle=g;cx.fillRect(0,0,24,24);return c;});
function resizeAtmosphere(){
 cw=innerWidth;ch=innerHeight;const ratio=Math.min(devicePixelRatio||1,1.5);canvas.width=Math.round(cw*ratio);canvas.height=Math.round(ch*ratio);ctx?.setTransform(ratio,0,0,ratio,0,0);particles=Array.from({length:cw<600?22:52},(_,i)=>({x:Math.random()*cw,y:Math.random()*ch,v:.12+Math.random()*.35,r:3+Math.random()*8,phase:Math.random()*6.28,color:i%3}));
}
function atmosphere(now){
 ambienceFrame=0;if(effectsPaused||motion.matches||document.hidden||!ctx){canvas.dataset.running='false';return;}canvas.dataset.running='true';ambienceFrame=requestAnimationFrame(atmosphere);if(now-ambienceLast<33)return;const delta=Math.min(3,(now-ambienceLast)/33);ambienceLast=now;ctx.clearRect(0,0,cw,ch);
 for(const p of particles){p.y-=p.v*delta;p.x+=Math.sin(now/4500+p.phase)*.16*delta;if(p.y<-15){p.y=ch+15;p.x=Math.random()*cw;}ctx.globalAlpha=.3+.45*(.5+.5*Math.sin(now/1400+p.phase));ctx.drawImage(sprites[p.color],p.x,p.y,p.r,p.r);}ctx.globalAlpha=1;
}
function ambientState(){
 cancelAnimationFrame(ambienceFrame);ambienceFrame=0;root.classList.toggle('effects-paused',effectsPaused||motion.matches);root.classList.toggle('effects-background',document.hidden);const button=q('[data-market-effects]');button.disabled=motion.matches;button.setAttribute('aria-pressed',String(effectsPaused||motion.matches));button.textContent=motion.matches?'Efectos reducidos':effectsPaused?'Activar efectos ▷':'Pausar efectos Ⅱ';
 if(effectsPaused||motion.matches||document.hidden){canvas.dataset.running='false';if(motion.matches)ctx?.clearRect(0,0,cw,ch);}else ambienceFrame=requestAnimationFrame(atmosphere);
 if(effectsPaused||motion.matches)root.querySelectorAll('[data-market-metric]').forEach(el=>{cancelAnimationFrame(animations.get(el));if(el.dataset.metricValue)el.textContent=fmt(el.dataset.metricValue);fitMetric(el);el.closest('article')?.classList.remove('is-updating');});
}
q('[data-market-effects]').addEventListener('click',()=>{effectsPaused=!effectsPaused;ambientState();});motion.addEventListener('change',ambientState);document.addEventListener('visibilitychange',ambientState);addEventListener('resize',()=>{resizeAtmosphere();ambientState();},{passive:true});resizeAtmosphere();ambientState();
const hero=q('.vessara-hero');hero.addEventListener('pointermove',e=>{if(e.pointerType==='touch'||motion.matches||effectsPaused)return;const rect=hero.getBoundingClientRect();hero.style.setProperty('--scene-x',((e.clientX-rect.left)/rect.width-.5)*12+'px');hero.style.setProperty('--scene-y',((e.clientY-rect.top)/rect.height-.5)*8+'px');},{passive:true});hero.addEventListener('pointerleave',()=>{hero.style.setProperty('--scene-x','0px');hero.style.setProperty('--scene-y','0px');});
q('[data-market-showcase]').addEventListener('pointermove',e=>{if(e.pointerType==='touch'||motion.matches||effectsPaused)return;const card=e.target.closest('.vessara-treasure');if(!card)return;const rect=card.getBoundingClientRect();card.style.setProperty('--treasure-y',((e.clientX-rect.left)/rect.width-.5)*12+'deg');card.style.setProperty('--treasure-x',-((e.clientY-rect.top)/rect.height-.5)*8+'deg');},{passive:true});q('[data-market-showcase]').addEventListener('pointerout',e=>{const card=e.target.closest('.vessara-treasure');if(card&&!card.contains(e.relatedTarget)){card.style.setProperty('--treasure-y','0deg');card.style.setProperty('--treasure-x','0deg');}});
function tradeImpact(){if(motion.matches||effectsPaused)return;const burst=q('[data-market-burst]');burst.classList.remove('is-firing');void burst.offsetWidth;burst.classList.add('is-firing');setTimeout(()=>burst.classList.remove('is-firing'),1800);}
function render(){
 root.querySelectorAll('[data-market-metric]').forEach(el=>metric(el,data?.metrics?.[el.dataset.marketMetric]));
 const last=data?.sales?.[0];q('[data-market-last]').textContent=last?title(last):'—';q('[data-market-last-price]').textContent=last?fmt(last.price)+' '+(last.currency===0?'Zen':'Eryns'):'Esperando un nuevo trato';
 q('[data-market-last-actors]').textContent=last?last.buyer+' compró a '+last.seller+' · '+when(last):'';
 spark('zen');spark('eryns');tradingTape();showcase();connection(!data||data.stale);
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
 try{const res=await fetch(root.dataset.marketUrl,{headers:{Accept:'application/json'},cache:'no-store',signal:request.signal});if(!res.ok)throw Error('Unavailable');const next=await res.json();if(next?.schema!==1||!Array.isArray(next.sales)||!Array.isArray(next.days)||!next.metrics)throw Error('Invalid');const previousIds=new Set((data?.sales||[]).map(s=>s.id));const latest=data?.sales?.[0]?new Date(data.sales[0].soldAt).getTime():0;newIds=new Set(data&&!next.stale?next.sales.filter(s=>!previousIds.has(s.id)&&new Date(s.soldAt).getTime()>=latest).map(s=>s.id):[]);data=next;render();if(newIds.size){tradeImpact();const notice=q('[data-market-notification]');notice.textContent=newIds.size===1?'Nuevo trato sellado en Vessara ↗':fmt(newIds.size)+' nuevos tratos sellados en Vessara ↗';notice.hidden=false;clearTimeout(noticeTimer);noticeTimer=setTimeout(()=>{notice.hidden=true;root.querySelectorAll('.is-new').forEach(el=>el.classList.remove('is-new'));newIds.clear();},6000);}}
 catch{q('[data-market-status]').textContent=data?'Último registro disponible':'Datos no disponibles';q('[data-market-status]').classList.add('is-stale');connection(true);}
 finally{clearTimeout(timeout);request=null;nextPoll=Date.now()+15000;clock();}
}
render();document.fonts?.ready.then(()=>root.querySelectorAll('strong[data-market-metric]').forEach(fitMetric));if(typeof ResizeObserver!=='undefined')new ResizeObserver(()=>root.querySelectorAll('strong[data-market-metric]').forEach(fitMetric)).observe(root);clock();setInterval(()=>{if(!document.hidden){clock();if(Date.now()>=nextPoll&&!request)refresh();}},1000);document.addEventListener('visibilitychange',()=>{if(!document.hidden)refresh();});
})();
