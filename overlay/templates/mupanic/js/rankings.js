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
    const labels = {level:'Nivel',masterlevel:'Nivel Master',master:'Nivel Master',killers:'Asesinatos',guilds:'Guilds',online:'Tiempo conectado',votes:'Votos',resets:'Resets',reset:'Resets',grandresets:'Master Resets',gens:'Gens'};
    const translations = {country:'País',class:'Clase',character:'Personaje',level:'Nivel',location:'Mapa',guild:'Guild',logo:'Emblema',master:'Líder',score:'Puntos',kills:'Asesinatos','master level':'Nivel Master',resets:'Resets','grand resets':'Master Resets','guild name':'Guild','guild master':'Líder','guild score':'Puntos','pk count':'Asesinatos','pk level':'Estado PK','master level':'Nivel Master'};
    const classLabels = {all:'Todas',wizards:'Magos',knights:'Guerreros',elves:'Elfas',gladiators:'Gladiadores',lords:'Dark Lords',summoners:'Summoners',fighters:'Rage Fighters',lancers:'Lancers','rune wizards':'Rune Wizards',slayers:'Slayers'};
    const intro = make('p','rankings-intro','Conocé a los líderes de MU PANIC. Elegí una categoría y buscá un personaje o una guild para ver su posición.');
    const menu = surface.querySelector('.rankings_menu');
    if (menu) {
        menu.before(intro);
        menu.setAttribute('aria-label','Categorías de ranking');
        menu.querySelectorAll('a').forEach(link => {
            const key = new URL(link.href,location.href).pathname.split('/').filter(Boolean).pop();
            if (labels[key]) link.textContent = labels[key];
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
            card.append(make('span','rankings-leader-position',`Puesto ${place.textContent.trim()}`));
            const original = row.cells[nameIndex].querySelector('a');
            const name = original ? original.cloneNode(true) : make('strong','',row.cells[nameIndex].textContent.trim());
            name.className = 'rankings-leader-name'; card.append(name);
            card.append(make('span','rankings-leader-class',classIndex >= 0 ? row.cells[classIndex].textContent.trim() : 'Guild de MU PANIC'));
            card.append(make('span','rankings-leader-score',`${header.cells[scoreIndex].textContent}: ${row.cells[scoreIndex].textContent.trim()}`));
            podium.append(card);
        });
        if (podium.children.length) (menu || table).after(podium);
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
    input.addEventListener('input',()=>{query=normalize(input.value.trim());page=0;render();});
    prev.addEventListener('click',()=>{page--;render();});next.addEventListener('click',()=>{page++;render();});
    const filter = surface.querySelector('.rankings-class-filter');
    if (filter) filter.querySelectorAll('a').forEach(link => {
        const action = link.getAttribute('onclick') || '';
        const match = action.match(/^\s*rankingsFilterByClass\(([\d,\s]+)\)\s*;?\s*$/);
        const all = /^\s*rankingsFilterRemove\(\)\s*;?\s*$/.test(action);
        if (!match && !all) return;
        const text = link.textContent.trim();const button=make('button','rankings-class-button',classLabels[text.toLowerCase()] || text);button.type='button';button.setAttribute('aria-pressed',String(all));
        button.addEventListener('click',()=>{classIds=all?null:match[1].split(',').map(Number);page=0;filter.querySelectorAll('button').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));render();});link.replaceWith(button);
    });
    render();
})();
