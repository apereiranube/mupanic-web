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
