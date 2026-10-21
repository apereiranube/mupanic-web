/* MU PANIC — Brasas flotantes en toda la página.
   Uso: incluir este archivo con una etiqueta script (defer) al final de la página.
   Una sola capa fija sobre toda la página, no bloquea clics y se apaga si el
   usuario tiene activado "reducir movimiento". */
(function () {
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var cv = document.createElement('canvas');
  cv.className = 'mp-brasas';
  cv.setAttribute('aria-hidden', 'true');
  cv.style.cssText = 'position:fixed;inset:0;width:100%;height:100%;z-index:60;pointer-events:none;mix-blend-mode:screen';
  document.body.appendChild(cv);

  var cx = cv.getContext('2d'), P = [], W, H, N, dpr = Math.min(2, window.devicePixelRatio || 1);
  var lastY = window.scrollY, running = true;

  function size() {
    W = cv.clientWidth; H = cv.clientHeight;
    cv.width = W * dpr; cv.height = H * dpr; cx.setTransform(dpr, 0, 0, dpr, 0, 0);
    N = Math.max(22, Math.round(55 * Math.min(1, W / 1440)));
    while (P.length < N) P.push(spawn({}, true));
    P.length = N;
  }
  function spawn(p, init) {
    p.x = Math.random() * W;
    p.y = init ? Math.random() * H : H + 10;
    p.r = .6 + Math.random() * 2.2;        // tamaño
    p.vy = .25 + Math.random() * .9;       // velocidad de subida
    p.vx = (Math.random() - .5) * .35;     // deriva lateral
    p.a = .3 + Math.random() * .55;        // brillo
    p.w = Math.random() * 6.28;            // fase del vaivén
    p.d = .4 + Math.random() * .8;         // profundidad (parallax con el scroll)
    return p;
  }

  window.addEventListener('resize', size);
  window.addEventListener('scroll', function () {
    var dy = window.scrollY - lastY; lastY = window.scrollY;
    for (var i = 0; i < P.length; i++) P[i].y -= dy * .25 * P[i].d;
  }, { passive: true });
  document.addEventListener('visibilitychange', function () { running = !document.hidden; });

  size();
  (function loop() {
    requestAnimationFrame(loop);
    if (!running) return;
    cx.clearRect(0, 0, W, H);
    for (var i = 0; i < P.length; i++) {
      var p = P[i];
      p.y -= p.vy; p.w += .03; p.x += p.vx + Math.sin(p.w) * .3;
      if (p.y < -12) spawn(p);
      if (p.y > H + 12) { p.y = -10; p.x = Math.random() * W; }
      var g = cx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.r * 4);
      g.addColorStop(0, 'rgba(255,190,110,' + p.a + ')');
      g.addColorStop(.4, 'rgba(230,90,30,' + p.a * .5 + ')');
      g.addColorStop(1, 'rgba(200,40,10,0)');
      cx.fillStyle = g; cx.beginPath(); cx.arc(p.x, p.y, p.r * 4, 0, 6.29); cx.fill();
    }
  })();
})();
