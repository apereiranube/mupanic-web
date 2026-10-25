// MU PANIC · Descargas: tutorial por pasos (pestañas accesibles con teclado).
(function () {
  var guide = document.querySelector('[data-dl-guide]');
  if (!guide) return;
  var tabs = Array.prototype.slice.call(guide.querySelectorAll('[role="tab"]'));
  var panels = Array.prototype.slice.call(guide.querySelectorAll('[role="tabpanel"]'));
  function show(i, focus) {
    if (i < 0 || i >= tabs.length) return;
    tabs.forEach(function (t, k) {
      var on = k === i;
      t.setAttribute('aria-selected', on ? 'true' : 'false');
      t.tabIndex = on ? 0 : -1;
      panels[k].hidden = !on;
    });
    if (focus) tabs[i].focus();
  }
  tabs.forEach(function (t, i) {
    t.addEventListener('click', function () { show(i, false); });
    t.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') { e.preventDefault(); show((i + 1) % tabs.length, true); }
      if (e.key === 'ArrowLeft') { e.preventDefault(); show((i - 1 + tabs.length) % tabs.length, true); }
    });
  });
  guide.addEventListener('click', function (e) {
    var b = e.target.closest('[data-dl-go]');
    if (!b) return;
    var i = parseInt(b.getAttribute('data-dl-go'), 10);
    show(i, false);
    var top = guide.getBoundingClientRect().top + window.pageYOffset - 90;
    if (guide.getBoundingClientRect().top < 0) window.scrollTo({ top: top, behavior: 'smooth' });
    tabs[i].focus({ preventScroll: true });
  });
})();
