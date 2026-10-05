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
  var dropMonster = wiki.querySelector('[data-drop-monster]');
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
    if (target) {
      target.hidden = false;
      var atlasPanel = target.closest('[data-atlas-panel]');
      if (atlasPanel) selectAtlasSpot(atlasPanel.closest('[data-atlas-explorer]'), atlasPanel.getAttribute('data-atlas-panel'));
      var details = target.closest('details');
      while (details) { details.open = true; details = details.parentElement && details.parentElement.closest('details'); }
      if (target.closest('#progresion')) { mapFilter.value = ''; filterMaps(); }
      if (target.hasAttribute('data-reward-list') && rewardFilter) { rewardFilter.value = ''; rewardFilter.dispatchEvent(new Event('input')); }
      if (target.hasAttribute('data-drop-row')) { dropFilter.value = ''; dropMap.value = ''; dropMonster.value = ''; updateMonsterOptions(); filterDrops(); }
      requestAnimationFrame(function () { target.scrollIntoView({block: 'start', behavior: 'instant'}); });
    }
  }
  wiki.classList.add('wiki-enhanced');
  window.addEventListener('hashchange', function () { clearSearch(); revealHash(); });
  nav.forEach(function (link) { link.addEventListener('click', function () { clearSearch(); if (location.hash === link.hash) revealHash(); }); });
  var searchable = [];
  panels.forEach(function (panel) { searchable.push({title: panel.querySelector('h2').textContent, context: 'Guía del servidor', text: normalize(panel.querySelector('h2').textContent), hash: '#' + panel.id}); });
  var rewardFilter = wiki.querySelector('[data-reward-filter]');
  if (rewardFilter) rewardFilter.addEventListener('input',function() {
    var query = normalize(rewardFilter.value), visible = 0;
    wiki.querySelectorAll('[data-reward-list]').forEach(function(list) { list.hidden = !normalize(list.textContent).includes(query); if (!list.hidden) visible++; });
    wiki.querySelector('[data-reward-status]').textContent = visible + ' listas de recompensas' + (visible ? '' : ' · probá otro nombre');
  });
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
      map.hidden = !normalize(map.textContent + ' ' + (map.getAttribute('data-map-aliases') || '')).includes(query); if (!map.hidden) visible++;
    });
    wiki.querySelector('[data-map-status]').textContent = visible + ' mapas' + (visible ? '' : ' · probá otro nombre');
  }
  function eligible(drop, map) {
    if (drop.map !== -1 && drop.map !== map.id) return false;
    var mode = map.equipmentDrop && map.equipmentDrop.dropMode;
    if (mode !== '*' && mode !== 'unknown' && (Number(mode) & 4) === 0) return false;
    return map.monsters.some(function (monster) { return monster.level >= drop.min && monster.level <= drop.max && (drop.monster === -1 || monster.id === drop.monster); });
  }
  var allMonsters = Array.from(new Map(data.maps.flatMap(function(map) { return map.monsters; }).map(function(m) { return [m.id,m]; })).values()).sort(function(a,b) { return a.name.localeCompare(b.name); });
  function matchesMonster(drop, monster) { return monster.level >= drop.min && monster.level <= drop.max && (drop.monster === -1 || drop.monster === monster.id); }
  function updateMonsterOptions() {
    var selected = dropMonster.value;
    var map = data.maps.find(function(m) { return String(m.id) === dropMap.value; });
    dropMonster.replaceChildren(new Option('Todos los monstruos', ''));
    (map ? map.monsters : allMonsters).forEach(function(m) { dropMonster.add(new Option(m.name, String(m.id))); });
    dropMonster.value = selected;
  }
  updateMonsterOptions();
  function filterDrops() {
    var query = normalize(dropFilter.value), account = Number(dropAccount.value);
    var map = data.maps.find(function (item) { return String(item.id) === dropMap.value; });
    var monster = allMonsters.find(function(m) { return String(m.id) === dropMonster.value; });
    var visible = 0;
    wiki.querySelectorAll('[data-drop-row]').forEach(function (row) {
      var drop = data.drops[Number(row.getAttribute('data-drop-row'))];
      row.hidden = !normalize(drop.name).includes(query) || (map && !eligible(drop, map)) || (monster && !matchesMonster(drop, monster));
      row.querySelector('[data-drop-rate]').textContent = drop.rates[account].toLocaleString('es-AR', {maximumFractionDigits: 6}) + '%';
      if (!row.hidden) visible++;
    });
    wiki.querySelector('[data-account-name]').textContent = data.accounts[account].name;
    wiki.querySelector('[data-drop-status]').textContent = visible + ' reglas de drop' + (map ? ' compatibles con monstruos de ' + map.name : '') + (monster ? ' · ' + monster.name : '') + (visible ? '' : ' · probá otro objeto o mapa');
  }
  mapFilter.addEventListener('input', filterMaps);
  dropFilter.addEventListener('input', filterDrops);
  dropMap.addEventListener('change', function() { dropMonster.value = ''; updateMonsterOptions(); filterDrops(); });
  dropMonster.addEventListener('change', filterDrops);
  dropAccount.addEventListener('change', filterDrops);
  wiki.querySelectorAll('[data-drop-query]').forEach(function (button) { button.addEventListener('click', function () { dropFilter.value = button.getAttribute('data-drop-query'); filterDrops(); }); });
  wiki.querySelectorAll('[data-map-drops]').forEach(function (link) { link.addEventListener('click', function () { dropMap.value = link.getAttribute('data-map-drops'); dropFilter.value = ''; dropMonster.value = ''; updateMonsterOptions(); filterDrops(); if (location.hash === '#drops') revealHash(); }); });
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
  function selectAtlasSpot(explorer, key) {
    explorer.querySelectorAll('[data-atlas-panel]').forEach(function(panel) { panel.hidden = panel.getAttribute('data-atlas-panel') !== key; });
    explorer.querySelectorAll('[data-atlas-select]').forEach(function(link) {
      if (link.getAttribute('data-atlas-select') === key) link.setAttribute('aria-current', 'true');
      else link.removeAttribute('aria-current');
    });
  }
  wiki.querySelectorAll('[data-atlas-explorer]').forEach(function(explorer) {
    selectAtlasSpot(explorer, explorer.querySelector('[data-atlas-panel]').getAttribute('data-atlas-panel'));
    explorer.querySelectorAll('[data-atlas-select]').forEach(function(link) { link.addEventListener('click', function(event) {
      event.preventDefault(); selectAtlasSpot(explorer, link.getAttribute('data-atlas-select'));
      var inspector = explorer.querySelector('.atlas-inspector');
      if (window.matchMedia('(max-width: 900px)').matches) inspector.scrollIntoView({block:'start',behavior:'smooth'});
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
  wiki.querySelectorAll('[data-material-drop]').forEach(function (link) { link.addEventListener('click', function () { dropFilter.value = link.getAttribute('data-material-drop'); dropMap.value = ''; dropMonster.value = ''; updateMonsterOptions(); filterDrops(); if (location.hash === '#drops') revealHash(); }); });
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
  wiki.querySelectorAll('[data-save-map]').forEach(function (button) { button.addEventListener('click', function () { var id = Number(button.getAttribute('data-save-map')); saved = saved.includes(id) ? saved.filter(function (value) { return value !== id; }) : saved.concat(id); try { localStorage.setItem('panic-atlas-maps', JSON.stringify(saved)); } catch (e) {} renderSaved(); }); });
  renderSaved();
  filterMaps(); filterDrops(); revealHash();
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
