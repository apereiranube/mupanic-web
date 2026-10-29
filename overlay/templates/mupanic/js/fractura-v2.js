/* MU PANIC · La Fractura (estilo nuevo): marcador semanal (endpoint fractura_week), números que cuentan
   hasta el valor real, la balanza que se inclina hacia el bando que va al frente y paginación del Salón. */
(function () {
  'use strict';
  var root = document.getElementById('fr-page'); if (!root) return;
  var q = function (s) { return root.querySelector(s); };
  var qa = function (s) { return [].slice.call(root.querySelectorAll(s)); };
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var SAMPLE = window.FR_SAMPLE || null; // solo en la vista previa local
  var nf = new Intl.NumberFormat('es-AR');
  var fmt = function (v) { try { return nf.format(BigInt(v)); } catch (e) { return '—'; } };
  var digits = function (s) { return String(s || '').replace(/[^0-9]/g, ''); };
  var motion = !reduce && 'IntersectionObserver' in window;
  if (motion) root.classList.add('fr-anim'); // sin esto, todo se ve quieto y completo
  function once(els, threshold, fn) {
    els = els.filter(Boolean); if (!els.length) return;
    if (!motion) { els.forEach(function (el) { fn(el, 0); }); return; }
    var o = new IntersectionObserver(function (es) {
      var k = 0; es.forEach(function (e) { if (e.isIntersecting) { o.unobserve(e.target); fn(e.target, k++); } });
    }, {threshold: threshold});
    els.forEach(function (el) { o.observe(el); });
  }

  /* ---------- Marcador ---------- */
  var valid = function (d) {
    return d && d.schema === 2 && d.kind === 'weekly' && Number.isSafeInteger(d.updated) && d.updated > 0 && d.updated <= Date.now() / 1000 + 60 &&
      typeof d.weekStart === 'string' && /^[0-9]{4}-[0-9]{2}-[0-9]{2}$/.test(d.weekStart) && typeof d.closeUnix === 'string' && /^[0-9]{1,10}$/.test(d.closeUnix) &&
      typeof d.enabled === 'boolean' && typeof d.isClosed === 'boolean' &&
      [1, 2].every(function (id) { return d.factions && d.factions[id] && typeof d.factions[id].points === 'string' && /^[0-9]{1,18}$/.test(d.factions[id].points); });
  };
  function parts(unix) {
    var p = {};
    new Intl.DateTimeFormat('es-AR', {timeZone: 'America/Argentina/Buenos_Aires', day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23'})
      .formatToParts(new Date(Number(unix) * 1000)).forEach(function (x) { p[x.type] = x.value; });
    return p;
  }
  function closeLabel(unix) { var p = parts(unix); return 'CIERRE ' + p.day + '/' + p.month + ' · ' + p.hour + ':' + p.minute + ' ARG'; }
  function closeShort(unix) { var p = parts(unix); return p.day + '/' + p.month + ' · ' + p.hour + ':' + p.minute; }

  // Números: cuentan desde 0 la primera vez que se ven; después, del valor anterior al nuevo.
  function setNum(el, value, animate) {
    var to = BigInt(value), from = typeof el._v === 'bigint' ? el._v : 0n; el._v = to;
    cancelAnimationFrame(el._raf);
    if (!animate || reduce || from === to || to > 9007199254740991n) { el.textContent = fmt(to); return; }
    var a = Number(from), b = Number(to), t0 = performance.now(), dur = from === 0n ? 1500 : 900;
    (function step(t) {
      var x = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - x, 3);
      el.textContent = nf.format(Math.round(a + (b - a) * e));
      if (x < 1) el._raf = requestAnimationFrame(step);
    })(t0);
  }
  var seen = new WeakSet(), current = null;
  var nums = qa('[data-fr-points],[data-fr-diff]');
  function valueFor(el, d) {
    if (el.hasAttribute('data-fr-diff')) { var n = BigInt(d.factions[1].points), c = BigInt(d.factions[2].points); return String(n > c ? n - c : c - n); }
    return d.factions[el.getAttribute('data-fr-points')].points;
  }

  function render(d, animate) {
    current = d;
    var nexo = BigInt(d.factions[1].points), caos = BigInt(d.factions[2].points), total = nexo + caos;
    var share = total ? Number((nexo * 1000n + total / 2n) / total) / 10 : 50;
    nums.forEach(function (el) { if (seen.has(el)) setNum(el, valueFor(el, d), animate); else el._pending = valueFor(el, d); });
    var shareN = q('[data-fr-share="1"]'), shareC = q('[data-fr-share="2"]');
    if (shareN) shareN.textContent = total ? nf.format(share) + '%' : '—';
    if (shareC) shareC.textContent = total ? nf.format(Math.round((100 - share) * 10) / 10) + '%' : '—';
    if (beamSeen) beam(share, total);
    split(share, total);
    var lead = !d.enabled ? 'Competencia semanal pausada' : d.isClosed ? (nexo === caos ? 'Semana cerrada en empate' : nexo > caos ? 'Nexo ganó la semana' : 'Caos ganó la semana')
      : total === 0n ? 'La semana está por disputarse' : nexo === caos ? 'Nexo y Caos están igualados' : nexo > caos ? 'Nexo lleva la delantera' : 'Caos lleva la delantera';
    var leadEl = q('[data-fr-lead]'); if (leadEl) leadEl.textContent = lead;
    root.classList.remove('is-nexo', 'is-caos', 'is-even');
    root.classList.add(!total || nexo === caos ? 'is-even' : nexo > caos ? 'is-nexo' : 'is-caos');
    qa('[data-fr-chip]').forEach(function (el) { el.textContent = d.isClosed ? 'Ganó la semana' : 'Al frente'; });
    qa('[data-fr-close]').forEach(function (el) { el.textContent = closeLabel(d.closeUnix); });
    qa('[data-fr-close-short]').forEach(function (el) { el.firstChild.nodeValue = closeShort(d.closeUnix) + ' '; });
    var status = q('[data-fr-status]'); if (status) status.textContent = d.stale ? 'El marcador está temporalmente sin actualizar.' : '';
  }

  // La balanza: el color de cada bando ocupa su parte y la barra se inclina hacia el que va al frente (3° con 15 puntos de ventaja o más).
  var track = q('[data-fr-track]'), bar = q('[data-fr-bar]'), crack = q('[data-fr-crack]'), beamSeen = false;
  function beam(share, total) {
    if (!track || !bar) return;
    track.classList.toggle('is-empty', !total);
    bar.style.width = share + '%'; if (crack) crack.style.left = share + '%';
    track.style.setProperty('--tilt', (-Math.max(-15, Math.min(15, share - 50)) / 15 * 3).toFixed(2) + 'deg');
  }

  // Portada: la línea entre Nexo y Caos se corre según la semana (50% ± 15 de ventaja → centro 50% ± 8%). Sin datos, 50%.
  var splitEl = document.querySelector('[data-fr-split]');
  function split(share, total) {
    if (!splitEl) return;
    var m = total ? 50 + Math.max(-15, Math.min(15, share - 50)) / 15 * 8 : 50;
    splitEl.style.setProperty('--fr-mid', m.toFixed(2));
  }

  // Estado inicial: lo que ya vino del servidor (o la muestra de la vista previa).
  var initial = null;
  if (SAMPLE && valid(SAMPLE.week)) initial = SAMPLE.week;
  else {
    var n0 = digits(q('[data-fr-points="1"]') && q('[data-fr-points="1"]').textContent), c0 = digits(q('[data-fr-points="2"]') && q('[data-fr-points="2"]').textContent);
    if (n0 && c0) initial = {factions: {1: {points: n0}, 2: {points: c0}}};
  }
  if (initial) {
    nums.forEach(function (el) { el._pending = valueFor(el, initial); if (!reduce) el.textContent = '0'; });
    if (!reduce && bar) { bar.style.width = '50%'; if (crack) crack.style.left = '50%'; track.style.setProperty('--tilt', '0deg'); }
  }
  function shareOf(d) { var n = BigInt(d.factions[1].points), c = BigInt(d.factions[2].points), t = n + c; return [t ? Number((n * 1000n + t / 2n) / t) / 10 : 50, t]; }
  function onSeen(el) {
    if (seen.has(el)) return; seen.add(el);
    if (el._pending !== undefined) setNum(el, el._pending, true);
  }
  function onBeam() {
    if (beamSeen) return; beamSeen = true;
    var d = current || initial; if (!d) return;
    var s = shareOf(d);
    // Un cuadro de espera para que la transición arranque desde el centro.
    requestAnimationFrame(function () { requestAnimationFrame(function () { beam(s[0], s[1]); }); });
  }
  if ('IntersectionObserver' in window && !reduce) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (!e.isIntersecting) return; io.unobserve(e.target); if (e.target === track) onBeam(); else onSeen(e.target); });
    }, {threshold: .6});
    nums.forEach(function (el) { io.observe(el); }); if (track) io.observe(track);
  } else { nums.forEach(onSeen); onBeam(); }
  if (SAMPLE && initial) render(initial, true);
  else if (initial) { var s0 = shareOf(initial); if (motion) requestAnimationFrame(function () { requestAnimationFrame(function () { split(s0[0], s0[1]); }); }); else split(s0[0], s0[1]); }

  // Consulta cada minuto mientras la pestaña está visible (mismo endpoint que la página anterior).
  var busy = false, timer = null;
  function refresh() {
    if (busy || document.hidden || SAMPLE) return;
    busy = true;
    var ctrl = 'AbortController' in window ? new AbortController() : null, to = setTimeout(function () { if (ctrl) ctrl.abort(); }, 12000);
    fetch(root.getAttribute('data-fractura-url'), {credentials: 'same-origin', cache: 'no-store', signal: ctrl ? ctrl.signal : undefined})
      .then(function (r) { if (!r.ok) throw new Error('x'); return r.json(); })
      .then(function (d) { if (!valid(d)) throw new Error('x'); render(d, true); })
      .catch(function () { var s = q('[data-fr-status]'); if (s) s.textContent = 'El marcador está temporalmente sin actualizar.'; })
      .then(function () { clearTimeout(to); busy = false; });
  }
  function schedule() { clearInterval(timer); if (!document.hidden && !SAMPLE) timer = setInterval(refresh, 60000); }
  document.addEventListener('visibilitychange', function () { schedule(); if (!document.hidden) refresh(); });
  schedule();

  /* ---------- Boss: la barra de vida se llena una vez ---------- */
  once([q('[data-fr-hp]')], .5, function (el) { el.classList.add('is-in'); });

  /* ---------- Historia: las tres partes aparecen una tras otra ---------- */
  once(qa('[data-fr-beat]'), .25, function (el, k) { setTimeout(function () { el.classList.add('is-in'); }, k * 220); });

  /* ---------- Salón: las filas entran de a una y la insignia se estampa (al verse y al cambiar de página) ---------- */
  qa('[data-fr-rows]').forEach(function (list) {
    list._enter = function () {
      if (!motion) return;
      var vis = [].slice.call(list.querySelectorAll('[data-fr-item]')).filter(function (it) { return !it.hidden; });
      vis.forEach(function (it, i) { it.classList.remove('is-enter'); it.style.setProperty('--i', i); });
      void list.offsetWidth;
      vis.forEach(function (it) { it.classList.add('is-enter'); });
    };
    once([list], .2, function () { list._enter(); });
  });

  /* ---------- Paginación del Salón (alto fijo: nunca cambia al pasar de página) ---------- */
  var small = window.matchMedia ? matchMedia('(max-width:760px)') : {matches: false};
  qa('[data-fr-pager]').forEach(function (list) {
    var items = [].slice.call(list.querySelectorAll('[data-fr-item]'));
    var sizeOf = function () { return +(small.matches && list.getAttribute('data-size-sm') || list.getAttribute('data-size')) || 6; };
    var size = sizeOf(), pages = 1, page = 0, nav = document.createElement('div'); nav.className = 'fr-pager';
    nav.innerHTML = '<button type="button" class="pui-btn pui-btn--dark pui-btn--sm" data-prev>Anterior</button><span aria-live="polite"></span><button type="button" class="pui-btn pui-btn--dark pui-btn--sm" data-next>Siguiente</button>';
    var label = nav.querySelector('span'), prev = nav.querySelector('[data-prev]'), next = nav.querySelector('[data-next]');
    function show(p) { page = p; items.forEach(function (it, i) { it.hidden = pages > 1 && Math.floor(i / size) !== p; }); label.textContent = (p + 1) + ' / ' + pages; prev.disabled = p === 0; next.disabled = p === pages - 1; }
    // Reserva el alto de una página completa (la primera siempre lo es): pasar de página nunca mueve lo de abajo.
    function setup() {
      size = sizeOf(); pages = Math.ceil(items.length / size);
      if (pages < 2) { nav.remove(); list.style.minHeight = ''; items.forEach(function (it) { it.hidden = false; }); return; }
      if (!nav.parentNode) list.after(nav);
      var p = Math.min(page, pages - 1); list.style.minHeight = ''; show(0); list.style.minHeight = list.offsetHeight + 'px'; show(p);
    }
    prev.addEventListener('click', function () { if (page > 0) { show(page - 1); if (list._enter) list._enter(); } });
    next.addEventListener('click', function () { if (page < pages - 1) { show(page + 1); if (list._enter) list._enter(); } });
    setup();
    var rt; window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(setup, 120); });
  });
})();

/* Grieta de la portada: se alinea con la diagonal (de 53,6% arriba a 46,6% abajo). */
(function () {
  var s = document.querySelector('.fr-hero > .fr-hero-split'); if (!s) return;
  function fit() { var w = s.clientWidth, h = s.clientHeight, dx = .07 * w; if (!h) return;
    s.style.setProperty('--fr-ang', (Math.atan2(dx, h) * 180 / Math.PI).toFixed(2) + 'deg');
    s.style.setProperty('--fr-len', (Math.sqrt(h * h + dx * dx) * 1.06).toFixed(0) + 'px'); }
  fit(); addEventListener('resize', fit); setTimeout(fit, 300);
})();
