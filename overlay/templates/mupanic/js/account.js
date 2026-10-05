(() => {
    'use strict';
    const surface=document.querySelector('.is-account .module-surface');if(!surface)return;
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
        const row=table.rows[0];if(row)[...row.cells].forEach((cell,i)=>{
            if(cell.tagName==='TH')return;
            const th=make('th','',cell.textContent.trim() || (i===0?'Clase':'Acción'));th.scope='col';cell.replaceWith(th);
        });
        const wrap=make('div','account-table-scroll');wrap.tabIndex=0;wrap.setAttribute('role','region');wrap.setAttribute('aria-label','Personajes y acciones; desplazá horizontalmente para ver todas las columnas');table.before(wrap);wrap.append(table);
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
