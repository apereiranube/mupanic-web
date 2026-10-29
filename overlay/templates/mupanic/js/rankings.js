/* MU PANIC · Rankings (estilo nuevo). Mejora el HTML del CMS sin cambiar el orden, los puntajes ni los enlaces de perfil.
   Arte: window.RK_ART (inc/rankings-art.php). Si falta un archivo, ese lugar queda limpio (número o vacío), nunca un dibujo.
   Movimiento: el podio sube 3.º → 2.º → 1.º, los puntajes cuentan hasta el valor real, la barra de categorías se desliza,
   las filas entran escalonadas en cada página o filtro. Con movimiento reducido todo se muestra quieto y completo. */
(() => {
    'use strict';
    const surface = document.querySelector('.is-rankings .module-surface');
    if (!surface) return;
    const ART = window.RK_ART || {};
    const art = (group, key) => (ART[group] && ART[group][key]) || null;
    const reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
    const motion = !reduce && 'IntersectionObserver' in window;
    /* ---------- Portada: el fondo se desplaza un poco más lento que la página ---------- */
    const fondo = document.querySelector('[data-rk-parallax]');
    if (fondo && motion) {
        let ticking = false;
        addEventListener('scroll', () => {
            if (ticking) return; ticking = true;
            requestAnimationFrame(() => { ticking = false; const y = Math.min(scrollY, 600); fondo.style.transform = 'translate3d(0,' + (y * .18).toFixed(1) + 'px,0)'; });
        }, {passive: true});
    }
    /* ---------- Portada: el Soul Master sigue un poco al mouse (solo con mouse) ---------- */
    const hero = document.querySelector('.rk-hero'), fig = document.querySelector('.rk-hero-fig.has-art');
    if (hero && fig && !reduce && matchMedia('(hover: hover) and (pointer: fine)').matches) {
        let raf = 0, tx = 0, ty = 0;
        hero.addEventListener('mousemove', e => {
            const r = hero.getBoundingClientRect();
            tx = ((e.clientX - r.left) / r.width - .5) * 2; ty = ((e.clientY - r.top) / r.height - .5) * 2;
            if (!raf) raf = requestAnimationFrame(() => { raf = 0; fig.style.setProperty('--mx', (tx * 14).toFixed(1) + 'px'); fig.style.setProperty('--my', (ty * 8).toFixed(1) + 'px'); fig.style.setProperty('--rot', (tx * 1.2).toFixed(2) + 'deg'); });
        });
        hero.addEventListener('mouseleave', () => { fig.style.setProperty('--mx', '0px'); fig.style.setProperty('--my', '0px'); fig.style.setProperty('--rot', '0deg'); });
    }
    // Profiles open in the same tab, so the browser's Back action also works.
    surface.querySelectorAll('a').forEach(link => {
        const url = new URL(link.href, location.href);
        if (url.origin === location.origin && /\/profile\/(player|guild)\//.test(url.pathname)) link.removeAttribute('target');
    });
    surface.addEventListener('click', event => {
        const link = event.target.closest('a');
        if (!link) return;
        const url = new URL(link.href, location.href);
        if (url.origin !== location.origin || !/\/profile\/(player|guild)\//.test(url.pathname)) return;
        try { sessionStorage.setItem('mupanic:ranking-return', JSON.stringify({from:location.href,to:url.href,at:Date.now()})); } catch (_) {}
    });
    const make = (tag, cls, text) => {
        const el = document.createElement(tag);
        if (cls) el.className = cls;
        if (text !== undefined) el.textContent = text;
        return el;
    };
    // Las variables CSS con url() se resuelven contra la hoja de estilos: siempre se pasan absolutas.
    const abs = src => new URL(src, document.baseURI).href;
    // Íconos de arte (SVG con trazo hueso): se tiñen con el color del texto mediante máscara.
    const icon = (src, cls) => { const el = make('span', 'rk-ico ' + (cls || '')); el.setAttribute('aria-hidden', 'true'); el.style.setProperty('--ico', 'url("' + abs(src) + '")'); return el; };
    const classKey = id => ({0:'sm',16:'bk',32:'elfa',48:'mg',64:'dl',80:'su',96:'rf'})[Math.floor(Number(id) / 16) * 16] || null;
    const classIcon = id => { const key = classKey(id); return key ? art('clase', key) : null; };
    const hasClassArt = ART.clase && Object.keys(ART.clase).length > 0;
    const labels = {level:'Nivel',masterlevel:'Nivel Master',master:'Nivel Master',killers:'Asesinatos',guilds:'Guilds',online:'Tiempo conectado',votes:'Votos',resets:'Resets',reset:'Resets',grandresets:'Master Resets',gens:'Gens',bloodcastle:'Blood Castle',devilsquare:'Devil Square',duels:'Duelos'};
    const keyAlias = {masterlevel:'master',reset:'resets'};
    const translations = {country:'País',class:'Clase',character:'Personaje',level:'Nivel',location:'Mapa',guild:'Guild',logo:'Emblema',master:'Líder',score:'Puntos',kills:'Asesinatos','master level':'Nivel Master',resets:'Resets','grand resets':'Master Resets','guild name':'Guild','guild master':'Líder','guild score':'Puntos','pk count':'Asesinatos','pk level':'Estado PK'};
    const classLabels = {all:'Todas',wizards:'Magos',knights:'Guerreros',elves:'Elfas',gladiators:'Gladiadores',lords:'Dark Lords',summoners:'Summoners',fighters:'Rage Fighters',lancers:'Lancers','rune wizards':'Rune Wizards',slayers:'Slayers'};

    /* ---------- Bandas: categorías / podio / tabla ---------- */
    surface.classList.add('rk-js');
    if (motion) surface.classList.add('rk-anim');
    const band = cls => { const s = make('section', 'rk-band ' + cls); const w = make('div', 'pui-wrap rk-band-in'); s.append(w); return [s, w]; };
    const [tabsBand, tabsIn] = band('rk-tabs');
    const [boardBand, boardIn] = band('rk-board');
    const menu = surface.querySelector('.rankings_menu');
    let activeLink = null, activeKey = '';
    if (menu) {
        menu.setAttribute('aria-label','Categorías de ranking');
        menu.querySelectorAll('a').forEach(link => {
            const raw = new URL(link.href,location.href).pathname.split('/').filter(Boolean).pop() || '';
            const key = keyAlias[raw] || raw;
            link.dataset.rkKey = key;
            const text = labels[raw] || link.textContent.trim();
            link.textContent = '';
            const src = art('cat', key);
            if (src) link.append(icon(src, 'rk-tab-ico'));
            link.append(make('span', 'rk-tab-label', text));
            if (link.classList.contains('active')) { link.setAttribute('aria-current','page'); activeLink = link; activeKey = key; }
        });
        tabsBand.setAttribute('aria-label', 'Categorías');
    }
    // Todo lo que trae el CMS pasa a la banda de la tabla, en su orden original.
    [...surface.childNodes].forEach(node => { if (node !== menu) boardIn.append(node); });
    if (menu) tabsIn.append(menu);
    surface.append(tabsBand, boardBand);
    surface.querySelectorAll('.rankings-update-time').forEach(el => { el.textContent = el.textContent.replace(/^\s*last update:?/i, 'Última consulta:'); });

    // Barra de la categoría activa: se desliza desde la categoría anterior (o desde la izquierda).
    if (menu && activeLink) {
        const bar = make('i', 'rk-tab-bar'); bar.setAttribute('aria-hidden', 'true'); menu.append(bar);
        const place = (link, animate) => {
            bar.style.transition = animate ? '' : 'none';
            bar.style.width = (link ? link.offsetWidth : 0) + 'px';
            bar.style.transform = 'translateX(' + (link ? link.offsetLeft : 0) + 'px)';
        };
        let previous = null;
        try { previous = sessionStorage.getItem('mupanic:rk-tab'); sessionStorage.setItem('mupanic:rk-tab', activeKey); } catch (_) {}
        const fromLink = previous && previous !== activeKey ? menu.querySelector('a[data-rk-key="' + CSS.escape(previous) + '"]') : null;
        const ready = document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve();
        ready.then(() => {
            if (menu.scrollWidth > menu.clientWidth) menu.scrollLeft = activeLink.offsetLeft - (menu.clientWidth - activeLink.offsetWidth) / 2;
            if (!motion) { place(activeLink, false); return; }
            place(fromLink, false); void bar.offsetWidth;
            requestAnimationFrame(() => requestAnimationFrame(() => place(activeLink, true)));
        });
        let resizeTimer;
        addEventListener('resize', () => { clearTimeout(resizeTimer); resizeTimer = setTimeout(() => place(activeLink, false), 80); });
    }

    const categoryCopy = boardIn.querySelector('.rankings-category-copy');
    const table = boardIn.querySelector('.rankings-table');
    if (!table || !table.rows.length) {
        const empty = boardIn.querySelector('.rankings-empty, .alert');
        if (empty) empty.id = 'rankings-results'; else boardBand.id = 'rankings-results';
        return;
    }

    /* ---------- Tabla: encabezados y celdas ---------- */
    const header = table.rows[0];
    const columns = [...header.cells].map(cell => cell.textContent.trim().toLowerCase());
    [...header.cells].forEach(cell => {
        const th = make('th','',translations[cell.textContent.trim().toLowerCase()] || cell.textContent.trim() || 'Puesto');
        th.scope = 'col'; cell.replaceWith(th);
    });
    const rows = [...table.rows].slice(1).filter(row => row.cells.length === columns.length);
    rows.forEach(row => {
        const place = row.querySelector('.rankings-table-place');
        if (place) row.dataset.rankPosition = place.textContent.trim();
        row.querySelectorAll('.rankings-class-image').forEach(img => {
            const label = make('span','rankings-class-label',img.alt || 'Clase'); img.replaceWith(label);
        });
        row.querySelectorAll('.online-status-indicator').forEach(img => {
            const file = new URL(img.src,location.href).pathname.split('/').pop();
            if (/^online[.-]/i.test(file)) img.alt = img.title = 'En línea';
            else if (/^offline[.-]/i.test(file)) img.alt = img.title = 'Desconectado';
        });
    });
    const nameIndex = columns.findIndex(x => ['character','personaje','guild','guild name'].includes(x));
    const isGuild = nameIndex >= 0 && ['guild','guild name'].includes(columns[nameIndex]);
    const classIndex = columns.findIndex(x => ['class','clase'].includes(x));
    const masterIndex = columns.findIndex(x => ['guild master','master','líder'].includes(x));
    const metricGroups = [['grand resets','master resets'],['resets'],['victorias'],['pk count','kills','asesinatos'],['guild score','score','puntos'],['master level','nivel master'],['level','nivel']];
    let scoreIndex = -1;
    for (const group of metricGroups) { scoreIndex = columns.findIndex(x => group.includes(x)); if (scoreIndex >= 0) break; }
    const scoreLabel = scoreIndex >= 0 ? header.cells[scoreIndex].textContent : '';

    /* ---------- Podio: tres columnas (2.º izquierda, 1.º centro, 3.º derecha) sobre el arte del podio ---------- */
    let rowsOf = [];
    if (nameIndex >= 0 && scoreIndex >= 0 && rows.length) {
        const [stageBand, stageIn] = band('rk-stage rankings-stage');
        stageBand.setAttribute('aria-label','Líderes de la categoría');
        const head = make('div', 'rk-stage-head');
        const copy = make('div');
        copy.append(make('p', 'pui-kicker', 'Top ' + Math.min(3, rows.length)), make('h2', 'pui-h2', activeLink ? activeLink.textContent.trim() : (categoryCopy && categoryCopy.querySelector('h2') ? categoryCopy.querySelector('h2').textContent.trim() : 'Líderes')));
        head.append(copy);
        if (categoryCopy && categoryCopy.querySelector('p')) head.append(make('p', 'rk-stage-copy', categoryCopy.querySelector('p').textContent.trim()));
        if (categoryCopy) categoryCopy.remove();
        const podium = make('div','rankings-podium rk-podium'); podium.dataset.leaders = String(Math.min(3,rows.length)); podium.setAttribute('aria-label','Primeros puestos del ranking completo');
        [1, 2, 3].forEach(n => {
            const col = make('div', 'rk-col rk-col--' + n);
            const row = rows[n - 1];
            if (row) {
                const card = make('article','rankings-leader rk-leader');
                card.dataset.place = String(n);
                const ins = make('div', 'rk-ins'); ins.setAttribute('aria-hidden', 'true');
                const insSrc = art('insignia', n);
                if (insSrc) {
                    const img = make('img'); img.src = insSrc; img.alt = ''; img.decoding = 'async'; ins.append(img); ins.classList.add('has-art');
                    if (n === 1) { const sweep = make('i', 'rk-sweep'); sweep.style.setProperty('--ins', 'url("' + abs(insSrc) + '")'); ins.append(sweep); }
                } else ins.append(make('span', 'rk-ins-num', String(n)));
                card.append(ins, make('span','rankings-leader-position','Puesto ' + n));
                const original = row.cells[nameIndex].querySelector('a');
                const name = original ? original.cloneNode(true) : make('strong','',row.cells[nameIndex].textContent.trim());
                name.className = 'rankings-leader-name'; card.append(name);
                const cls = make('span','rankings-leader-class');
                const cSrc = !isGuild && classIcon(row.dataset.classId);
                if (cSrc) cls.append(icon(cSrc, 'rk-leader-ico'));
                cls.append(make('span', '', classIndex >= 0 ? row.cells[classIndex].textContent.trim() : (masterIndex >= 0 ? 'Líder: ' + row.cells[masterIndex].textContent.trim() : 'Guild')));
                card.append(cls);
                const score = make('div','rankings-leader-score');
                const lab = make('span', '', scoreLabel); lab.append(make('span', 'pui-sr', ': '));
                const value = make('strong','',row.cells[scoreIndex].textContent.trim()); value.dataset.rkCount = value.textContent;
                score.append(lab, value); card.append(score);
                const inspect = make('button','rankings-inspect','Ver en la tabla ↓'); inspect.type = 'button'; inspect.addEventListener('click',() => focusRow(row)); card.append(inspect);
                col.append(card);
            }
            const base = make('div', 'rk-base'); base.setAttribute('aria-hidden', 'true');
            if (ART.podio) { base.classList.add('has-art'); base.style.backgroundImage = 'url("' + abs(ART.podio) + '")'; }
            base.append(make('span', 'rk-base-num', String(n)));
            col.append(base);
            podium.append(col);
        });
        stageIn.append(head, podium);
        tabsBand.after(stageBand);
        rowsOf = [...podium.querySelectorAll('[data-rk-count]')];
        // Cuenta hasta el valor real (mismo formato con puntos que trae la tabla).
        const fmt = (n, dots) => dots ? String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.') : String(n);
        const counters = rowsOf.map(el => {
            const text = el.dataset.rkCount, ok = /^\d{1,3}(\.\d{3})*$|^\d+$/.test(text), target = ok ? Number(text.replace(/\./g, '')) : NaN;
            return {el, text, dots: text.indexOf('.') >= 0, target, ok: ok && Number.isSafeInteger(target)};
        });
        const count = (c, delay) => {
            if (!c.ok) return;
            setTimeout(() => {
                const t0 = performance.now(), dur = 1300;
                (function step(t) {
                    const x = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - x, 3);
                    c.el.textContent = x >= 1 ? c.text : fmt(Math.round(c.target * e), c.dots);
                    if (x < 1) requestAnimationFrame(step);
                })(t0);
            }, delay);
        };
        if (motion) {
            counters.forEach(c => { if (c.ok) c.el.textContent = '0'; });
            const io = new IntersectionObserver(entries => {
                if (!entries.some(e => e.isIntersecting)) return;
                io.disconnect(); podium.classList.add('is-up');
                // 3.º, 2.º y 1.º suben en ese orden; cada número arranca cuando su columna llega.
                const delays = {3: 350, 2: 550, 1: 750};
                counters.forEach((c, i) => count(c, delays[i + 1]));
            }, {threshold: .25});
            io.observe(podium);
        } else podium.classList.add('is-up');
    }

    /* ---------- Filas: puesto, identidad, puntaje y perfil ---------- */
    const countryIndex = columns.findIndex(x => ['country','país'].includes(x));
    header.querySelectorAll('th').forEach((cell,index) => {
        if (index === classIndex || index === countryIndex) cell.classList.add('rankings-merged-column');
        if (index === nameIndex) cell.classList.add('rankings-identity-column');
        if (index === scoreIndex) cell.classList.add('rankings-score-column');
    });
    rows.forEach(row => {
        row.dataset.rankingSearch = nameIndex >= 0 ? row.cells[nameIndex].textContent + (isGuild ? ' ' + row.textContent : '') : row.textContent;
        [...row.cells].forEach((cell,index) => {
            cell.dataset.label = header.cells[index].textContent;
            if (index === classIndex || index === countryIndex) cell.classList.add('rankings-merged-column');
            if (index !== nameIndex && index !== classIndex && index !== countryIndex && !cell.classList.contains('rankings-table-place')) cell.classList.add('rankings-detail-cell');
        });
        const place = row.querySelector('.rankings-table-place');
        if (place) {
            const number = place.textContent.trim(); place.textContent = '';
            const medal = make('span','rankings-place-medal'); const src = Number(number) <= 3 ? art('insignia', Number(number)) : null;
            const b = make('b', src ? 'pui-sr' : '', number);
            if (src) { const img = make('img', 'rk-place-ins'); img.src = src; img.alt = ''; img.decoding = 'async'; img.loading = 'lazy'; medal.append(img); medal.classList.add('has-art'); }
            medal.append(b); place.append(medal);
        }
        if (nameIndex >= 0) {
            const cell = row.cells[nameIndex]; cell.classList.add('rankings-identity-cell');
            const identity = make('div','rankings-identity');
            if (hasClassArt && !isGuild) {
                const emblem = make('span','rankings-identity-emblem'); emblem.setAttribute('aria-hidden', 'true');
                const src = classIcon(row.dataset.classId); if (src) emblem.append(icon(src, 'rk-row-ico'));
                identity.append(emblem);
            }
            const copy = make('div','rankings-identity-copy'); const line = make('div','rankings-identity-name');
            while (cell.firstChild) line.append(cell.firstChild);
            const status = line.querySelector('.online-status-indicator');
            if (status) { const presence = make('span','rankings-presence',status.alt); presence.classList.toggle('is-online',status.alt === 'En línea'); status.replaceWith(presence); }
            copy.append(line);
            const meta = make('div','rankings-identity-meta');
            if (classIndex >= 0) meta.append(make('span','',row.cells[classIndex].textContent.trim()));
            if (countryIndex >= 0) [...row.cells[countryIndex].childNodes].forEach(node => meta.append(node.cloneNode(true)));
            if (meta.childNodes.length) copy.append(meta);
            identity.append(copy); cell.append(identity);
        }
        if (scoreIndex >= 0) { const cell = row.cells[scoreIndex]; cell.classList.add('rankings-score-cell'); const value = cell.textContent.trim(); cell.textContent = ''; cell.append(make('strong','rankings-score-value',value)); }
        const original = nameIndex >= 0 ? row.cells[nameIndex].querySelector('a') : null;
        if (original) { const cell = document.createElement('td'); cell.className = 'rankings-profile-cell'; cell.dataset.label = 'Perfil'; const link = original.cloneNode(false); link.className = 'rankings-profile-link'; link.textContent = 'Ver perfil ↗︎'; link.setAttribute('aria-label','Ver perfil de ' + original.textContent.trim()); cell.append(link); row.append(cell); }
    });
    if (rows.length && rows.every(row => row.querySelector('.rankings-profile-cell'))) { const th = make('th','rankings-profile-column','Perfil'); th.scope = 'col'; header.append(th); }
    table.classList.add('rankings-designed-table');
    // Alto fijo de las tarjetas en celular: una línea por dato.
    if (rows[0]) table.style.setProperty('--rk-lines', String(rows[0].querySelectorAll('.rankings-detail-cell,.rankings-profile-cell').length));

    /* ---------- Buscador, clases y páginas (alto reservado: la página nunca salta) ---------- */
    let classIds = null, query = '', page = 0;
    const pageSize = 20, slots = Math.min(pageSize, rows.length);
    const listHeading = make('div','rankings-list-heading');
    const listCopy = make('div'); listCopy.append(make('p','pui-kicker','Clasificación completa'), make('h2','pui-h2','Tabla de posiciones'));
    listHeading.append(listCopy, make('span','rankings-list-total',`${rows.length} ${isGuild ? (rows.length === 1 ? 'guild' : 'guilds') : (rows.length === 1 ? 'personaje' : 'personajes')}`));
    const tools = make('div','rankings-tools');
    const label = make('label','rankings-search');
    label.append(make('span', 'rk-search-label', isGuild ? 'Buscar guild o líder' : 'Buscar personaje'));
    const input = make('input'); input.type = 'search'; input.placeholder = 'Escribí un nombre…'; input.autocomplete = 'off'; label.append(input);
    tools.append(label);
    const filter = boardIn.querySelector('.rankings-class-filter');
    if (filter) tools.append(filter);
    const area = make('div', 'rk-table-area');
    const wrap = make('div','rankings-table-scroll'); wrap.id = 'rankings-results'; wrap.tabIndex = 0; wrap.setAttribute('role','region'); wrap.setAttribute('aria-label','Tabla de posiciones');
    table.before(listHeading, tools, area); area.append(wrap); wrap.append(table);
    const empty = make('p','rankings-empty','No encontramos resultados con esos filtros. Probá otro nombre o elegí Todas.'); empty.hidden = true; area.append(empty);
    const foot = make('div', 'rk-foot');
    const status = make('p','rankings-result-status'); status.setAttribute('role','status');
    const nav = make('div','rankings-pagination rk-pager');
    const prev = make('button','pui-btn pui-btn--dark pui-btn--sm','← Anterior'), next = make('button','pui-btn pui-btn--dark pui-btn--sm','Siguiente →'), counter = make('span');
    prev.type = next.type = 'button'; nav.append(prev,counter,next);
    if (rows.length <= pageSize) nav.hidden = true;
    foot.append(status, nav); area.after(foot);
    boardIn.querySelectorAll('.rankings-update-time').forEach(el => boardIn.append(el));
    // Filas de relleno: ocupan el lugar de las que no se muestran, con el mismo alto.
    const tbody = rows[0] ? rows[0].parentNode : table.tBodies[0];
    const fillers = Array.from({length: slots}, () => { const tr = make('tr', 'rk-filler'); tr.setAttribute('aria-hidden', 'true'); const td = make('td'); td.colSpan = header.cells.length; tr.append(td); tr.hidden = true; tbody.append(tr); return tr; });

    let seen = !motion;
    function enter(visible) {
        if (!motion || !seen) return;
        visible.forEach(r => r.classList.remove('rk-enter'));
        void table.offsetWidth;
        visible.forEach((r, i) => { r.style.setProperty('--i', i); r.classList.add('rk-enter'); });
    }
    const normalize = text => text.normalize('NFD').replace(/[̀-ͯ]/g,'').toLowerCase();
    function render() {
        const matching = rows.filter(row => (!classIds || classIds.includes(Number(row.dataset.classId))) && normalize(row.dataset.rankingSearch).includes(query));
        const pages = Math.max(1,Math.ceil(matching.length / pageSize)); page = Math.min(page,pages - 1);
        const shown = matching.slice(page * pageSize,(page + 1) * pageSize), visible = new Set(shown);
        rows.forEach(row => { row.hidden = !visible.has(row); });
        fillers.forEach((tr, i) => { tr.hidden = i >= slots - shown.length; });
        status.textContent = matching.length ? `${page * pageSize + 1}–${Math.min((page + 1) * pageSize,matching.length)} de ${matching.length} resultados` : 'Sin resultados';
        empty.hidden = !!matching.length;
        nav.classList.toggle('is-idle', matching.length <= pageSize);
        prev.disabled = !page; next.disabled = page >= pages - 1; counter.textContent = `Página ${page + 1} de ${pages}`;
        enter(shown);
    }
    function focusRow(row) {
        classIds = null; query = ''; input.value = ''; page = Math.floor(rows.indexOf(row) / pageSize);
        boardIn.querySelectorAll('.rankings-class-button').forEach((b,i) => b.setAttribute('aria-pressed',String(i === 0))); render();
        rows.forEach(r => r.classList.remove('rankings-row-focus')); row.classList.add('rankings-row-focus'); row.tabIndex = -1;
        row.scrollIntoView({behavior: reduce ? 'auto' : 'smooth',block:'center'}); row.focus({preventScroll:true});
    }
    input.addEventListener('input',() => { query = normalize(input.value.trim()); page = 0; render(); });
    prev.addEventListener('click',() => { page--; render(); }); next.addEventListener('click',() => { page++; render(); });
    if (filter) filter.querySelectorAll('a').forEach(link => {
        const action = link.getAttribute('onclick') || '';
        const match = action.match(/^\s*rankingsFilterByClass\(([\d,\s]+)\)\s*;?\s*$/);
        const all = /^\s*rankingsFilterRemove\(\)\s*;?\s*$/.test(action);
        if (!match && !all) return;
        const text = link.textContent.trim(); const button = make('button','rankings-class-button'); button.type = 'button'; button.setAttribute('aria-pressed',String(all));
        const src = !all && classIcon(match[1].split(',')[0]); if (src) button.append(icon(src, 'rk-filter-ico'));
        button.append(make('span', '', classLabels[text.toLowerCase()] || text));
        button.addEventListener('click',() => { classIds = all ? null : match[1].split(',').map(Number); page = 0; filter.querySelectorAll('button').forEach(b => b.setAttribute('aria-pressed',String(b === button))); render(); });
        link.replaceWith(button);
    });
    render();
    if (motion) {
        const io = new IntersectionObserver(entries => {
            if (!entries.some(e => e.isIntersecting)) return;
            io.disconnect(); seen = true; area.classList.add('is-seen'); render();
        }, {threshold: .08});
        io.observe(area);
    }

})();
