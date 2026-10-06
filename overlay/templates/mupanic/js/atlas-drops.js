/* Visual drop explorer. Every percentage stays attached to its original server rule. */
(function(root){
 'use strict';
 function create(wiki,data){
  var section=wiki.querySelector('#drops'),query=section.querySelector('[data-drop-filter]'),mapSelect=section.querySelector('[data-drop-map]'),monsterSelect=section.querySelector('[data-drop-monster]'),account=section.querySelector('[data-drop-account]');
  var cards=Array.from(section.querySelectorAll('[data-drop-card]')),items=Array.from(section.querySelectorAll('[data-drop-item]')),rows=Array.from(section.querySelectorAll('[data-drop-row]')),catalogue=section.querySelector('[data-drop-catalogue]'),category='joyas',current=null;
  var compatible=root.PanicAtlasSearch.compatible,eligibility=new WeakMap();
  function normalize(s){return String(s||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim();}
  function selectedMaps(){return data.maps.filter(function(m){return !mapSelect.value||String(m.id)===mapSelect.value;});}
  function eligibleMobs(drop,map){var cached=eligibility.get(drop);if(!cached){cached=new Map();eligibility.set(drop,cached);}if(!cached.has(map.id))cached.set(map.id,Array.from(new Map(map.monsters.filter(function(m){return compatible(drop,map,m);}).map(function(m){return [m.id+':'+m.level,m];})).values()));return cached.get(map.id).filter(function(m){return !monsterSelect.value||String(m.id)===monsterSelect.value;});}
  function routeMaps(drop){return selectedMaps().filter(function(m){return eligibleMobs(drop,m).length;});}
  function closeRoutes(){section.querySelectorAll('[data-drop-route-panel]').forEach(function(p){p.hidden=true;});section.querySelectorAll('[data-drop-routes]').forEach(function(b){b.setAttribute('aria-expanded','false');});}
  function updateMonsters(){
   var selected=monsterSelect.value,monsters=Array.from(new Map(selectedMaps().flatMap(function(m){return m.monsters;}).map(function(m){return [m.id,m];})).values()).sort(function(a,b){return a.name.localeCompare(b.name);});
   var nameCount={};monsters.forEach(function(m){nameCount[m.name]=(nameCount[m.name]||0)+1;});
   monsterSelect.replaceChildren(new Option('Todos los monstruos',''));
   monsters.forEach(function(m){monsterSelect.add(new Option(m.name+(nameCount[m.name]>1?' · Lv. '+Array.from(new Set(selectedMaps().flatMap(function(map){return map.monsters.filter(function(mob){return mob.id===m.id;}).map(function(mob){return mob.level;});}))).sort(function(a,b){return a-b;}).join('/'): ''),String(m.id)));});monsterSelect.value=selected;
  }
  function view(){
   catalogue.hidden=!!current;
   items.forEach(function(item){item.hidden=item.getAttribute('data-drop-item')!==current;});
   section.querySelector('[data-drop-empty]').hidden=!!current||cards.some(function(c){return !c.hidden;});
  }
  function filter(){
   var words=normalize(query.value).split(/\s+/).filter(Boolean),visibleRules=0,visibleGroups=0;
   closeRoutes();
   rows.forEach(function(row){
    var drop=data.drops[Number(row.getAttribute('data-drop-row'))],item=row.closest('[data-drop-item]'),card=cards.find(function(c){return c.getAttribute('data-drop-card')===item.getAttribute('data-drop-item');}),variant=item.querySelector('[data-drop-variant]');
    if(category&&card.getAttribute('data-drop-kind')!==category){row.hidden=true;return;}
    var text=normalize(drop.name+' '+card.querySelector('h3').textContent),maps=routeMaps(drop);
    row.hidden=!words.every(function(w){return text.includes(w);})||((mapSelect.value||monsterSelect.value)&&!maps.length)||(current===item.getAttribute('data-drop-item')&&variant&&variant.value!==''&&String(drop.variant)!==variant.value);
    row.querySelector('[data-drop-rate]').textContent=Number(drop.rates[Number(account.value)]).toLocaleString('es-AR',{maximumFractionDigits:6})+'%';
    var specific=row.querySelector('[data-drop-monster-name]');if(specific){var m=data.maps.flatMap(function(m){return m.monsters;}).find(function(m){return m.id===drop.monster;});specific.textContent=m?m.name:'ID '+drop.monster;}
    var count=row.querySelector('[data-drop-compatible-count]'),button=row.querySelector('[data-drop-routes]');button.disabled=!maps.length;
    count.textContent=maps.length?(maps.length===1?maps[0].name:maps.length+' mapas')+' con población fija compatible':'Sin población fija compatible identificada';
    if(!row.hidden)visibleRules++;
   });
   cards.forEach(function(card){var item=items.find(function(p){return p.getAttribute('data-drop-item')===card.getAttribute('data-drop-card');});card.hidden=!Array.from(item.querySelectorAll('[data-drop-row]')).some(function(r){return !r.hidden;});if(!card.hidden)visibleGroups++;});
   if(current&&cards.find(function(c){return c.getAttribute('data-drop-card')===current;}).hidden)current=null;
   section.querySelectorAll('[data-account-name]').forEach(function(n){n.textContent=data.accounts[Number(account.value)].name;});
   section.querySelectorAll('[data-drop-category]').forEach(function(b){b.setAttribute('aria-pressed',String(category===b.getAttribute('data-drop-category')));});
   section.querySelector('[data-drop-status]').textContent=visibleGroups+' '+(visibleGroups===1?'objeto o familia':'objetos y familias')+' · '+visibleRules+' '+(visibleRules===1?'regla':'reglas')+(mapSelect.value?' · '+selectedMaps()[0].name:'');
   view();
  }
  function reset(options){options=options||{};current=null;category=options.query?null:'joyas';query.value=options.query||'';mapSelect.value=options.map||'';monsterSelect.value='';items.forEach(function(item){var v=item.querySelector('[data-drop-variant]');if(v)v.value='';});updateMonsters();filter();}
  function open(key,options){options=options||{};var card=cards.find(function(c){return c.getAttribute('data-drop-card')===key;}),item=items.find(function(i){return i.getAttribute('data-drop-item')===key;});if(!item)return;
   if(card.hidden)reset();category=card.getAttribute('data-drop-kind');current=key;filter();view();item.querySelectorAll('img').forEach(function(img){img.loading='eager';});if(options.focus!==false)item.querySelector('h3').focus({preventScroll:true});
  }
  function back(options){options=options||{};var previous=current;current=null;items.forEach(function(item){var v=item.querySelector('[data-drop-variant]');if(v)v.value='';});filter();if(previous&&options.focus!==false){var c=cards.find(function(c){return c.getAttribute('data-drop-card')===previous;});if(c&&!c.hidden)c.focus({preventScroll:true});}}
  function browse(){current=null;items.forEach(function(item){var v=item.querySelector('[data-drop-variant]');if(v)v.value='';});if(location.hash.startsWith('#drop-'))history.replaceState(null,'','#drops');}
  function node(tag,text,className){var el=document.createElement(tag);if(text!==undefined)el.textContent=text;if(className)el.className=className;return el;}
  function link(text,hash){var a=node('a',text);a.href=hash;return a;}
  function renderRoutes(button){
   var row=button.closest('[data-drop-row]'),panel=row.querySelector('[data-drop-route-panel]'),wasOpen=!panel.hidden;closeRoutes();if(wasOpen)return;
   var drop=data.drops[Number(row.getAttribute('data-drop-row'))],maps=routeMaps(drop);if(!maps.length)return;
   panel.replaceChildren();var top=node('div',undefined,'atlas-drop-route-toolbar'),label=node('label','Explorá un mapa compatible'),select=node('select');select.setAttribute('data-drop-route-map','');maps.forEach(function(m){select.add(new Option(m.name,String(m.id)));});label.appendChild(select);top.appendChild(label);
   var close=node('button','Cerrar ×');close.type='button';close.addEventListener('click',function(){panel.hidden=true;button.setAttribute('aria-expanded','false');button.focus();});top.appendChild(close);panel.appendChild(top);
   var list=node('div',undefined,'atlas-drop-mob-grid'),pager=node('div',undefined,'atlas-drop-pager');panel.append(list,pager);var page=0;
   function draw(){
    var map=maps.find(function(m){return String(m.id)===select.value;}),mobs=eligibleMobs(drop,map);list.replaceChildren();pager.replaceChildren();
    mobs.slice(page*6,page*6+6).forEach(function(mob){
     var card=node('article',undefined,'atlas-drop-mob'),head=node('div',undefined,'atlas-drop-mob-head'),art=data.portraits&&data.portraits[String(mob.id)];
     if(art){var image=node('img');var script=document.querySelector('script[src*="atlas-search.js"]');image.src=(script?script.src.split('/js/atlas-search.js')[0]+'/':'/templates/mupanic/')+art.file;image.width=44;image.height=52;image.alt='';head.appendChild(image);}
     var name=node('div');name.append(node('strong',mob.name),node('small','Lv. '+mob.level));head.appendChild(name);card.append(head,link('Ver mob →','#mob-'+map.id+'-'+mob.id));
     var spots=map.spots.map(function(s,i){return {spot:s,index:i};}).filter(function(s){return s.spot.monsters.some(function(m){return m.id===mob.id&&compatible(drop,map,m);});});
     var positions=node('div',undefined,'atlas-drop-mob-spots');if(spots.length){spots.slice(0,2).forEach(function(s){positions.appendChild(link('Spot '+(s.index+1)+' · '+s.spot.x+' / '+s.spot.y,'#spot-'+map.id+'-'+s.index));});if(spots.length>2)positions.appendChild(link('Ver '+spots.length+' spots ↗','#mapa-'+map.id));}else positions.appendChild(node('small','Sin spot fijo identificado'));card.appendChild(positions);list.appendChild(card);
    });
    var prev=node('button','← Anterior'),next=node('button','Siguiente →'),status=node('span',(page*6+1)+'–'+Math.min(page*6+6,mobs.length)+' de '+mobs.length+' mobs');prev.type=next.type='button';prev.disabled=page===0;next.disabled=(page+1)*6>=mobs.length;prev.addEventListener('click',function(){page--;draw();var target=pager.querySelector('button');(target.disabled?pager.querySelector('button:last-child'):target).focus();});next.addEventListener('click',function(){page++;draw();var target=pager.querySelector('button:last-child');(target.disabled?pager.querySelector('button'):target).focus();});pager.append(prev,status,next);
   }
   select.addEventListener('change',function(){page=0;draw();});draw();panel.hidden=false;button.setAttribute('aria-expanded','true');panel.focus({preventScroll:true});
  }
  query.addEventListener('input',function(){browse();category=query.value.trim()?null:'joyas';filter();});mapSelect.addEventListener('change',function(){monsterSelect.value='';updateMonsters();filter();});monsterSelect.addEventListener('change',filter);account.addEventListener('change',filter);
  section.querySelectorAll('[data-drop-category]').forEach(function(b){b.addEventListener('click',function(){browse();category=b.getAttribute('data-drop-category');filter();});});
  section.querySelectorAll('[data-drop-reset]').forEach(function(b){b.addEventListener('click',function(){reset();history.replaceState(null,'','#drops');query.focus();});});
  section.querySelectorAll('[data-drop-variant]').forEach(function(v){v.addEventListener('change',filter);});section.querySelectorAll('[data-drop-routes]').forEach(function(b){b.addEventListener('click',function(){renderRoutes(b);});});
  section.querySelectorAll('[data-drop-back]').forEach(function(a){a.addEventListener('click',function(){back();});});
  updateMonsters();filter();return {filter:filter,updateMonsters:updateMonsters,reset:reset,open:open,back:back};
 }
 root.PanicAtlasDrops={create:create};
})(window);
