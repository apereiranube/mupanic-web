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
  var mapScope = 'all';
  var mapSort = wiki.querySelector('[data-map-sort]');
  function normalize(value) { return value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim(); }
  function clearSearch() { search.value = ''; results.replaceChildren(); results.hidden = true; searchStatus.textContent = ''; }
  function revealHash() {
    var id = location.hash.slice(1) || 'inicio';
    var target = document.getElementById(id);
    var panel = target && (target.classList.contains('wiki-section') ? target : target.closest('.wiki-section'));
    if (!panel) panel = panels[0];
    panels.forEach(function (item) { item.hidden = item !== panel; });
    nav.forEach(function (link) { if (link.hash === '#' + panel.id) { link.setAttribute('aria-current', 'page'); var group = link.closest('details'); if (group) group.open = true; } else link.removeAttribute('aria-current'); });
    if (panel.id === 'taller') selectRecipe(target && target.hasAttribute('data-recipe-id') ? target.getAttribute('data-recipe-id') : currentRecipe);
    // A shared deep link must reveal a map/row even after local filtering.
    wiki.querySelectorAll('[data-event-reward-list]').forEach(function(list) { list.hidden = true; });
    if (target) {
      target.hidden = false;
      var atlasPanel = target.closest('[data-atlas-panel]');
      if (atlasPanel) { selectAtlasSpot(atlasPanel.closest('[data-atlas-explorer]'), atlasPanel.getAttribute('data-atlas-panel')); if (target.hasAttribute('data-atlas-mob')) selectAtlasMonster(atlasPanel, target.getAttribute('data-atlas-mob')); }
      var details = target.closest('details');
      while (details) { details.open = true; details = details.parentElement && details.parentElement.closest('details'); }
      if (target.closest('#progresion')) { mapFilter.value = ''; if (target !== panel) setMapScope('all'); else filterMaps(); }
      if (target.hasAttribute('data-reward-list') && target.closest('#recompensas') && rewardFilter) { rewardKind = ''; rewardFilter.value = ''; filterRewards(); }
      if (panel.id === 'drops') { var dropItem = target.closest('[data-drop-item]'); if (target.hasAttribute('data-drop-row')) dropExplorer.reset(); if (dropItem) dropExplorer.open(dropItem.getAttribute('data-drop-item')); else dropExplorer.back({focus:false}); }
      if (location.hash) requestAnimationFrame(function () { target.scrollIntoView({block: 'start', behavior: 'instant'}); });
    }
  }
  wiki.classList.add('wiki-enhanced');
  window.addEventListener('hashchange', function () { clearSearch(); revealHash(); });
  nav.forEach(function (link) { link.addEventListener('click', function () { clearSearch(); if (location.hash === link.hash) revealHash(); }); });
  var searchable = [];
  panels.forEach(function (panel) { searchable.push({title: panel.querySelector('h2').textContent, context: 'Guía del servidor', text: normalize(panel.querySelector('h2').textContent), hash: '#' + panel.id}); });
  wiki.addEventListener('input', function(event) {
      var input = event.target;
      if (!input.matches('[data-reward-item-filter]')) return;
      var list = input.closest('[data-reward-list]'), query = normalize(input.value), visible = 0;
      list.querySelectorAll('[data-reward-item]').forEach(function(row) { row.hidden = !normalize(row.textContent).includes(query); if(!row.hidden) visible++; });
      list.querySelector('[data-reward-item-status]').textContent = visible + ' objetos posibles' + (visible ? '' : ' · probá otro nombre');
  });
  var rewardFilter = wiki.querySelector('[data-reward-filter]');
  var rewardKind = '';
  function filterRewards() {
    if (!rewardFilter) return;
    var query = normalize(rewardFilter.value), visible = 0;
    wiki.querySelectorAll('#recompensas [data-reward-list]').forEach(function(list) {
      list.hidden = (rewardKind && list.getAttribute('data-reward-type') !== rewardKind) || !normalize(list.textContent).includes(query);
      if (!list.hidden) visible++;
    });
    wiki.querySelector('[data-reward-status]').textContent = visible + ' listas de recompensas' + (visible ? '' : ' · probá otro nombre');
    wiki.querySelectorAll('[data-reward-kind]').forEach(function(button) { button.setAttribute('aria-pressed', String(button.getAttribute('data-reward-kind') === rewardKind)); });
  }
  if (rewardFilter) rewardFilter.addEventListener('input',filterRewards);
  wiki.querySelectorAll('[data-reward-kind]').forEach(function(button) { button.addEventListener('click',function() { rewardKind = button.getAttribute('data-reward-kind'); filterRewards(); }); });
  search.addEventListener('input', function () {
    var query = normalize(search.value); results.replaceChildren();
    if (!query) { clearSearch(); return; }
    finderQuery.value = search.value; finderType.value = ''; finderMap.value = ''; finderLevel.value = ''; renderFinder(true);
    var matches = window.PanicAtlasSearch.filter(finderIndex,{query:search.value,type:'',map:'',level:''});
    results.hidden = false; searchStatus.textContent = matches.length + ' resultados en objetos, mobs y mapas';
    var link = document.createElement('a'); link.href = '#buscar'; link.textContent = 'Ver resultados de «' + search.value + '» ↗';
    link.addEventListener('click',function () { if (location.hash === '#buscar') revealHash(); clearSearch(); }); results.appendChild(link);
    searchable.filter(function (item) { return item.text.includes(query); }).slice(0,3).forEach(function (item) {
      var guideLink = document.createElement('a'); guideLink.href = item.hash; guideLink.textContent = item.title + ' · Guía ↗';
      guideLink.addEventListener('click',function () { if (location.hash === item.hash) revealHash(); clearSearch(); }); results.appendChild(guideLink);
    });
  });
  search.addEventListener('keydown',function (event) {
    if (event.key === 'Enter' && normalize(search.value)) { event.preventDefault(); location.hash = '#buscar'; revealHash(); clearSearch(); finderQuery.focus(); }
  });
  function filterMaps() {
    var query = normalize(mapFilter.value), visible = 0;
    wiki.querySelectorAll('#progresion .wiki-map').forEach(function (map) {
      var id = Number(map.id.replace('mapa-', '')), spots = Number(map.getAttribute('data-map-spots'));
      var matchesScope = mapScope === 'all' || (mapScope === 'spots' && spots > 0) || (mapScope === 'other' && spots === 0) || (mapScope === 'saved' && (saved || []).includes(id));
      map.hidden = !matchesScope || !normalize(map.getAttribute('data-map-search') || map.textContent).includes(query);
      if (!map.hidden) visible++;
    });
    wiki.querySelector('[data-map-status]').textContent = visible + (visible === 1 ? ' mapa' : ' mapas') + ' · ' + data.maps.length + ' territorios';
    var empty = wiki.querySelector('[data-map-empty]'); if (empty) empty.hidden = visible !== 0;
  }
  function setMapScope(scope) {
    mapScope = scope;
    wiki.querySelectorAll('[data-map-scope]').forEach(function(button) { button.setAttribute('aria-pressed', String(button.getAttribute('data-map-scope') === scope)); });
    filterMaps();
  }
  function sortMaps() {
    if (!mapSort) return;
    var list = wiki.querySelector('#progresion>.wiki-map-list');
    Array.from(list.children).sort(function(a,b) {
      if (mapSort.value === 'name') return a.getAttribute('data-map-name').localeCompare(b.getAttribute('data-map-name'), 'es', {numeric:true});
      if (mapSort.value === 'level') {
        var left = a.getAttribute('data-map-level'), right = b.getAttribute('data-map-level');
        var difference = (left === '' ? Infinity : Number(left)) - (right === '' ? Infinity : Number(right));
        if (difference && !Number.isNaN(difference)) return difference;
      }
      return Number(a.getAttribute('data-map-index')) - Number(b.getAttribute('data-map-index'));
    }).forEach(function(map) { list.appendChild(map); });
  }
  var dropExplorer = window.PanicAtlasDrops.create(wiki, data);
  function filterDrops() { dropExplorer.filter(); }
  mapFilter.addEventListener('input', filterMaps);
  if (mapSort) mapSort.addEventListener('change', sortMaps);
  wiki.querySelectorAll('[data-map-scope]').forEach(function(button) { button.addEventListener('click', function() { setMapScope(button.getAttribute('data-map-scope')); }); });
  wiki.querySelectorAll('[data-map-reset]').forEach(function(button) { button.addEventListener('click', function() { mapFilter.value = ''; if (mapSort) mapSort.value = 'atlas'; sortMaps(); setMapScope('all'); mapFilter.focus(); }); });
  wiki.querySelectorAll('#progresion>.wiki-map-list>.wiki-map').forEach(function(map) {
    map.addEventListener('toggle', function() {
      var preview = map.querySelector('.atlas-map-preview');
      if (preview && preview.srcset) preview.sizes = map.open ? '(max-width: 750px) 92vw, (max-width: 1400px) 68vw, 1140px' : '(max-width: 540px) 92vw, (max-width: 750px) 44vw, (max-width: 1400px) 34vw, 560px';
      if (map.open) map.querySelectorAll('.atlas-terrain img,[data-atlas-panel]:not([hidden]) img').forEach(function(img) { img.loading = 'eager'; });
      map.querySelector('.atlas-map-open-label>span').textContent = map.open ? 'Cerrar exploración' : 'Explorar mapa';
      map.querySelector('.atlas-map-open-label>i').textContent = map.open ? '−' : '↗';
    });
    var close = map.querySelector('[data-map-close]');
    if (close) close.addEventListener('click', function() { map.open = false; var summary = map.querySelector('summary'); summary.focus({preventScroll:true}); summary.scrollIntoView({block:'nearest',behavior:'instant'}); });
  });
  wiki.querySelectorAll('[data-map-drops]').forEach(function (link) { link.addEventListener('click', function () { dropExplorer.reset({map:link.getAttribute('data-map-drops')}); if (location.hash === '#drops') revealHash(); }); });
  var finderIndex = window.PanicAtlasSearch.build(data);
  var finderQuery = wiki.querySelector('[data-atlas-query]');
  var finderType = wiki.querySelector('[data-atlas-type]');
  var finderMap = wiki.querySelector('[data-atlas-map]');
  var finderLevel = wiki.querySelector('[data-atlas-level]');
  var finderResults = wiki.querySelector('[data-atlas-find-results]');
  var finderStatus = wiki.querySelector('[data-atlas-find-status]');
  var finderMore = wiki.querySelector('[data-atlas-find-more]');
  var finderLimit = 12;
  function finderLink(route) {
    var link = document.createElement('a'); link.href = route.hash; link.textContent = route.label + ' ↗';
    link.addEventListener('click', function () { if (location.hash === route.hash) revealHash(); });
    return link;
  }
  function finderRoute(route) {
    var row = document.createElement('li');
    row.appendChild(finderLink(route));
    if (route.detail) { var detail = document.createElement('small'); detail.textContent = route.detail; row.appendChild(detail); }
    if (route.spots && route.spots.length) {
      var spots = document.createElement('div'); spots.className = 'atlas-find-spots';
      route.spots.forEach(function (spot) { spots.appendChild(finderLink(spot)); }); row.appendChild(spots);
    }
    return row;
  }
  function finderRoutes(card, routes, heading) {
    if (!routes.length) return;
    var title = document.createElement('h4'); title.textContent = heading; card.appendChild(title);
    var list = document.createElement('ul'); routes.slice(0,3).forEach(function (route) { list.appendChild(finderRoute(route)); }); card.appendChild(list);
    if (routes.length > 3) {
      var more = document.createElement('details'), summary = document.createElement('summary');
      summary.textContent = 'Ver otras ' + (routes.length - 3) + ' opciones'; more.appendChild(summary);
      var rest = document.createElement('ul'); routes.slice(3).forEach(function (route) { rest.appendChild(finderRoute(route)); });
      more.appendChild(rest); card.appendChild(more);
    }
  }
  function renderFinder(reset) {
    if (reset) finderLimit = 12;
    var showLevel = finderType.value === 'mob' || finderType.value === 'boss';
    wiki.querySelector('[data-atlas-level-control]').hidden = !showLevel;
    if (!showLevel) finderLevel.value = '';
    wiki.querySelector('[data-atlas-map-note]').hidden = finderMap.value === '';
    finderResults.replaceChildren();
    var active = normalize(finderQuery.value) || finderType.value || finderMap.value !== '' || finderLevel.value !== '';
    if (!active) { finderStatus.textContent = 'Escribí un nombre o elegí qué querés explorar.'; finderMore.hidden = true; return; }
    var matches = window.PanicAtlasSearch.filter(finderIndex, {query:finderQuery.value, type:finderType.value, map:finderMap.value, level:finderLevel.value});
    finderStatus.textContent = matches.length ? matches.length + ' resultados · mostrando ' + Math.min(matches.length, finderLimit) : 'No encontramos resultados. Probá otro nombre o quitá un filtro.';
    finderMore.hidden = matches.length <= finderLimit;
    matches.slice(0,finderLimit).forEach(function (entry) {
      var card = document.createElement('article'); card.className = 'atlas-find-card';
      var header = document.createElement('header');
      var portrait = data.portraits && data.portraits[String(entry.id)];
      if (entry.type === 'mob' && portrait) {
        var image = document.createElement('img'); image.src = sourceBase() + portrait.file; image.alt = entry.name;
        image.width = 72; image.height = 72; image.loading = 'lazy'; header.appendChild(image);
      }
      var names = document.createElement('div'), type = document.createElement('small'), title = document.createElement('h3');
      type.textContent = {object:'OBJETO', mob:'MONSTRUO', map:'MAPA'}[entry.type]; title.textContent = entry.name;
      names.appendChild(type); names.appendChild(title); header.appendChild(names); card.appendChild(header);
      var routes = entry.routes.filter(function (route) { return finderMap.value === '' || entry.type === 'map' || (route.maps || []).includes(Number(finderMap.value)); });
      if (entry.type === 'object') {
        finderRoutes(card,routes.filter(function (route) { return route.kind === 'drop'; }),'Drops de monstruos');
        finderRoutes(card,routes.filter(function (route) { return route.kind === 'reward'; }),'Cajas, bosses y eventos');
      } else {
        finderRoutes(card,routes,'Dónde encontrarlo');
        if (!entry.maps.length) { var note = document.createElement('p'); note.textContent = 'La ubicación de aparición no está confirmada en la población fija. Consultá las condiciones del evento.'; card.appendChild(note); }
      }
      finderResults.appendChild(card);
    });
  }
  function sourceBase() {
    var script = document.querySelector('script[src*="atlas-search.js"]');
    return script ? script.src.split('/js/atlas-search.js')[0] + '/' : '';
  }
  finderQuery.addEventListener('input', function () { renderFinder(true); });
  [finderType,finderMap,finderLevel].forEach(function (control) { control.addEventListener('change',function () { renderFinder(true); }); });
  finderMore.addEventListener('click',function () { finderLimit += 12; renderFinder(false); });
  wiki.querySelectorAll('[data-atlas-example]').forEach(function (button) { button.addEventListener('click',function () {
    finderQuery.value = button.getAttribute('data-atlas-example'); finderType.value = ''; finderMap.value = ''; finderLevel.value = ''; renderFinder(true);
  }); });
  wiki.querySelector('[data-atlas-clear]').addEventListener('click',function () {
    finderQuery.value = ''; finderType.value = ''; finderMap.value = ''; finderLevel.value = ''; renderFinder(true); finderQuery.focus();
  });
  renderFinder(true);
  function selectAtlasMonster(panel, id) {
    panel.querySelectorAll('[data-atlas-mob]').forEach(function(card) { card.hidden = card.getAttribute('data-atlas-mob') !== id; });
    panel.querySelectorAll('[data-atlas-mob-select]').forEach(function(button) { button.setAttribute('aria-pressed', String(button.getAttribute('data-atlas-mob-select') === id)); });
    var picker = panel.querySelector('[data-atlas-mob-picker]'); if (picker) picker.value = id;
    if (panel.closest('.wiki-map').open) panel.querySelectorAll('[data-atlas-mob]:not([hidden]) img').forEach(function(img) { img.loading = 'eager'; });
  }
  function selectAtlasSpot(explorer, key) {
    explorer.querySelectorAll('[data-atlas-panel]').forEach(function(panel) { panel.hidden = panel.getAttribute('data-atlas-panel') !== key; });
    if (explorer.closest('.wiki-map').open) explorer.querySelectorAll('[data-atlas-panel]:not([hidden]) img').forEach(function(img) { img.loading = 'eager'; });
    explorer.querySelectorAll('[data-atlas-select]').forEach(function(link) {
      if (link.getAttribute('data-atlas-select') === key) link.setAttribute('aria-current', 'true');
      else link.removeAttribute('aria-current');
    });
  }
  wiki.querySelectorAll('[data-atlas-explorer]').forEach(function(explorer) {
    explorer.querySelectorAll('[data-atlas-panel]').forEach(function(panel) {
      var first = panel.querySelector('[data-atlas-mob]'); if (first) selectAtlasMonster(panel, first.getAttribute('data-atlas-mob'));
      panel.querySelectorAll('[data-atlas-mob-select]').forEach(function(button) { button.addEventListener('click', function() { selectAtlasMonster(panel, button.getAttribute('data-atlas-mob-select')); }); });
      var picker = panel.querySelector('[data-atlas-mob-picker]'); if (picker) picker.addEventListener('change', function() { selectAtlasMonster(panel, picker.value); });
    });
    selectAtlasSpot(explorer, explorer.querySelector('[data-atlas-panel]').getAttribute('data-atlas-panel'));
    explorer.querySelectorAll('[data-atlas-select]').forEach(function(link) { link.addEventListener('click', function(event) {
      event.preventDefault(); selectAtlasSpot(explorer, link.getAttribute('data-atlas-select'));
      var inspector = explorer.querySelector('.atlas-inspector');
      if (window.matchMedia('(max-width: 900px)').matches) inspector.scrollIntoView({block:'start',behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});
    }); });
    explorer.querySelector('[data-atlas-zoom]').addEventListener('click',function(event) {
      var frame = explorer.querySelector('.atlas-map-window');
      var zoomed = frame.classList.toggle('is-zoomed');
      event.currentTarget.setAttribute('aria-pressed',String(zoomed));
      event.currentTarget.textContent = zoomed ? 'Alejar −' : 'Acercar +';
    });
    explorer.querySelector('[data-atlas-account]').addEventListener('change',function(event) {
      var account = Number(event.target.value);
      explorer.querySelectorAll('[data-atlas-rates]').forEach(function(rate) {
        rate.textContent = JSON.parse(rate.getAttribute('data-atlas-rates'))[account].toLocaleString('es-AR',{maximumFractionDigits:6}) + '%';
      });
    });
  });
  // Keep just one map expanded; deep links still open the requested map.
  wiki.querySelectorAll('#progresion > .wiki-map-list > .wiki-map').forEach(function(map) {
    map.addEventListener('toggle',function() {
      if (map.open) wiki.querySelectorAll('#progresion > .wiki-map-list > .wiki-map').forEach(function(other) { if (other !== map) other.open = false; });
    });
  });
  // The workshop and the upgrade planner share the same public balance snapshot.
  var recipeSearch = wiki.querySelector('[data-recipe-search]');
  var recipeAccount = wiki.querySelector('[data-recipe-account]');
  var recipeGroup = 'Todas';
  var currentRecipe = data.recipes[0].id;
  var materialChecks = [];
  try { var storedMaterials = JSON.parse(localStorage.getItem('panic-atlas-materials') || '[]'); if (Array.isArray(storedMaterials)) materialChecks = storedMaterials.filter(function (key) { return typeof key === 'string' && wiki.querySelector('[data-material="' + CSS.escape(key) + '"]'); }); } catch (e) {}
  function updateMaterialProgress(article) {
    var checks = Array.from(article.querySelectorAll('[data-material]'));
    var ready = checks.filter(function (check) { return check.checked; }).length;
    article.querySelector('[data-recipe-progress]').textContent = ready === checks.length ? 'Lista de materiales completa. Revisá la tasa y el Zen en el juego antes de intentar la mezcla.' : ready + ' de ' + checks.length + ' materiales preparados · faltan ' + (checks.length - ready);
  }
  wiki.querySelectorAll('[data-material]').forEach(function (check) {
    check.checked = materialChecks.includes(check.getAttribute('data-material'));
    check.addEventListener('change', function () {
      var key = check.getAttribute('data-material');
      materialChecks = check.checked ? materialChecks.concat(key) : materialChecks.filter(function (item) { return item !== key; });
      try { localStorage.setItem('panic-atlas-materials', JSON.stringify(materialChecks)); } catch (e) {}
      updateMaterialProgress(check.closest('.recipe-article'));
    });
  });
  function recipeRates() {
    var account = Number(recipeAccount.value);
    data.recipes.forEach(function (recipe) {
      var rate = data.crafting.mixRates[recipe.rateKey][account];
      var article = document.getElementById('crear-' + recipe.id);
      article.querySelector('[data-recipe-rate]').textContent = rate === -1 ? 'Variable' : rate + '%';
      updateMaterialProgress(article);
    });
  }
  function selectRecipe(id) {
    if (!data.recipes.some(function (recipe) { return recipe.id === id; })) return;
    currentRecipe = id;
    var link = wiki.querySelector('[data-recipe-nav="' + id + '"]');
    if (link.hidden) { recipeGroup = 'Todas'; recipeSearch.value = ''; filterRecipes(false); }
    wiki.querySelectorAll('.recipe-article').forEach(function (article) { article.hidden = article.getAttribute('data-recipe-id') !== id; });
    wiki.querySelectorAll('[data-recipe-nav]').forEach(function (item) { if (item.getAttribute('data-recipe-nav') === id) item.setAttribute('aria-current', 'true'); else item.removeAttribute('aria-current'); });
    wiki.querySelectorAll('[data-recipe-group]').forEach(function (button) { button.setAttribute('aria-pressed', String(button.getAttribute('data-recipe-group') === recipeGroup)); });
  }
  function filterRecipes(selectFirst) {
    var query = normalize(recipeSearch.value);
    var matches = data.recipes.filter(function (recipe) { return (recipeGroup === 'Todas' || recipe.group === recipeGroup) && normalize(recipe.name + ' ' + recipe.materials.map(function (material) { return material.name; }).join(' ')).includes(query); });
    wiki.querySelectorAll('[data-recipe-nav]').forEach(function (link) { link.hidden = !matches.some(function (recipe) { return recipe.id === link.getAttribute('data-recipe-nav'); }); });
    wiki.querySelector('[data-recipe-status]').textContent = matches.length ? matches.length + ' recetas · elegí tu objetivo' : 'No hay recetas con ese nombre en esta categoría.';
    if (selectFirst && matches.length) { selectRecipe(matches[0].id); history.replaceState(null, '', '#crear-' + matches[0].id); }
    if (!matches.length) wiki.querySelectorAll('.recipe-article').forEach(function (article) { article.hidden = true; });
    wiki.querySelectorAll('[data-recipe-group]').forEach(function (button) { button.setAttribute('aria-pressed', String(button.getAttribute('data-recipe-group') === recipeGroup)); });
  }
  recipeSearch.addEventListener('input', function () { filterRecipes(true); });
  recipeAccount.addEventListener('change', recipeRates);
  wiki.querySelectorAll('[data-recipe-group]').forEach(function (button) { button.addEventListener('click', function () { recipeGroup = button.getAttribute('data-recipe-group'); filterRecipes(true); }); });
  wiki.querySelectorAll('[data-material-drop]').forEach(function (link) { link.addEventListener('click', function () { dropExplorer.reset({query:link.getAttribute('data-material-drop')}); if (location.hash === '#drops') revealHash(); }); });
  data.recipes.forEach(function (recipe) { searchable.push({title: recipe.name, context: 'Taller · ' + recipe.group, text: normalize(recipe.name + ' ' + recipe.materials.map(function (material) { return material.name; }).join(' ')), hash: '#crear-' + recipe.id}); });
  function updateUpgradePlanner() {
    var account = Number(wiki.querySelector('[data-upgrade-account]').value);
    var type = wiki.querySelector('[data-upgrade-type]').value;
    var from = Number(wiki.querySelector('[data-upgrade-from]').value);
    var to = Number(wiki.querySelector('[data-upgrade-to]').value);
    var luck = wiki.querySelector('[data-upgrade-luck]').checked;
    var rows = wiki.querySelector('[data-upgrade-steps]'); rows.replaceChildren();
    var total = 1, bless = 0, soul = 0, chaos = 0;
    var summary = wiki.querySelector('[data-upgrade-summary]');
    var probability = wiki.querySelector('[data-chain-rate]');
    var resources = wiki.querySelector('[data-upgrade-resources]');
    if (to <= from) { probability.textContent = '—'; summary.textContent = 'Elegí un objetivo mayor que el nivel actual.'; resources.textContent = ''; return; }
    for (var level = from + 1; level <= to; level++) {
      var amount = level - 9;
      var rate, where, materials;
      if (level <= 6) { rate = 100; where = 'Inventario'; materials = '1 Bless'; bless++; }
      else if (level <= 9) { rate = Math.min(100, data.crafting.jewels.SoulSuccessRate[account] + (luck ? data.crafting.jewels.AddLuckSuccessRate1[account] : 0)); where = 'Inventario'; materials = '1 Soul'; soul++; }
      else { rate = Math.min(100, data.crafting.mixRates[type + amount][account] + (luck ? data.crafting.jewels.AddLuckSuccessRate2[account] : 0)); where = 'Chaos Goblin'; materials = '1 Chaos · ' + amount + ' Bless · ' + amount + ' Soul'; chaos++; bless += amount; soul += amount; }
      total *= rate / 100;
      var row = document.createElement('tr');
      ['+' + (level - 1) + ' → +' + level, where, materials, rate + '%'].forEach(function (value) { var cell = document.createElement('td'); cell.textContent = value; row.appendChild(cell); });
      rows.appendChild(row);
    }
    probability.textContent = (total * 100).toLocaleString('es-AR', {maximumFractionDigits: 4}) + '%';
    summary.textContent = 'Probabilidad calculada de completar de +' + from + ' a +' + to + ' sin fallar ningún paso. Cada intento sigue teniendo su propia tasa.';
    resources.textContent = 'Materiales de una cadena sin fallos: ' + bless + ' Bless · ' + soul + ' Soul · ' + chaos + ' Chaos. No incluye equipo ni Zen; un fallo exige reconsiderar la ruta.';
  }
  wiki.querySelectorAll('[data-upgrade-calculator] select,[data-upgrade-luck]').forEach(function (control) { control.addEventListener('change', updateUpgradePlanner); });
  filterRecipes(false); selectRecipe(currentRecipe); recipeRates(); updateUpgradePlanner();

  var saved = [];
  try { var stored = JSON.parse(localStorage.getItem('panic-atlas-maps') || '[]'); if (Array.isArray(stored)) saved = stored.filter(function (id) { return data.maps.some(function (map) { return map.id === id; }); }); } catch (e) { /* Reference works without browser storage. */ }
  function renderSaved() {
    var list = wiki.querySelector('[data-saved-maps]'); list.replaceChildren();
    wiki.querySelector('.wiki-saved').hidden = saved.length === 0;
    saved.forEach(function (id) { var map = data.maps.find(function (item) { return item.id === id; }); var link = document.createElement('a'); link.href = '#mapa-' + id; link.textContent = map.name + ' ↗'; list.appendChild(link); });
    wiki.querySelectorAll('[data-save-map]').forEach(function (button) { var active = saved.includes(Number(button.getAttribute('data-save-map'))); button.setAttribute('aria-pressed', String(active)); button.textContent = active ? 'Mapa guardado ✓' : 'Guardar mapa'; });
  }
  wiki.querySelectorAll('[data-save-map]').forEach(function (button) { button.addEventListener('click', function () { var id = Number(button.getAttribute('data-save-map')); saved = saved.includes(id) ? saved.filter(function (value) { return value !== id; }) : saved.concat(id); try { localStorage.setItem('panic-atlas-maps', JSON.stringify(saved)); } catch (e) {} renderSaved(); filterMaps(); if (button.closest('.wiki-map').hidden) wiki.querySelector('[data-map-scope="saved"]').focus(); }); });
  renderSaved();
  filterMaps(); filterDrops(); filterRewards(); revealHash();
  wiki.classList.remove('atlas-boot');
})();

;(function () {
  if (!window.matchMedia || !window.matchMedia('(pointer:fine)').matches) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var last = 0;
  document.addEventListener('pointermove', function (event) {
    var now = performance.now();
    if (now - last < 34) return;
    last = now;

    var ember = document.createElement('span');
    ember.className = 'cursor-ember';
    ember.style.left = event.clientX + 'px';
    ember.style.top = event.clientY + 'px';
    ember.style.setProperty('--dx', ((Math.random() - 0.5) * 24).toFixed(1) + 'px');
    ember.style.setProperty('--dy', (-10 - Math.random() * 22).toFixed(1) + 'px');
    document.body.appendChild(ember);
    window.setTimeout(function () { ember.remove(); }, 650);
  }, {passive:true});
})();

;(function () {
  if (!document.body.classList.contains('is-home') && !document.body.classList.contains('is-server-info')) return;
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var layer = document.createElement('div');
  layer.className = 'ambient-embers';
  layer.setAttribute('aria-hidden', 'true');
  document.body.appendChild(layer);

  var mobile = window.matchMedia && window.matchMedia('(max-width: 700px)').matches;
  var maxAlive = mobile ? 16 : 34;
  var interval = mobile ? 520 : 280;

  function spawnEmber(seed) {
    if (!layer.isConnected || layer.childElementCount >= maxAlive) return;

    var ember = document.createElement('i');
    ember.className = 'ambient-ember' + (Math.random() > .68 ? ' is-soft' : '');

    var size = (mobile ? 2.5 : 3) + Math.random() * (mobile ? 3.8 : 5.5);
    var duration = 5.8 + Math.random() * 4.4;
    var x = Math.random() * 100;
    var drift = (Math.random() - .5) * (mobile ? 90 : 180);
    var opacity = .24 + Math.random() * .48;
    var blur = Math.random() > .76 ? .8 : 0;

    ember.style.setProperty('--x', x.toFixed(2) + 'vw');
    ember.style.setProperty('--size', size.toFixed(2) + 'px');
    ember.style.setProperty('--duration', duration.toFixed(2) + 's');
    ember.style.setProperty('--drift', drift.toFixed(1) + 'px');
    ember.style.setProperty('--opacity', opacity.toFixed(2));
    ember.style.setProperty('--blur', blur.toFixed(1) + 'px');

    if (seed) {
      ember.style.bottom = (Math.random() * 82 - 10).toFixed(1) + 'vh';
      ember.style.animationDelay = (-Math.random() * duration).toFixed(2) + 's';
    }

    layer.appendChild(ember);
    window.setTimeout(function () { ember.remove(); }, (duration + 1.2) * 1000);
  }

  for (var i = 0; i < (mobile ? 8 : 18); i++) spawnEmber(true);
  var timer = window.setInterval(function () {
    spawnEmber(false);
    if (!mobile && Math.random() > .72) spawnEmber(false);
  }, interval);

  window.addEventListener('pagehide', function () { window.clearInterval(timer); }, {once:true});
})();

;(function () {
  var dialog = document.querySelector('[data-system-modal]');
  if (!dialog || typeof dialog.showModal !== 'function') return;
  var panels = Array.prototype.slice.call(dialog.querySelectorAll('[data-system-panel]'));
  var closeButton = dialog.querySelector('[data-system-close]');
  var lastTrigger = null;
  var backdropPress = false;

  function outside(event) {
    var rect = dialog.getBoundingClientRect();
    return event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom;
  }
  document.querySelectorAll('[data-system-open]').forEach(function (button) {
    button.addEventListener('click', function () {
      var id = button.getAttribute('data-system-open');
      var panel = panels.find(function (item) { return item.getAttribute('data-system-panel') === id; });
      if (!panel || dialog.open) return;
      panels.forEach(function (item) { item.hidden = item !== panel; });
      dialog.setAttribute('aria-labelledby', 'system-title-' + id);
      dialog.setAttribute('aria-describedby', 'system-description-' + id);
      lastTrigger = button;
      document.documentElement.classList.add('system-modal-open');
      dialog.showModal();
      dialog.scrollTop = 0;
      closeButton.focus({preventScroll: true});
    });
  });
  function restorePage() {
    document.documentElement.classList.remove('system-modal-open');
    panels.forEach(function (item) { item.hidden = true; });
    if (lastTrigger && document.contains(lastTrigger)) lastTrigger.focus({preventScroll: true});
    lastTrigger = null;
  }
  function closeSystem() {
    dialog.close();
    restorePage();
  }
  closeButton.addEventListener('click', closeSystem);
  dialog.addEventListener('cancel', function (event) {
    event.preventDefault();
    closeSystem();
  });
  // Keep Tab within the content rather than moving focus into browser chrome.
  dialog.addEventListener('keydown', function (event) {
    if (event.key !== 'Tab') return;
    var controls = Array.prototype.slice.call(dialog.querySelectorAll('button, a[href], input, select, textarea, [tabindex]'))
      .filter(function (item) { return !item.disabled && item.tabIndex >= 0 && item.getClientRects().length; });
    var first = controls[0];
    var last = controls[controls.length - 1];
    if ((event.shiftKey && document.activeElement === first) || (!event.shiftKey && document.activeElement === last)) {
      event.preventDefault();
      (event.shiftKey ? last : first).focus();
    }
  });
  // Native dialog supplies Escape and an inert background.
  dialog.addEventListener('pointerdown', function (event) { backdropPress = outside(event); });
  dialog.addEventListener('click', function (event) {
    if (backdropPress && outside(event)) closeSystem();
    backdropPress = false;
  });
  dialog.addEventListener('close', function () {
    if (!dialog.open) restorePage();
  });
})();
