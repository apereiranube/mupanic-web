/* MU PANIC · Atlas (estilo nuevo): barra lima de la navegación, páginas de listas en un área fija,
   fichas que se abren encima (mapas, recompensas, vínculos, objetos), números que cuentan y revelado escalonado.
   No toca los datos: main.js, atlas-drops.js y atlas-ui.js siguen armando filtros, tasas y agenda. */
(function () {
  'use strict';
  var wiki = document.querySelector('[data-wiki]');
  if (!wiki) return;
  var still = matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!still) wiki.classList.add('atl-anim');

  /* ── Revelado escalonado ── */
  function reveal(items) {
    if (still) return;
    items.forEach(function (el, i) {
      var t = el.matches('details') ? el.querySelector(':scope>summary') || el : el;
      t.classList.remove('atl-rv'); void t.offsetWidth;
      t.style.setProperty('--i', Math.min(i, 12)); t.classList.add('atl-rv');
      t.addEventListener('animationend', function done() { t.classList.remove('atl-rv'); t.removeEventListener('animationend', done); });
    });
  }

  /* ── Páginas: las listas nunca crecen; el alto queda reservado ── */
  var pagers = [];
  function px(v) { return parseFloat(v) || 0; }
  function Pager(list, itemSel, sizes, emptySel) {
    if (!list) return;
    var page = 0, maxH = 0, lastW = 0, applying = false;
    var empty = emptySel ? wiki.querySelector(emptySel) : null;
    var bar = document.createElement('div'); bar.className = 'atl-pager';
    var prev = document.createElement('button'), next = document.createElement('button'), label = document.createElement('span');
    prev.type = next.type = 'button'; prev.textContent = '← Anterior'; next.textContent = 'Siguiente →';
    prev.setAttribute('aria-label', 'Página anterior'); next.setAttribute('aria-label', 'Página siguiente'); label.setAttribute('aria-live', 'polite');
    bar.append(prev, label, next); list.after(bar);
    if (empty) empty.parentElement.style.position = 'relative';
    function size() { var w = innerWidth; return w <= 600 ? sizes[2] : (w <= 1180 ? sizes[1] : sizes[0]); }
    function all() { return Array.from(list.children).filter(function (el) { return el.matches(itemSel); }); }
    function shown() { return all().filter(function (el) { return !el.hidden; }); }
    function lock() {
      if (!list.getClientRects().length) return;
      if (lastW !== innerWidth) { lastW = innerWidth; maxH = 0; }
      var cs = getComputedStyle(list), n = size();
      if (cs.display === 'grid' && cs.gridAutoRows && cs.gridAutoRows !== 'auto') {
        var cols = cs.gridTemplateColumns.split(' ').length, rows = Math.ceil(n / cols), gap = px(cs.rowGap);
        list.style.minHeight = (rows * px(cs.gridAutoRows) + (rows - 1) * gap) + 'px';
      } else {
        list.style.minHeight = ''; maxH = Math.max(maxH, list.offsetHeight); list.style.minHeight = maxH + 'px';
      }
      if (empty) empty.style.top = list.offsetTop + 'px';
    }
    function apply(animate) {
      applying = true;
      var items = shown(), n = size(), pages = Math.max(1, Math.ceil(items.length / n));
      var opened = items.findIndex(function (el) { return el.open; });
      if (opened >= 0) page = Math.floor(opened / n);
      page = Math.min(Math.max(page, 0), pages - 1);
      all().forEach(function (el) { el.classList.remove('atl-off'); });
      var current = [];
      items.forEach(function (el, i) { var on = Math.floor(i / n) === page; el.classList.toggle('atl-off', !on); if (on) current.push(el); });
      label.textContent = (page + 1) + ' / ' + pages;
      prev.disabled = page === 0; next.disabled = page >= pages - 1;
      bar.classList.toggle('is-single', pages <= 1);
      lock();
      if (animate) reveal(current);
      setTimeout(function () { applying = false; }, 0);
    }
    function go(p) { page = p; apply(true); var top = list.getBoundingClientRect().top; if (top < 90) list.scrollIntoView({block: 'start', behavior: still ? 'instant' : 'smooth'}); }
    prev.addEventListener('click', function () { go(page - 1); });
    next.addEventListener('click', function () { go(page + 1); });
    var queued = false;
    new MutationObserver(function (records) {
      if (applying || queued) return;
      if (!records.some(function (r) { return r.type === 'childList' ? r.target === list : r.target.parentElement === list; })) return;
      queued = true;
      requestAnimationFrame(function () { queued = false; page = 0; apply(true); });
    }).observe(list, {childList: true, attributes: true, subtree: true, attributeFilter: ['hidden']});
    var api = {
      list: list, apply: apply,
      show: function (el) { var items = shown(), i = items.indexOf(el); if (i >= 0) { page = Math.floor(i / size()); apply(false); } },
      has: function (el) { return list.contains(el); },
      item: function (el) { return all().find(function (it) { return it === el || it.contains(el); }); }
    };
    pagers.push(api); apply(false);
    return api;
  }
  Pager(wiki.querySelector('#progresion>.wiki-map-list'), '.wiki-map', [6, 4, 3], '[data-map-empty]');
  Pager(wiki.querySelector('.atlas-event-grid'), '.atlas-event-card', [6, 4, 3], '[data-event-empty]');
  Pager(wiki.querySelector('.atlas-drop-catalogue'), '.atlas-drop-card', [9, 6, 4], '[data-drop-empty]');
  Pager(wiki.querySelector('#recompensas>.wiki-map-list'), '.wiki-map', [8, 6, 5]);
  Pager(wiki.querySelector('[data-atlas-find-results]'), '.atlas-find-card', [4, 2, 3]);
  wiki.querySelectorAll('.atlas-drop-rules').forEach(function (rules) { Pager(rules, '.atlas-drop-rule', [5, 5, 4]); });

  /* La búsqueda aparece con un fundido en su área fija */
  var results = wiki.querySelector('[data-atlas-find-results]');
  if (results && !still) new MutationObserver(function () { results.classList.remove('atl-fade'); void results.offsetWidth; results.classList.add('atl-fade'); }).observe(results, {childList: true});

  /* ── Fichas que se abren encima ── */
  var sheetSel = 'details.wiki-map,details.vinculo-card';
  function sheetBody(d) { return d.querySelector(':scope>.wiki-map-body,:scope>.vinculo-story'); }
  function sheetTitle(d) {
    if (d.dataset.mapName) return d.dataset.mapName;
    var t = d.querySelector(':scope>summary strong,:scope>summary h4'); return t ? t.textContent.trim() : '';
  }
  function sheetKicker(d) {
    if (d.closest('#progresion')) return 'Territorio';
    if (d.matches('.vinculo-card')) return 'Vínculo';
    return 'Recompensa';
  }
  function ensureHead(d) {
    var body = sheetBody(d); if (!body || body.querySelector(':scope>.atl-sheet-head')) return;
    var head = document.createElement('div'), small = document.createElement('small'), strong = document.createElement('strong'), close = document.createElement('button');
    head.className = 'atl-sheet-head'; small.textContent = sheetKicker(d); strong.textContent = sheetTitle(d);
    close.type = 'button'; close.className = 'atl-sheet-close'; close.textContent = 'Cerrar ×';
    close.addEventListener('click', function () { d.open = false; var s = d.querySelector(':scope>summary'); if (s) s.focus({preventScroll: true}); });
    head.append(small, strong, close); body.prepend(head);
  }
  function visible(el) { return !!el && el.getClientRects().length > 0; }
  function openSheets() { return Array.from(wiki.querySelectorAll(sheetSel)).filter(function (d) { return d.open && visible(sheetBody(d)); }); }
  function openDrop() { return Array.from(wiki.querySelectorAll('.atlas-drop-detail')).find(function (el) { return !el.hidden && visible(el); }); }
  function sync() { document.documentElement.classList.toggle('atl-sheet-on', openSheets().length > 0 || !!openDrop()); }
  wiki.querySelectorAll(sheetSel).forEach(function (d) {
    d.addEventListener('toggle', function () { if (d.open) { ensureHead(d); var b = sheetBody(d); if (b) b.scrollTop = 0; } sync(); });
    if (d.open) ensureHead(d);
  });
  var dropDetails = wiki.querySelector('.atlas-drop-details');
  if (dropDetails) new MutationObserver(sync).observe(dropDetails, {attributes: true, subtree: true, attributeFilter: ['hidden']});
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape' || document.querySelector('dialog[open]')) return;
    var open = openSheets();
    if (open.length) { open[open.length - 1].open = false; e.preventDefault(); return; }
    var drop = openDrop(); if (drop) { var back = drop.querySelector('[data-drop-back]'); if (back) back.click(); e.preventDefault(); }
  });

  /* Agenda completa y ayuda de drops: se abren encima, sin empujar nada */
  wiki.querySelectorAll('.atlas-full-agenda').forEach(function (d) {
    var pop = document.createElement('div'); pop.className = 'atl-agenda-pop';
    Array.from(d.children).forEach(function (c) { if (c.tagName !== 'SUMMARY') pop.appendChild(c); }); d.appendChild(pop);
  });
  wiki.querySelectorAll('.atlas-drop-help').forEach(function (d) {
    var pop = document.createElement('div'); pop.className = 'atl-help-pop';
    Array.from(d.children).forEach(function (c) { if (c.tagName !== 'SUMMARY') pop.appendChild(c); }); d.appendChild(pop);
  });
  document.addEventListener('click', function (e) {
    wiki.querySelectorAll('.atlas-full-agenda[open],.atlas-drop-help[open],.vinculo-drop-rules[open],.atlas-find-card details[open]').forEach(function (d) { if (!d.contains(e.target)) d.open = false; });
  });

  /* ── Navegación: barra lima que se desliza ── */
  var nav = wiki.querySelector('[data-atl-tabs]');
  function moveBar() {
    if (!nav) return;
    var a = nav.querySelector('a[aria-current]');
    if (!a || !a.offsetHeight || getComputedStyle(nav.querySelector('.atl-nav-bar')).display === 'none') { nav.classList.remove('has-bar'); return; }
    nav.style.setProperty('--by', a.offsetTop + 'px'); nav.style.setProperty('--bh', a.offsetHeight + 'px'); nav.classList.add('has-bar');
    if (innerWidth <= 900) { var strip = a.parentElement; strip.scrollTo({left: a.offsetLeft - 16, behavior: 'instant'}); }
  }
  function scrollStrip() {
    if (!nav || innerWidth > 900) return;
    var a = nav.querySelector('a[aria-current]'); if (!a) return;
    var strip = a.parentElement; strip.scrollLeft = a.offsetLeft - strip.offsetLeft - 16;
  }

  /* ── Al cambiar de capítulo: página correcta, cierre de fichas ajenas, revelado ── */
  function onHash() {
    var id = decodeURIComponent(location.hash.slice(1)), target = id ? document.getElementById(id) : null;
    wiki.querySelectorAll(sheetSel).forEach(function (d) { if (d.open && target && !d.contains(target) && !(d === target)) { if (!target.closest('.atlas-event-dialog')) d.open = false; } });
    pagers.forEach(function (p) { if (target && p.has(target)) { var it = p.item(target); if (it) p.show(it); } else p.apply(false); });
    var section = wiki.querySelector('.wiki-section:not([hidden])');
    if (section) reveal(Array.from(section.querySelectorAll('.atl-tile,.atl-system,.atl-feature,.atl-trail,.wiki-route>li,.atlas-resource-grid>div')).filter(visible));
    if (section) pagers.forEach(function (p) { if (section.contains(p.list)) reveal(Array.from(p.list.children).filter(function (el) { return visible(el); })); });
    moveBar(); scrollStrip(); sync(); count(section);
  }
  window.addEventListener('hashchange', function () { requestAnimationFrame(onHash); });
  var lastW = innerWidth;
  window.addEventListener('resize', function () { if (innerWidth === lastW) return; lastW = innerWidth; pagers.forEach(function (p) { p.apply(false); }); moveBar(); });

  /* ── Números que cuentan ── */
  var counted = new WeakSet();
  function countEl(el) {
    if (counted.has(el)) return; counted.add(el);
    var text = el.textContent, m = /-?\d+(?:[.,]\d+)?/.exec(text); if (!m || still) return;
    var target = parseFloat(m[0].replace(',', '.')), dec = (m[0].split(/[.,]/)[1] || '').length, sep = m[0].indexOf(',') >= 0 ? ',' : '.';
    var t0 = performance.now(), dur = 900;
    (function step(t) {
      var k = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - k, 3), v = (target * e).toFixed(dec).replace('.', sep);
      el.textContent = text.slice(0, m.index) + v + text.slice(m.index + m[0].length);
      if (k < 1) requestAnimationFrame(step); else el.textContent = text;
    })(t0);
  }
  var countIO = 'IntersectionObserver' in window ? new IntersectionObserver(function (entries) {
    entries.forEach(function (e) { if (e.isIntersecting) { countEl(e.target); countIO.unobserve(e.target); } });
  }, {threshold: .4}) : null;
  function count(scope) {
    if (!countIO) return;
    (scope || document).querySelectorAll('[data-atl-count],.atlas-furia-stats strong,.atlas-territories-facts strong,.atlas-drop-count>strong').forEach(function (el) { if (!counted.has(el)) countIO.observe(el); });
  }
  document.querySelectorAll('.atl-hero [data-atl-count]').forEach(function (el) { if (countIO) countIO.observe(el); });

  /* ── Portada: el mapa sigue al mouse y se mueve apenas al bajar ── */
  var hero = document.querySelector('[data-atl-hero]'), art = hero && hero.querySelector('[data-atl-parallax]');
  if (hero && art && !still) {
    var mx = 0, my = 0, sy = 0, raf = 0;
    function paint() { raf = 0; art.style.transform = 'translate3d(' + (mx * -22).toFixed(1) + 'px,' + (my * -14 + sy * .12).toFixed(1) + 'px,0)'; }
    function queue() { if (!raf) raf = requestAnimationFrame(paint); }
    hero.addEventListener('pointermove', function (e) { var r = hero.getBoundingClientRect(); mx = (e.clientX - r.left) / r.width - .5; my = (e.clientY - r.top) / r.height - .5; queue(); });
    hero.addEventListener('pointerleave', function () { mx = my = 0; queue(); });
    window.addEventListener('scroll', function () { sy = Math.min(scrollY, 600); queue(); }, {passive: true});
  }

  requestAnimationFrame(function () { onHash(); });
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(moveBar);
})();
