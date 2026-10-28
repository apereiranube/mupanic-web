/* MU PANIC · Inicio (estilo nuevo): estado del servidor, online, rates y próximos eventos en vivo. */
(function () {
  'use strict';
  var home = document.querySelector('[data-panic-home]');
  if (!home) return;
  var endpoint;
  try { endpoint = new URL(home.getAttribute('data-launcher-url'), location.href); } catch (e) { return; }
  if (endpoint.origin !== location.origin) return;
  var state = home.querySelector('[data-home-state]');
  var online = home.querySelector('[data-home-online]');
  var list = home.querySelector('[data-home-events]');
  var live = home.querySelector('.h2-live');
  var labels = {online: 'Online', offline: 'Offline', mantenimiento: 'Mantenimiento'};
  var busy = false, timer;
  var format = new Intl.DateTimeFormat('es-AR', {timeZone: 'America/Argentina/Buenos_Aires', day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23'});
  function schedule() { clearTimeout(timer); timer = setTimeout(refresh, 30000); }
  function valid(d) {
    return d && Object.prototype.hasOwnProperty.call(labels, d.servidor) && Number.isSafeInteger(d.online) && d.online >= 0 &&
      Array.isArray(d.eventos) && d.eventos.length <= 10 && d.eventos.every(function (ev) {
        return ev && typeof ev.nombre === 'string' && ev.nombre.length > 0 && ev.nombre.length <= 150 &&
          typeof ev.proximo === 'string' && /(?:Z|[+-]\d{2}:\d{2})$/.test(ev.proximo) && Number.isFinite(Date.parse(ev.proximo));
      });
  }
  function show(d) {
    state.textContent = labels[d.servidor];
    state.setAttribute('data-state', d.servidor);
    live.dataset.serverState = d.servidor;
    var value = d.online.toLocaleString('es-AR');
    if (online.textContent !== value) {
      online.textContent = value;
      if (online.animate && !matchMedia('(prefers-reduced-motion: reduce)').matches) online.animate([{transform: 'translateY(6px)', opacity: .3}, {transform: 'none', opacity: 1}], {duration: 600, easing: 'cubic-bezier(.2,.8,.2,1)'});
    }
    if (d.rates && ['experience', 'master', 'drop'].every(function (k) { return typeof d.rates[k] === 'number' && Number.isFinite(d.rates[k]) && d.rates[k] >= 0; })) {
      ['experience', 'master', 'drop'].forEach(function (k) {
        var el = home.querySelector('[data-home-rate="' + k + '"]');
        if (el) el.textContent = d.rates[k].toLocaleString('es-AR') + (k === 'drop' ? '%' : 'x');
      });
    }
    var events = d.eventos.filter(function (ev) { return Date.parse(ev.proximo) > Date.now(); })
      .sort(function (a, b) { return Date.parse(a.proximo) - Date.parse(b.proximo); }).slice(0, 3);
    var frag = document.createDocumentFragment();
    events.forEach(function (ev) {
      var li = document.createElement('li'), a = document.createElement('a');
      a.href = new URL('../info/#eventos', endpoint).href;
      var name = document.createElement('span'); name.textContent = ev.nombre;
      var time = document.createElement('time'); time.dateTime = ev.proximo; time.textContent = format.format(new Date(ev.proximo)) + ' hs';
      a.append(name, time); li.append(a); frag.append(li);
    });
    list.replaceChildren(frag);
    var empty = home.querySelector('[data-home-events-empty]');
    if (!events.length && !empty) {
      empty = document.createElement('p'); empty.className = 'h2-events-empty'; empty.setAttribute('data-home-events-empty', '');
      empty.textContent = 'Consultá el calendario de eventos.'; list.after(empty);
    }
    if (empty) empty.hidden = events.length > 0;
    tick();
  }
  function tick() {
    var first = list.querySelector('li:first-child a');
    if (!first) return;
    var time = first.querySelector('time');
    var left = Math.max(0, Math.ceil((Date.parse(time.dateTime) - Date.now()) / 1000));
    var cd = first.querySelector('[data-event-countdown]');
    if (!cd) { cd = document.createElement('strong'); cd.setAttribute('data-event-countdown', ''); first.append(cd); }
    var h = Math.floor(left / 3600), m = Math.floor(left % 3600 / 60), s = left % 60;
    cd.textContent = h > 24 ? 'En ' + Math.floor(h / 24) + ' días' : 'En ' + [h, m, s].map(function (n) { return String(n).padStart(2, '0'); }).join(':');
  }
  tick();
  setInterval(function () { if (!document.hidden) tick(); }, 1000);
  async function refresh() {
    if (busy) return;
    if (document.hidden || navigator.onLine === false) { schedule(); return; }
    busy = true;
    var ctl = new AbortController(), to = setTimeout(function () { ctl.abort(); }, 10000);
    try {
      var r = await fetch(endpoint.href, {cache: 'no-store', credentials: 'omit', signal: ctl.signal});
      if (!r.ok) throw new Error('Unavailable');
      var d = await r.json();
      if (!valid(d)) throw new Error('Invalid');
      show(d);
    } catch (e) {
      state.textContent = 'Sin verificar'; state.removeAttribute('data-state'); online.textContent = '—'; live.dataset.serverState = 'unknown';
    } finally { clearTimeout(to); busy = false; schedule(); }
  }
  document.addEventListener('visibilitychange', function () { if (!document.hidden) refresh(); });
  window.addEventListener('online', refresh);
  refresh();
})();
/* Hub F11: la tecla abre la ventana, las pestañas cambian el contenido, y se aprieta sola una vez al llegar */
(function () {
  var hub = document.querySelector('[data-hub]');
  if (!hub) return;
  var key = hub.querySelector('[data-hub-key]');
  var tabs = Array.prototype.slice.call(hub.querySelectorAll('[data-hub-tab]'));
  function press(open) {
    key.classList.add('is-pressing');
    setTimeout(function () { key.classList.remove('is-pressing'); }, 160);
    var o = open === undefined ? !hub.classList.contains('is-open') : open;
    hub.classList.toggle('is-open', o);
    key.setAttribute('aria-expanded', o ? 'true' : 'false');
  }
  key.addEventListener('click', function () { press(); });
  tabs.forEach(function (t) {
    t.addEventListener('click', function () {
      tabs.forEach(function (x) {
        var on = x === t; x.setAttribute('aria-selected', on ? 'true' : 'false');
        var pane = hub.querySelector('#' + x.getAttribute('aria-controls')); if (pane) pane.hidden = !on;
      });
    });
  });
  // Cuenta regresiva del primer evento de la ventana
  var list = hub.querySelector('[data-hub-events]');
  function tick() {
    if (!list) return;
    var first = list.querySelector('li'); if (!first) return;
    var t = first.querySelector('time'); var left = Math.max(0, Math.ceil((Date.parse(t.dateTime) - Date.now()) / 1000));
    var cd = first.querySelector('strong'); if (!cd) { cd = document.createElement('strong'); first.append(cd); }
    var h = Math.floor(left / 3600), m = Math.floor(left % 3600 / 60), s = left % 60;
    cd.textContent = h > 24 ? 'En ' + Math.floor(h / 24) + ' días' : 'En ' + [h, m, s].map(function (n) { return String(n).padStart(2, '0'); }).join(':');
  }
  tick(); setInterval(function () { if (!document.hidden) tick(); }, 1000);
  var src = document.querySelector('[data-home-events]');
  if (src && list && 'MutationObserver' in window) {
    new MutationObserver(function () {
      var frag = document.createDocumentFragment();
      src.querySelectorAll('li').forEach(function (li) {
        var a = li.querySelector('span'), t = li.querySelector('time'); if (!a || !t) return;
        var n = document.createElement('li'), s1 = document.createElement('span'), t1 = document.createElement('time');
        s1.textContent = a.textContent; t1.dateTime = t.dateTime; t1.textContent = t.textContent; n.append(s1, t1); frag.append(n);
      });
      if (frag.childNodes.length) { list.replaceChildren(frag); tick(); }
    }).observe(src, {childList: true});
  }
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) {
      if (es[0].isIntersecting) { io.disconnect(); setTimeout(function () { if (!hub.classList.contains('is-open')) press(true); }, 700); }
    }, {threshold: .45});
    io.observe(hub);
  } else press(true);
})();
/* Furia: carga → Shift → combos hasta MASACRE → se vacía y vuelve a cargar */
(function () {
  var f = document.querySelector('[data-furia]'); if (!f) return;
  var bar = f.querySelector('[data-fbar]'), pct = f.querySelector('[data-fpct]'), key = f.querySelector('[data-fkey]'), keyText = f.querySelector('[data-fkey-text]');
  var combo = f.querySelector('[data-combo]'), cn = f.querySelector('[data-combo-n]'), cl = f.querySelector('[data-combo-l]');
  var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  var state = 'idle', val = 0, raf = 0, visible = false, timers = [];
  function setBar(v) { val = Math.max(0, Math.min(100, v)); bar.style.width = val + '%'; pct.textContent = Math.round(val) + '%'; }
  function phase(n) { f.setAttribute('data-fphase', n); }
  function charge() {
    state = 'charging'; phase(1); f.classList.remove('is-full', 'is-furia', 'is-masacre');
    key.disabled = true; keyText.textContent = 'Cargando…';
    var t0 = performance.now(), from = val, dur = reduce ? 1 : 2600;
    cancelAnimationFrame(raf);
    (function step(t) { var k = Math.min(1, (t - t0) / dur); setBar(from + (100 - from) * k); if (k < 1) raf = requestAnimationFrame(step); else ready(); })(t0);
  }
  function ready() { state = 'ready'; phase(2); f.classList.add('is-full'); key.disabled = false; keyText.textContent = 'Furia lista: apretá'; }
  function unleash() {
    if (state !== 'ready') return;
    state = 'furia'; phase(3); f.classList.remove('is-full'); f.classList.add('is-furia');
    if (!reduce) { f.classList.add('is-shake'); timers.push(setTimeout(function () { f.classList.remove('is-shake'); }, 460)); }
    key.disabled = true; keyText.textContent = '¡Desatada!';
    var n = 0, total = 20;
    function hit() {
      n++; cn.textContent = 'x' + n;
      cl.textContent = n >= 20 ? 'Masacre' : n >= 10 ? 'Imparable' : 'Combo';
      if (n >= 20) f.classList.add('is-masacre');
      combo.classList.remove('is-bump'); void combo.offsetWidth; combo.classList.add('is-bump');
      setBar(100 - n * 2.5);
      if (n < total) timers.push(setTimeout(hit, reduce ? 0 : (n < 10 ? 170 : 120)));
      else timers.push(setTimeout(drain, 1600));
    }
    timers.push(setTimeout(hit, 250));
  }
  function drain() {
    state = 'drain'; var t0 = performance.now(), from = val, dur = reduce ? 1 : 1400;
    (function step(t) { var k = Math.min(1, (t - t0) / dur); setBar(from * (1 - k)); if (k < 1) raf = requestAnimationFrame(step); else { f.classList.remove('is-furia', 'is-masacre'); cn.textContent = 'x0'; timers.push(setTimeout(function () { if (visible) charge(); else { state = 'idle'; } }, 900)); } })(t0);
  }
  key.addEventListener('click', unleash);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Shift' && visible && state === 'ready' && !e.repeat) { var tag = (document.activeElement || {}).tagName; if (tag !== 'INPUT' && tag !== 'TEXTAREA') unleash(); }
  });
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (es) { visible = es[0].isIntersecting; if (visible && state === 'idle') charge(); }, {threshold: .4}).observe(f);
  } else { visible = true; charge(); }
})();
/* Al bajar, el BK se acerca a la cámara (crece y se sale del cuadro) */
(function () {
  var hero = document.querySelector('[data-parallax]');
  if (!hero || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var ticking = false;
  function upd() { ticking = false; var r = hero.getBoundingClientRect(); var k = Math.max(0, Math.min(1, -r.top / (r.height * .8))); hero.style.setProperty('--sc', k.toFixed(3)); }
  addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(upd); } }, {passive: true});
  upd();
})();
