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
