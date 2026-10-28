/* MU PANIC · efectos comunes del estilo nuevo: revelado de títulos y bloques al entrar en pantalla. */
(function () {
  if (!('IntersectionObserver' in window) || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var items = document.querySelectorAll('.pui-reveal,.pui-rise,.pui-slash');
  if (!items.length) return;
  document.documentElement.classList.add('pui-reveal-js');
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
  }, {rootMargin: '0px 0px -12% 0px', threshold: .15});
  items.forEach(function (el) { io.observe(el); });
})();
