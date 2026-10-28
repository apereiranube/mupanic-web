/* MU PANIC · Mercado de Vessara (estilo nuevo): ventas, vidriera con ficha del objeto, destacados y pasos. */
(function () {
  'use strict';
  var root = document.getElementById('mk-page'); if (!root) return;
  var q = function (s) { return root.querySelector(s); };
  var qa = function (s) { return [].slice.call(root.querySelectorAll(s)); };
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var SAMPLE = window.MK_SAMPLE || null;
  var fmt = function (v) { try { return new Intl.NumberFormat('es-AR').format(BigInt(v)); } catch (e) { return '—'; } };
  var el = function (tag, text, cls) { var n = document.createElement(tag); if (text !== null && text !== undefined) n.textContent = String(text); if (cls) n.className = cls; return n; };
  var title = function (s) { return s.item + (s.level ? ' +' + s.level : ''); };
  var money = function (s) { return fmt(s.price) + ' ' + (s.currency === 1 ? 'Eryns' : 'Zen'); };
  var CUR = (window.MK_CUR || {});
  function cash(node, s) { var src = s.currency === 1 ? CUR.eryns : CUR.zen; if (src) { var i = document.createElement('img'); i.src = src; i.alt = ''; i.className = 'mk-cur'; i.width = 20; i.height = 20; node.prepend(i); } return node; }
  var when = function (s) { try { var p = {}; new Intl.DateTimeFormat('es-AR', {timeZone: 'America/Argentina/Buenos_Aires', day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23'}).formatToParts(new Date(s.soldAt)).forEach(function (x) { p[x.type] = x.value; }); return p.day + '/' + p.month + ' ' + p.hour + ':' + p.minute; } catch (e) { return ''; } };

  /* ---------- Ventas (market_data) ---------- */
  var data = null, cur = 'all', lpage = 1, tapeKey = '';
  try { data = JSON.parse(document.getElementById('vessara-initial').textContent); } catch (e) {}
  if (SAMPLE && SAMPLE.market) data = SAMPLE.market;

  function trend(s) {
    var list = (data && data.sales) || [], at = list.indexOf(s);
    var prev = at < 0 ? null : list.slice(at + 1).filter(function (x) { return x.item === s.item && x.level === s.level && x.currency === s.currency; })[0];
    if (!prev) return null;
    var a = BigInt(s.price), b = BigInt(prev.price); if (b === 0n) return null;
    var pct = Number((a - b) * 1000n / b) / 10;
    return {dir: a > b ? 'up' : a < b ? 'down' : 'flat', label: (a > b ? '▲ ' : a < b ? '▼ ' : '= ') + (a === b ? '' : Math.abs(pct).toLocaleString('es-AR', {maximumFractionDigits: 1}) + '%')};
  }
  function loop(run, makeGroup, pxPerSec) {
    run.replaceChildren(); run.classList.remove('is-static');
    var g = makeGroup(); run.appendChild(g);
    var win = run.parentNode, i = 0;
    while (g.scrollWidth < win.clientWidth + 80 && i++ < 6) { [].slice.call(makeGroup().children).forEach(function (c) { c.setAttribute('aria-hidden', 'true'); g.appendChild(c); }); }
    var dup = g.cloneNode(true); dup.setAttribute('aria-hidden', 'true'); run.appendChild(dup);
    function speed() { var w = g.getBoundingClientRect().width; if (w > 0) run.style.setProperty('--dur', Math.max(30, w / pxPerSec) + 's'); }
    speed(); requestAnimationFrame(speed); setTimeout(speed, 600); if (document.fonts && document.fonts.ready) document.fonts.ready.then(speed);
    if (!run._rs) { run._rs = 1; window.addEventListener('resize', function () { if (run._speed) run._speed(); }); } run._speed = speed;
    if (reduce) run.classList.add('is-static');
  }
  function tape() {
    var sales = (data && data.sales) || [], key = JSON.stringify(sales.slice(0, 10)); if (key === tapeKey) return; tapeKey = key;
    var run = q('[data-mk-tape]');
    if (!sales.length) { run.replaceChildren(el('span', data ? 'Todavía no hay ventas.' : 'El registro de ventas no está disponible.', 'mk-tape-empty')); run.classList.add('is-static'); return; }
    loop(run, function () {
      var g = el('div'); g.style.display = 'flex';
      sales.slice(0, 10).forEach(function (s) {
        var t = trend(s), n = el('span', null, 'mk-sale');
        n.append(el('b', title(s)), cash(el('em', money(s), s.currency === 1 ? 'eryns' : ''), s));
        if (t) n.append(el('i', t.label, t.dir));
        g.appendChild(n);
      });
      return g;
    }, 22);
  }
  function fitNum(b) { b.style.fontSize = ''; var w = b.clientWidth; for (var i = 0; w && b.scrollWidth > w && i < 4; i++) b.style.fontSize = (parseFloat(getComputedStyle(b).fontSize) * w / b.scrollWidth * .97) + 'px'; }
  function metrics() {
    var m = (data && data.metrics) || {};
    qa('[data-mk-metric]').forEach(function (b) { var v = m[b.getAttribute('data-mk-metric')]; if (/^\d+$/.test(String(v))) countTo(b, +v); else { b.textContent = '—'; b._v = null; } });
    lastSale();
  }
  /* Números vivos: cuentan hasta el valor real y, cuando entra una venta, suben rodando con un "+N". Nunca se inventan cifras. */
  function countTo(b, target) {
    var from = typeof b._v === 'number' ? b._v : 0, first = typeof b._v !== 'number'; b._v = target;
    var stat = b.closest('.mk-stat');
    if (!first && target > from && stat) {
      var k = b.getAttribute('data-mk-metric'), d = target - from, txt = '+' + fmt(String(d)) + (k === 'zen' ? ' Zen' : k === 'eryns' ? ' Eryns' : '');
      var p = stat.querySelector('.mk-delta');
      if (p) { p.textContent = txt; p.classList.remove('on'); void p.offsetWidth; p.classList.add('on'); clearTimeout(p._t); p._t = setTimeout(function () { p.classList.remove('on'); }, 4000); }
      stat.classList.remove('is-bump'); void stat.offsetWidth; stat.classList.add('is-bump');
    }
    if (reduce || from === target) { b.textContent = fmt(String(target)); if (stat) fitNum(b); return; }
    var t0 = performance.now(), dur = first ? 1400 : 900; cancelAnimationFrame(b._raf);
    (function st(t) { var x = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - x, 3); b.textContent = fmt(String(Math.round(from + (target - from) * e))); if (stat && (x === 1 || first && x < .05)) fitNum(b); if (x < 1) b._raf = requestAnimationFrame(st); })(t0);
  }
  function lastSale() {
    var n = q('[data-mk-last]'); if (!n) return;
    var s = data && data.sales && data.sales[0], ms = s && Date.parse(s.soldAt);
    if (!ms) { n.textContent = 'Todavía no hay ventas hoy'; return; }
    var m = Math.max(0, Math.round((Date.now() - ms) / 60000));
    n.textContent = 'Última venta ' + (m < 1 ? 'hace instantes' : m < 60 ? 'hace ' + m + ' min' : m < 1440 ? 'hace ' + Math.floor(m / 60) + ' h' : 'hace ' + Math.floor(m / 1440) + ' d');
  }
  setInterval(lastSale, 15000);
  addEventListener('resize', function () { qa('.mk-stat b').forEach(fitNum); });
  function rows(enter) {
    var list = ((data && data.sales) || []).filter(function (s) { return cur === 'all' || String(s.currency) === cur; });
    var pages = Math.max(1, Math.ceil(list.length / 10)); lpage = Math.min(Math.max(1, lpage), pages);
    var shown = list.slice((lpage - 1) * 10, lpage * 10), box = q('[data-mk-rows]'); box.replaceChildren();
    shown.forEach(function (s) {
      var li = el('li', null, 'mk-row' + (s.currency === 1 ? ' eryns' : ''));
      var t = el('time', when(s)); t.dateTime = s.soldAt;
      li.append(el('b', title(s)), el('span', s.buyer, 'who'), el('span', s.seller, 'who b'), cash(el('span', money(s), 'pr'), s), t);
      if (enter && !reduce) { li.classList.add('enter'); li.style.setProperty('--i', box.children.length); }
      box.appendChild(li);
    });
    var empty = q('[data-mk-empty]'); empty.hidden = !!shown.length;
    empty.textContent = !data ? 'El registro del mercado no está disponible en este momento.' : (!(data.sales || []).length ? 'Todavía no hay tratos sellados.' : 'No hay ventas en esta moneda.');
    q('[data-mk-pager]').classList.toggle('off', pages <= 1); q('[data-mk-page]').textContent = 'Página ' + lpage + ' de ' + pages;
    q('[data-mk-prev]').disabled = lpage <= 1; q('[data-mk-next]').disabled = lpage >= pages;
  }
  function records() {
    var box = q('[data-mk-records]'), h = (data && data.highlights) || {}; box.replaceChildren();
    function card(label, eryns, fill) { var c = el('article', null, 'mk-rec' + (eryns ? ' eryns' : '')); c.append(el('small', label)); if (fill) fill(c); else c.append(el('p', data ? 'Todavía libre hoy.' : 'Sin datos por ahora.')); box.appendChild(c); }
    [1, 0].forEach(function (c) { var r = (h.records || []).filter(function (x) { return x.currency === c; })[0]; card('Mayor venta en ' + (c ? 'Eryns' : 'Zen'), c === 1, r && function (n) { n.append(el('strong', title(r)), el('b', money(r)), el('p', r.buyer + ' le compró a ' + r.seller)); }); });
    var p = (h.popular || [])[0]; card('El más vendido', false, p && function (n) { n.append(el('strong', title(p)), el('b', fmt(p.trades) + (p.trades === '1' ? ' venta' : ' ventas'))); });
    [1, 0].forEach(function (c) { var s = (h.sellers || []).filter(function (x) { return x.currency === c; })[0]; card('Mejor vendedor en ' + (c ? 'Eryns' : 'Zen'), c === 1, s && function (n) { n.append(el('strong', s.seller), el('b', fmt(s.volume) + ' ' + (c ? 'Eryns' : 'Zen')), el('p', fmt(s.trades) + (s.trades === '1' ? ' venta' : ' ventas'))); }); });
  }
  function week() {
    var box = q('[data-mk-days]'), days = (data && data.days) || []; box.replaceChildren();
    var max = days.reduce(function (a, d) { return Math.max(a, Number(d.trades) || 0); }, 0) || 1;
    var names = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    days.forEach(function (d, i) {
      var n = Number(d.trades) || 0, b = el('div', null, 'mk-bar' + (i === days.length - 1 ? ' is-today' : ''));
      var bar = el('i'); bar.style.setProperty('--h', Math.max(2, n / max * 100) + '%');
      var dt = new Date(d.day + 'T12:00:00-03:00');
      b.append(el('b', fmt(String(n))), bar, el('small', i === days.length - 1 ? 'Hoy' : names[dt.getDay()]));
      box.appendChild(b);
    });
  }
  /* Sube un número desde 0 hasta su valor (solo dígitos; respeta el texto alrededor). */
  function roll(b, dur) {
    var txt = b.getAttribute('data-txt') || b.textContent; b.setAttribute('data-txt', txt);
    var m = txt.match(/[\d.]+/); if (!m || reduce) { b.textContent = txt; return; }
    var target = parseInt(m[0].replace(/\./g, ''), 10), pre = txt.slice(0, m.index), post = txt.slice(m.index + m[0].length), t0 = performance.now();
    (function st(t) { var x = Math.min(1, (t - t0) / (dur || 1000)), e = 1 - Math.pow(1 - x, 3); b.textContent = pre + fmt(String(Math.round(target * e))) + post; if (x < 1) requestAnimationFrame(st); })(t0);
  }
  var logSeen = false;
  function onLogSeen() {
    if (logSeen) return; logSeen = true;
    q('[data-mk-days]').classList.add('is-in');
    qa('[data-mk-records] .mk-rec b').forEach(function (b) { roll(b, 1100); });
  }
  if ('IntersectionObserver' in window) new IntersectionObserver(function (e, o) { if (e[0].isIntersecting) { o.disconnect(); onLogSeen(); } }, {threshold: .2}).observe(q('.mk-log-grid')); else logSeen = true;
  function renderSales() { metrics(); tape(); rows(); records(); week(); if (logSeen) q('[data-mk-days]').classList.add('is-in'); }
  qa('[data-mk-cur]').forEach(function (b) { b.addEventListener('click', function () {
    cur = b.getAttribute('data-mk-cur'); lpage = 1;
    qa('[data-mk-cur]').forEach(function (x) { x.setAttribute('aria-pressed', x === b); x.classList.toggle('is-active', x === b); });
    rows(true);
  }); });
  q('[data-mk-prev]').addEventListener('click', function () { lpage--; rows(true); });
  q('[data-mk-next]').addEventListener('click', function () { lpage++; rows(true); });
  function pollSales() {
    if (SAMPLE || document.hidden) return;
    fetch(root.getAttribute('data-market-url'), {headers: {Accept: 'application/json'}, cache: 'no-store'})
      .then(function (r) { if (!r.ok) throw 0; return r.json(); })
      .then(function (v) { if (v && v.metrics && Array.isArray(v.sales)) { data = v; renderSales(); } })
      .catch(function () {});
  }
  renderSales(); setInterval(pollSales, 15000);
  /* Solo en la vista previa con datos de muestra: simula ventas nuevas para ver el marcador en movimiento. */
  if (SAMPLE && data && data.sales && data.sales.length) setInterval(function () {
    var b = data.sales[Math.floor(Math.random() * data.sales.length)], n = JSON.parse(JSON.stringify(b)); n.soldAt = new Date().toISOString();
    var m = data.metrics || (data.metrics = {}); m.today = String((+m.today || 0) + 1); var k = n.currency === 1 ? 'eryns' : 'zen'; m[k] = String((+m[k] || 0) + (+n.price || 0));
    data = Object.assign({}, data, {sales: [n].concat(data.sales).slice(0, 40), metrics: m}); renderSales();
  }, 9000);

  /* ---------- Vidriera (market_listings) ---------- */
  var listings = null, page = 1, lcur = 'all', search = '', sort = 'recent', typing = 0, lseq = 0;
  var active = function (it) { return !it.expiresAt || new Date(it.expiresAt).getTime() > Date.now(); };
  var promoted = function (it) { return it.featured === true && it.featuredUntil && new Date(it.featuredUntil).getTime() > Date.now() && active(it); };
  var rarity = function (it) { var d = it.details || {}; return d.ancient ? 'is-ancient' : (Array.isArray(d.sockets) && d.sockets.length) ? 'is-socket' : (d.excellentCount > 0) ? 'is-excellent' : ''; };
  function nameNode(tag, it) { var h = el(tag, it.item); if (Number(it.level) > 0) h.append(el('span', ' +' + it.level, 'lv')); return h; }
  function sources(it) {
    var list = [], i = Number(it.itemIndex);
    if (it.photo && it.photo.url) list.push(it.photo.url);
    if (Number.isInteger(i) && i >= 0 && i <= 8191) { var g = Math.floor(i / 512), s = i % 512; list.push('https://mureforge.com/item-images/' + g + '/' + s + '.webp', 'https://bless.gs/images/items/' + g + '-' + s + '.webp'); }
    return list.filter(function (v, k, a) { return a.indexOf(v) === k; });
  }
  function media(it) {
    var f = el('div', null, 'mk-card-media'), img = document.createElement('img'), urls = sources(it), k = 0;
    img.alt = ''; img.loading = 'lazy'; img.decoding = 'async';
    function next() { if (k < urls.length) { img.src = urls[k++]; return; } img.remove(); f.append(el('span', 'Sin imagen', 'miss')); }
    img.addEventListener('error', next);
    img.addEventListener('load', function () {
      img.style.setProperty('--nat-w', img.naturalWidth + 'px'); img.style.setProperty('--nat-h', img.naturalHeight + 'px');
      if (img.naturalWidth < 128 && img.naturalHeight < 128) { img.classList.add('is-small-sprite'); img.style.setProperty('--sprite-width', Math.min(120, img.naturalWidth * 2) + 'px'); }
    });
    f.append(img); next(); return f;
  }
  /* Ficha del objeto */
  var tip = el('div', null, 'mk-tip'); tip.id = 'mk-tip'; tip.hidden = true; tip.setAttribute('role', 'dialog'); tip.setAttribute('aria-label', 'Ficha del objeto');
  var shade = el('div', null, 'mk-tip-shade'); shade.hidden = true;
  document.body.append(tip, shade);
  var shown = null, pinned = false;
  var mobile = function () { return matchMedia('(max-width:720px), (pointer:coarse)').matches; };
  function fill(it) {
    tip.replaceChildren(); tip.classList.toggle('is-featured', promoted(it));
    var top = el('div', null, 'mk-tip-top'); top.append(el('span', 'Ficha del objeto'));
    var x = el('button', '✕', 'mk-tip-close'); x.type = 'button'; x.setAttribute('aria-label', 'Cerrar'); x.addEventListener('click', hide); top.append(x); tip.append(top);
    var intro = el('div', null, 'mk-tip-intro'), info = el('div');
    if (promoted(it)) info.append(el('span', 'Destacado', 'mk-tip-promo'));
    info.append(nameNode('h3', it), el('p', 'Publicado por ' + (it.seller || '—')));
    intro.append(media(it), info); tip.append(intro);
    var pr = el('div', null, 'mk-tip-price'); pr.append(el('span', 'Precio'), cash(el('b', money(it), it.currency === 1 ? 'eryns' : ''), it)); tip.append(pr);
    var o = it.details;
    if (!o) tip.append(el('p', 'Las opciones de este objeto se ven dentro del juego.', 'mk-tip-note'));
    else {
      var std = [];
      if (o.skill) std.push('Skill'); if (o.luck) std.push('Luck');
      if (o.additional > 0) std.push('Opción adicional +' + o.additional);
      if (typeof o.durability === 'number') std.push('Durabilidad ' + o.durability);
      if (o.lot > 1) std.push('Lote de ' + o.lot + ' unidades');
      function sec(t, items, cls) { var s = el('section', null, 'mk-tip-sec' + (cls ? ' ' + cls : '')); s.append(el('h4', t)); var ul = el('ul'); items.forEach(function (v) { ul.append(el('li', v)); }); s.append(ul); tip.append(s); }
      sec('Atributos', std.length ? std : ['Sin atributos adicionales']);
      if (o.excellentCount > 0 && Array.isArray(o.excellent)) sec('Excellent · ' + o.excellentCount + (o.excellentCount === 1 ? ' opción' : ' opciones'), o.excellent, 'is-excellent');
      var rare = [];
      if (o.ancient) rare.push(o.ancientType || 'Ancient · bonificación de set');
      if (o.harmony) rare.push(o.harmonyText || 'Opción Harmony');
      if (o.option380) rare.push('Opción 380');
      if (Array.isArray(o.sockets)) o.sockets.forEach(function (s) { rare.push('Socket ' + s.slot + ': ' + (s.state === 'vacío' ? 'vacío' : 'gema insertada')); });
      if (rare.length) sec('Opciones especiales', rare, 'is-rare');
    }
    tip.append(el('p', 'La compra se hace dentro del juego, hablando con Vessara.', 'mk-tip-foot'));
  }
  function place(a) {
    if (mobile()) { tip.style.left = ''; tip.style.top = ''; return; }
    var r = a.getBoundingClientRect(), n = tip.getBoundingClientRect(), m = 14;
    var left = r.right + 14; if (left + n.width > innerWidth - m) left = r.left - n.width - 14; if (left < m) left = m;
    var top = Math.max(m, Math.min(r.top, innerHeight - n.height - m));
    tip.style.left = Math.round(left) + 'px'; tip.style.top = Math.round(top) + 'px';
  }
  function show(it, a, stick) {
    if (shown === it && pinned) return;
    shown = it; pinned = !!stick || mobile(); fill(it); tip.hidden = false; shade.hidden = !mobile();
    a.setAttribute('aria-expanded', 'true'); place(a);
    if (mobile()) tip.querySelector('.mk-tip-close').focus({preventScroll: true});
  }
  function hide() { shown = null; pinned = false; tip.hidden = true; shade.hidden = true; qa('[aria-expanded="true"][data-mk-offer]').forEach(function (n) { n.setAttribute('aria-expanded', 'false'); }); }
  shade.addEventListener('click', hide);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !tip.hidden) hide(); });
  document.addEventListener('pointerdown', function (e) { if (pinned && !tip.contains(e.target) && !e.target.closest('[data-mk-offer]') && !mobile()) hide(); });
  addEventListener('scroll', function () { if (shown && !pinned) hide(); }, {passive: true});
  function card(it) {
    var c = el('article', null, 'mk-card ' + (it.currency === 1 ? 'eryns' : 'zen') + (promoted(it) ? ' is-featured' : '') + (rarity(it) ? ' ' + rarity(it) : ''));
    c.setAttribute('data-mk-offer', it.id); c.tabIndex = 0; c.setAttribute('role', 'button'); c.setAttribute('aria-expanded', 'false'); c.setAttribute('aria-controls', 'mk-tip');
    c.setAttribute('aria-label', 'Ver ficha de ' + title(it) + ' · ' + money(it));
    var b = el('div', null, 'mk-card-body'), tags = el('div', null, 'mk-card-tags');
    tags.append(el('span', it.currency === 1 ? 'Eryns' : 'Zen', 'mk-card-cur' + (it.currency === 1 ? ' eryns' : '')));
    if (promoted(it)) tags.append(el('span', 'Destacado', 'mk-card-badge'));
    b.append(tags, nameNode('h4', it), el('p', it.seller || '—', 'mk-card-seller'), cash(el('strong', money(it), 'mk-card-price' + (it.currency === 1 ? ' eryns' : '')), it));
    c.append(media(it), b);
    if (promoted(it)) { var sh = el('span', null, 'mk-sheen'); sh.setAttribute('aria-hidden', 'true'); c.append(sh); }
    if (promoted(it)) { var rib = el('span', null, 'mk-card-rib'); rib.setAttribute('aria-hidden', 'true'); rib.append(el('span', 'Destacado')); c.append(rib); }
    var t = 0;
    c.addEventListener('pointerenter', function (e) { if (e.pointerType === 'touch' || pinned) return; clearTimeout(t); t = setTimeout(function () { if (!pinned && c.matches(':hover')) show(it, c, false); }, 110); });
    c.addEventListener('pointerleave', function () { clearTimeout(t); if (!pinned && shown === it) hide(); });
    c.addEventListener('click', function () { if (shown === it && pinned) hide(); else show(it, c, true); });
    c.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); if (shown === it && pinned) hide(); else show(it, c, true); } });
    return c;
  }

  /* Destacados: tarjetas en una cinta que se mueve sola (pausa al pasar el mouse; en pantallas táctiles se desliza con el dedo). */
  var featKey = '', marq = q('[data-listings-featured]'), mrun = q('[data-mk-feat-run]'), featItems = [], featW = 0;
  var touch = matchMedia('(pointer:coarse)').matches;
  function buildMarquee() {
    mrun.replaceChildren(); mrun.classList.remove('is-moving');
    if (!featItems.length) return;
    var g = el('div', null, 'mk-marquee-group'); featItems.forEach(function (it) { g.append(card(it)); }); mrun.append(g);
    if (reduce || touch) { marq.classList.add('is-scroll'); return; }
    marq.classList.remove('is-scroll');
    var i = 0; while (g.scrollWidth < marq.clientWidth + 260 && i++ < 8) featItems.forEach(function (it) { var c = card(it); c.tabIndex = -1; c.setAttribute('aria-hidden', 'true'); g.append(c); });
    var d = g.cloneNode(false); d.setAttribute('aria-hidden', 'true');
    [].slice.call(g.children).forEach(function (x, k) { var it = featItems[k % featItems.length], c = card(it); c.tabIndex = -1; c.setAttribute('aria-hidden', 'true'); d.append(c); });
    mrun.append(d);
    mrun.style.setProperty('--travel', g.getBoundingClientRect().width + 'px');
    mrun.style.setProperty('--dur', Math.max(20, g.getBoundingClientRect().width / 45) + 's');
    mrun.classList.add('is-moving'); featW = marq.clientWidth;
  }
  addEventListener('resize', function () { if (featItems.length && Math.abs(marq.clientWidth - featW) > 8) buildMarquee(); });
  function renderFeatured(items) {
    var key = JSON.stringify(items.map(function (x) { return x.id + x.price; })); if (key === featKey) return; featKey = key;
    featItems = items; q('[data-mk-feat-none]').hidden = items.length > 0; marq.hidden = !items.length;
    buildMarquee();
  }
  function renderListings() {
    hide();
    var grid = q('[data-listings-grid]'), state = q('[data-listings-state]'), pag = q('[data-listings-pagination]');
    grid.replaceChildren();
    var ok = listings && listings.available === true && Array.isArray(listings.items) && Array.isArray(listings.featured);
    if (!ok) { renderFeatured([]); pag.classList.add('off'); state.hidden = false; state.textContent = 'No se pudieron consultar las publicaciones. Probá de nuevo en un rato.'; q('[data-mk-count]').textContent = '—'; return; }
    var feat = listings.featured.filter(promoted); renderFeatured(feat);
    listings.items.filter(function (x) { return active(x) && !promoted(x); }).forEach(function (x) { grid.append(card(x)); });
    countTo(q('[data-mk-count]'), (parseInt(listings.total, 10) || 0) + feat.length);
    state.hidden = grid.children.length > 0;
    state.textContent = listings.total === '0' && lcur === 'all' && !search ? 'Todavía no hay publicaciones. Cuando los jugadores publiquen sus objetos, aparecen acá.' : 'No hay publicaciones con estos filtros.';
    pag.classList.toggle('off', listings.pages <= 1);
    q('[data-listings-page]').textContent = 'Página ' + listings.page + ' de ' + listings.pages;
    q('[data-listings-prev]').disabled = listings.page <= 1;
    q('[data-listings-next]').disabled = listings.page >= listings.pages;
  }
  function sampleListings() {
    var all = SAMPLE.listings.items.filter(function (x) { return (lcur === 'all' || String(x.currency) === lcur) && (!search || (x.item + ' ' + x.seller).toLowerCase().indexOf(search.toLowerCase()) >= 0); });
    if (sort !== 'recent') all.sort(function (a, b) { return sort === 'price-asc' ? Number(a.price) - Number(b.price) : Number(b.price) - Number(a.price); });
    var pages = Math.max(1, Math.ceil(all.length / 10)); page = Math.min(page, pages);
    return {schema: 1, available: true, page: page, pages: pages, total: String(all.length), items: all.slice((page - 1) * 10, page * 10), featured: SAMPLE.listings.featured};
  }
  function loadListings() {
    if (SAMPLE && SAMPLE.listings) { listings = sampleListings(); renderListings(); return; }
    if (document.hidden) return;
    var n = ++lseq, url = new URL(root.getAttribute('data-market-listings-url'), location.href);
    url.searchParams.set('listing_page', page); url.searchParams.set('currency', lcur); url.searchParams.set('search', search); url.searchParams.set('sort', sort);
    fetch(url.toString(), {headers: {Accept: 'application/json'}, cache: 'no-store'})
      .then(function (r) { if (!r.ok) throw 0; return r.json(); })
      .then(function (v) { if (n !== lseq) return; listings = v; page = v.page || 1; renderListings(); })
      .catch(function () { if (n !== lseq) return; listings = null; renderListings(); });
  }
  qa('[data-listings-currency]').forEach(function (b) { b.addEventListener('click', function () {
    lcur = b.getAttribute('data-listings-currency'); page = 1;
    qa('[data-listings-currency]').forEach(function (x) { x.setAttribute('aria-pressed', x === b); x.classList.toggle('is-active', x === b); });
    loadListings();
  }); });
  q('[data-listings-sort]').addEventListener('change', function (e) { sort = e.target.value; page = 1; loadListings(); });
  q('[data-listings-search]').addEventListener('input', function (e) { clearTimeout(typing); var v = e.target.value.slice(0, 60); typing = setTimeout(function () { search = v; page = 1; loadListings(); }, 300); });
  q('[data-listings-prev]').addEventListener('click', function () { if (page > 1) { page--; loadListings(); } });
  q('[data-listings-next]').addEventListener('click', function () { if (listings && page < listings.pages) { page++; loadListings(); } });
  loadListings(); setInterval(function () { if (!pinned) loadListings(); }, 30000);

  /* ---------- Así se sella un trato ---------- */
  (function () {
    var stage = q('[data-mk-stage]'), items = qa('[data-mk-steps] > li'), cap = q('[data-mk-cap]');
    var caps = ['Publicás tu Jewel of Chaos en Loren Market.', 'Vessara guarda el objeto bajo su sello.', 'La publicación aparece en el juego y en la web.', 'Kaelor la compra: el sello cae y el trato queda hecho.', 'Cobrás 950 Eryns, aunque estés desconectado.'];
    var span = 5000, step = 0, auto = !reduce, visible = false, timer = 0;
    function show(i) {
      step = i; stage.setAttribute('data-step', i + 1); cap.textContent = caps[i];
      if (i === 4) qa('[data-mk-stage] [data-roll]').forEach(function (b) { var v = +b.getAttribute('data-roll'), pre = b.getAttribute('data-pre') || '', t0 = performance.now(); if (reduce) { b.textContent = pre + fmt(String(v)); return; } (function st(t) { var x = Math.min(1, (t - t0) / 900); b.textContent = pre + fmt(String(Math.round(v * (1 - Math.pow(1 - x, 3))))); if (x < 1) requestAnimationFrame(st); })(t0); });
      var vw = q('[data-mk-views]'); if (vw) { clearInterval(vw._t); if (i === 2 && !reduce) { var n = 0; vw.textContent = '0'; vw._t = setInterval(function () { n = Math.min(128, n + 4); vw.textContent = n; if (n >= 128) clearInterval(vw._t); }, 30); } else vw.textContent = '128'; }
      items.forEach(function (li, k) {
        li.classList.toggle('on', k === i); li.classList.toggle('done', k < i);
        var pg = li.querySelector('.pg'); pg.classList.remove('run');
        if (k === i && auto && visible) { void pg.offsetWidth; pg.style.setProperty('--d', span + 'ms'); pg.classList.add('run'); }
      });
    }
    function sync() { clearTimeout(timer); if (auto && visible && !document.hidden) { show(step); timer = setTimeout(function next() { show((step + 1) % items.length); timer = setTimeout(next, span); }, span); } }
    items.forEach(function (li, i) { li.querySelector('button').addEventListener('click', function () { auto = false; clearTimeout(timer); show(i); }); });
    /* Jugable desde el lado del vendedor: Publicar recorre los 5 pasos; Publicar otro lo repite. */
    var buy = q('[data-mk-buy]'), seq = [], sold = false;
    function clearSeq() { seq.forEach(clearTimeout); seq = []; }
    buy.addEventListener('click', function () {
      auto = false; clearTimeout(timer); clearSeq(); sold = false;
      buy.disabled = true; show(0);
      var at = reduce ? [0, 0, 0, 0, 0] : [0, 1300, 2600, 4100, 5700];
      [1, 2, 3, 4].forEach(function (k) { seq.push(setTimeout(function () { show(k); }, at[k])); });
      seq.push(setTimeout(function () { sold = true; buy.disabled = false; buy.textContent = 'Publicar otro'; }, reduce ? 0 : 6700));
    });
    items.forEach(function (li) { li.querySelector('button').addEventListener('click', function () { clearSeq(); sold = false; buy.disabled = false; buy.textContent = 'Publicar'; }); });
    if ('IntersectionObserver' in window) new IntersectionObserver(function (e) { visible = e[0].isIntersecting; sync(); }, {threshold: .35}).observe(stage);
    document.addEventListener('visibilitychange', sync);
    show(0);
  })();

  /* Garantía: los números suben y cada regla recibe su lacre. */
  (function () {
    var g = q('[data-mk-rules]'); if (!g) return;
    function go() { g.classList.add('is-in'); if (reduce) return; qa('[data-mk-rules] .mk-n').forEach(function (n) { var v = +n.getAttribute('data-n'), t0 = performance.now(); (function st(t) { var x = Math.min(1, (t - t0) / 900); n.textContent = Math.round(v * (1 - Math.pow(1 - x, 3))); if (x < 1) requestAnimationFrame(st); })(t0); }); }
    if ('IntersectionObserver' in window) new IntersectionObserver(function (e, o) { if (e[0].isIntersecting) { o.disconnect(); go(); } }, {threshold: .4}).observe(g); else go();
  })();
})();

/* Portada: el sello cae sobre "Vessara" cuando la página ya cargó; pasar el mouse por el lacre lo repite. */
(function () {
  var hs = document.querySelector('.mk-hs'), h = document.getElementById('mk-title'); if (!hs || !h) return;
  var seal = hs.querySelector('.mk-hs-seal'), mark = hs.querySelector('.mk-hs-mark');
  function play() {
    [seal, mark].forEach(function (i) { i.style.animation = 'none'; void i.offsetWidth; i.style.animation = ''; });
    hs.classList.add('go');
    clearTimeout(play._t); play._t = setTimeout(function () { h.classList.remove('mk-hit'); void h.offsetWidth; h.classList.add('mk-hit'); }, 1250);
  }
  function start() { setTimeout(play, 500); }
  var imgs = [seal, mark].filter(function (i) { return !i.complete; }), n = imgs.length;
  var go = function () { if (document.readyState === 'complete' && !n) start(); };
  imgs.forEach(function (i) { i.addEventListener('load', function () { n--; go(); }); i.addEventListener('error', function () { n--; go(); }); });
  if (document.readyState === 'complete') go(); else addEventListener('load', go);
  var last = 0; mark.addEventListener('pointerenter', function () { var t = Date.now(); if (t - last > 2600) { last = t; play(); } });
})();
