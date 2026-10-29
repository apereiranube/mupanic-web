// MU PANIC · Cuenta (estilo nuevo): el arte sigue un poco al mouse, el fondo se desplaza lento
// y las contraseñas tienen "Ver". No lee, guarda ni envía nada de lo que se escribe en el formulario.
(function () {
  var page = document.getElementById('auth-page');
  if (!page) return;
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var fine = window.matchMedia && matchMedia('(hover: hover) and (pointer: fine)').matches;

  /* Mostrar u ocultar la contraseña: solo cambia el tipo del campo; el botón no envía el formulario */
  page.querySelectorAll('.auth-card input[type="password"]').forEach(function (input) {
    var wrap = document.createElement('span');
    wrap.className = 'auth-pw';
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'auth-pw-btn';
    btn.textContent = 'Ver';
    btn.setAttribute('aria-pressed', 'false');
    btn.setAttribute('aria-label', 'Mostrar contraseña');
    btn.addEventListener('click', function () {
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.textContent = show ? 'Ocultar' : 'Ver';
      btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      btn.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
      input.focus();
    });
    wrap.appendChild(btn);
  });

  /* El código de invitación se escribe en mayúsculas (el servidor igual lo pasa a mayúsculas) */
  var ref = page.querySelector('input[name="panic_ref_code"]');
  if (ref) ref.addEventListener('input', function () {
    var s = ref.selectionStart, v = ref.value.toUpperCase();
    if (v !== ref.value) { ref.value = v; try { ref.setSelectionRange(s, s); } catch (e) {} }
  });

  if (reduce) return;
  var art = page.querySelector('[data-auth-art]');
  var fig = page.querySelector('[data-auth-fig]'), door = page.querySelector('[data-auth-door]');
  var fondo = page.querySelector('[data-auth-parallax]');
  var desk = matchMedia('(min-width: 861px)');

  /* Fondo: se desplaza más lento que la página */
  if (fondo) {
    var ticking = false, fx = 0;
    var paint = function () { var y = Math.min(scrollY, 600); fondo.style.transform = 'translate3d(' + fx.toFixed(1) + 'px,' + (y * .14).toFixed(1) + 'px,0)'; };
    addEventListener('scroll', function () {
      if (ticking) return; ticking = true;
      requestAnimationFrame(function () { ticking = false; paint(); });
    }, {passive: true});
  }

  /* El personaje (o la puerta) sigue un poco al mouse; el fondo va al revés. Solo con mouse y en escritorio. */
  if (art && fine && (fig || door || fondo)) {
    var target = fig || door, raf = 0, tx = 0, ty = 0;
    var set = function (x, y) {
      if (target) {
        target.style.setProperty('--mx', (x * (fig ? 18 : 8)).toFixed(1) + 'px');
        target.style.setProperty('--my', (y * (fig ? 10 : 6)).toFixed(1) + 'px');
        if (fig) target.style.setProperty('--rot', (x * 1.4).toFixed(2) + 'deg');
      }
      if (fondo) { fx = x * -14; paint(); }
    };
    page.addEventListener('mousemove', function (e) {
      if (!desk.matches) return;
      tx = (e.clientX / innerWidth - .5) * 2; ty = (e.clientY / innerHeight - .5) * 2;
      if (!raf) raf = requestAnimationFrame(function () { raf = 0; set(tx, ty); });
    });
    page.addEventListener('mouseleave', function () { set(0, 0); });
  }
})();
