/* Enhance the CMS markup without changing its order, scores or profile URLs. */
(() => {
    'use strict';
    const surface = document.querySelector('.is-rankings .module-surface');
    if (!surface) return;
    const make = (tag, cls, text) => {
        const el = document.createElement(tag);
        if (cls) el.className = cls;
        if (text !== undefined) el.textContent = text;
        return el;
    };
    const paths = {
        crest: 'M6 9l14-5 14 5v17L20 38 6 26zM12 25V13l8 7 8-7v12l-8 7z',
        sword: 'M25 4l3 9-15 15-5-5zM6 24l10 10M4 36l6-6',
        magic: 'M20 3l5 12 12 5-12 5-5 12-5-12-12-5 12-5zM20 12v16M12 20h16',
        wings: 'M20 31V17M20 23L4 6l2 14 12 11M20 23L36 6l-2 14-12 11M8 14l9 10M32 14l-9 10',
        crown: 'M6 12l7 7 7-13 7 13 7-7-3 19H9zM9 36h22',
        gem: 'M11 8h18l7 12-16 18L4 20zM4 20h32M11 8l9 30 9-30',
    };
    function glyph(type) {
        const svg = document.createElementNS('http://www.w3.org/2000/svg','svg');
        svg.setAttribute('viewBox','0 0 40 42'); svg.setAttribute('fill','none');svg.setAttribute('stroke','currentColor');svg.setAttribute('stroke-width','1.4');svg.setAttribute('aria-hidden','true');
        const path = document.createElementNS('http://www.w3.org/2000/svg','path');path.setAttribute('d',paths[type] || paths.crest);svg.append(path);return svg;
    }
    function classGlyph(id) { return ({0:'magic',16:'sword',32:'wings',48:'sword',64:'crown',80:'gem',96:'sword'})[Math.floor(Number(id)/16)*16] || 'crest'; }
    const labels = {level:'Nivel',masterlevel:'Nivel Master',master:'Nivel Master',killers:'Asesinatos',guilds:'Guilds',online:'Tiempo conectado',votes:'Votos',resets:'Resets',reset:'Resets',grandresets:'Master Resets',gens:'Gens'};
    const translations = {country:'País',class:'Clase',character:'Personaje',level:'Nivel',location:'Mapa',guild:'Guild',logo:'Emblema',master:'Líder',score:'Puntos',kills:'Asesinatos','master level':'Nivel Master',resets:'Resets','grand resets':'Master Resets','guild name':'Guild','guild master':'Líder','guild score':'Puntos','pk count':'Asesinatos','pk level':'Estado PK','master level':'Nivel Master'};
    const classLabels = {all:'Todas',wizards:'Magos',knights:'Guerreros',elves:'Elfas',gladiators:'Gladiadores',lords:'Dark Lords',summoners:'Summoners',fighters:'Rage Fighters',lancers:'Lancers','rune wizards':'Rune Wizards',slayers:'Slayers'};
    const intro = make('p','rankings-intro','Conocé a los líderes de MU PANIC. Elegí una categoría y buscá un personaje o una guild para ver su posición.');
    const menu = surface.querySelector('.rankings_menu');
    if (menu) {
        menu.before(intro);
        const hero = document.querySelector('.is-rankings .inner-head > div');
        if (hero) hero.append(make('p','rankings-hero-copy','Cada batalla deja una marca. Estos son los nombres que lideran MU PANIC.'));
        menu.setAttribute('aria-label','Categorías de ranking');
        menu.querySelectorAll('a').forEach(link => {
            const key = new URL(link.href,location.href).pathname.split('/').filter(Boolean).pop();
            if (labels[key]) link.textContent = labels[key];
            const icon = glyph(({level:'sword',master:'magic',guilds:'crest',killers:'sword',resets:'gem',grandresets:'crown'})[key] || 'crest');link.prepend(icon);
            if (link.classList.contains('active')) link.setAttribute('aria-current','page');
        });
    }
    const table = surface.querySelector('.rankings-table');
    if (!table || !table.rows.length) return;
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
    const metricGroups = [['grand resets','master resets'],['resets'],['pk count','kills','asesinatos'],['guild score','score','puntos'],['master level','nivel master'],['level','nivel']];
    let scoreIndex = -1;
    for (const group of metricGroups) { scoreIndex = columns.findIndex(x => group.includes(x)); if (scoreIndex >= 0) break; }
    if (nameIndex >= 0 && scoreIndex >= 0 && rows.length) {
        const podium = make('div','rankings-podium'); podium.setAttribute('aria-label','Primeros puestos del ranking completo');
        rows.slice(0,3).forEach(row => {
            const place = row.querySelector('.rankings-table-place');
            if (!place) return;
            const card = make('article','rankings-leader');
            card.dataset.place = place.textContent.trim();
            const emblem = make('div','rankings-leader-emblem');emblem.append(glyph(isGuild ? 'crest' : classGlyph(row.dataset.classId)));card.append(emblem);
            card.append(make('span','rankings-leader-position',`Puesto ${place.textContent.trim()}`));
            const original = row.cells[nameIndex].querySelector('a');
            const name = original ? original.cloneNode(true) : make('strong','',row.cells[nameIndex].textContent.trim());
            name.className = 'rankings-leader-name'; card.append(name);
            card.append(make('span','rankings-leader-class',classIndex >= 0 ? row.cells[classIndex].textContent.trim() : 'Guild de MU PANIC'));
            card.append(make('span','rankings-leader-score',`${header.cells[scoreIndex].textContent}: ${row.cells[scoreIndex].textContent.trim()}`));
            const inspect = make('button','rankings-inspect','Ver en la tabla ↓');inspect.type='button';inspect.addEventListener('click',()=>focusRow(row));card.append(inspect);
            podium.append(card);
        });
        if (podium.children.length) {
            const stage = make('section','rankings-stage');stage.setAttribute('aria-label','Líderes de la categoría');
            const heading = make('div','rankings-stage-heading');
            const copy = make('div');copy.append(make('span','rankings-stage-overline','EL SALÓN DE LA GLORIA'),make('h2','','Los nombres de la cima'));
            heading.append(copy,make('span','rankings-stage-note','TOP 3 · RANKING COMPLETO'));stage.append(heading,podium);(menu || table).after(stage);
        }
    }
    let classIds = null, query = '', page = 0;
    const pageSize = 20;
    const tools = make('div','rankings-tools');
    const label = make('label','rankings-search',isGuild ? 'Buscar guild o líder' : 'Buscar personaje');
    const input = make('input'); input.type = 'search'; input.placeholder = 'Escribí un nombre…'; input.autocomplete = 'off'; label.append(input);
    const status = make('p','rankings-result-status');status.setAttribute('role','status'); tools.append(label,status);
    table.before(tools);
    const wrap = make('div','rankings-table-scroll'); wrap.tabIndex = 0; wrap.setAttribute('role','region');wrap.setAttribute('aria-label','Tabla de posiciones; desplazá horizontalmente para ver todas las columnas');table.before(wrap);wrap.append(table);
    const empty = make('p','rankings-empty','No encontramos resultados con esos filtros. Probá otro nombre o elegí Todas.');empty.hidden=true;wrap.after(empty);
    const nav = make('div','rankings-pagination');const prev=make('button','','← Anterior'),next=make('button','','Siguiente →'),counter=make('span');prev.type=next.type='button';nav.append(prev,counter,next);empty.after(nav);
    const normalize = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
    function render() {
        const matching = rows.filter(row => (!classIds || classIds.includes(Number(row.dataset.classId))) && normalize(nameIndex >= 0 ? row.cells[nameIndex].textContent + (isGuild ? row.textContent : '') : row.textContent).includes(query));
        const pages = Math.max(1,Math.ceil(matching.length/pageSize));page=Math.min(page,pages-1);
        const visible = new Set(matching.slice(page*pageSize,(page+1)*pageSize));rows.forEach(row => {row.hidden=!visible.has(row);});
        status.textContent = matching.length ? `${page*pageSize+1}–${Math.min((page+1)*pageSize,matching.length)} de ${matching.length} resultados · Se conserva el puesto original` : 'Sin resultados';
        empty.hidden=!!matching.length;nav.hidden=matching.length<=pageSize;prev.disabled=!page;next.disabled=page>=pages-1;counter.textContent=`Página ${page+1} de ${pages}`;
    }
    function focusRow(row) {
        classIds=null;query='';input.value='';page=Math.floor(rows.indexOf(row)/pageSize);
        surface.querySelectorAll('.rankings-class-button').forEach((b,i)=>b.setAttribute('aria-pressed',String(i===0)));render();
        rows.forEach(r=>r.classList.remove('rankings-row-focus'));row.classList.add('rankings-row-focus');row.tabIndex=-1;
        row.scrollIntoView({behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'center'});row.focus({preventScroll:true});
    }
    input.addEventListener('input',()=>{query=normalize(input.value.trim());page=0;render();});
    prev.addEventListener('click',()=>{page--;render();});next.addEventListener('click',()=>{page++;render();});
    const filter = surface.querySelector('.rankings-class-filter');
    if (filter) filter.querySelectorAll('a').forEach(link => {
        const action = link.getAttribute('onclick') || '';
        const match = action.match(/^\s*rankingsFilterByClass\(([\d,\s]+)\)\s*;?\s*$/);
        const all = /^\s*rankingsFilterRemove\(\)\s*;?\s*$/.test(action);
        if (!match && !all) return;
        const text = link.textContent.trim();const button=make('button','rankings-class-button',classLabels[text.toLowerCase()] || text);button.type='button';button.setAttribute('aria-pressed',String(all));button.prepend(glyph(all?'crest':classGlyph(match[1].split(',')[0])));
        button.addEventListener('click',()=>{classIds=all?null:match[1].split(',').map(Number);page=0;filter.querySelectorAll('button').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));render();});link.replaceWith(button);
    });
    render();
})();
