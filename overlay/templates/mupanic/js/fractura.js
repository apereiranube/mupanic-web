(() => {
    'use strict';
    const root = document.querySelector('[data-fractura-url]');
    if (!root) return;
    const button = root.querySelector('[data-fractura-refresh]');
    const status = root.querySelector('[data-fractura-status]');
    const dot = root.querySelector('[data-fractura-dot]');
    const effects = root.querySelector('[data-fractura-effects]');
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    const nf = new Intl.NumberFormat('es-AR');
    let busy = false, timer = null, paused = false;
    try { paused = localStorage.getItem('panic-fractura-effects') === 'paused'; } catch {}
    function setEffects(value) {
        paused = value; root.classList.toggle('effects-paused', paused);
        effects.setAttribute('aria-pressed', String(paused));
        effects.textContent = paused ? 'Activar efectos ▶' : 'Pausar efectos Ⅱ';
        try { localStorage.setItem('panic-fractura-effects', paused ? 'paused' : 'active'); } catch {}
    }
    setEffects(paused); effects.hidden = false;
    effects.addEventListener('click', () => setEffects(!paused));
    root.querySelectorAll('[data-fractura-points]').forEach(el => el.classList.toggle('is-long', el.textContent.replace(/[^0-9]/g, '').length > 9));
    const number = v => nf.format(BigInt(v));
    const valid = d => d && d.schema === 1 && Number.isSafeInteger(d.updated) &&
        d.updated > 0 && d.updated <= Date.now() / 1000 + 60 &&
        [1, 2].every(id => ['points', 'members'].every(key =>
            typeof d.factions?.[id]?.[key] === 'string' && /^[0-9]{1,38}$/.test(d.factions[id][key])));
    function render(d) {
        [1, 2].forEach(id => {
            const el = root.querySelector('[data-fractura-points="' + id + '"]');
            const next = number(d.factions[id].points);
            const changed = el.textContent !== next;
            el.textContent = next;
            el.classList.toggle('is-long', d.factions[id].points.length > 9);
            if (changed && !paused && !reduced.matches && el.animate) el.animate([{ opacity: .4, transform: 'translateY(4px)' }, { opacity: 1, transform: 'translateY(0)' }], { duration: 550, easing: 'ease-out' });
            root.querySelector('[data-fractura-members="' + id + '"]').textContent = number(d.factions[id].members);
            root.querySelector('[data-fractura-member-label="' + id + '"]').textContent = BigInt(d.factions[id].members) === 1n ? 'personaje afiliado' : 'personajes afiliados';
        });
        // The native identities do not change: Vanert 2 = Caos, Duprian 1 = Nexo.
        const caos = BigInt(d.factions[2].points), nexo = BigInt(d.factions[1].points), total = caos + nexo;
        const ratio = total ? Number((caos * 1000n + total / 2n) / total) / 10 : 50;
        root.querySelector('[data-fractura-bar]').style.width = ratio + '%';
        root.querySelector('[data-fractura-track]').classList.toggle('is-empty', total === 0n);
        root.querySelector('[data-fractura-share="2"]').textContent = total ? nf.format(ratio) + '%' : '—';
        root.querySelector('[data-fractura-share="1"]').textContent = total ? nf.format(100 - ratio) + '%' : '—';
        root.querySelector('[data-fractura-lead]').textContent = total === 0n ? 'La historia todavía está por escribirse' :
            caos === nexo ? 'Caos y Nexo están igualados' : caos > nexo ? 'Caos lleva la delantera' : 'Nexo lleva la delantera';
        const time = new Date(d.updated * 1000).toLocaleString('es-AR', { timeZone: 'America/Argentina/Buenos_Aires' });
        status.textContent = (d.stale ? 'Última lectura disponible · ' : 'Última consulta · ') + time + ' (Argentina)';
        dot.classList.toggle('is-offline', !!d.stale);
    }
    async function refresh() {
        if (busy || document.hidden) return;
        busy = true; button.disabled = true;
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 12000);
        try {
            const response = await fetch(root.dataset.fracturaUrl, { credentials: 'same-origin', cache: 'no-store', signal: controller.signal });
            if (!response.ok) throw new Error('Unavailable');
            const data = await response.json();
            if (!valid(data)) throw new Error('Unavailable');
            render(data);
        } catch {
            status.textContent = 'No pudimos actualizar. Se conserva la última lectura visible; volvé a intentarlo.';
            dot.classList.add('is-offline');
        } finally { clearTimeout(timeout); busy = false; button.disabled = false; }
    }
    button.hidden = false; button.addEventListener('click', refresh);
    function schedule() { clearInterval(timer); if (!document.hidden) timer = setInterval(refresh, 60000); }
    document.addEventListener('visibilitychange', () => { schedule(); if (!document.hidden) refresh(); }); schedule();
    const dialog = root.querySelector('[data-fractura-map-dialog]');
    if (dialog && typeof dialog.showModal === 'function') {
        const image = dialog.querySelector('[data-fractura-map-image]');
        const zoom = dialog.querySelector('[data-fractura-map-zoom]');
        const scale = dialog.querySelector('[data-fractura-map-scale]');
        function reset() { zoom.value = '100'; image.style.width = '100%'; scale.textContent = '100%'; dialog.querySelector('.fractura-map-viewport').scrollTo(0, 0); }
        root.querySelectorAll('[data-fractura-map-open]').forEach(link => link.addEventListener('click', event => {
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault(); reset(); dialog.showModal(); document.body.classList.add('fractura-map-open');
        }));
        zoom.addEventListener('input', () => { image.style.width = zoom.value + '%'; scale.textContent = zoom.value + '%'; });
        dialog.querySelector('[data-fractura-map-reset]').addEventListener('click', reset);
        dialog.querySelector('[data-fractura-map-close]').addEventListener('click', () => dialog.close());
        dialog.addEventListener('close', () => document.body.classList.remove('fractura-map-open'));
        dialog.addEventListener('click', event => { const r = dialog.getBoundingClientRect(); if (event.target === dialog && (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom)) dialog.close(); });
    }
})();
