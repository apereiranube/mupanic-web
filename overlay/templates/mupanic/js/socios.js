// MU PANIC · Socios y Creadores: calculadora de ganancias, portada que sigue al mouse, cifras que cuentan,
// pasos unidos por la barra, ranking que entra y revelado al bajar. Todo pasa en cajas de tamaño fijo:
// la página no cambia de alto. Con movimiento reducido no se anima nada.
(function () {
  var page = document.getElementById('soc-page');
  if (!page) return;
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var motion = !reduce && 'IntersectionObserver' in window;
  var fmt = function (n) { return Math.round(n).toLocaleString('es-AR'); };

  /* Portada: el fondo se desplaza más lento que la página */
  var fondo = page.querySelector('[data-soc-parallax]');
  if (fondo && !reduce) {
    var ticking = false;
    addEventListener('scroll', function () {
      if (ticking) return; ticking = true;
      requestAnimationFrame(function () { ticking = false; var y = Math.min(scrollY, 700); fondo.style.transform = 'translate3d(0,' + (y * .18).toFixed(1) + 'px,0)'; });
    }, {passive: true});
  }

  /* Portada: la Elfa (o la escena de Creadores) sigue un poco al mouse (solo con mouse) */
  var hero = page.querySelector('.soc-hero'), fig = page.querySelector('[data-soc-fig]');
  if (hero && fig && !reduce && matchMedia('(hover: hover) and (pointer: fine) and (min-width: 761px)').matches) {
    var raf = 0, tx = 0, ty = 0;
    hero.addEventListener('mousemove', function (e) {
      var r = hero.getBoundingClientRect();
      tx = ((e.clientX - r.left) / r.width - .5) * 2; ty = ((e.clientY - r.top) / r.height - .5) * 2;
      if (!raf) raf = requestAnimationFrame(function () { raf = 0; fig.style.setProperty('--mx', (tx * 16).toFixed(1) + 'px'); fig.style.setProperty('--my', (ty * 9).toFixed(1) + 'px'); fig.style.setProperty('--rot', (tx * 1.2).toFixed(2) + 'deg'); });
    });
    hero.addEventListener('mouseleave', function () { fig.style.setProperty('--mx', '0px'); fig.style.setProperty('--my', '0px'); fig.style.setProperty('--rot', '0deg'); });
  }

  /* Al entrar en pantalla: pasos, ranking, reglas y cifras (una sola vez) */
  if (motion) {
    page.classList.add('soc-js');
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        e.target.classList.add('is-in'); io.unobserve(e.target);
        if (e.target.hasAttribute('data-soc-count')) countUp(e.target);
      });
    }, {rootMargin: '0px 0px -10% 0px', threshold: .2});
    page.querySelectorAll('[data-soc-steps],[data-soc-rank],[data-soc-reveal],[data-soc-count]').forEach(function (el) { io.observe(el); });
  }

  /* Cifras: cada número sube desde 0 hasta su valor; el ancho queda fijo para que nada se corra */
  function countUp(list) {
    list.querySelectorAll('[data-to]').forEach(function (el, k) {
      var to = parseInt(el.getAttribute('data-to'), 10) || 0;
      el.style.minWidth = el.offsetWidth + 'px';
      el.textContent = '0';
      var t0 = 0, dur = 900 + k * 120;
      function step(t) {
        if (!t0) t0 = t;
        var p = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.round(to * e);
        if (p < 1) requestAnimationFrame(step);
      }
      setTimeout(function () { requestAnimationFrame(step); }, k * 140);
    });
  }

  /* Calculadora */
  var box = page.querySelector('[data-soc-calc]');
  if (!box) return;
  var vip = parseInt(page.getAttribute('data-vip'), 10) || 25000;
  var inA = box.querySelector('[data-in="amigos"]');
  var inR = box.querySelector('[data-in="recarga"]');
  var out = function (k) { return box.querySelector('[data-out="' + k + '"]'); };
  var gem = box.querySelector('.soc-calc-gem');
  var totalEl = out('total');
  var tipo = 'socio';
  var MESES = 3;
  var shown = 0, target = 0, prefix = '', anim = 0, first = true;

  function fill(input) {
    var p = (input.value - input.min) / (input.max - input.min) * 100;
    input.style.setProperty('--p', p + '%');
  }

  function paint(v) { totalEl.textContent = prefix + fmt(v); }

  // El número corre suave hasta el valor nuevo (la caja tiene alto fijo)
  function animateTo(v) {
    target = v;
    cancelAnimationFrame(anim);
    if (reduce || first) { shown = v; paint(v); first = false; return; }
    var from = shown, t0 = 0, dur = 420;
    function step(t) {
      if (!t0) t0 = t;
      var p = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - p, 3);
      shown = from + (target - from) * e; paint(shown);
      if (p < 1) anim = requestAnimationFrame(step);
    }
    anim = requestAnimationFrame(step);
  }

  function pulse() {
    if (reduce || !gem || tipo !== 'socio') return;
    gem.classList.remove('is-pulse'); void gem.offsetWidth; gem.classList.add('is-pulse');
  }

  function render(changed) {
    var amigos = +inA.value, recarga = +inR.value;
    out('amigos').textContent = amigos;
    out('recarga').textContent = '$' + fmt(recarga);
    fill(inA); fill(inR);
    var base = amigos * recarga * MESES;
    if (tipo === 'socio') {
      var eryns = base * 0.05;
      var meses = eryns / vip;
      out('titulo').textContent = 'Ganás en 3 meses';
      out('unit').textContent = 'Eryns';
      out('equiv').textContent = meses >= 1
        ? 'Te alcanza para ' + (meses >= 10 ? fmt(meses) : meses.toLocaleString('es-AR', { maximumFractionDigits: 1 })) + ' meses de VIP, o para jugar, comprar en la tienda y comerciar en Vessara.'
        : 'Para usar en la tienda, en los juegos o para comerciar en Vessara. Con ' + fmt(Math.ceil(vip / (recarga * MESES * 0.05))) + ' amigos así ya te pagás el VIP.';
      box.classList.remove('is-creador');
      if (prefix !== '') { prefix = ''; shown = 0; }
      animateTo(eryns);
    } else {
      var pesos = base * 0.10;
      out('titulo').textContent = 'Cobrás en 3 meses';
      out('unit').textContent = 'En plata';
      out('equiv').textContent = 'Por transferencia, una vez por mes: unos $' + fmt(pesos / MESES) + ' por mes con ' + amigos + (amigos === 1 ? ' jugador' : ' jugadores') + ' de tu comunidad.';
      box.classList.add('is-creador');
      if (prefix !== '$') { prefix = '$'; shown = 0; }
      animateTo(pesos);
    }
    if (changed) pulse();
  }

  box.querySelectorAll('[data-tipo]').forEach(function (b) {
    b.addEventListener('click', function () {
      tipo = b.getAttribute('data-tipo');
      box.querySelectorAll('[data-tipo]').forEach(function (o) {
        var on = o === b; o.classList.toggle('is-on', on); o.setAttribute('aria-checked', on ? 'true' : 'false');
      });
      render(true);
    });
  });
  inA.addEventListener('input', function () { render(true); });
  inR.addEventListener('input', function () { render(true); });
  render(false);
})();
