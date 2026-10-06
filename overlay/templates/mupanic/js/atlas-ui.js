/* Compact event dossiers; original articles remain available without JavaScript. */
(function () {
  'use strict';
  var wiki = document.querySelector('[data-wiki]');
  var dialog = document.querySelector('[data-atlas-event-dialog]');
  if (!wiki || !dialog || typeof dialog.showModal !== 'function') return;
  var content = dialog.querySelector('[data-atlas-event-content]');
  var closeButton = dialog.querySelector('[data-atlas-event-close]');
  var trigger = null, backdropPress = false;
  wiki.classList.add('atlas-events-enhanced');
  function openEvent(id, button) {
    var source = wiki.querySelector('[data-atlas-event="' + id + '"]');
    if (!source || dialog.open) return;
    trigger = button || wiki.querySelector('[data-atlas-event-open="' + id + '"]');
    var dossier = source.cloneNode(true); dossier.removeAttribute('id'); dossier.removeAttribute('data-atlas-event'); dossier.hidden = false;
    var title = dossier.querySelector('.atlas-event-copy h3'); title.id = 'atlas-event-dialog-title';
    var intro = dossier.querySelector('.atlas-event-copy>p'); intro.id = 'atlas-event-dialog-intro';
    content.replaceChildren(dossier);
    dialog.setAttribute('aria-labelledby', title.id); dialog.setAttribute('aria-describedby', intro.id);
    document.documentElement.classList.add('atlas-event-open');
    dialog.showModal(); dialog.scrollTop = 0; closeButton.focus({preventScroll: true});
  }
  function restore() {
    document.documentElement.classList.remove('atlas-event-open');
    content.replaceChildren();
    if (trigger && document.contains(trigger)) trigger.focus({preventScroll: true});
    trigger = null;
  }
  function closeEvent() { if (dialog.open) dialog.close(); restore(); }
  closeButton.addEventListener('click', closeEvent);
  dialog.addEventListener('cancel', function (event) { event.preventDefault(); closeEvent(); });
  dialog.addEventListener('close', function () { if (!dialog.open) restore(); });
  function outside(event) {
    var bounds = dialog.getBoundingClientRect();
    return event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom;
  }
  dialog.addEventListener('pointerdown', function (event) { backdropPress = outside(event); });
  dialog.addEventListener('click', function (event) {
    if (backdropPress && outside(event)) closeEvent();
    backdropPress = false;
    var link=event.target.closest('a[href]');
    if(link){event.preventDefault();var href=link.getAttribute('href');closeEvent();location.hash=href;}
  });
  dialog.addEventListener('keydown', function (event) {
    if (event.key !== 'Tab') return;
    var controls = Array.from(dialog.querySelectorAll('button,a[href]')).filter(function (item) { return item.getClientRects().length; });
    var first = controls[0], last = controls[controls.length-1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
  });
  wiki.addEventListener('click', function (event) {
    var button=event.target.closest('[data-atlas-event-open]');
    if(button){event.preventDefault();openEvent(button.getAttribute('data-atlas-event-open'),button);}
    var filter=event.target.closest('[data-event-group]');
    if(filter){group=filter.dataset.eventGroup;filterEvents();}
  });
  wiki.addEventListener('input',function(event){if(event.target.matches('[data-event-search]'))filterEvents();});
  var group='all';
  function filterEvents(){
    var section=wiki.querySelector('#eventos'),query=section.querySelector('[data-event-search]').value.toLocaleLowerCase('es'),count=0;
    section.querySelectorAll('[data-event-kind]').forEach(function(card){card.hidden=!((group==='all'||card.dataset.eventKind===group)&&card.dataset.eventName.toLocaleLowerCase('es').includes(query));if(!card.hidden)count++;});
    section.querySelectorAll('[data-event-group]').forEach(function(button){button.setAttribute('aria-pressed',String(button.dataset.eventGroup===group));});
    section.querySelector('[data-event-empty]').hidden=count>0;
  }
  function nextOccurrence(rows, now){
    var argentina=new Date(now.getTime()-3*3600000),found=null;
    for(var day=0;day<370;day++){
      var civil=new Date(Date.UTC(argentina.getUTCFullYear(),argentina.getUTCMonth(),argentina.getUTCDate()+day));
      rows.forEach(function(r){
        if((r[0]>=0&&r[0]!==civil.getUTCFullYear())||(r[1]>=0&&r[1]!==civil.getUTCMonth()+1)||(r[2]>=0&&r[2]!==civil.getUTCDate())||(r[3]>=0&&r[3]!==civil.getUTCDay()+1))return;
        var hours=r[4]<0?Array.from({length:24},function(_,i){return i;}):[r[4]];
        var minutes=r[5]<0?Array.from({length:60},function(_,i){return i;}):[r[5]];
        hours.forEach(function(h){minutes.forEach(function(m){var date=new Date(civil.getTime()+(h+3)*3600000+m*60000+Math.max(0,r[6])*1000);if(date>now&&(!found||date<found))found=date;});});
      });
      if(found)break;
    }
    return found;
  }
  function updateAgenda(){
    var section=wiki.querySelector('#eventos'),data=JSON.parse(section.querySelector('[data-event-data]').textContent),now=new Date();
    var entries=data.events.filter(function(e){return e.enabled&&e.schedule.length;}).map(function(e){return {event:e,date:nextOccurrence(e.schedule,now)};}).filter(function(e){return e.date;}).sort(function(a,b){return a.date-b.date;}).slice(0,6);
    var holder=section.querySelector('[data-event-upcoming]');holder.replaceChildren();
    entries.forEach(function(entry){var row=document.createElement('a');row.className='atlas-next-event';row.href='#evento-'+entry.event.id;row.dataset.atlasEventOpen=entry.event.id;
      var label=document.createElement('span');label.textContent=entry.event.name;var time=document.createElement('time');time.dateTime=entry.date.toISOString();time.textContent=entry.date.toLocaleString('es-AR',{timeZone:data.timezone,weekday:'short',hour:'2-digit',minute:'2-digit',hourCycle:'h23'});row.append(label,time);holder.append(row);});
    if(!entries.length){var empty=document.createElement('p');empty.textContent='No hay eventos programados en la configuración publicada.';holder.append(empty);}
    section.querySelector('[data-event-published]').textContent=new Date(data.generatedAt).toLocaleString('es-AR',{timeZone:data.timezone,dateStyle:'short',timeStyle:'short',hourCycle:'h23'});
  }
  wiki.atlasNextOccurrence=nextOccurrence;
  updateAgenda();window.setInterval(updateAgenda,60000);
  var endpoint=new URL('../api/atlas-events.php',document.currentScript.src).href,refreshing=false;
  async function refreshEvents(){
    if(refreshing||document.hidden||dialog.open)return;refreshing=true;
    try{var response=await fetch(endpoint,{cache:'no-store'});if(!response.ok)return;var result=await response.json(),section=wiki.querySelector('#eventos');
      if(result.version!==section.dataset.eventsVersion){var template=document.createElement('template');template.innerHTML=result.html;var replacement=template.content.querySelector('#eventos');if(!replacement)return;
        section.innerHTML=replacement.innerHTML;section.dataset.eventsVersion=result.version;group='all';updateAgenda();filterEvents();window.dispatchEvent(new Event('hashchange'));}
    }catch(error){/* Retain the last confirmed catalogue on network errors. */}finally{refreshing=false;}
  }
  window.setInterval(refreshEvents,60000);document.addEventListener('visibilitychange',function(){if(!document.hidden)refreshEvents();});
  function revealEvent() {
    var match = /^#evento-([a-z0-9-]+)$/.exec(location.hash);
    if (match) openEvent(match[1]);
    else if (dialog.open) closeEvent();
  }
  window.addEventListener('hashchange', revealEvent); revealEvent();
})();
