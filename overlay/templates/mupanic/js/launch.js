(() => {
  'use strict';
  const countdown = document.querySelector('[data-launch-at]');
  if (countdown) {
    const target = Date.parse(countdown.dataset.launchAt);
    const updateClock = () => {
      if (!Number.isFinite(target)) return;
      const total = Math.max(0, Math.floor((target - Date.now()) / 1000));
      const values = {days: Math.floor(total / 86400), hours: Math.floor(total / 3600) % 24, minutes: Math.floor(total / 60) % 60, seconds: total % 60};
      for (const [key, value] of Object.entries(values)) countdown.querySelector(`[data-clock="${key}"]`).textContent = String(value).padStart(2, '0');
      countdown.classList.toggle('campaign-countdown-ended', total === 0);
    };
    updateClock(); setInterval(updateClock, 1000);
    document.addEventListener('visibilitychange', updateClock);
  }
  const tabs = [...document.querySelectorAll('[data-event-tab]')];
  const selectTab = tab => {
    for (const entry of tabs) {
      const selected = entry === tab;
      entry.setAttribute('aria-selected', String(selected)); entry.tabIndex = selected ? 0 : -1;
      const panel = document.getElementById(entry.getAttribute('aria-controls'));
      if (panel) { panel.hidden = !selected; if (selected) panel.querySelector('img').loading = 'eager'; }
    }
  };
  for (const [index, tab] of tabs.entries()) {
    tab.addEventListener('click', () => selectTab(tab));
    tab.addEventListener('keydown', event => {
      let next;
      if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
      if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
      if (event.key === 'Home') next = 0;
      if (event.key === 'End') next = tabs.length - 1;
      if (next === undefined) return;
      event.preventDefault(); selectTab(tabs[next]); tabs[next].focus();
    });
  }
  const button = document.querySelector('.campaign-motion');
  const hero = document.querySelector('.campaign-hero');
  const canvas = document.querySelector('.campaign-fx');
  if (!button || !hero || !canvas) return;
  const ctx = canvas.getContext('2d');
  const preference = window.matchMedia('(prefers-reduced-motion: reduce)');
  let paused = preference.matches, visible = true, frame = 0, last = 0, elapsed = 0;
  let width = 0, height = 0, particles = [], sparks = [], bolts = [], nextStrike = 1.1;
  const scenes = [...document.querySelectorAll('.campaign-scene')];
  const rail = [...document.querySelectorAll('.campaign-rail a')];
  const ratios = new Map();
  const sceneObserver = new IntersectionObserver(entries => {
    for (const entry of entries) {
      ratios.set(entry.target, entry.intersectionRatio);
      entry.target.classList.toggle('is-visible', entry.isIntersecting);
    }
    const current = [...ratios.entries()].sort((a,b) => b[1] - a[1])[0]?.[0];
    for (const link of rail) link.classList.toggle('is-current', link.hash === `#${current?.id}`);
  }, {threshold:[0,.15,.4,.6,.9]});
  scenes.forEach(scene => sceneObserver.observe(scene));
  let scrollFrame = 0;
  const parallax = () => {
    scrollFrame = 0;
    if (paused) return;
    for (const scene of scenes) {
      const r = scene.getBoundingClientRect();
      if (r.bottom > 0 && r.top < innerHeight) scene.style.setProperty('--parallax', `${Math.max(-35, Math.min(35, r.top * -.045))}px`);
    }
  };
  window.addEventListener('scroll', () => { if (!scrollFrame) scrollFrame = requestAnimationFrame(parallax); }, {passive:true});
  function resize() {
    width = hero.clientWidth; height = hero.clientHeight;
    const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
    canvas.width = Math.round(width * dpr); canvas.height = Math.round(height * dpr);
    if (!ctx) return;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    particles = Array.from({length:width < 700 ? 45 : 110}, () => ({x:Math.random()*width,y:Math.random()*height,speed:30+Math.random()*95,radius:.7+Math.random()*2,phase:Math.random()*Math.PI*2}));
    ctx.clearRect(0,0,width,height);
  }
  function running() { return !paused && visible && !document.hidden && ctx; }
  function burst(x,y,count=50) {
    for(let i=0;i<count;i++) {
      const a=Math.random()*Math.PI*2, speed=70+Math.random()*240;
      sparks.push({x,y,vx:Math.cos(a)*speed,vy:Math.sin(a)*speed,life:.5+Math.random()*.8});
    }
    sparks=sparks.slice(-180);
  }
  function strike() {
    const left = Math.random() > .5;
    const start={x:width*(left ? .03 : .98), y:height*(.15+Math.random()*.45)};
    const end={x:width*(left ? .35 : .7), y:height*(.45+Math.random()*.3)};
    const points=Array.from({length:12},(_,i)=>({x:start.x+(end.x-start.x)*i/11+(i&&i<11?(Math.random()-.5)*20:0),y:start.y+(end.y-start.y)*i/11+(i&&i<11?(Math.random()-.5)*25:0)}));
    bolts.push({points,life:.4,color:left?'255,110,38':'181,116,255'});
    burst(end.x,end.y,width<700?18:35);
  }
  function tick(now) {
    frame=0; if(!running()) return;
    if(now-last<32){frame=requestAnimationFrame(tick);return;}
    const dt=Math.min((now-last)/1000,.05);last=now;elapsed+=dt;
    ctx.clearRect(0,0,width,height);ctx.globalCompositeOperation='lighter';
    for(const p of particles){
      p.y-=p.speed*dt;p.x+=Math.sin(elapsed+p.phase)*18*dt;
      if(p.y<-20){p.y=height+20;p.x=Math.random()*width;}
      const alpha=.3+Math.sin(elapsed+p.phase)*.2;
      ctx.strokeStyle=`rgba(${p.x<width*.5?'255,123,43':'193,142,255'},${alpha})`;ctx.lineWidth=p.radius;
      ctx.beginPath();ctx.moveTo(p.x,p.y);ctx.lineTo(p.x-2,p.y+p.radius*7);ctx.stroke();
    }
    if(elapsed>nextStrike){strike();nextStrike=elapsed+2.5+Math.random()*2;}
    bolts=bolts.filter(b=>b.life>0);
    for(const b of bolts){b.life-=dt;ctx.shadowBlur=15;ctx.shadowColor=`rgba(${b.color},.8)`;ctx.strokeStyle=`rgba(${b.color},${Math.max(0,b.life)*1.4})`;ctx.lineWidth=2;ctx.beginPath();b.points.forEach((p,i)=>i?ctx.lineTo(p.x,p.y):ctx.moveTo(p.x,p.y));ctx.stroke();}
    ctx.shadowBlur=0;sparks=sparks.filter(s=>s.life>0);
    for(const s of sparks){s.life-=dt;s.x+=s.vx*dt;s.y+=s.vy*dt;s.vy+=80*dt;ctx.fillStyle=`rgba(255,177,83,${Math.min(.9,Math.max(0,s.life))})`;ctx.fillRect(s.x,s.y,2,2);}
    ctx.globalCompositeOperation='source-over';frame=requestAnimationFrame(tick);
  }
  function sync(){if(frame)cancelAnimationFrame(frame);frame=0;last=performance.now();if(running())frame=requestAnimationFrame(tick);}
  function render(){document.body.classList.toggle('launch-paused',paused);button.setAttribute('aria-pressed',String(paused));button.textContent=paused?'Activar efectos':'Pausar efectos';if(paused&&ctx)ctx.clearRect(0,0,width,height);sync();}
  button.hidden=false;resize();render();
  button.addEventListener('click',()=>{paused=!paused;render();});
  preference.addEventListener('change',()=>{paused=preference.matches;render();});
  document.addEventListener('visibilitychange',()=>{document.body.classList.toggle('launch-background',document.hidden);sync();});
  new ResizeObserver(resize).observe(hero);
  new IntersectionObserver(entries=>{visible=entries[0].isIntersecting;sync();}).observe(hero);
  hero.addEventListener('pointermove',event=>{
    if(!running()||event.pointerType!=='mouse')return;
    const r=hero.getBoundingClientRect();
    hero.style.setProperty('--drift-x',`${((event.clientX-r.left)/width-.5)*-20}px`);
    hero.style.setProperty('--drift-y',`${((event.clientY-r.top)/height-.5)*-12}px`);
  });
  hero.addEventListener('pointerleave',()=>{hero.style.setProperty('--drift-x','0px');hero.style.setProperty('--drift-y','0px');});
  hero.addEventListener('click',event=>{if(!running()||event.target.closest('a,button'))return;const r=hero.getBoundingClientRect();burst(event.clientX-r.left,event.clientY-r.top,75);});
})();
