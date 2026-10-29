(() => {
    'use strict';
    const surface=document.querySelector('.is-account .module-surface');if(!surface)return;
    const nav=document.querySelector('.account-nav');
    if(nav) {
        const menu=nav.querySelector('nav'),head=nav.querySelector('.account-nav-head');
        if(menu && head) { menu.id='account-navigation';nav.dataset.enhanced='true';
            const toggle=document.createElement('button');toggle.type='button';toggle.className='account-nav-toggle';toggle.textContent='Menú +';toggle.setAttribute('aria-controls',menu.id);toggle.setAttribute('aria-expanded','false');head.append(toggle);
            toggle.addEventListener('click',()=>{const open=nav.classList.toggle('is-menu-open');toggle.setAttribute('aria-expanded',String(open));toggle.textContent=open?'Cerrar −':'Menú +';});
        }
    }
    const script=document.currentScript;
    const assets=script ? new URL('../img/character-avatars/',script.src) : null;
    const avatars=new Set(['alc','avatar','cru','dk','dl','dw','elf','gc','gl','ik','lem','liw','mg','rf','rw','sl','sum']);
    const names={dk:'Dark Knight',dw:'Dark Wizard',elf:'Elfa',dl:'Dark Lord',mg:'Magic Gladiator',sum:'Summoner',rf:'Rage Fighter'};
    const make=(tag,cls,text)=>{const el=document.createElement(tag);if(cls)el.className=cls;if(text!==undefined)el.textContent=text;return el;};
    surface.querySelectorAll('img').forEach(img=>{
        let file='';try{file=new URL(img.src,location.href).pathname.split('/').pop().toLowerCase();}catch(_){return;}
        if(img.classList.contains('online-status-indicator')) {
            const online=/^online[.-]/.test(file),offline=/^offline[.-]/.test(file);
            if(online || offline) img.replaceWith(make('span','account-presence '+(online?'is-online':'is-offline'),online?'En línea':'Desconectado'));
            return;
        }
        const key=file.replace(/\.(?:jpg|png|webp)$/,'');
        if(assets && avatars.has(key)) {
            img.src=new URL(key+'.jpg',assets).href;img.classList.add('account-class-avatar');
            if(!img.alt)img.alt=names[key] || 'Retrato de personaje';
            img.addEventListener('error',()=>{img.replaceWith(make('span','account-class-fallback',img.alt));},{once:true});
        }
    });
    surface.querySelectorAll('.myaccount-character-name').forEach(name=>{
        const card=name.parentElement;if(!card)return;card.classList.add('account-character');card.parentElement.classList.add('account-characters-grid');
        card.querySelectorAll('a').forEach(a=>a.removeAttribute('target'));
        const level=card.querySelector('.myaccount-character-block-level');if(level){const value=level.textContent.trim();level.textContent='Nivel total · '+value;}
        const location=card.querySelector('.myaccount-character-block-location');if(location)location.setAttribute('aria-label','Mapa y coordenadas del personaje');
    });
    surface.querySelectorAll('.general-table-ui').forEach(table=>{
        const row=table.rows[0];if(row)row.classList.add('account-table-header');if(row)[...row.cells].forEach((cell,i)=>{
            if(cell.tagName==='TH')return;
            const th=make('th','',cell.textContent.trim() || (i===0?'Clase':'Acción'));th.scope='col';cell.replaceWith(th);
        });
        const headings=row?[...row.cells].map(cell=>cell.textContent.trim()):[];
        [...table.rows].slice(1).forEach(bodyRow=>[...bodyRow.cells].forEach((cell,i)=>{cell.dataset.label=headings[i] || 'Acción';}));
        const wrap=make('div','account-table-scroll');wrap.tabIndex=0;wrap.setAttribute('role','region');wrap.setAttribute('aria-label','Personajes, datos y acciones');table.before(wrap);wrap.append(table);
    });
    surface.querySelectorAll('.module-requirements').forEach(block=>{
        if(block.textContent.trim())block.prepend(make('h3','','Requisitos y condiciones'));
    });
    // Accessible labels for the native forms, preserving every submitted name,
    // value, bound, hidden token and backend action.
    const usedIds=new Set();
    surface.querySelectorAll('input:not([type=hidden]),select,textarea').forEach((input,i)=>{
        if(!input.id || usedIds.has(input.id))input.id='panic-account-field-'+i;
        usedIds.add(input.id);
        const group=input.closest('.form-group');const label=group && group.querySelector('label');if(label)label.htmlFor=input.id;
    });
    surface.querySelectorAll('input[type=password]').forEach(input=>{input.autocomplete=/current/.test(input.name)?'current-password':'new-password';});
    const email=surface.querySelector('[name="webengineEmail_newemail"]');if(email){email.autocomplete='email';const button=email.form && email.form.querySelector('[name="webengineEmail_submit"]');if(button)button.textContent='Guardar correo';}
})();

/* Mi cuenta (estilo nuevo): barra azul que se desliza entre las filas de la lista, números que cuentan al aparecer
   y sellos (emblema VIP) que caen al verse. Todo solo con transform/opacidad; nada cambia de alto. */
(() => {
    'use strict';
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const nav = document.querySelector('.cta-nav nav');
    if (nav) {
        const active = nav.querySelector('.cta-nav-row[aria-current=page]');
        if (active) {
            const bar = document.createElement('i'); bar.className = 'cta-nav-bar'; bar.setAttribute('aria-hidden', 'true');
            nav.prepend(bar); nav.closest('.cta-nav').dataset.bar = '1';
            const place = (row, instant) => {
                const top = row.getBoundingClientRect().top - nav.getBoundingClientRect().top;
                if (instant) bar.style.transition = 'none';
                bar.style.height = row.offsetHeight + 'px'; bar.style.setProperty('--bar-y', top + 'px');
                if (instant) { void bar.offsetWidth; bar.style.transition = ''; }
            };
            // Entra desde arriba hasta la fila actual
            place(nav.querySelector('.cta-nav-row'), true);
            requestAnimationFrame(() => requestAnimationFrame(() => place(active, reduce)));
            nav.querySelectorAll('.cta-nav-row').forEach(row => {
                row.addEventListener('pointerenter', () => place(row, reduce));
                row.addEventListener('focus', () => place(row, reduce));
            });
            nav.addEventListener('pointerleave', () => place(active, reduce));
            nav.addEventListener('focusout', event => { if (!nav.contains(event.relatedTarget)) place(active, reduce); });
            window.addEventListener('resize', () => place(active, true));
        }
    }
    const format = new Intl.NumberFormat('es-AR', {maximumFractionDigits: 0});
    const counters = Array.from(document.querySelectorAll('.cta [data-count]'));
    const stamps = Array.from(document.querySelectorAll('.cta [data-stamp]'));
    if (reduce || !('IntersectionObserver' in window)) return;
    const count = el => {
        const target = Number(el.dataset.count); if (!Number.isFinite(target) || target <= 0) return;
        const start = performance.now(), duration = 900;
        const step = now => { const t = Math.min(1, (now - start) / duration); el.textContent = format.format(Math.round(target * (1 - Math.pow(1 - t, 3)))); if (t < 1) requestAnimationFrame(step); };
        el.textContent = '0'; requestAnimationFrame(step);
    };
    stamps.forEach(el => el.classList.add('cta-stamp-pre'));
    const io = new IntersectionObserver(entries => entries.forEach(entry => {
        if (!entry.isIntersecting) return; io.unobserve(entry.target);
        if (entry.target.hasAttribute('data-count')) count(entry.target);
        if (entry.target.hasAttribute('data-stamp')) { entry.target.classList.remove('cta-stamp-pre'); entry.target.classList.add('cta-stamp'); }
    }), {threshold: .4});
    counters.concat(stamps).forEach(el => io.observe(el));
})();
