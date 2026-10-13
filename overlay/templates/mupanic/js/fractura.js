(() => {
    'use strict';
    const root = document.querySelector('[data-fractura-url]');
    if (!root) return;
    const button = root.querySelector('[data-fractura-refresh]');
    const status = root.querySelector('[data-fractura-status]');
    const dot = root.querySelector('[data-fractura-dot]');
    const nf = new Intl.NumberFormat('es-AR');
    let busy = false, timer = null;
    const number = v => nf.format(BigInt(v));
    const valid = d => d && d.schema === 1 && Number.isSafeInteger(d.updated) &&
        d.updated > 0 && d.updated <= Date.now() / 1000 + 60 &&
        [1, 2].every(id => ['points', 'members'].every(key =>
            typeof d.factions?.[id]?.[key] === 'string' && /^[0-9]{1,38}$/.test(d.factions[id][key])));
    function render(d) {
        [1, 2].forEach(id => {
            root.querySelector('[data-fractura-points="' + id + '"]').textContent = number(d.factions[id].points);
            root.querySelector('[data-fractura-members="' + id + '"]').textContent = number(d.factions[id].members);
        });
        const a = BigInt(d.factions[1].points), b = BigInt(d.factions[2].points), total = a + b;
        const ratio = total ? Number(a * 1000n / total) / 10 : 50;
        root.querySelector('[data-fractura-bar]').style.width = ratio + '%';
        root.querySelector('[data-fractura-track]').classList.toggle('is-empty', total === 0n);
        root.querySelector('[data-fractura-share="1"]').textContent = total ? nf.format(ratio) + '%' : '—';
        root.querySelector('[data-fractura-share="2"]').textContent = total ? nf.format(100 - ratio) + '%' : '—';
        root.querySelector('[data-fractura-lead]').textContent = total === 0n ? 'Las órdenes todavía no registran puntos' :
            a === b ? 'Las dos órdenes están igualadas' : a > b ? 'Custodios del Sello al frente' : 'Herederos de la Fractura al frente';
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
    function schedule() {
        clearInterval(timer);
        if (!document.hidden) timer = setInterval(refresh, 60000);
    }
    document.addEventListener('visibilitychange', () => { schedule(); if (!document.hidden) refresh(); });
    schedule();
})();
