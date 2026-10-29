// MU PANIC · Descargas: portada que sigue al mouse, revelado al bajar e instalación jugable
// (caja → carpeta → launcher → Jugar). Todo pasa dentro de cajas de tamaño fijo: la página no cambia de alto.
(function () {
  var page = document.getElementById('dl-page');
  if (!page) return;
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var motion = !reduce && 'IntersectionObserver' in window;

  /* Portada: el fondo naranja se desplaza más lento que la página */
  var fondo = page.querySelector('[data-dl-parallax]');
  if (fondo && !reduce) {
    var ticking = false;
    addEventListener('scroll', function () {
      if (ticking) return; ticking = true;
      requestAnimationFrame(function () { ticking = false; var y = Math.min(scrollY, 700); fondo.style.transform = 'translate3d(0,' + (y * .18).toFixed(1) + 'px,0)'; });
    }, {passive: true});
  }

  /* Portada: el Rage Fighter sigue un poco al mouse (solo con mouse) */
  var hero = page.querySelector('.dl-hero'), fig = page.querySelector('[data-dl-fig]');
  if (hero && fig && !reduce && matchMedia('(hover: hover) and (pointer: fine)').matches) {
    var raf = 0, tx = 0, ty = 0;
    hero.addEventListener('mousemove', function (e) {
      var r = hero.getBoundingClientRect();
      tx = ((e.clientX - r.left) / r.width - .5) * 2; ty = ((e.clientY - r.top) / r.height - .5) * 2;
      if (!raf) raf = requestAnimationFrame(function () { raf = 0; fig.style.setProperty('--mx', (tx * 16).toFixed(1) + 'px'); fig.style.setProperty('--my', (ty * 9).toFixed(1) + 'px'); fig.style.setProperty('--rot', (tx * 1.4).toFixed(2) + 'deg'); });
    });
    hero.addEventListener('mouseleave', function () { fig.style.setProperty('--mx', '0px'); fig.style.setProperty('--my', '0px'); fig.style.setProperty('--rot', '0deg'); });
  }

  /* Mirrors y ayuda: entran escalonados una sola vez */
  if (motion) {
    page.classList.add('dl-js');
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
    }, {rootMargin: '0px 0px -10% 0px', threshold: .12});
    page.querySelectorAll('[data-dl-reveal]').forEach(function (el) { io.observe(el); });
  }

  /* Instalación jugable */
  var sim = page.querySelector('[data-dl-sim]');
  if (!sim) return;
  var q = function (s) { return sim.querySelector(s); };
  var steps = Array.prototype.slice.call(sim.querySelectorAll('.dl-sim-steps li'));
  var marker = q('.dl-sim-marker');
  var chest = q('[data-sim-chest]'), folder = q('[data-sim-folder]'), launcher = q('[data-sim-launcher]');
  var mini = q('[data-sim-mini]'), bar = q('[data-sim-bar]'), state = q('[data-sim-state]');
  var play = q('[data-sim-play]'), done = q('[data-sim-done]'), hint = q('[data-sim-hint]'), reset = q('[data-sim-reset]');
  var timer = null, wait = null;
  var hints = [
    'Hacé clic en la caja para descomprimirla.',
    'Ahora abrí MU PANIC Launcher.',
    'El launcher está revisando y bajando lo que falta…',
    '¡Todo listo! Apretá Jugar.',
    ''
  ];

  function setStep(n) {
    sim.setAttribute('data-step', n);
    steps.forEach(function (li, i) {
      li.classList.toggle('is-current', i === n);
      li.classList.toggle('is-done', i < n);
    });
    cur = n; placeMarker();
    hint.textContent = hints[n] || '';
  }

  var cur = 0;
  function placeMarker() {
    if (!marker) return;
    var li = steps[Math.min(cur, steps.length - 1)];
    marker.style.height = li.offsetHeight + 'px';
    marker.style.transform = 'translateY(' + li.offsetTop + 'px)';
  }
  addEventListener('resize', placeMarker);
  addEventListener('load', placeMarker);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(placeMarker);

  function setBar(pct) { bar.style.transform = 'scaleX(' + (pct / 100).toFixed(3) + ')'; }

  function start() {
    clearInterval(timer); clearTimeout(wait);
    chest.classList.remove('is-opened', 'is-shaking');
    chest.disabled = false;
    folder.hidden = true; mini.hidden = true; done.hidden = true;
    setBar(0);
    state.textContent = 'Actualizando el juego';
    play.disabled = true;
    setStep(0);
  }

  function openChest() {
    chest.classList.remove('is-shaking');
    chest.classList.add('is-opened');
    folder.hidden = false;
    setStep(1);
    launcher.focus({preventScroll: true});
  }

  chest.addEventListener('click', function () {
    if (chest.disabled) return;
    chest.disabled = true;
    if (reduce) return openChest();
    chest.classList.add('is-shaking');
    wait = setTimeout(openChest, 520);
  });

  launcher.addEventListener('click', function () {
    mini.hidden = false;
    setStep(2);
    var pct = 0;
    clearInterval(timer);
    timer = setInterval(function () {
      pct = Math.min(100, pct + (reduce ? 100 : 4 + Math.random() * 6));
      setBar(pct);
      if (pct >= 100) {
        clearInterval(timer);
        state.textContent = 'Todo listo para jugar';
        play.disabled = false;
        setStep(3);
        play.focus({preventScroll: true});
      }
    }, 90);
  });

  play.addEventListener('click', function () {
    done.hidden = false;
    setStep(4);
    var link = done.querySelector('a');
    if (link) link.focus({preventScroll: true});
  });

  reset.addEventListener('click', function () { start(); chest.focus({preventScroll: true}); });
  start();
})();
