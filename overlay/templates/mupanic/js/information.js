/* MU PANIC · El servidor: demos animadas, reloj de eventos y brasas. */
(function(){
  // Copia de los eventos habilitados con horario (Atlas, data-event-data). En el template se lee del JSON real.
  var ROOT=document.getElementById('srv-page');if(!ROOT)return;
  var IMG=ROOT.getAttribute('data-img');
  var EVENTS=[];try{EVENTS=JSON.parse(document.getElementById('srv-events').textContent)||[]}catch(e){}
  var GN={classic:'Clásico',invasion:'Invasión',custom:'Competencia'};
  var filter='all';
  var $=function(id){return document.getElementById(id)};
  function pad(n){return String(n).padStart(2,'0')}
  // Minutos desde la medianoche en Argentina (UTC-3, sin horario de verano)
  function nowArt(){var d=new Date(Date.now()-3*3600e3);return d.getUTCHours()*60+d.getUTCMinutes()+d.getUTCSeconds()/60}
  function toMin(s){var p=s.split(':');return +p[0]*60+ +p[1]}
  function rel(m){m=Math.max(0,Math.round(m));if(m<1)return'ahora';var h=Math.floor(m/60),r=m%60;return'en '+(h?h+' h '+(r?r+' min':''):r+' min').trim()}
  function occurrences(now){
    var out=[];EVENTS.forEach(function(e){e.t.forEach(function(t){var s=toMin(t);
      [0,1440].forEach(function(off){var start=s+off;if(start+e.d>now&&start<now+1440)out.push({e:e,start:start,end:start+e.d,t:t,tom:off>0})});});});
    return out.sort(function(a,b){return a.start-b.start});
  }
  function render(){
    var now=nowArt(),occ=occurrences(now);
    var nx=occ[0];
    if(nx){
      var live=nx.start<=now,target=live?nx.end:nx.start,left=Math.max(0,(target-now)*60);
      var st=$('nx-state');st.classList.toggle('live',live);st.lastElementChild.textContent=live?'En curso ahora':'Próximo evento';
      $('nx-name').textContent=nx.e.n;
      $('nx-when').textContent=(live?'Termina a las ':'Empieza a las ')+(live?pad(Math.floor(nx.end%1440/60))+':'+pad(nx.end%60):nx.t)+' · '+GN[nx.e.g]+' · dura '+nx.e.d+' min';
      var img=$('nx-img'),src=(nx.e.i.indexOf('data:')===0?'':IMG)+nx.e.i;if(img.getAttribute('src')!==src)img.setAttribute('src',src);
      [['c-h',Math.floor(left/3600)],['c-m',Math.floor(left%3600/60)],['c-s',Math.floor(left%60)]].forEach(function(x){
        var el=$(x[0]),v=pad(x[1]);if(el.textContent!==v){el.textContent=v;el.classList.remove('tick');void el.offsetWidth;el.classList.add('tick')}});
    }
    var list=$('ev-list'),rows=occ.filter(function(o){return filter==='all'||o.e.g===filter}).slice(0,10);
    list.innerHTML=rows.length?rows.map(function(o){var live=o.start<=now;
      return '<li class="'+(live?'live':'')+(o.tom?' tomorrow':'')+'"><time>'+o.t+'</time><span class="n">'+o.e.n+'<span class="g">'+GN[o.e.g]+(o.tom?' · mañana':'')+'</span></span><span class="rel">'+(live?'En curso':rel(o.start-now))+'</span></li>'}).join('')
      :'<li class="empty">No hay eventos de esta categoría en las próximas 24 horas.</li>';
  }
  function ruler(){
    var r=$('ruler'),now=nowArt(),h='<div class="track"></div>';
    for(var i=0;i<=24;i+=3)h+='<span class="tick" style="left:'+(i/24*100)+'%">'+pad(i)+'</span>';
    EVENTS.forEach(function(e){e.t.forEach(function(t){var m=toMin(t);h+='<span class="ev '+(e.g==='invasion'?'inv':e.g==='custom'?'cus':'')+(m+e.d<now?' done':'')+(m>now&&m-now<=60?' soon':'')+'" style="left:'+(m/1440*100)+'%" title="'+t+' '+e.n+'"></span>'})});
    h+='<span class="now" style="left:'+(now/1440*100)+'%"></span>';r.innerHTML=h;
  }
  document.querySelectorAll('.filters button').forEach(function(b){b.addEventListener('click',function(){filter=b.dataset.g;
    document.querySelectorAll('.filters button').forEach(function(x){x.setAttribute('aria-pressed',x===b)});render()})});
  render();ruler();setInterval(render,1000);setInterval(ruler,60000);

  var reduce=window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Clases: recorrido automático hasta que el jugador toca o pasa el mouse
  var cls=[].slice.call(document.querySelectorAll('.cls')),ci=0,auto=!reduce,timer;
  function openCls(i){cls.forEach(function(c,k){c.classList.toggle('on',k===i);var p=c.querySelector('.prog');p.classList.remove('run');if(k===i&&auto){void p.offsetWidth;p.classList.add('run')}})}
  function stopAuto(){auto=false;clearInterval(timer);cls.forEach(function(c){c.querySelector('.prog').classList.remove('run')})}
  cls.forEach(function(c,i){c.querySelector('button').addEventListener('click',function(){stopAuto();openCls(i)})});
  $('roster').addEventListener('mouseenter',function(){stopAuto();cls.forEach(function(c){c.classList.remove('on')})});
  openCls(0);
  if(auto)timer=setInterval(function(){if(!auto)return;ci=(ci+1)%cls.length;openCls(ci)},4000);

  // Aparición al hacer scroll (lo visible al cargar no se oculta nunca)
  ROOT.classList.add('js');
  var targets=[].slice.call(document.querySelectorAll('.rv,[data-anim]'));
  var vh=window.innerHeight;
  targets.forEach(function(el){if(el.getBoundingClientRect().top>vh*0.92)el.classList.add('wait')});
  function countUp(root){[].slice.call(root.querySelectorAll('[data-to]')).forEach(function(el){
    var to=+el.dataset.to,suf=el.dataset.suf||'',t0=null;if(reduce||el.dataset.done)return;el.dataset.done=1;
    function step(t){t0=t0||t;var k=Math.min(1,(t-t0)/1300);k=1-Math.pow(1-k,3);el.textContent=Math.round(to*k)+suf;if(k<1)requestAnimationFrame(step)}
    requestAnimationFrame(step)})}
  if('IntersectionObserver' in window){
    var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.remove('wait');countUp(e.target);io.unobserve(e.target)}})},{threshold:.18});
    targets.forEach(function(el){io.observe(el)});
  }else targets.forEach(function(el){el.classList.remove('wait')});


  // PvP: explicación guiada. Réplica de la barra del cliente (vida + SD pegado + destello de daño + latido < 30%).
  (function(){
    var LM=100,SM=40,T=LM+SM,L=LM,Sd=SM,lostR=0,lostOn=false,tA,tB,tC;
    var life=$('bLife'),sd=$('bSd'),lost=$('bLost'),co=$('callout'),coT=$('callout-t'),fl=$('floaters'),bar=$('bar');
    function pct(v){return v/T*100}
    function draw(){life.style.width=pct(L)+'%';sd.style.left=pct(L)+'%';sd.style.width=pct(Sd)+'%';
      var f=L/LM,c=life.classList;c.remove('lime','warn','crit');if(f<.3)c.add('crit');else if(f<.45)c.add('warn');else if(f<.65)c.add('lime')}
    function set(l,s){L=l;Sd=s;lost.style.transition='none';lost.style.width='0';lostOn=false;draw()}
    function num(n){return '−'+String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g,'.')}
    function floater(txt,cls,atPct){var d=document.createElement('span');d.className='dmg'+(cls?' '+cls:'');d.textContent=txt;d.style.left=Math.max(8,Math.min(92,atPct+(Math.random()-.5)*10))+'%';fl.appendChild(d);setTimeout(function(){d.remove()},1000)}
    function hit(dmg){
      var before=pct(L+Sd),toSd=Math.min(Sd,dmg*.9),wasSd=toSd>0;Sd-=toSd;L=Math.max(0,L-(dmg-toSd));draw();
      var after=pct(L+Sd);lostR=lostOn?Math.max(lostR,before):before;lostOn=true;
      lost.style.transition='none';lost.style.left=after+'%';lost.style.width=(lostR-after)+'%';lost.style.background='#fff';void lost.offsetWidth;
      lost.style.transition='background .3s ease';lost.style.background='#5a0d0a';
      clearTimeout(tA);tA=setTimeout(function(){var w=lostR-after;lost.style.transition='width .4s ease';lost.style.width='0';lostOn=false},650);
      floater(num(dmg*118),wasSd?'sd':'',(after+before)/2);
    }
    function potion(amount){sd.classList.remove('fill');void sd.offsetWidth;sd.classList.add('fill');Sd=Math.min(SM,Sd+amount);draw();floater('+ Escudo','heal',pct(L+Sd/2));setTimeout(function(){sd.classList.remove('fill')},1000)}
    function callout(part,text){coT.textContent=text;var c;
      if(part==='life')c=pct(L)/2;else if(part==='sd')c=pct(L)+pct(Sd)/2;else if(part==='lost')c=pct(L+Sd)+4;else c=50;
      co.style.left=Math.max(10,Math.min(90,c))+'%';co.classList.add('show')}
    function hide(){co.classList.remove('show')}
    var STEPS=[
      {s:[100,40],d:4200,a:[[300,function(){callout('life','Tu vida')}],[2100,function(){callout('sd','Tu escudo (SD)')}]]},
      {s:[100,40],d:4400,a:[[200,function(){callout('sd','El SD absorbe el golpe')}],[600,function(){hit(10)}],[1600,function(){hit(12)}],[2600,function(){hit(11)}],[2700,function(){callout('sd','El SD absorbe el golpe')}]]},
      {s:[97,11],d:4400,a:[[400,function(){hit(13)}],[900,function(){callout('life','Sin escudo: directo a la vida')}],[1500,function(){hit(14)}],[2500,function(){hit(12)}]]},
      {s:[69,0],d:4000,a:[[500,function(){hit(6)}],[680,function(){hit(6.6)}],[860,function(){hit(5.4)}],[900,function(){callout('lost','El daño queda marcado')}]]},
      {s:[51,0],d:4600,a:[[400,function(){hit(16)}],[1500,function(){hit(13)}],[1800,function(){callout('life','Menos del 30%: peligro')}]]},
      {s:[22,0],d:4800,a:[[600,function(){potion(16)}],[800,function(){callout('sd','Poción de escudo')}]]}
    ];
    var btns=[].slice.call(document.querySelectorAll('#steps button')),cur=0,timers=[],autoP=!reduce,vis=false;
    function clear(){timers.forEach(clearTimeout);timers=[]}
    function play(i){clear();hide();cur=i;var st=STEPS[i];set(st.s[0],st.s[1]);
      btns.forEach(function(b,k){var li=b.parentElement,pg=b.querySelector('.pg');li.classList.toggle('on',k===i);pg.classList.remove('run');if(k===i){pg.style.setProperty('--d',st.d+'ms');void pg.offsetWidth;pg.classList.add('run')}});
      if(reduce){st.a.forEach(function(a){a[1]()});return}
      st.a.forEach(function(a){timers.push(setTimeout(a[1],a[0]))});
      timers.push(setTimeout(function(){if(autoP&&vis)play((i+1)%STEPS.length);else if(autoP)pending=true},st.d));
    }
    var pending=false;
    btns.forEach(function(b,k){b.addEventListener('click',function(){play(k)})});
    if('IntersectionObserver' in window)new IntersectionObserver(function(e){vis=e[0].isIntersecting;if(vis&&pending){pending=false;play((cur+1)%STEPS.length)}},{threshold:.35}).observe($('steps').parentElement);else vis=true;
    play(0);
  })();
  // Furia: explicación guiada
  (function(){
    var st=$('fstage'),bar=$('fbar'),ready=$('fready'),key=$('fkey'),chips=[].slice.call(document.querySelectorAll('#fchips span')),
        dig=$('fdig'),lab=$('flabel'),mas=$('fmas'),cls=$('fcls'),clsS=[].slice.call(cls.children),co=$('fco'),coT=$('fco-t'),fl=$('ffloat');
    var f=0,drain=null,timers=[],cur=0,vis=false,pending=false;
    var POS=[0,9.0909,18.1818,27.2727,36.3636,45.4545,54.5454,63.6363,72.7272,81.8181,90.909];
    function fill(v){f=Math.max(0,Math.min(100,v));bar.style.setProperty('--f',f+'%')}
    function T(ms,fn){timers.push(setTimeout(fn,ms))}
    function every(ms,fn){var id=setInterval(fn,ms);timers.push({i:id});return id}
    function clearAll(){timers.forEach(function(t){t&&t.i?clearInterval(t.i):clearTimeout(t)});timers=[]}
    function float(txt,cls,x){var d=document.createElement('span');d.className='dmg '+cls;d.textContent=txt;d.style.left=x+'%';fl.appendChild(d);setTimeout(function(){d.remove()},1000)}
    function callout(text,x){coT.textContent=text;co.style.left=x+'%';co.classList.add('show')}
    function digits(n){var s='x'+n;dig.innerHTML=s.split('').map(function(c){var p=c==='x'?POS[10]:POS[+c];return '<i style="background-position:'+p+'% 0"></i>'}).join('');dig.classList.add('in');dig.classList.remove('bump');void dig.offsetWidth;dig.classList.add('bump')}
    function label(t){lab.textContent=t;lab.classList.remove('in');void lab.offsetWidth;if(t)lab.classList.add('in')}
    function reset(){clearAll();co.classList.remove('show');st.classList.remove('active','flash','shake');bar.classList.remove('lit','on');ready.classList.remove('show');key.classList.remove('press');
      chips.forEach(function(c){c.classList.remove('in')});dig.classList.remove('in');dig.innerHTML='';label('');mas.hidden=true;mas.classList.remove('in');cls.classList.remove('in');clsS.forEach(function(c){c.classList.remove('hot')});pw.classList.remove('in')}
    function activeOn(withFlash){st.classList.add('active');bar.classList.add('on','lit');if(withFlash){st.classList.remove('flash');void st.offsetWidth;st.classList.add('flash')}}
    function startDrain(rate){every(160,function(){fill(f-rate)})}
    function kill(gain,x){fill(f+gain);float('+ segundos','time',x||60)}
    var STEPS=[
      {d:5600,run:function(){fill(0);var n=0;T(500,function(){callout('Cada golpe carga la barra',40)});
        every(330,function(){n++;if(n>15)return;var pvp=n>9;fill(f+(pvp?11:5));float(pvp?'+ carga PvP':'+ carga','gain',30+Math.random()*50);if(n===10)callout('En PvP carga más rápido',70)})},
        end:function(){fill(100)}},
      {d:4400,run:function(){fill(100);bar.classList.add('lit');ready.classList.add('show');T(300,function(){callout('La calavera despierta',8)});
        T(2600,function(){key.classList.add('press');callout('Soltá Shift',50)});T(2900,function(){key.classList.remove('press')})}},
      {d:5200,run:function(){fill(100);T(200,function(){activeOn(true);callout('¡Furia!',50)});chips.forEach(function(c,i){T(700+i*420,function(){c.classList.add('in')})});T(1200,function(){startDrain(.35)})},
        end:function(){activeOn(false);chips.forEach(function(c){c.classList.add('in')})}},
      {d:5600,run:function(){fill(72);activeOn(false);chips.forEach(function(c){c.classList.add('in')});startDrain(1.1);
        T(300,function(){callout('La barra se consume',30)});[1300,2300,3200,4100].forEach(function(t){T(t,function(){kill(14,50+Math.random()*30)})});T(1500,function(){callout('Cada muerte suma tiempo',62)})}},
      {d:6400,run:function(){fill(80);activeOn(false);startDrain(.6);var n=0,times=[400,800,1150,1450,1700,1950,2150,2350,2550,2730,2900,3060,3220,3370,3510,3640,3770,3890,4000,4110];
        times.forEach(function(t){T(t,function(){n++;digits(n);fill(f+3);
          if(n===5)label('Combo');if(n===12)label('Imparable');
          if(n===20){label('');mas.hidden=false;void mas.offsetWidth;mas.classList.add('in');st.classList.remove('shake');void st.offsetWidth;st.classList.add('shake')}})})},
        end:function(){activeOn(false);digits(20);mas.hidden=false;mas.classList.add('in')}},
      {d:9200,run:function(){fill(85);activeOn(false);startDrain(.22);cls.classList.add('in');T(200,function(){callout('Un poder por clase',50)});
        clsS.forEach(function(c,i){T(500+i*1200,function(){power(i)})})},
        end:function(){activeOn(false);cls.classList.add('in');power(2)}}
    ];
    // Poderes de clase durante la Furia (modo-panico.md, tabla de Panico.lua V13)
    var POW=[
      ['COLOSO','Resiste el aturdimiento y parte de cada golpe atraviesa la defensa rival.'],
      ['CATACLISMO','Maná infinito mientras dura la Furia y más golpes excelentes.'],
      ['CERTERA','Sus flechas atraviesan la defensa y aciertan más críticos.'],
      ['DUELISTA','Más chance de doble daño y más golpes excelentes.'],
      ['EMPERADOR','Más críticos y chance de triple daño.'],
      ['ROMPEALMAS','Parte de su daño atraviesa el escudo SD del rival.'],
      ['VAMPIRO','Cada golpe le devuelve vida.']
    ];
    var pw=$('fpow'),pwC=$('fpow-c'),pwN=$('fpow-n'),pwD=$('fpow-d');
    function power(i){clsS.forEach(function(x,k){x.classList.toggle('hot',k===i)});pwC.textContent=clsS[i].textContent;pwN.textContent=POW[i][0];pwD.textContent=POW[i][1];
      pw.classList.add('in');pw.classList.remove('swap');void pw.offsetWidth;pw.classList.add('swap')}
    var btns=[].slice.call(document.querySelectorAll('#fsteps button'));
    function play(i){reset();cur=i;var s=STEPS[i];st.dataset.step=i;
      btns.forEach(function(b,k){var li=b.parentElement,pg=b.querySelector('.pg');li.classList.toggle('on',k===i);pg.classList.remove('run');if(k===i){pg.style.setProperty('--d',s.d+'ms');void pg.offsetWidth;pg.classList.add('run')}});
      if(reduce){(s.end||s.run)();clearAll();return}
      s.run();T(s.d,function(){if(vis)play((i+1)%STEPS.length);else pending=true});
    }
    btns.forEach(function(b,k){b.addEventListener('click',function(){play(k)})});
    clsS.forEach(function(c,i){c.addEventListener('click',function(){if(cur!==5)return;clearAll();power(i)})});
    if('IntersectionObserver' in window)new IntersectionObserver(function(e){vis=e[0].isIntersecting;if(vis&&pending){pending=false;play((cur+1)%STEPS.length)}},{threshold:.35}).observe(st);else vis=true;
    play(0);
  })();
  // Simulador de resets (tramos del Atlas: 200/250/300/350 puntos, 5 resets por etapa)
  (function(){
    var r=$('simR'),out=$('simOut'),pts=$('simPts'),nxt=$('simNext'),ol=$('ladderOl'),lis=[].slice.call(ol.children),shown=0,raf;
    function per(r){return r<=4?200:r<=9?250:r<=14?300:350}
    function total(n){var t=0;for(var r=1;r<=n;r++)t+=per(r);return t}
    var LV=1995,LV2=2793;
    function fmt(n){return String(n).replace(/\B(?=(\d{3})+(?!\d))/g,'.')}
    function animTo(v){cancelAnimationFrame(raf);var from=shown,t0=null;(function st(t){t0=t0||t;var k=Math.min(1,(t-t0)/350);shown=Math.round(from+(v-from)*k);pts.textContent=fmt(shown);if(k<1)raf=requestAnimationFrame(st)})(performance.now())}
    function upd(){var n=+r.value;r.style.setProperty('--p',(n/20*100)+'%');out.firstChild.nodeValue=n;
      ol.classList.add('simming');var st=n===0?-1:(n<=4?0:n<=9?1:n<=14?2:3),nx=n>=20?3:(n+1<=4?0:n+1<=9?1:n+1<=14?2:3);lis.forEach(function(li,i){li.classList.toggle('reached',i<=st);li.classList.toggle('now',i===nx)});
      if(n>=20){nxt.textContent='Máximo';nxt.classList.add('max')}else{nxt.textContent='+'+per(n+1);nxt.classList.remove('max')}
      $('simTot').textContent=fmt(total(n)+LV);$('simTot2').textContent=fmt(total(n)+LV2);
      if(reduce){shown=total(n);pts.textContent=fmt(shown)}else animTo(total(n))}
    r.addEventListener('input',function(){auto=false;upd()});
    var auto=!reduce,played=false;
    function demo(){if(played)return;played=true;var n=0;var id=setInterval(function(){if(!auto){clearInterval(id);return}n++;r.value=n;upd();if(n>=20){clearInterval(id);setTimeout(function(){if(!auto)return;r.value=7;upd()},1400)}},140)}
    upd();ol.classList.remove('simming');
    if(auto&&'IntersectionObserver' in window)new IntersectionObserver(function(e,o){if(e[0].isIntersecting){o.disconnect();setTimeout(demo,500)}},{threshold:.6}).observe($('sim'));
  })();

  // Launcher real: Guard + verificación (sobre la captura)
  (function(){
    var L=$('lch'),pill=$('lPill'),pT=$('lPillT'),toast=$('lToast'),t=$('lT'),s=$('lS'),bar=$('lBar'),timers=[],vis=false,started=false,pending=false;
    function T(ms,fn){timers.push(setTimeout(fn,ms))}
    function st(cls,title,sub,w){L.classList.remove('bad','lock','ok');(cls||'').split(' ').forEach(function(c){if(c)L.classList.add(c)});t.textContent=title;s.textContent=sub;if(w!=null)bar.style.width=w+'%'}
    function guard(c,html){pill.className='gpill'+(c?' '+c:'');pT.innerHTML=html}
    var FILES=['Archivos del juego','Interfaz y texturas','Scripts del Hub','Sonidos y efectos'];
    function run(){timers.forEach(clearTimeout);timers=[];toast.classList.remove('show');
      guard('scan','<b>Guard</b> revisando');st('lock','Verificando archivos','Comparando con la versión oficial…',0);
      FILES.forEach(function(f,i){T(500+i*650,function(){st('lock','Verificando archivos · '+(i+1)+'/4',f+' · original',(i+1)*14)})});
      T(3300,function(){st('lock','Buscando programas de trampa','Navegador · Discord · Música · permitido',70)});
      T(4600,function(){guard('bad','<b>Guard</b> trampa detectada');toast.classList.add('show');st('bad lock','Programa de trampa detectado','Cheat Engine 7.5 está abierto. Cerralo para poder jugar.',82)});
      T(8200,function(){toast.classList.remove('show');guard('scan','<b>Guard</b> revisando');st('lock','Revisando de nuevo','Programa cerrado.',90)});
      T(9400,function(){guard('','<b>Guard</b> activo');st('ok','Listo para jugar','Tus archivos son originales. Durante la partida, Guard sigue vigilando.',100)});
      T(14000,function(){if(vis)run();else pending=true});
    }
    if(reduce){guard('','<b>Guard</b> activo');st('ok','Listo para jugar','Tus archivos son originales.',100);return}
    if('IntersectionObserver' in window)new IntersectionObserver(function(e){vis=e[0].isIntersecting;if(vis&&(!started||pending)){started=true;pending=false;run()}},{threshold:.4}).observe(L);else run();
  })();
  // Circuito de los Eryns
  (function(){
    var nodes=[].slice.call(document.querySelectorAll('#loop .lnode')),tok=$('lTok'),line=$('lLine'),cap=$('lCap'),sum=$('lSum'),coins=$('coins'),i=-1,vis=false,total=0,id;
    var CAPS=['Cazás y cae una Jewel of Bless.','La publicás en el Mercado de Vessara y le ponés precio en Eryns.','Te desconectás. Tu publicación queda en custodia y sigue a la venta.','Alguien la compra y los Eryns quedan en tu cuenta. Sin poner un peso.'];
    var POS=[12.5,37.5,62.5,87.5];
    function fmt(n){return String(n).replace(/\B(?=(\d{3})+(?!\d))/g,'.')}
    function burst(){var r=coins.getBoundingClientRect(),n=nodes[3].querySelector('.lic').getBoundingClientRect();
      for(var k=0;k<14;k++){var c=document.createElement('i');c.className='coin';c.style.left=(n.left-r.left+n.width/2-6)+'px';c.style.top=(n.top-r.top+n.height/2-6)+'px';
        var a=Math.random()*Math.PI*2,d=40+Math.random()*50;c.style.setProperty('--dx',Math.cos(a)*d+'px');c.style.setProperty('--dy',(Math.sin(a)*d-20)+'px');c.style.animationDelay=(k*25)+'ms';coins.appendChild(c);setTimeout(function(x){return function(){x.remove()}}(c),1400)}}
    function go(k){i=k;nodes.forEach(function(n,j){n.classList.toggle('on',j===k);n.classList.toggle('done',j<k)});
      tok.style.left=POS[k]+'%';line.style.width=(k/3*100)+'%';cap.classList.add('fade');setTimeout(function(){cap.textContent=CAPS[k];cap.classList.remove('fade')},200);
      if(k===3){if(!reduce)burst();var from=total,to=total+1140,t0=null;total=to;
        (function st(t){t0=t0||t;var q=Math.min(1,(t-t0)/800);sum.textContent=fmt(Math.round(from+(to-from)*q));if(q<1)requestAnimationFrame(st)})(performance.now())}}
    function tick(){if(!vis)return;go((i+1)%4)}
    if(reduce){go(3);return}
    go(0);
    if('IntersectionObserver' in window)new IntersectionObserver(function(e){vis=e[0].isIntersecting},{threshold:.4}).observe($('loop'));else vis=true;
    id=setInterval(tick,2300);
  })();
  // Plaza voxel de Loren Market (three.js) + destacado sincronizado con la vidriera web
  (function(){
    var f=$('feat'),sw=$('fswitch'),rows=[].slice.call(document.querySelectorAll('#vlist .vrow')),vnote=$('vnote'),on=false,auto=!reduce,vis=false,H=66;
    var COIN=IMG+'market/eryns-vessara-v1.webp';
    function layout(){var order=on?['me','a','b']:['a','b','me'];rows.forEach(function(r){r.style.top=(order.indexOf(r.dataset.k)*H)+'px';r.classList.toggle('star',on&&r.dataset.k==='me')});
      vnote.textContent=on?'Primera en Destacados, y un vendedor la ofrece en la plaza.':'Tu publicación espera en la vidriera, junto a las demás.'}
    function set(v){on=v;sw.setAttribute('aria-pressed',v);f.classList.toggle('on',v);layout();if(window.PZ)PZ.setStar(v)}
    sw.addEventListener('click',function(){auto=false;set(!on)});
    layout();
    var box=$('plaza3d');
    if('IntersectionObserver' in window)new IntersectionObserver(function(e){vis=e[0].isIntersecting},{threshold:.15}).observe(box);else vis=true;
    var phase=0;if(!reduce)setInterval(function(){if(!auto||!vis)return;phase=(phase+1)%5;if(phase===1)set(true);else if(phase===0)set(false)},3200);
    if(typeof THREE==='undefined'){box.innerHTML='<p class="pz-hint" style="left:12px">La plaza en 3D necesita conexión a internet.</p>';if(reduce)set(true);return}

    // ---------- escena ----------
    var cv=$('pz'),tagsEl=$('pzTags');
    var R=new THREE.WebGLRenderer({canvas:cv,antialias:true,alpha:true});R.setPixelRatio(Math.min(2,window.devicePixelRatio||1));R.shadowMap.enabled=true;R.shadowMap.type=THREE.PCFSoftShadowMap;
    var sc=new THREE.Scene();sc.fog=new THREE.Fog(0x140d08,40,70);
    var cam=new THREE.OrthographicCamera(-10,10,7,-7,0.1,200);
    sc.add(new THREE.HemisphereLight(0xffd9a8,0x1a1020,.55));
    var sun=new THREE.DirectionalLight(0xffc27a,1.0);sun.position.set(-14,22,10);sun.castShadow=true;sun.shadow.mapSize.set(1024,1024);
    var sc2=sun.shadow.camera;sc2.left=-16;sc2.right=16;sc2.top=16;sc2.bottom=-16;sc2.near=1;sc2.far=60;sun.shadow.bias=-0.0008;sc.add(sun);
    var M={};function mat(c,o){var k=c+(o?JSON.stringify(o):'');if(!M[k])M[k]=new THREE.MeshLambertMaterial(Object.assign({color:c},o||{}));return M[k]}
    var BG={};function bx(w,h,d){var k=w+'_'+h+'_'+d;if(!BG[k])BG[k]=new THREE.BoxGeometry(w,h,d);return BG[k]}
    function block(w,h,d,c,x,y,z,parent,o){var m=new THREE.Mesh(bx(w,h,d),Array.isArray(c)?c:mat(c,o));m.position.set(x,y,z);m.castShadow=true;m.receiveShadow=true;(parent||sc).add(m);return m}
    // piso de piedra (instanciado)
    var GX=13,GZ=9,cnt=(GX*2)*(GZ*2),ig=new THREE.InstancedMesh(bx(1,.5,1),new THREE.MeshLambertMaterial(),cnt),dm=new THREE.Object3D(),col=new THREE.Color(),n=0;
    for(var x=-GX;x<GX;x++)for(var z=-GZ;z<GZ;z++){dm.position.set(x+.5,-.25-(Math.random()<.15?.06:0),z+.5);dm.updateMatrix();ig.setMatrixAt(n,dm.matrix);
      var path=Math.abs(x+.5)<2.2||Math.abs(z+.5)<1.6;var g=path?.42+Math.random()*.08:.30+Math.random()*.09;col.setRGB(g*1.08,g*.98,g*.86);ig.setColorAt(n,col);n++}
    ig.receiveShadow=true;sc.add(ig);
    // muros bajos con entradas
    for(var x=-GX;x<GX;x++){[-GZ-.5,GZ-.5].forEach(function(z){if(Math.abs(x+.5)>2.2)block(1,1+(x%3===0?.4:0),1,0x4a3d33,x+.5,.5,z+.5)})}
    for(var z=-GZ;z<GZ;z++){[-GX-.5,GX-.5].forEach(function(x){if(Math.abs(z+.5)>1.6)block(1,1+(z%3===0?.4:0),1,0x4a3d33,x+.5,.5,z+.5)})}
    // puestos
    var WOOD=0x6b4a2b,DARK=0x3a2717;
    function stall(x,z,c1,c2,rot){var g=new THREE.Group();g.position.set(x,0,z);g.rotation.y=rot||0;sc.add(g);
      block(3,1,1,WOOD,0,.5,0,g);block(3.1,.12,1.1,DARK,0,1.04,0,g);
      [[-1.4,-.45],[1.4,-.45],[-1.4,.75],[1.4,.75]].forEach(function(p){block(.15,2.3,.15,DARK,p[0],1.15,p[1],g)});
      for(var i=0;i<6;i++)block(.55,.14,1.7,i%2?c1:c2,-1.375+i*.55,2.35+(i%2?0:.03),.15,g);
      var gems=[0x5cc8ff,0xff5a5a,0x9fe08a,0xf6dc96,0xc78bff];for(var i=0;i<4;i++){var q=block(.22,.22,.22,gems[(i+Math.floor(x))%5],-1+i*.65,1.22,-.05,g,{emissive:gems[(i+Math.floor(x))%5],emissiveIntensity:.35});q.rotation.y=.6}
      block(.6,.6,.6,0x7a5634,1.9,.3,.2,g);block(.5,.5,.5,0x6b4a2b,1.9,.85,.25,g);return g}
    stall(-8,-5.5,0xa3241c,0xe9d9b8,0);stall(-2.8,-6.4,0x1f4f8a,0xe9d9b8,0);stall(-8,4.8,0x2e6b35,0xe9d9b8,Math.PI);stall(-3,6,0x6b2a7a,0xe9d9b8,Math.PI);
    stall(8.2,4.8,0xb07a1c,0xf3e2c0,Math.PI);stall(9.6,-2,0x1f4f8a,0xe9d9b8,-Math.PI/2);
    // tienda de Vessara
    var tent=new THREE.Group();tent.position.set(5,0,-5.3);sc.add(tent);
    block(4.2,2.4,3,0x2b1f3a,0,1.2,0,tent);block(4.4,.2,3.2,0xc6a578,0,2.5,0,tent,{emissive:0x3a2a10});
    var roof=new THREE.Mesh(new THREE.ConeGeometry(3.3,2,4),mat(0x3d2a55));roof.position.set(0,3.6,0);roof.rotation.y=Math.PI/4;roof.castShadow=true;tent.add(roof);
    block(1.2,1.7,.1,0x120c18,0,.85,1.52,tent);block(.25,1.6,.12,0xc6a578,-1.2,1.6,1.55,tent,{emissive:0x5a4010});block(.25,1.6,.12,0xc6a578,1.2,1.6,1.55,tent,{emissive:0x5a4010});
    block(.1,1.4,.1,0x2a1c0d,1.9,3.6,1.3,tent);block(.9,.6,.05,0x6b2a7a,2.35,4,1.3,tent);
    // faroles y barriles
    var lamps=[];[[-4.6,-1.9],[4.6,1.9],[-4.6,2.2],[1.5,-2.6]].forEach(function(p){block(.18,2.6,.18,0x1c1612,p[0],1.3,p[1]);var l=block(.38,.38,.38,0xffd27a,p[0],2.75,p[1],null,{emissive:0xffb347,emissiveIntensity:1});l.castShadow=false;
      var pl=new THREE.PointLight(0xffb347,.9,7,1.6);pl.position.set(p[0],2.6,p[1]);sc.add(pl);lamps.push(pl)});
    [[-11.5,-7.5],[-11,-6.9],[11.5,7.2],[12,6.5],[-11.6,7.4],[11.6,-7.4]].forEach(function(p){var b=new THREE.Mesh(new THREE.CylinderGeometry(.35,.35,.8,8),mat(0x5a3d22));b.position.set(p[0],.4,p[1]);b.castShadow=true;sc.add(b)});
    // fuente
    block(3,.5,3,0x6d655c,0,.25,1.2);block(2.4,.12,2.4,0x2f6f9a,0,.5,1.2,null,{emissive:0x0d2a40});block(.5,1.6,.5,0x6d655c,0,1,1.2);var fw=block(.9,.18,.9,0x7fc4ec,0,1.85,1.2,null,{emissive:0x1a4a6a});

    // ---------- personajes voxel ----------
    function face(skin,eyes,extra){var c=document.createElement('canvas');c.width=c.height=8;var x=c.getContext('2d');x.fillStyle=skin;x.fillRect(0,0,8,8);x.fillStyle=eyes;
      if(extra==='skull'){x.fillRect(1,2,2,2);x.fillRect(5,2,2,2);x.fillStyle='#3a3530';x.fillRect(3,4,2,1);x.fillStyle='#2a2522';for(var i=1;i<7;i+=2)x.fillRect(i,6,1,1)}
      else{x.fillRect(1,3,2,1);x.fillRect(5,3,2,1);x.fillStyle='#8a5a44';x.fillRect(3,6,2,1)}
      var t=new THREE.CanvasTexture(c);t.magFilter=THREE.NearestFilter;t.minFilter=THREE.NearestFilter;return new THREE.MeshLambertMaterial({map:t})}
    function person(o){var g=new THREE.Group(),s=mat(o.skin),head=new THREE.Group();g.add(head);head.position.y=1.38;
      var fm=face(o.faceSkin,o.eyes,o.kind);var hm=new THREE.Mesh(bx(.5,.5,.5),[s,s,s,s,fm,s]);hm.castShadow=true;head.add(hm);
      if(o.hat){block(.86,.07,.86,o.hat,0,.27,0,head);block(.48,.32,.48,o.hat,0,.45,0,head);block(.5,.06,.5,o.band||0x6b2a1a,0,.31,0,head)}
      if(o.helm){block(.56,.22,.56,o.helm,0,.2,0,head);block(.08,.24,.08,o.plume||0xa3241c,0,.42,-.1,head)}
      if(o.hood){block(.58,.58,.58,o.hood,0,.04,-.03,head).scale.set(1,1,1)}
      var body=block(.56,.62,.3,o.body,0,.84,0,g);if(o.cape)block(.6,.8,.06,o.cape,0,.78,-.2,g);
      if(o.belt)block(.58,.08,.32,o.belt,0,.6,0,g);
      function limb(w,h,c,x,y){var p=new THREE.Group();p.position.set(x,y,0);g.add(p);block(w,h,w,c,0,-h/2,0,p);return p}
      var L=limb(.2,.55,o.legs,-.14,.55),Rl=limb(.2,.55,o.legs,.14,.55),A=limb(.17,.58,o.arms,-.38,1.12),B=limb(.17,.58,o.arms,.38,1.12);
      if(o.weapon){var wg=new THREE.Group();B.add(wg);wg.position.set(0,-.55,.12);block(.07,.9,.07,o.weapon,0,.2,0,wg,{emissive:o.weapon,emissiveIntensity:.25})}
      if(o.sign){var sg=new THREE.Group();A.add(sg);sg.position.set(0,-.5,.1);block(.05,1.2,.05,0x2a1c0d,0,.55,0,sg);var pl=block(.7,.45,.06,0x6b4a2b,0,1.25,0,sg);o.plank=pl}
      g.userData={L:L,R:Rl,A:A,B:B,head:head,o:o};g.scale.setScalar(o.scale||1);sc.add(g);return g}
    var SK={kind:'skull',skin:0xe8e0cc,faceSkin:'#e8e0cc',eyes:'#1a1410',arms:0xe0d6c0,legs:0x2e2620,body:0x4a3524,belt:0x7a5634,sign:true};
    var hats=[0x4a3424,0x3a2a1c,0x5a3a22,0x2a2a2a,0x4a3020],coats=[0x4a3524,0x3d2b2b,0x2f3a2a,0x3a2d40,0x4d3a22];
    var ITEMS=[['Jewel of Bless ×10','1.000',1],['Great Reign Crossbow +13','2.400',1],['Wings of Despair +11','4.000.000 Zen',0],['Excellent Dragon Armor +11','1.800',1],['Box of Kundun +5','300',1],['Legendary Staff +13','8.000.000 Zen',0],['Jewel of Soul ×5','5.000.000 Zen',0],['Dark Phoenix Helm +13','1.500',1],['Pendant of Ice +9','2.200.000 Zen',0]];
    var STAR=['Hurricane Armor +15','1.200',1];
    var crew=[];
    function rnd(a,b){return a+Math.random()*(b-a)}
    function freeSpot(){for(var i=0;i<40;i++){var x=rnd(-10.5,10.5),z=rnd(-3.6,3.8);if(Math.hypot(x,z-1.2)<2.3)continue;return new THREE.Vector3(x,0,z)}return new THREE.Vector3(0,0,-1.5)}
    function mkTag(){var d=document.createElement('div');d.className='tag3d';tagsEl.appendChild(d);return d}
    function fillTag(t,it,small,cls){t.className='tag3d'+(cls?' '+cls:'');t.innerHTML='<small>'+small+'</small><b>'+it[0]+'</b><span>'+(it[2]?'<img src="'+COIN+'" alt="">'+it[1]+' Eryns':it[1])+'</span>'}
    for(var i=0;i<10;i++){var o=Object.assign({},SK,{hat:hats[i%5],body:coats[i%5],scale:.92});var c=person(o);var p=freeSpot();c.position.copy(p);
      var it=i===9?STAR:ITEMS[i%ITEMS.length];c.userData.ai={t:freeSpot(),wait:rnd(0,2),offer:0,item:it,tag:mkTag(),speed:rnd(1.1,1.5),star:false,idx:i};crew.push(c)}
    var hero=crew[9];hero.userData.o.plank.material=mat(0x6b4a2b);
    var PL=[{helm:0xb9c2cc,body:0x8a96a3,arms:0x8a96a3,legs:0x5a646e,cape:0x7a1a1a,weapon:0xd0d8e0,plume:0xa3241c},{hood:0x2e7a3a,body:0x3a8a46,arms:0xe6c9a8,legs:0x2a4a2a,weapon:0x8a5a2a},{hood:0x3d2a6b,body:0x4a3580,arms:0x4a3580,legs:0x2a1f45,weapon:0x9b7aff},{helm:0xc6a578,body:0x2a2420,arms:0x2a2420,legs:0x1a1612,cape:0xb07a1c,weapon:0xf6dc96,plume:0xf6dc96}];
    var players=PL.map(function(q){var c=person(Object.assign({skin:0xe6c9a8,faceSkin:'#e6c9a8',eyes:'#2a1a12',belt:0x3a2a1c,scale:1},q));c.position.copy(freeSpot());c.userData.ai={t:freeSpot(),wait:rnd(0,3),speed:rnd(1.4,1.9),buy:0,tag:mkTag()};return c});
    var vess=person({skin:0xd8c0a8,faceSkin:'#d8c0a8',eyes:'#5a2a7a',hood:0x3d2a55,body:0x3d2a55,arms:0x3d2a55,legs:0x221830,cape:0xc6a578,belt:0xc6a578,scale:1.05});vess.position.set(5,0,-3.2);
    // destellos sobre el destacado
    var sparkG=new THREE.BufferGeometry(),SP=24,spPos=new Float32Array(SP*3),spSeed=[];for(var i=0;i<SP;i++)spSeed.push([Math.random()*6.28,Math.random(),Math.random()*.6+.4]);
    sparkG.setAttribute('position',new THREE.BufferAttribute(spPos,3));var spark=new THREE.Points(sparkG,new THREE.PointsMaterial({color:0xffe7a6,size:.13,transparent:true,opacity:0}));sc.add(spark);
    var star=false;window.PZ={setStar:function(v){star=v;var a=hero.userData.ai;a.star=v;if(!v){a.offer=0;a.wait=0;a.t=freeSpot()}hero.userData.o.plank.material=v?mat(0xf6dc96,{emissive:0xb07a1c,emissiveIntensity:.6}):mat(0x6b4a2b);
      if(v){a.t=new THREE.Vector3(1.6,0,-1.4);a.wait=0}fillTag(a.tag,STAR,v?'★ Destacado':'Oferta',v?'star':'')}};
    PZ.setStar(false);

    // ---------- loop ----------
    var ang=Math.PI/4,drag=null,last=performance.now(),clock=0,V=new THREE.Vector3();
    function resize(){var w=box.clientWidth,h=box.clientHeight;R.setSize(w,h,false);var a=w/h,vs=w<560?9.5:7.6;cam.left=-vs*a;cam.right=vs*a;cam.top=vs;cam.bottom=-vs;cam.updateProjectionMatrix()}
    resize();window.addEventListener('resize',resize);
    box.addEventListener('pointerdown',function(e){drag={x:e.clientX,a:ang};box.setPointerCapture(e.pointerId)});
    box.addEventListener('pointermove',function(e){if(drag)ang=drag.a-(e.clientX-drag.x)*.008});
    box.addEventListener('pointerup',function(){drag=null});box.addEventListener('pointercancel',function(){drag=null});
    function walk(c,dt,moving){var u=c.userData,s=moving?Math.sin(clock*9+u.o.scale*7):0;u.L.rotation.x=s*.6;u.R.rotation.x=-s*.6;u.A.rotation.x=-s*.5;u.B.rotation.x=s*.5;c.position.y=moving?Math.abs(Math.sin(clock*9))*.05:0}
    function step(c,dt){var a=c.userData.ai;if(a.wait>0){a.wait-=dt;walk(c,dt,false);return false}
      V.subVectors(a.t,c.position);V.y=0;var d=V.length();if(d<.15){return true}
      V.normalize();c.position.addScaledVector(V,Math.min(d,a.speed*dt));c.rotation.y=Math.atan2(V.x,V.z);walk(c,dt,true);return false}
    function place(tag,obj,yOff,visible){V.copy(obj.position);V.y+=yOff;V.project(cam);var w=box.clientWidth,h=box.clientHeight;
      tag.style.transform='translate('+((V.x*.5+.5)*w)+'px,'+((-V.y*.5+.5)*h)+'px) translate(-50%,-100%)';tag.classList.toggle('show',visible)}
    function frame(t){requestAnimationFrame(frame);var dt=Math.min(.05,(t-last)/1000);last=t;if(!vis&&!reduce)return;clock+=dt;
      if(!drag&&!reduce)ang+=Math.sin(clock*.15)*.0009;
      cam.position.set(Math.sin(ang)*30,24,Math.cos(ang)*30);cam.lookAt(0,0,0);
      fw.rotation.y+=dt*.8;lamps.forEach(function(l,i){l.intensity=.8+Math.sin(clock*7+i*2)*.08});
      crew.forEach(function(c){var a=c.userData.ai;
        if(a.offer>0){a.offer-=dt;walk(c,dt,false);c.userData.A.rotation.x=-1.1;c.userData.A.rotation.z=-.15;var tgt=cam.position.clone();c.rotation.y+=((Math.atan2(tgt.x-c.position.x,tgt.z-c.position.z))-c.rotation.y)*.08}
        else{c.userData.A.rotation.z=0;if(a.star&&star){if(step(c,dt)){a.offer=99;}}else if(step(c,dt)){a.offer=rnd(2.2,3.4);if(!a.star)fillTag(a.tag,a.item,'Oferta');a.t=freeSpot();a.wait=0}}
        var show=a.offer>0||(a.star&&star);
        place(a.tag,c,(c.userData.o.scale||1)*2.95,show)});
      players.forEach(function(c){var a=c.userData.ai;if(a.buy>0){a.buy-=dt;walk(c,dt,false);place(a.tag,c,3.0,true);return}
        place(a.tag,c,3.0,false);if(step(c,dt)){a.t=freeSpot();a.wait=rnd(.5,2.5)}
        if(star&&hero.userData.ai.offer>0&&c.position.distanceTo(hero.position)<1.6&&!a.cool){a.buy=2.2;a.cool=true;a.t=freeSpot();
          a.tag.className='tag3d sold';a.tag.innerHTML='<small>Compró</small><b>Hurricane Armor +15</b><span><img src="'+COIN+'" alt="">¡Vendido!</span>';setTimeout(function(){a.cool=false},9000)}});
      walk(vess,dt,false);vess.rotation.y=Math.sin(clock*.5)*.4;
      var hp=hero.position;for(var i=0;i<SP;i++){var s=spSeed[i],k=(clock*s[2]+s[1])%1;spPos[i*3]=hp.x+Math.cos(s[0]+clock)*.5;spPos[i*3+1]=1+k*2.4;spPos[i*3+2]=hp.z+Math.sin(s[0]+clock)*.5}
      sparkG.attributes.position.needsUpdate=true;spark.material.opacity+=((star?.9:0)-spark.material.opacity)*.08;
      R.render(sc,cam)}
    if(reduce){cam.position.set(Math.sin(ang)*30,24,Math.cos(ang)*30);cam.lookAt(0,0,0);set(true);R.render(sc,cam);crew.forEach(function(c){var a=c.userData.ai;place(a.tag,c,2.8,a.star)});return}
    requestAnimationFrame(frame);
  })();
  // Free vs VIP
  (function(){
    var sec=$('vip'),bF=$('v-free'),bV=$('v-vip'),vip=false,auto=!reduce,vis=false;
    var J=IMG+'atlas/drops/';
    var JW=[J+'jewel-bless-2c80b97e4d75.webp',J+'jewel-soul-05f3890104b2.webp',J+'jewel-chaos-809384516512.webp',J+'jewel-life-6e1a802e1074.webp'];
    var slots=$('vSlots');for(var i=0;i<10;i++){var s=document.createElement('span');slots.appendChild(s)}
    var slotS=[].slice.call(slots.children);
    var inv=$('vInv');JW.forEach(function(u){var im=document.createElement('img');im.src=u;im.alt='';inv.appendChild(im)});
    function setTxt(id,t){$(id).textContent=t}
    function apply(){sec.classList.toggle('is-vip',vip);bF.setAttribute('aria-pressed',!vip);bV.setAttribute('aria-pressed',vip);
      setTxt('vExp',vip?'20x':'15x');setTxt('vExpS',vip?'+33% más rápido':'ritmo base');setTxt('vExpT',vip?'Cada nivel llega antes.':'Subís a tu ritmo.');
      setTxt('vDrop',vip?'30%':'25%');setTxt('vDropS',vip?'+20% más drops':'drop base');
      setTxt('vSlotsN',vip?'10':'3');setTxt('vSlotsT',vip?'Diez objetos vendiéndose a la vez, aunque no estés.':'Tres objetos en la vidriera a la vez.');
      slotS.forEach(function(s,i){var on=i<(vip?10:3);setTimeout(function(){s.className=on?'on':'lock'},on?i*60:0)});
      setTxt('vOffT',vip?'Te desconectás y tu personaje sigue cazando con el MU Helper.':'Cuando te desconectás, tu personaje se queda quieto.');
      setTxt('vBankT',vip?'Las joyas que juntás van solas al banco. Tu inventario queda libre.':'Las joyas ocupan tu inventario hasta que las guardás vos.');
      setTxt('vDisc',vip?'10%':'0%');setTxt('vPrice',vip?'900':'1.000');setTxt('vDiscT',vip?'Todo lo de la Tienda te sale un 10% menos.':'Pagás el precio de lista.');
      setTxt('vChestT',vip?'Un baúl reservado para tu cuenta. Si el VIP vence, lo que guardaste sigue ahí para retirarlo.':'Usás los baúles de tu cuenta.');
      setTxt('vChatT',vip?'/autopost publica tu anuncio solo, cada 10 minutos, mientras jugás.':'Tus anuncios en el chat los escribís cada vez.');
      chatReset();offLog()}
    var chatN=0;function chatLine(t,c){var ch=$('vChat'),sp=document.createElement('span');if(c)sp.className=c;sp.textContent=t;ch.appendChild(sp);while(ch.children.length>3)ch.firstChild.remove()}
    function chatReset(){$('vChat').innerHTML='';chatN=0;if(vip){chatLine('Tú: /autopost');chatLine('[Sistema] Publicación automática activada.')}else{chatLine('Tú: /autopost');chatLine('[Sistema] Solo las cuentas VIP pueden usar este comando.','no')}}
    var CH=['Kael: alguien para Kalima?','Nyx: vendo Jewel of Soul','Brann: party BC 22:40','Lira: gg'];
    function choose(v){auto=false;vip=v;apply()}
    bF.addEventListener('click',function(){choose(false)});bV.addEventListener('click',function(){choose(true)});
    // EXP
    var exp=0,lvl=312;
    // log offline
    var offT=[];function offLog(){offT.forEach(clearTimeout);offT=[];var lg=$('vOff');
      if(!vip){lg.innerHTML='<span class="in">Desconectado</span><span class="in">Sin actividad</span>';return}
      var L=['Desconectado · Helper activo','Cazando en tu spot…','+ experiencia','+ Jewel of Bless'];lg.innerHTML=L.map(function(t,i){return '<span'+(i>1?' class="gold"':'')+'>'+t+'</span>'}).join('');
      [].slice.call(lg.children).forEach(function(s,i){offT.push(setTimeout(function(){s.classList.add('in')},200+i*450))})}
    apply();
    if(reduce){$('vExpBar').style.width='60%';return}
    if('IntersectionObserver' in window)new IntersectionObserver(function(e){vis=e[0].isIntersecting},{threshold:.2}).observe(sec);else vis=true;
    var chatAcc=0,last=performance.now(),dropAcc=0,jw=0,bankAcc=0,bank=0,logAcc=0;
    (function loop(t){requestAnimationFrame(loop);var dt=Math.min(.05,(t-last)/1000);last=t;if(!vis)return;
      exp+=dt*(vip?20:15)*1.6;if(exp>=100){exp-=100;lvl++;var l=$('vLvl');l.textContent=lvl;l.classList.remove('vlvl');void l.offsetWidth;l.classList.add('vlvl')}
      $('vExpBar').style.width=exp+'%';
      dropAcc+=dt*(vip?30:25)/25;if(dropAcc>=1.15){dropAcc=0;var m=$('vMob');m.classList.remove('hit');void m.offsetWidth;m.classList.add('hit');
        var fl=$('vFloor'),im=document.createElement('img');im.className='jw';im.src=JW[jw%4];im.alt='';im.style.left=(6+(jw*23)%84)+'%';fl.appendChild(im);if(fl.children.length>7)fl.firstChild.remove();jw++;$('vJw').textContent=jw}
      if(vip){bankAcc+=dt;if(bankAcc>1.1){bankAcc=0;var f=document.createElement('img');f.className='fly';f.src=JW[bank%4];f.alt='';$('vLane').appendChild(f);
        (function(n){setTimeout(function(){n.remove();bank++;$('vBank').textContent=bank},900)})(f)}}
      logAcc+=dt;if(vip&&logAcc>4.5){logAcc=0;offLog()}
      chatAcc+=dt;if(chatAcc>1.4){chatAcc=0;chatN++;if(vip&&chatN%3===0)chatLine('[AutoPost] Tú: Vendo Hurricane Armor +15 · 1.200 Eryns','ap');else chatLine(CH[chatN%CH.length])}
      if(auto){autoT=(autoT||0)+dt;if(autoT>6){autoT=0;vip=!vip;apply()}}
    })(last);
    var autoT=0;
  })();
})();

/* MU PANIC — Brasas flotantes en toda la página.
   Uso: incluir este archivo con una etiqueta script (defer) al final de la página.
   Una sola capa fija sobre toda la página, no bloquea clics y se apaga si el
   usuario tiene activado "reducir movimiento". */
(function () {
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var cv = document.createElement('canvas');
  cv.className = 'mp-brasas';
  cv.setAttribute('aria-hidden', 'true');
  cv.style.cssText = 'position:fixed;inset:0;width:100%;height:100%;z-index:60;pointer-events:none;mix-blend-mode:screen';
  document.body.appendChild(cv);

  var cx = cv.getContext('2d'), P = [], W, H, N, dpr = Math.min(2, window.devicePixelRatio || 1);
  var lastY = window.scrollY, running = true;

  function size() {
    W = cv.clientWidth; H = cv.clientHeight;
    cv.width = W * dpr; cv.height = H * dpr; cx.setTransform(dpr, 0, 0, dpr, 0, 0);
    N = Math.max(22, Math.round(55 * Math.min(1, W / 1440)));
    while (P.length < N) P.push(spawn({}, true));
    P.length = N;
  }
  function spawn(p, init) {
    p.x = Math.random() * W;
    p.y = init ? Math.random() * H : H + 10;
    p.r = .6 + Math.random() * 2.2;        // tamaño
    p.vy = .25 + Math.random() * .9;       // velocidad de subida
    p.vx = (Math.random() - .5) * .35;     // deriva lateral
    p.a = .3 + Math.random() * .55;        // brillo
    p.w = Math.random() * 6.28;            // fase del vaivén
    p.d = .4 + Math.random() * .8;         // profundidad (parallax con el scroll)
    return p;
  }

  window.addEventListener('resize', size);
  window.addEventListener('scroll', function () {
    var dy = window.scrollY - lastY; lastY = window.scrollY;
    for (var i = 0; i < P.length; i++) P[i].y -= dy * .25 * P[i].d;
  }, { passive: true });
  document.addEventListener('visibilitychange', function () { running = !document.hidden; });

  size();
  (function loop() {
    requestAnimationFrame(loop);
    if (!running) return;
    cx.clearRect(0, 0, W, H);
    for (var i = 0; i < P.length; i++) {
      var p = P[i];
      p.y -= p.vy; p.w += .03; p.x += p.vx + Math.sin(p.w) * .3;
      if (p.y < -12) spawn(p);
      if (p.y > H + 12) { p.y = -10; p.x = Math.random() * W; }
      var g = cx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.r * 4);
      g.addColorStop(0, 'rgba(255,190,110,' + p.a + ')');
      g.addColorStop(.4, 'rgba(230,90,30,' + p.a * .5 + ')');
      g.addColorStop(1, 'rgba(200,40,10,0)');
      cx.fillStyle = g; cx.beginPath(); cx.arc(p.x, p.y, p.r * 4, 0, 6.29); cx.fill();
    }
  })();
})();
