(() => {
    'use strict';
    const surface = document.querySelector('.is-profile .module-surface');
    if (!surface) return;
    const make = (tag, cls, text) => {
        const el = document.createElement(tag); if (cls) el.className = cls;
        if (text !== undefined) el.textContent = text; return el;
    };
    const back = document.querySelector('.profile-back');
    if (back) {
        const fallback = new URL(back.href, location.href);
        const validRanking = value => {
            try { const url = new URL(value); return url.origin === location.origin && url.pathname.startsWith(fallback.pathname) ? url : null; } catch (_) { return null; }
        };
        let target = validRanking(document.referrer);
        try {
            const saved = JSON.parse(sessionStorage.getItem('mupanic:ranking-return'));
            if (saved && saved.to === location.href && Date.now()-saved.at >= 0 && Date.now()-saved.at < 86400000) target = validRanking(saved.from) || target;
        } catch (_) {}
        if (target) {
            back.href = target.href;
            back.addEventListener('click', event => {
                if (!event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey && document.referrer === target.href && history.length > 1) { event.preventDefault(); history.back(); }
            });
        }
        const bottom = make('div', 'profile-actions'); bottom.append(back.cloneNode(true)); surface.after(bottom);
    }
    const card = surface.querySelector('.profiles_player_card');
    surface.querySelectorAll('.profiles_guild_card table tr').forEach(row=>{
        if(row.cells.length !== 2) return;
        const labels={master:'Líder',score:'Puntos',members:'Miembros'};
        const key=row.cells[0].textContent.trim().toLowerCase();if(labels[key]) row.cells[0].textContent=labels[key];
    });
    const members=surface.querySelector('.guild_members');if(members && members.textContent.trim().toLowerCase()==='guild members') members.textContent='Miembros de la guild';
    if (!card) return;
    const source = card.querySelector('.profiles_player_table_info');
    if (!source) return;
    const name = card.querySelector('.cname'), className = card.querySelector('.cclass');
    if (name) { const title = document.querySelector('.inner-head h1'); if (title) title.textContent = name.textContent.trim(); }
    const identity = card.querySelector('.profiles_player_table:not(.profiles_player_table_info)');
    if (identity) { const crest = make('div','profile-crest','MU'); crest.setAttribute('aria-hidden','true'); identity.before(crest); }
    const labels = {
        level:'Nivel',nivel:'Nivel','master level':'Nivel Master','nivel master':'Nivel Master',resets:'Resets','grand resets':'Master Resets','master resets':'Master Resets',
        strength:'Fuerza',fuerza:'Fuerza',agility:'Agilidad',agilidad:'Agilidad',dexterity:'Agilidad',vitality:'Vitalidad',vitalidad:'Vitalidad',energy:'Energía','energía':'Energía',leadership:'Liderazgo',command:'Liderazgo',liderazgo:'Liderazgo',
        kills:'Asesinatos',asesinatos:'Asesinatos',guild:'Guild',status:'Estado',estado:'Estado',location:'Mapa',mapa:'Mapa',
    };
    const normalize = text => text.trim().toLowerCase().replace(/\s+/g,' ');
    const sections = [
        {title:'Progreso',copy:'Nivel y resets del personaje.',labels:['Nivel','Nivel Master','Resets','Master Resets'],cls:'profile-progress'},
        {title:'Atributos',copy:'Los valores actuales del personaje.',labels:['Fuerza','Agilidad','Vitalidad','Energía','Liderazgo'],cls:'profile-attributes'},
        {title:'En el continente',copy:'Actividad y pertenencia.',labels:[],cls:'profile-details'},
    ];
    const grid = make('div','profile-sections');
    sections.forEach(section => {
        section.el = make('section',section.cls);section.el.append(make('h2','',section.title),make('p','profile-section-copy',section.copy));section.list=make('dl','profile-values');section.el.append(section.list);
    });
    [...source.rows].forEach(row => {
        if (row.cells.length !== 2) return;
        const original = row.cells[0].textContent.trim();const label = labels[normalize(original)] || original;
        const section = sections.find(s=>s.labels.includes(label)) || sections[2];
        const item = make('div','profile-value');const term=make('dt','',label),value=make('dd');
        const cell=row.cells[1];while(cell.firstChild) value.append(cell.firstChild);
        if (label === 'Estado') {
            if (cell.classList.contains('isonline') || normalize(value.textContent)==='online') { value.textContent='En línea';item.classList.add('profile-online'); }
            else if (cell.classList.contains('isoffline') || normalize(value.textContent)==='offline') { value.textContent='Desconectado';item.classList.add('profile-offline'); }
        }
        item.append(term,value);section.list.append(item);
    });
    sections.forEach(section=>{ if(section.list.children.length) grid.append(section.el); });
    if (grid.children.length) { card.after(grid);source.remove();card.classList.add('profile-identity'); }
})();
