(() => {
  'use strict';
  const button = document.querySelector('.launch-motion');
  const hero = document.querySelector('.launch-hero');
  const canvas = document.querySelector('.launch-particles');
  if (!button || !hero || !canvas) return;
  const ctx = canvas.getContext('2d');
  const preference = window.matchMedia('(prefers-reduced-motion: reduce)');
  let paused = preference.matches, visible = true, frame = 0, last = 0;
  let width = 0, height = 0, particles = [], bursts = [], elapsed = 0;
  function resize() {
    width = hero.clientWidth; height = hero.clientHeight;
    const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
    canvas.width = Math.round(width * dpr); canvas.height = Math.round(height * dpr);
    if (!ctx) return;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    particles = Array.from({length: width < 600 ? 38 : 90}, () => ({
      x: Math.random() * width, y: Math.random() * height,
      speed: 18 + Math.random() * 55, radius: .7 + Math.random() * 1.9,
      phase: Math.random() * Math.PI * 2
    }));
    ctx.clearRect(0, 0, width, height);
  }
  function running() { return !paused && visible && !document.hidden && ctx; }
  function tick(now) {
    frame = 0;
    if (!running()) return;
    if (now - last < 32) { frame = requestAnimationFrame(tick); return; }
    const dt = Math.min((now - last) / 1000, .05); last = now; elapsed += dt;
    ctx.clearRect(0, 0, width, height);
    ctx.globalCompositeOperation = 'lighter';
    for (const p of particles) {
      p.y -= p.speed * dt; p.x += Math.sin(elapsed + p.phase) * 12 * dt;
      if (p.y < -15) { p.y = height + 15; p.x = Math.random() * width; }
      const alpha = (p.x < width * .5 ? .25 : .65) * (.6 + Math.sin(elapsed * 2 + p.phase) * .3);
      ctx.strokeStyle = `rgba(239,152,70,${alpha})`; ctx.lineWidth = p.radius;
      ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(p.x - 1, p.y + p.radius * 5); ctx.stroke();
    }
    // A slowly moving arc of molten energy around the gateway, never a full-screen flash.
    const x = width * (width < 600 ? .65 : .78), y = height * .42;
    const rx = width * (width < 600 ? .24 : .12), ry = height * .36;
    for (let band = 0; band < 2; band++) {
      ctx.beginPath();
      for (let step = 0; step <= 42; step++) {
        const a = elapsed * .55 + band * Math.PI + step * .028;
        const ripple = Math.sin(step * 1.9 + elapsed * 2) * 3;
        const px = x + Math.cos(a) * (rx + ripple), py = y + Math.sin(a) * (ry + ripple);
        step ? ctx.lineTo(px, py) : ctx.moveTo(px, py);
      }
      ctx.strokeStyle = 'rgba(255,180,92,.4)'; ctx.lineWidth = 1.5; ctx.stroke();
    }
    bursts = bursts.filter(b => b.life > 0);
    for (const b of bursts) {
      b.life -= dt; b.x += b.vx * dt; b.y += b.vy * dt;
      ctx.fillStyle = `rgba(255,182,99,${Math.max(0, b.life) * .55})`;
      ctx.fillRect(b.x, b.y, 2, 2);
    }
    ctx.globalCompositeOperation = 'source-over';
    frame = requestAnimationFrame(tick);
  }
  function sync() {
    if (frame) cancelAnimationFrame(frame);
    frame = 0; last = performance.now();
    if (running()) frame = requestAnimationFrame(tick);
  }
  function render() {
    document.body.classList.toggle('launch-paused', paused);
    button.setAttribute('aria-pressed', String(paused));
    button.textContent = paused ? 'Activar efectos' : 'Pausar efectos';
    if (paused && ctx) ctx.clearRect(0, 0, width, height);
    sync();
  }
  button.hidden = false;
  resize(); render();
  button.addEventListener('click', () => { paused = !paused; render(); });
  preference.addEventListener('change', () => { paused = preference.matches; render(); });
  document.addEventListener('visibilitychange', () => {
    document.body.classList.toggle('launch-background', document.hidden); sync();
  });
  new ResizeObserver(resize).observe(hero);
  new IntersectionObserver(entries => { visible = entries[0].isIntersecting; sync(); }).observe(hero);
  hero.addEventListener('pointermove', e => {
    if (!running() || e.pointerType !== 'mouse') return;
    const r = hero.getBoundingClientRect();
    hero.style.setProperty('--drift-x', `${(e.clientX / width - .5) * -12}px`);
    hero.style.setProperty('--drift-y', `${((e.clientY - r.top) / height - .5) * -8}px`);
    if (bursts.length < 80) bursts.push({x:e.clientX-r.left,y:e.clientY-r.top,vx:(Math.random()-.5)*35,vy:-35,life:.8});
  });
  hero.addEventListener('pointerleave', () => {
    hero.style.setProperty('--drift-x', '0px'); hero.style.setProperty('--drift-y', '0px');
  });
  hero.addEventListener('click', e => {
    if (!running() || e.target.closest('a,button')) return;
    const r = hero.getBoundingClientRect();
    for (let i=0;i<36;i++) {
      const a=Math.random()*Math.PI*2, speed=40+Math.random()*110;
      bursts.push({x:e.clientX-r.left,y:e.clientY-r.top,vx:Math.cos(a)*speed,vy:Math.sin(a)*speed,life:1});
    }
    bursts=bursts.slice(-120);
  });
})();
