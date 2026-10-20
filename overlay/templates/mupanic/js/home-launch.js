(function () {
  'use strict';
  var home = document.querySelector('[data-panic-home]');
  if (!home) return;
  var endpoint;
  try { endpoint = new URL(home.getAttribute('data-launcher-url'), location.href); }
  catch (error) { return; }
  if (endpoint.origin !== location.origin) return;
  var state = home.querySelector('[data-home-state]');
  var online = home.querySelector('[data-home-online]');
  var note = home.querySelector('[data-home-update]');
  var list = home.querySelector('[data-home-events]');
  var labels = {online: 'Online', offline: 'Offline', mantenimiento: 'Mantenimiento'};
  var busy = false;
  var timer;
  var lastSuccess = 0;
  var format = new Intl.DateTimeFormat('es-AR', {timeZone: 'America/Argentina/Buenos_Aires', day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit', hourCycle:'h23'});
  function schedule() { clearTimeout(timer); timer = setTimeout(refresh, 60000); }
  function valid(data) {
    return data && Object.prototype.hasOwnProperty.call(labels, data.servidor) &&
      Number.isSafeInteger(data.online) && data.online >= 0 && Array.isArray(data.eventos) && data.eventos.length <= 10 &&
      data.eventos.every(function (event) {
        return event && typeof event.nombre === 'string' && event.nombre.length > 0 && event.nombre.length <= 150 &&
          typeof event.proximo === 'string' && /(?:Z|[+-]\d{2}:\d{2})$/.test(event.proximo) && Number.isFinite(Date.parse(event.proximo));
      });
  }
  function show(data) {
    state.textContent = labels[data.servidor];
    state.setAttribute('data-state', data.servidor);
    online.textContent = data.online.toLocaleString('es-AR');
    note.textContent = 'Consultado ' + format.format(new Date()) + ' hs · Argentina';
    lastSuccess = Date.now();
    var fragment = document.createDocumentFragment();
    var events = data.eventos.filter(function (event) { return Date.parse(event.proximo) > Date.now(); })
      .sort(function (a, b) { return Date.parse(a.proximo) - Date.parse(b.proximo); }).slice(0, 3);
    events.forEach(function (event) {
      var row = document.createElement('li');
      var link = document.createElement('a');
      link.href = new URL('info/#eventos', endpoint.href.replace(/api\/launcher\.php$/, '')).href;
      var name = document.createElement('span'); name.textContent = event.nombre;
      var time = document.createElement('time'); time.dateTime = event.proximo; time.textContent = format.format(new Date(event.proximo)) + ' hs';
      var arrow = document.createElement('b'); arrow.setAttribute('aria-hidden', 'true'); arrow.textContent = '↗';
      link.append(name, time, arrow); row.append(link); fragment.append(row);
    });
    list.replaceChildren(fragment);
    var empty = home.querySelector('[data-home-events-empty]');
    if (!events.length && !empty) {
      empty = document.createElement('p'); empty.setAttribute('data-home-events-empty', '');
      empty.textContent = 'Consultá el calendario de eventos del servidor.';
      list.after(empty);
    }
    if (empty) empty.hidden = events.length > 0;
  }
  async function refresh() {
    if (busy) return;
    if (document.hidden || navigator.onLine === false) { schedule(); return; }
    busy = true;
    var controller = new AbortController();
    var timeout = setTimeout(function () { controller.abort(); }, 10000);
    try {
      var response = await fetch(endpoint.href, {cache:'no-store', credentials:'omit', signal:controller.signal});
      if (!response.ok) throw new Error('Unavailable');
      var data = await response.json();
      if (!valid(data)) throw new Error('Invalid payload');
      show(data);
    } catch (error) {
      state.textContent = 'Sin verificar'; state.removeAttribute('data-state');
      note.textContent = lastSuccess ? 'Sin conexión con los datos. Se muestra el último registro consultado.' : 'No se pudo verificar el estado. Conectados: último registro disponible.';
    } finally {
      clearTimeout(timeout); busy = false; schedule();
    }
  }
  document.addEventListener('visibilitychange', function () { if (!document.hidden) refresh(); });
  window.addEventListener('online', refresh);
  refresh();
})();
