(function () {
  'use strict';
  var toggle = document.querySelector('.menu-toggle');
  var nav = document.querySelector('.main-nav');
  function closeMenu() {
    if (!nav || !toggle) return;
    nav.classList.remove('open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Abrir menú');
  }
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
    });
    nav.addEventListener('click', function (e) { if (e.target.closest('a')) closeMenu(); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && nav.classList.contains('open')) { closeMenu(); toggle.focus(); }
    });
    document.addEventListener('click', function (e) { if (!e.target.closest('.site-header')) closeMenu(); });
  }
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (!reduced.matches && 'IntersectionObserver' in window) {
    document.documentElement.classList.add('motion-ready');
    var revealObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) { entry.target.classList.add('is-visible'); revealObserver.unobserve(entry.target); }
      });
    }, { threshold: 0.08 });
    document.querySelectorAll('.reveal').forEach(function (el) { revealObserver.observe(el); });
  }
  var chapters = Array.prototype.slice.call(document.querySelectorAll('[data-chapter]'));
  var art = document.querySelectorAll('[data-chapter-art]');
  var word = document.querySelector('[data-journey-word]');
  var index = document.querySelector('[data-journey-index]');
  var progress = document.querySelector('[data-journey-progress]');
  var hero = document.querySelector('[data-scene]');
  var active = -1;
  var paused = false;
  var stage = document.querySelector('.journey-stage');
  var pending = false;
  function update() {
    pending = false;
    if (!chapters.length) return;
    var center = window.innerHeight * 0.55;
    var nearest = 0;
    var distance = Infinity;
    chapters.forEach(function (chapter, i) {
      var r = chapter.getBoundingClientRect();
      var d = Math.abs(r.top + r.height / 2 - center);
      if (d < distance) { distance = d; nearest = i; }
    });
    if (nearest === active) return;
    active = nearest;
    art.forEach(function (layer, i) { layer.classList.toggle('is-active', i === active); });
    if (stage) stage.setAttribute('data-ambience', String(active));
    if (word) word.textContent = chapters[active].getAttribute('data-word');
    if (index) index.textContent = '0' + (active + 1);
    if (progress) progress.style.setProperty('--journey-height', ((active + 1) / chapters.length * 100) + '%');
  }
  function schedule() { if (!pending) { pending = true; window.requestAnimationFrame(update); } }
  var sceneControl = document.querySelector('.scene-control');
  if (hero && sceneControl) {
    document.documentElement.classList.add('motion-controls');
    sceneControl.addEventListener('click', function () {
      paused = !paused;
      document.documentElement.classList.toggle('scene-paused', paused);
      sceneControl.setAttribute('aria-pressed', String(paused));
      sceneControl.setAttribute('aria-label', paused ? 'Reanudar animación' : 'Pausar animación');
      sceneControl.innerHTML = paused ? '▷ <span>Reanudar escena</span>' : 'Ⅱ <span>Pausar escena</span>';
      schedule();
    });

  }
  update();
  window.addEventListener('scroll', schedule, { passive: true });
  window.addEventListener('resize', schedule);
  if (reduced.addEventListener) reduced.addEventListener('change', function () {
    if (reduced.matches) document.documentElement.classList.remove('motion-ready');
    schedule();
  });
  // Read the same rendered home, so no new API, database query or server configuration is needed.
  var dossier = document.querySelector('[data-status-url]');
  var refresh = document.querySelector('.status-refresh');
  var statusMessage = document.querySelector('[data-status-message]');
  var statusBusy = false;
  var statusURL;
  if (dossier && refresh && statusMessage) {
    statusURL = new URL(dossier.getAttribute('data-status-url'), location.href);
    if (statusURL.origin === location.origin) {
      function updateStatus() {
        if (statusBusy || document.hidden || !navigator.onLine) return;
        statusBusy = true;refresh.disabled = true;
        var controller = new AbortController();
        var timeout = setTimeout(function () { controller.abort(); }, 12000);
        fetch(statusURL.href, { cache: 'no-store', credentials: 'same-origin', signal: controller.signal })
          .then(function (response) { if (!response.ok) throw new Error('Unavailable'); return response.text(); })
          .then(function (html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var count = doc.querySelector('[data-online-count]');
            var note = doc.querySelector('[data-online-note]');
            var stamp = doc.querySelector('[data-status-message]');
            if (!count || !note || !stamp) throw new Error('Missing data');
            var liveCount = document.querySelector('[data-online-count]');
            if (liveCount.textContent !== count.textContent) {
              liveCount.textContent = count.textContent;
              liveCount.classList.remove('count-changed');
              void liveCount.offsetWidth;liveCount.classList.add('count-changed');
            }
            document.querySelector('[data-online-note]').textContent = note.textContent;
            statusMessage.textContent = stamp.textContent + ' · Consultado ahora';
            statusMessage.setAttribute('data-cache-time', stamp.getAttribute('data-cache-time') || '');
            var roster = document.querySelector('[data-online-roster]');
            var newRoster = doc.querySelector('[data-online-roster]');
            if (roster && newRoster) roster.replaceChildren.apply(roster, Array.prototype.map.call(newRoster.childNodes, function (node) { return document.importNode(node, true); }));
          })
          .catch(function () { statusMessage.textContent = 'No se pudo actualizar. Se conserva el último registro.'; })
          .finally(function () { clearTimeout(timeout);statusBusy = false;refresh.disabled = false; });
      }
      refresh.addEventListener('click', updateStatus);
      setInterval(updateStatus, 60000);
      document.addEventListener('visibilitychange', function () { if (!document.hidden) updateStatus(); });
    }
  }
})();

(function () {
  'use strict';
  var wiki = document.querySelector('[data-wiki]');
  var source = document.getElementById('wiki-data');
  if (!wiki || !source) return;
  var data;
  try { data = JSON.parse(source.textContent); } catch (e) { return; }
  var panels = Array.from(wiki.querySelectorAll('.wiki-section'));
  var nav = Array.from(wiki.querySelectorAll('.wiki-nav nav a'));
  var search = wiki.querySelector('#wiki-search');
  var results = wiki.querySelector('.wiki-search-results');
  var searchStatus = wiki.querySelector('.wiki-search-status');
  var mapFilter = wiki.querySelector('[data-map-filter]');
  var dropFilter = wiki.querySelector('[data-drop-filter]');
  var dropMap = wiki.querySelector('[data-drop-map]');
  var dropAccount = wiki.querySelector('[data-drop-account]');
  function normalize(value) { return value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim(); }
  function clearSearch() { search.value = ''; results.replaceChildren(); results.hidden = true; searchStatus.textContent = ''; }
  function revealHash() {
    var id = location.hash.slice(1) || 'primeros-pasos';
    var target = document.getElementById(id);
    var panel = target && (target.classList.contains('wiki-section') ? target : target.closest('.wiki-section'));
    if (!panel) panel = panels[0];
    panels.forEach(function (item) { item.hidden = item !== panel; });
    nav.forEach(function (link) { if (link.hash === '#' + panel.id) link.setAttribute('aria-current', 'page'); else link.removeAttribute('aria-current'); });
    // A shared deep link must reveal a map/row even after local filtering.
    if (target) {
      target.hidden = false;
      var details = target.closest('details');
      if (details) details.open = true;
      if (target.matches('.wiki-map') || target.id.indexOf('spot-') === 0) { mapFilter.value = ''; filterMaps(); }
      if (target.hasAttribute('data-drop-row')) { dropFilter.value = ''; dropMap.value = ''; filterDrops(); }
      requestAnimationFrame(function () { target.scrollIntoView({block: 'start', behavior: 'instant'}); });
    }
  }
  wiki.classList.add('wiki-enhanced');
  window.addEventListener('hashchange', function () { clearSearch(); revealHash(); });
  nav.forEach(function (link) { link.addEventListener('click', function () { clearSearch(); if (location.hash === link.hash) revealHash(); }); });
  var searchable = [];
  panels.forEach(function (panel) { searchable.push({title: panel.querySelector('h2').textContent, context: 'Guía del servidor', text: normalize(panel.textContent), hash: '#' + panel.id}); });
  data.maps.forEach(function (map) { searchable.push({title: map.name, context: map.spots.length + ' spots · mapas y monstruos', text: normalize(map.name + ' ' + map.monsters.map(function(m) { return m.name; }).join(' ')), hash: '#mapa-' + map.id}); });
  data.drops.forEach(function (drop, i) { searchable.push({title: drop.name, context: 'Drop · monstruos Lv. ' + drop.min + '–' + drop.max, text: normalize(drop.name), hash: '#drop-' + i}); });
  search.addEventListener('input', function () {
    var query = normalize(search.value); results.replaceChildren();
    if (!query) { clearSearch(); return; }
    var matches = searchable.filter(function (item) { return item.text.includes(query); });
    results.hidden = matches.length === 0;
    searchStatus.textContent = matches.length ? matches.length + ' coincidencias' + (matches.length > 20 ? ' · primeras 20' : '') : 'Sin coincidencias. Probá otro nombre.';
    matches.slice(0, 20).forEach(function (item) {
      var link = document.createElement('a'); link.href = item.hash;
      link.appendChild(document.createTextNode(item.title));
      var context = document.createElement('small'); context.textContent = item.context; link.appendChild(context);
      link.addEventListener('click', function () { clearSearch(); if (location.hash === item.hash) revealHash(); });
      results.appendChild(link);
    });
  });
  function filterMaps() {
    var query = normalize(mapFilter.value), visible = 0;
    wiki.querySelectorAll('.wiki-map').forEach(function (map) {
      map.hidden = !normalize(map.textContent).includes(query); if (!map.hidden) visible++;
    });
    wiki.querySelector('[data-map-status]').textContent = visible + ' mapas' + (visible ? '' : ' · probá otro nombre');
  }
  function eligible(drop, map) {
    if (drop.map !== -1 && drop.map !== map.id) return false;
    return map.monsters.some(function (monster) { return monster.level >= drop.min && monster.level <= drop.max && (drop.monster === -1 || monster.id === drop.monster); });
  }
  function filterDrops() {
    var query = normalize(dropFilter.value), account = Number(dropAccount.value);
    var map = data.maps.find(function (item) { return String(item.id) === dropMap.value; });
    var visible = 0;
    wiki.querySelectorAll('[data-drop-row]').forEach(function (row) {
      var drop = data.drops[Number(row.getAttribute('data-drop-row'))];
      row.hidden = !normalize(drop.name).includes(query) || (map && !eligible(drop, map));
      row.querySelector('[data-drop-rate]').textContent = drop.rates[account].toLocaleString('es-AR', {maximumFractionDigits: 2}) + '%';
      if (!row.hidden) visible++;
    });
    wiki.querySelector('[data-account-name]').textContent = data.accounts[account].name;
    wiki.querySelector('[data-drop-status]').textContent = visible + ' reglas de drop' + (map ? ' compatibles con monstruos de ' + map.name : '') + (visible ? '' : ' · probá otro objeto o mapa');
  }
  mapFilter.addEventListener('input', filterMaps);
  dropFilter.addEventListener('input', filterDrops);
  dropMap.addEventListener('change', filterDrops);
  dropAccount.addEventListener('change', filterDrops);
  wiki.querySelectorAll('[data-drop-query]').forEach(function (button) { button.addEventListener('click', function () { dropFilter.value = button.getAttribute('data-drop-query'); filterDrops(); }); });
  wiki.querySelectorAll('[data-map-drops]').forEach(function (link) { link.addEventListener('click', function () { dropMap.value = link.getAttribute('data-map-drops'); dropFilter.value = ''; filterDrops(); if (location.hash === '#drops') revealHash(); }); });
  var saved = [];
  try { var stored = JSON.parse(localStorage.getItem('panic-atlas-maps') || '[]'); if (Array.isArray(stored)) saved = stored.filter(function (id) { return data.maps.some(function (map) { return map.id === id; }); }); } catch (e) { /* Reference works without browser storage. */ }
  function renderSaved() {
    var list = wiki.querySelector('[data-saved-maps]'); list.replaceChildren();
    wiki.querySelector('.wiki-saved').hidden = saved.length === 0;
    saved.forEach(function (id) { var map = data.maps.find(function (item) { return item.id === id; }); var link = document.createElement('a'); link.href = '#mapa-' + id; link.textContent = map.name + ' ↗'; list.appendChild(link); });
    wiki.querySelectorAll('[data-save-map]').forEach(function (button) { var active = saved.includes(Number(button.getAttribute('data-save-map'))); button.setAttribute('aria-pressed', String(active)); button.textContent = active ? 'Mapa guardado ✓' : 'Guardar mapa'; });
  }
  wiki.querySelectorAll('[data-save-map]').forEach(function (button) { button.addEventListener('click', function () { var id = Number(button.getAttribute('data-save-map')); saved = saved.includes(id) ? saved.filter(function (value) { return value !== id; }) : saved.concat(id); try { localStorage.setItem('panic-atlas-maps', JSON.stringify(saved)); } catch (e) {} renderSaved(); }); });
  renderSaved();
  filterMaps(); filterDrops(); revealHash();
})();
