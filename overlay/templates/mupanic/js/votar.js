/* MU PANIC · Votar: copiar cuenta, cuenta regresiva en anillo y el momento de cobrar (gemas + contador + sonido). */
(function () {
  var page = document.querySelector('.vot-page');
  if (!page) return;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var fmt = function (n) { return Math.round(n).toLocaleString('es-AR'); };

  /* Copiar el nombre de cuenta */
  page.querySelectorAll('.vot-copy').forEach(function (b) {
    b.addEventListener('click', function () {
      var t = b.getAttribute('data-copy');
      var done = function () { b.textContent = 'Copiado'; setTimeout(function () { b.textContent = 'Copiar'; }, 1600); };
      if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(t).then(done, function () {});
    });
  });

  /* Barra: se llena hasta que se puede votar de nuevo */
  var sites = Array.prototype.slice.call(page.querySelectorAll('.vot-site[data-next]'));
  var start = Date.now();
  var serverNow = parseInt(page.getAttribute('data-now'), 10) || Math.floor(start / 1000);
  function tick() {
    var now = serverNow + (Date.now() - start) / 1000;
    sites.forEach(function (card) {
      var next = parseInt(card.getAttribute('data-next'), 10) || 0;
      var period = parseInt(card.getAttribute('data-period'), 10) || 43200;
      var fill = card.querySelector('.vot-bar-fill');
      if (!next) { if (fill) fill.style.width = '100%'; return; }
      var left = next - now;
      if (left <= 0) {
        card.setAttribute('data-next', '0');
        card.classList.remove('is-wait'); card.classList.add('is-ready', 'just-ready');
        var state = card.querySelector('.vot-state');
        if (state) state.innerHTML = '<span class="vot-ready">Disponible</span> · ya podés votar';
        var btn = card.querySelector('.vot-btn--claim'); if (btn) btn.disabled = false;
        if (fill) fill.style.width = '100%';
        return;
      }
      if (fill) fill.style.width = Math.max(0, Math.min(100, 100 - (left / period) * 100)) + '%';
      var c = card.querySelector('[data-count]');
      if (c) {
        var s = Math.ceil(left), h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), x = s % 60;
        c.textContent = (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m + ':' + (x < 10 ? '0' : '') + x;
      }
    });
  }
  tick(); setInterval(tick, 1000);

  /* El momento de cobrar */
  var claimed = parseInt(page.getAttribute('data-claimed'), 10) || 0;
  if (!claimed) return;
  var alert = page.querySelector('.vot-alert--ok'); if (alert) alert.hidden = true;
  var toast = page.querySelector('.vot-toast');
  var earnedBox = document.getElementById('vot-earned');
  var counter = earnedBox ? earnedBox.querySelector('[data-earned]') : null;
  var from = counter ? parseInt(counter.getAttribute('data-from'), 10) || 0 : 0;
  var to = counter ? parseInt(counter.getAttribute('data-earned'), 10) || 0 : 0;

  function showToast() {
    if (!toast) return;
    var tc = toast.querySelector('[data-toast-coins]'); if (tc) tc.textContent = fmt(claimed);
    toast.hidden = false; requestAnimationFrame(function () { toast.classList.add('is-on'); });
    setTimeout(function () { toast.classList.remove('is-on'); setTimeout(function () { toast.hidden = true; }, 400); }, 5200);
  }
  function rollCounter() {
    if (!counter) return;
    if (reduce) { counter.textContent = fmt(to); return; }
    var t0 = performance.now(), dur = 900;
    (function step(t) {
      var k = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - k, 3);
      counter.textContent = fmt(from + (to - from) * e);
      if (k < 1) requestAnimationFrame(step);
    })(t0);
    earnedBox.classList.add('is-pulse');
    page.querySelector('.vot-hero').classList.add('is-pulse');
    setTimeout(function () { earnedBox.classList.remove('is-pulse'); }, 1400);
  }
  function chime() {
    try {
      var AC = window.AudioContext || window.webkitAudioContext; if (!AC) return;
      var ctx = new AC(), now = ctx.currentTime;
      [1318.5, 1760, 2637].forEach(function (f, i) {
        var o = ctx.createOscillator(), g = ctx.createGain();
        o.type = 'sine'; o.frequency.value = f;
        g.gain.setValueAtTime(0.0001, now + i * 0.07);
        g.gain.exponentialRampToValueAtTime(0.08, now + i * 0.07 + 0.02);
        g.gain.exponentialRampToValueAtTime(0.0001, now + i * 0.07 + 0.6);
        o.connect(g).connect(ctx.destination); o.start(now + i * 0.07); o.stop(now + i * 0.07 + 0.65);
      });
    } catch (e) {}
  }

  if (reduce || !earnedBox || !document.body.animate) { rollCounter(); showToast(); return; }

  window.scrollTo(0, 0);
  setTimeout(function () {
    var site = page.getAttribute('data-claimed-site');
    var srcEl = page.querySelector('.vot-site[data-site="' + site + '"] .vot-btn--claim');
    var r = srcEl ? srcEl.getBoundingClientRect() : null;
    var vh = window.innerHeight, vw = window.innerWidth;
    var sx = r && r.top < vh && r.bottom > 0 ? r.left + r.width / 2 : vw / 2;
    var sy = r && r.top < vh && r.bottom > 0 ? r.top + r.height / 2 : vh - 40;
    var tr = earnedBox.querySelector('img').getBoundingClientRect();
    var tx = tr.left + tr.width / 2, ty = tr.top + tr.height / 2;
    var gemSrc = page.getAttribute('data-gem');
    var n = 18, landed = 0;
    for (var i = 0; i < n; i++) {
      (function (i) {
        var g = document.createElement('img');
        g.src = gemSrc; g.alt = ''; g.className = 'vot-flygem';
        var size = 20 + Math.random() * 18;
        g.style.width = size + 'px'; g.style.height = 'auto';
        g.style.left = (sx - size / 2) + 'px'; g.style.top = (sy - size / 2) + 'px';
        document.body.appendChild(g);
        var ang = Math.random() * Math.PI * 2, burst = 60 + Math.random() * 90;
        var bx = Math.cos(ang) * burst, by = Math.sin(ang) * burst - 60;
        var dx = tx - sx, dy = ty - sy;
        var anim = g.animate([
          { transform: 'translate(0,0) scale(.4) rotate(0deg)', opacity: 0 },
          { transform: 'translate(' + bx + 'px,' + by + 'px) scale(1.1) rotate(' + (Math.random() * 180 - 90) + 'deg)', opacity: 1, offset: .32 },
          { transform: 'translate(' + (dx * .55 + bx * .4) + 'px,' + (dy * .55 + by * .2 - 80) + 'px) scale(.9) rotate(140deg)', opacity: 1, offset: .7 },
          { transform: 'translate(' + dx + 'px,' + dy + 'px) scale(.35) rotate(260deg)', opacity: .2 }
        ], { duration: 1150 + Math.random() * 350, delay: i * 35, easing: 'cubic-bezier(.3,.7,.2,1)', fill: 'forwards' });
        anim.onfinish = function () {
          g.remove(); landed++;
          if (landed === 1) { rollCounter(); chime(); }
          if (landed === n) showToast();
        };
      })(i);
    }
  }, 350);
})();
