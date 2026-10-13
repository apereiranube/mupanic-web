(() => {
    'use strict';
    const root = document.querySelector('[data-fractura-url]');
    if (!root) return;
    const status = root.querySelector('[data-fractura-status]');
    const effects = root.querySelector('[data-fractura-effects]');
    const scene = root.querySelector('[data-fractura-scene]');
    const canvas = root.querySelector('[data-fractura-particles]');
    const ctx = canvas && typeof canvas.getContext === 'function' ? canvas.getContext('2d') : null;
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    const nf = new Intl.NumberFormat('es-AR');
    let busy = false, timer = null, paused = false, frame = null, lastFrame = 0, width = 0, height = 0, particles = [];
    try { paused = localStorage.getItem('panic-fractura-effects') === 'paused'; } catch {}
    function resize() {
        if (!ctx || !scene) return;
        const box = scene.getBoundingClientRect(); width = box.width; height = box.height;
        const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
        canvas.width = Math.round(width * dpr); canvas.height = Math.round(height * dpr); ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        particles = Array.from({ length: width < 600 ? 36 : 78 }, () => ({ x: Math.random() * width, y: Math.random() * height, speed: 12 + Math.random() * 32, radius: .7 + Math.random() * 2, phase: Math.random() * 6.28, life: Math.random() }));
    }
    function draw(time) {
        frame = null;
        if (!ctx || paused || reduced.matches || document.hidden) return;
        const step = Math.min((time - lastFrame) / 1000 || .016, .05); lastFrame = time;
        ctx.clearRect(0, 0, width, height);
        for (const p of particles) {
            p.y -= p.speed * step; p.x += Math.sin(time / 1600 + p.phase) * step * 7;
            if (p.y < -12) { p.y = height + 12; p.x = Math.random() * width; }
            const cold = p.x > width / 2, alpha = .25 + .45 * Math.abs(Math.sin(time / 1800 + p.phase));
            ctx.beginPath(); ctx.fillStyle = cold ? 'rgba(163,225,247,' + alpha + ')' : 'rgba(255,131,105,' + alpha + ')';
            ctx.shadowColor = cold ? '#8dddfa' : '#fb6770'; ctx.shadowBlur = 12; ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2); ctx.fill();
        }
        ctx.shadowBlur = 0; frame = requestAnimationFrame(draw);
    }
    function motion() {
        if (frame !== null) { cancelAnimationFrame(frame); frame = null; }
        root.classList.toggle('effects-paused', paused || document.hidden);
        effects.setAttribute('aria-pressed', String(paused));
        effects.textContent = paused ? 'Reanudar escena ▶' : 'Pausar escena Ⅱ';
        if (ctx && !paused && !reduced.matches && !document.hidden) { lastFrame = performance.now(); frame = requestAnimationFrame(draw); }
    }
    effects.hidden = false;
    effects.addEventListener('click', () => { paused = !paused; try { localStorage.setItem('panic-fractura-effects', paused ? 'paused' : 'active'); } catch {} motion(); });
    if (ctx) { resize(); window.addEventListener('resize', resize); }
    if (scene) scene.addEventListener('pointermove', event => {
        if (paused || reduced.matches || event.pointerType === 'touch') return;
        const rect = scene.getBoundingClientRect(), x = (event.clientX - rect.left) / rect.width - .5;
        root.querySelectorAll('[data-fractura-depth]').forEach(el => { el.style.transform = 'translateX(' + (x * Number(el.dataset.fracturaDepth) * 18) + 'px)'; });
    });
    motion();
    if (typeof reduced.addEventListener === 'function') reduced.addEventListener('change', motion);
    root.querySelectorAll('[data-fractura-points]').forEach(el => el.classList.toggle('is-long', el.textContent.replace(/[^0-9]/g, '').length > 9));
    const number = v => nf.format(BigInt(v));
    const valid = d => d && d.schema === 1 && Number.isSafeInteger(d.updated) && d.updated > 0 && d.updated <= Date.now() / 1000 + 60 &&
        [1, 2].every(id => ['points', 'members'].every(key => typeof d.factions?.[id]?.[key] === 'string' && /^[0-9]{1,38}$/.test(d.factions[id][key])));
    function render(d) {
        [1, 2].forEach(id => {
            const el = root.querySelector('[data-fractura-points="' + id + '"]'), next = number(d.factions[id].points), changed = el.textContent !== next;
            el.textContent = next; el.classList.toggle('is-long', d.factions[id].points.length > 9);
            if (changed && !paused && !reduced.matches && el.animate) el.animate([{ opacity: .4, transform: 'translateY(4px)' }, { opacity: 1, transform: 'translateY(0)' }], { duration: 550, easing: 'ease-out' });
            root.querySelector('[data-fractura-members="' + id + '"]').textContent = number(d.factions[id].members);
            root.querySelector('[data-fractura-member-label="' + id + '"]').textContent = BigInt(d.factions[id].members) === 1n ? 'combatiente' : 'combatientes';
        });
        const caos = BigInt(d.factions[2].points), nexo = BigInt(d.factions[1].points), total = caos + nexo;
        const ratio = total ? Number((caos * 1000n + total / 2n) / total) / 10 : 50;
        root.querySelector('[data-fractura-bar]').style.width = ratio + '%';
        root.querySelector('[data-fractura-track]').classList.toggle('is-empty', total === 0n);
        root.querySelector('[data-fractura-share="2"]').textContent = total ? nf.format(ratio) + '%' : '—';
        root.querySelector('[data-fractura-share="1"]').textContent = total ? nf.format(100 - ratio) + '%' : '—';
        root.querySelector('[data-fractura-lead]').textContent = total === 0n ? 'La historia todavía está por escribirse' : caos === nexo ? 'Caos y Nexo están igualados' : caos > nexo ? 'Caos lleva la delantera' : 'Nexo lleva la delantera';
        status.hidden = !d.stale; status.textContent = d.stale ? 'El marcador está temporalmente sin actualizar.' : '';
    }
    async function refresh() {
        if (busy || document.hidden) return;
        busy = true;
        const controller = new AbortController(), timeout = setTimeout(() => controller.abort(), 12000);
        try {
            const response = await fetch(root.dataset.fracturaUrl, { credentials: 'same-origin', cache: 'no-store', signal: controller.signal });
            if (!response.ok) throw new Error('Unavailable');
            const data = await response.json(); if (!valid(data)) throw new Error('Unavailable'); render(data);
        } catch { status.hidden = false; status.textContent = 'El marcador está temporalmente sin actualizar.'; }
        finally { clearTimeout(timeout); busy = false; }
    }
    function schedule() { clearInterval(timer); if (!document.hidden) timer = setInterval(refresh, 60000); }
    document.addEventListener('visibilitychange', () => { schedule(); motion(); if (!document.hidden) refresh(); }); schedule();
})();
