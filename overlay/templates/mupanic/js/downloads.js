// MU PANIC · Descargas: instalación jugable (cofre → carpeta → launcher → Jugar).
(function () {
  var sim = document.querySelector('[data-dl-sim]');
  if (!sim) return;
  var q = function (s) { return sim.querySelector(s); };
  var steps = Array.prototype.slice.call(sim.querySelectorAll('.dl-sim-steps li'));
  var chest = q('[data-sim-chest]'), folder = q('[data-sim-folder]'), launcher = q('[data-sim-launcher]');
  var mini = q('[data-sim-mini]'), bar = q('[data-sim-bar]'), state = q('[data-sim-state]');
  var play = q('[data-sim-play]'), done = q('[data-sim-done]'), hint = q('[data-sim-hint]'), reset = q('[data-sim-reset]');
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var timer = null;
  var hints = [
    'Hacé clic en el cofre para descomprimirlo.',
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
    hint.textContent = hints[n] || '';
    hint.hidden = !hints[n];
  }

  function start() {
    clearInterval(timer);
    chest.classList.remove('is-opened');
    chest.disabled = false;
    folder.hidden = true; mini.hidden = true; done.hidden = true;
    bar.style.width = '0%';
    state.textContent = 'Actualizando el juego';
    play.disabled = true;
    setStep(0);
  }

  chest.addEventListener('click', function () {
    chest.classList.add('is-opened');
    chest.disabled = true;
    folder.hidden = false;
    setStep(1);
    launcher.focus({ preventScroll: true });
  });

  launcher.addEventListener('click', function () {
    mini.hidden = false;
    setStep(2);
    var pct = 0;
    clearInterval(timer);
    timer = setInterval(function () {
      pct = Math.min(100, pct + (reduce ? 100 : 4 + Math.random() * 6));
      bar.style.width = pct + '%';
      if (pct >= 100) {
        clearInterval(timer);
        state.textContent = 'Todo listo para jugar';
        play.disabled = false;
        setStep(3);
        play.focus({ preventScroll: true });
      }
    }, 90);
  });

  play.addEventListener('click', function () {
    done.hidden = false;
    setStep(4);
    var link = done.querySelector('a');
    if (link) link.focus({ preventScroll: true });
  });

  reset.addEventListener('click', start);
  start();
})();
