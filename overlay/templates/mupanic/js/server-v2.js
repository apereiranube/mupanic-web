/* MU PANIC · El servidor (estilo nuevo): reloj de eventos, simulador de resets y demos. */
(function () {
  'use strict';
  var ROOT = document.getElementById('sv-page');
  if (!ROOT) return;
  var $ = function (id) { return document.getElementById(id); };
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var pad = function (n) { return String(n).padStart(2, '0'); };
  var fmt = function (n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
  function watch(el, cb, th) {
    if (!el) return;
    if (!('IntersectionObserver' in window)) { cb(true); return; }
    new IntersectionObserver(function (e) { cb(e[0].isIntersecting); }, {threshold: th || .3}).observe(el);
  }

  /* Eventos en vivo: mismos datos que el Atlas, horarios de Argentina (UTC-3, sin horario de verano). */
  (function () {
    var EVENTS = [];
    try { EVENTS = JSON.parse($('sv-events').textContent) || []; } catch (e) {}
    var GN = {classic: 'Clásico', invasion: 'Invasión', custom: 'Competencia'};
    var filter = 'all';
    function nowArt() { var d = new Date(Date.now() - 3 * 3600e3); return d.getUTCHours() * 60 + d.getUTCMinutes() + d.getUTCSeconds() / 60; }
    function toMin(s) { var p = s.split(':'); return +p[0] * 60 + +p[1]; }
    function rel(m) { m = Math.max(0, Math.round(m)); if (m < 1) return 'ahora'; var h = Math.floor(m / 60), r = m % 60; return ('en ' + (h ? h + ' h ' + (r ? r + ' min' : '') : r + ' min')).trim(); }
    function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[c]; }); }
    function occurrences(now) {
      var out = [];
      EVENTS.forEach(function (e) { e.t.forEach(function (t) { var s = toMin(t);
        [0, 1440].forEach(function (off) { var start = s + off; if (start + e.d > now && start < now + 1440) out.push({e: e, start: start, end: start + e.d, t: t, tom: off > 0}); });
      }); });
      return out.sort(function (a, b) { return a.start - b.start; });
    }
    var more = false, curKey = '', pick = '';
    function hm(m) { m = ((m % 1440) + 1440) % 1440; return pad(Math.floor(m / 60)) + ':' + pad(Math.floor(m % 60)); }
    function render() {
      var now = nowArt(), occ = occurrences(now), nx = occ[0], af = occ[1];
      if (nx) {
        var live = nx.start <= now, target = live ? nx.end : nx.start, left = Math.max(0, (target - now) * 60);
        var card = $('svNext'), key = nx.e.n + nx.start;
        if (key !== curKey) {
          if (curKey && !reduce) { card.classList.remove('swap'); void card.offsetWidth; card.classList.add('swap'); }
          curKey = key; card.dataset.g = nx.e.g; card.dataset.ev = nx.e.n;
          var art = $('svNxArt'), src = (window.SV_EVART || {})[nx.e.n] || (window.SV_EVART || {})['*'] || '';
          art.style.backgroundImage = src ? 'url("' + src + '")' : ''; card.classList.toggle('has-art', !!src);
        }
        var st = $('svNxState'); st.classList.toggle('live', live); st.lastElementChild.textContent = live ? 'En curso ahora' : 'Próximo evento';
        $('svNxName').textContent = nx.e.n;
        $('svNxWhen').textContent = (live ? 'Termina a las ' + hm(nx.end) : 'Empieza a las ' + nx.t) + ' · ' + GN[nx.e.g] + ' · dura ' + nx.e.d + ' min';
        [['svCh', Math.floor(left / 3600)], ['svCm', Math.floor(left % 3600 / 60)], ['svCs', Math.floor(left % 60)]].forEach(function (x) {
          var el = $(x[0]), v = pad(x[1]);
          if (el.textContent !== v) { el.textContent = v; if (!reduce) { el.classList.remove('tick'); void el.offsetWidth; el.classList.add('tick'); } }
        });
        if (af) { $('svAfName').textContent = af.e.n; $('svAfWhen').textContent = af.t + ' · ' + GN[af.e.g]; $('svAfRel').textContent = rel(af.start - now); $('svAfter').hidden = false; } else $('svAfter').hidden = true;
      }
      var all = occ.filter(function (o) { return filter === 'all' || o.e.g === filter; }), rows = more ? all : all.slice(0, 5);
      $('svEvList').innerHTML = rows.length ? rows.map(function (o) {
        var live = o.start <= now, k = o.e.n + '|' + o.t;
        return '<li class="g-' + o.e.g + (live ? ' live' : '') + (o.tom ? ' tomorrow' : '') + (k === pick ? ' pick' : '') + '" data-k="' + esc(k) + '"><time>' + o.t + '</time><span class="n">' + esc(o.e.n) + '<span class="g">' + GN[o.e.g] + (o.tom ? ' · mañana' : '') + '</span></span><span class="rel">' + (live ? 'En curso' : rel(o.start - now)) + '</span></li>';
      }).join('') : '<li class="empty">No hay eventos de esta categoría en las próximas 24 horas.</li>';
      var mb = $('svEvMore'); mb.hidden = all.length <= 5; mb.textContent = more ? 'Ver menos' : 'Ver los ' + all.length + ' de las próximas 24 horas'; mb.setAttribute('aria-expanded', more);
    }
    $('svEvMore').addEventListener('click', function () { more = !more; render(); });
    function ruler() {
      var now = nowArt(), h = '<div class="track"></div>';
      for (var i = 0; i <= 24; i += 3) h += '<span class="tick" style="left:' + (i / 24 * 100) + '%">' + pad(i) + '</span>';
      EVENTS.forEach(function (e) { e.t.forEach(function (t) { var m = toMin(t), k = e.n + '|' + t;
        h += '<button type="button" class="ev g-' + e.g + (m + e.d < now ? ' done' : '') + (m <= now && m + e.d > now ? ' live' : '') + (m > now && m - now <= 60 ? ' soon' : '') + '" style="left:' + (m / 1440 * 100) + '%;width:max(6px,' + (e.d / 1440 * 100) + '%)" data-k="' + esc(k) + '" data-g="' + e.g + '" aria-label="' + esc(e.n + ' a las ' + t) + '"><span class="tip"><b>' + t + '</b> ' + esc(e.n) + '</span></button>';
      }); });
      h += '<span class="now" style="left:' + (now / 1440 * 100) + '%"></span>';
      $('svRuler').innerHTML = h;
      var day = $('svRuler').parentNode; if (day.scrollWidth > day.clientWidth && !ruler._done) { ruler._done = true; day.scrollLeft = Math.max(0, $('svRuler').offsetWidth * now / 1440 - day.clientWidth / 3); }
    }
    $('svRuler').addEventListener('click', function (ev) {
      var b = ev.target.closest('.ev'); if (!b) return;
      pick = b.dataset.k; filter = 'all'; more = true;
      tabs.forEach(function (x) { var on = x.dataset.g === 'all'; x.setAttribute('aria-pressed', on); x.classList.toggle('is-active', on); });
      render();
      var li = $('svEvList').querySelector('.pick');
      if (li) { var r = li.getBoundingClientRect(); if (r.top < 80 || r.bottom > innerHeight) li.scrollIntoView({block: 'center', behavior: reduce ? 'auto' : 'smooth'}); }
      clearTimeout(ruler._t); ruler._t = setTimeout(function () { pick = ''; render(); }, 4000);
    });
    var tabs = [].slice.call(ROOT.querySelectorAll('.sv-agenda [data-g]'));
    tabs.forEach(function (b) { b.addEventListener('click', function () {
      filter = b.dataset.g; more = false;
      tabs.forEach(function (x) { x.setAttribute('aria-pressed', x === b); x.classList.toggle('is-active', x === b); });
      render();
    }); });
    render(); ruler(); setInterval(render, 1000); setInterval(ruler, 20000);
  })();

  /* Simulador de resets (200/250/300/350 puntos por etapa). */
  (function () {
    var r = $('svSimR'), out = $('svSimOut'), pts = $('svSimPts'), nxt = $('svSimNext'), lis = [].slice.call($('svLadder').children), tws = [].slice.call($('svTowers').children), shown = 0, raf, auto = !reduce, played = false;
    var LV = 1995, LV2 = 2793;
    function per(n) { return n <= 4 ? 200 : n <= 9 ? 250 : n <= 14 ? 300 : 350; }
    function total(n) { var t = 0; for (var i = 1; i <= n; i++) t += per(i); return t; }
    function animTo(v) { cancelAnimationFrame(raf); var from = shown, t0 = null; (function st(t) { t0 = t0 || t; var k = Math.min(1, (t - t0) / 350); shown = Math.round(from + (v - from) * k); pts.textContent = fmt(shown); if (k < 1) raf = requestAnimationFrame(st); })(performance.now()); }
    function upd() {
      var n = +r.value; r.style.setProperty('--p', (n / 20 * 100) + '%'); out.firstChild.nodeValue = n;
      var st = n === 0 ? -1 : (n <= 4 ? 0 : n <= 9 ? 1 : n <= 14 ? 2 : 3), nx = n >= 20 ? 3 : (n + 1 <= 4 ? 0 : n + 1 <= 9 ? 1 : n + 1 <= 14 ? 2 : 3);
      lis.forEach(function (li, i) { li.classList.toggle('reached', i <= st); li.classList.toggle('now', i === nx); }); tws.forEach(function (tw, i) { tw.classList.toggle('on', i <= st); tw.classList.toggle('now', i === nx && n < 20); });
      if (n >= 20) { nxt.textContent = 'Máximo'; nxt.classList.add('max'); } else { nxt.textContent = '+' + per(n + 1); nxt.classList.remove('max'); }
      $('svSimTot').textContent = fmt(total(n) + LV); $('svSimTot2').textContent = fmt(total(n) + LV2);
      if (reduce) { shown = total(n); pts.textContent = fmt(shown); } else animTo(total(n));
    }
    r.addEventListener('input', function () { auto = false; upd(); });
    function demo() { if (played) return; played = true; var n = 0; var id = setInterval(function () { if (!auto) { clearInterval(id); return; } n++; r.value = n; upd(); if (n >= 20) { clearInterval(id); setTimeout(function () { if (!auto) return; r.value = 7; upd(); }, 1400); } }, 140); }
    upd();
    if (auto && 'IntersectionObserver' in window) new IntersectionObserver(function (e, o) { if (e[0].isIntersecting) { o.disconnect(); setTimeout(demo, 500); } }, {threshold: .6}).observe($('svSim'));
  })();

  /* PvP: la barra del cliente, paso a paso (vida + SD pegado + destello de daño + latido < 30%). */
  (function () {
    var LM = 100, SM = 40, T = LM + SM, L = LM, Sd = SM, lostR = 0, lostOn = false, tA;
    var life = $('svLife'), sd = $('svSd'), lost = $('svLost'), co = $('svCo'), coT = $('svCoT'), fl = $('svFloat');
    function pct(v) { return v / T * 100; }
    function draw() { life.style.width = pct(L) + '%'; sd.style.left = pct(L) + '%'; sd.style.width = pct(Sd) + '%';
      var f = L / LM, c = life.classList; c.remove('lime', 'warn', 'crit'); if (f < .3) c.add('crit'); else if (f < .45) c.add('warn'); else if (f < .65) c.add('lime'); }
    function set(l, s) { L = l; Sd = s; lost.style.transition = 'none'; lost.style.width = '0'; lostOn = false; draw(); }
    function num(n) { return '−' + fmt(Math.round(n)); }
    function floater(txt, cls, at) { var d = document.createElement('span'); d.className = 'dmg' + (cls ? ' ' + cls : ''); d.textContent = txt; d.style.left = Math.max(8, Math.min(92, at + (Math.random() - .5) * 10)) + '%'; fl.appendChild(d); setTimeout(function () { d.remove(); }, 1000); }
    function hit(dmg) {
      var before = pct(L + Sd), toSd = Math.min(Sd, dmg * .9), wasSd = toSd > 0; Sd -= toSd; L = Math.max(0, L - (dmg - toSd)); draw();
      var after = pct(L + Sd); lostR = lostOn ? Math.max(lostR, before) : before; lostOn = true;
      lost.style.transition = 'none'; lost.style.left = after + '%'; lost.style.width = (lostR - after) + '%'; lost.style.background = '#fff'; void lost.offsetWidth;
      lost.style.transition = 'background .3s ease'; lost.style.background = '#8a1a14';
      clearTimeout(tA); tA = setTimeout(function () { lost.style.transition = 'width .4s ease'; lost.style.width = '0'; lostOn = false; }, 650);
      floater(num(dmg * 118), wasSd ? 'sd' : '', (after + before) / 2);
    }
    function potion(a) { sd.classList.remove('fill'); void sd.offsetWidth; sd.classList.add('fill'); Sd = Math.min(SM, Sd + a); draw(); floater('+ Escudo', 'heal', pct(L + Sd / 2)); }
    function callout(part, text) { coT.textContent = text; var c;
      if (part === 'life') c = pct(L) / 2; else if (part === 'sd') c = pct(L) + pct(Sd) / 2; else if (part === 'lost') c = pct(L + Sd) + 4; else c = 50;
      co.style.left = Math.max(10, Math.min(90, c)) + '%'; co.classList.add('show'); }
    function hide() { co.classList.remove('show'); }
    var STEPS = [
      {s: [100, 40], d: 4200, a: [[300, function () { callout('life', 'Tu vida'); }], [2100, function () { callout('sd', 'Tu escudo (SD)'); }]]},
      {s: [100, 40], d: 4400, a: [[200, function () { callout('sd', 'El SD absorbe el golpe'); }], [600, function () { hit(10); }], [1600, function () { hit(12); }], [2600, function () { hit(11); }]]},
      {s: [97, 11], d: 4400, a: [[400, function () { hit(13); }], [900, function () { callout('life', 'Sin escudo: directo a la vida'); }], [1500, function () { hit(14); }], [2500, function () { hit(12); }]]},
      {s: [69, 0], d: 4000, a: [[500, function () { hit(6); }], [680, function () { hit(6.6); }], [860, function () { hit(5.4); }], [900, function () { callout('lost', 'El daño queda marcado'); }]]},
      {s: [51, 0], d: 4600, a: [[400, function () { hit(16); }], [1500, function () { hit(13); }], [1800, function () { callout('life', 'Menos del 30%: peligro'); }]]},
      {s: [22, 0], d: 4800, a: [[600, function () { potion(16); }], [800, function () { callout('sd', 'Poción de escudo'); }]]}
    ];
    var btns = [].slice.call(document.querySelectorAll('#svPvpSteps button')), cur = 0, timers = [], autoP = !reduce, vis = false, pending = false;
    function clear() { timers.forEach(clearTimeout); timers = []; }
    function play(i) { clear(); hide(); cur = i; var st = STEPS[i]; set(st.s[0], st.s[1]);
      btns.forEach(function (b, k) { var li = b.parentElement, pg = b.querySelector('.pg'); li.classList.toggle('on', k === i); b.setAttribute('aria-pressed', k === i); pg.classList.remove('run'); if (k === i && !reduce) { pg.style.setProperty('--d', st.d + 'ms'); void pg.offsetWidth; pg.classList.add('run'); } });
      if (reduce) { st.a.forEach(function (a) { a[1](); }); return; }
      st.a.forEach(function (a) { timers.push(setTimeout(a[1], a[0])); });
      timers.push(setTimeout(function () { if (autoP && vis) play((i + 1) % STEPS.length); else if (autoP) pending = true; }, st.d));
    }
    btns.forEach(function (b, k) { b.addEventListener('click', function () { autoP = false; play(k); }); });
    watch($('svPvpSteps').parentElement, function (v) { vis = v; if (vis && pending) { pending = false; play((cur + 1) % STEPS.length); } }, .35);
    play(0);
  })();

  /* Cliente protegido: el launcher verifica, detecta la trampa y bloquea JUGAR. */
  (function () {
    var G = $('svGuard'), pill = $('svGuardPill'), toast = $('svGuardToast'), t = $('svGuardT'), s = $('svGuardS'), bar = $('svGuardBar'), play = $('svGuardPlay'), timers = [], vis = false, started = false, pending = false;
    function T(ms, fn) { timers.push(setTimeout(fn, ms)); }
    function st(cls, title, sub, w) { G.classList.remove('bad', 'lock'); (cls || '').split(' ').forEach(function (c) { if (c) G.classList.add(c); }); t.textContent = title; s.textContent = sub; if (w != null) bar.style.width = w + '%'; play.textContent = G.classList.contains('lock') ? 'Bloqueado' : 'Jugar'; }
    function guard(c, html) { pill.className = 'sv-guard-pill' + (c ? ' ' + c : ''); pill.innerHTML = html; }
    var FILES = ['Archivos del juego', 'Interfaz y texturas', 'Scripts del Hub', 'Sonidos y efectos'];
    function run() { timers.forEach(clearTimeout); timers = []; toast.classList.remove('show');
      guard('scan', '<b>Guard</b> revisando'); st('lock', 'Verificando archivos', 'Comparando con la versión oficial…', 0);
      FILES.forEach(function (f, i) { T(500 + i * 650, function () { st('lock', 'Verificando archivos · ' + (i + 1) + '/4', f + ' · original', (i + 1) * 14); }); });
      T(3300, function () { st('lock', 'Buscando programas de trampa', 'Navegador · Discord · Música · permitido', 70); });
      T(4600, function () { guard('bad', '<b>Guard</b> trampa detectada'); toast.classList.add('show'); st('bad lock', 'Programa de trampa detectado', 'Cheat Engine está abierto. Cerralo para poder jugar.', 82); });
      T(8200, function () { toast.classList.remove('show'); guard('scan', '<b>Guard</b> revisando'); st('lock', 'Revisando de nuevo', 'Programa cerrado.', 90); });
      T(9400, function () { guard('', '<b>Guard</b> activo'); st('', 'Listo para jugar', 'Tus archivos son originales. Durante la partida, Guard sigue vigilando.', 100); });
      T(14000, function () { if (vis) run(); else pending = true; });
    }
    if (reduce) { st('', 'Listo para jugar', 'Tus archivos son originales.', 100); return; }
    watch(G, function (v) { vis = v; if (vis && (!started || pending)) { started = true; pending = false; run(); } }, .4);
  })();

  /* Circuito de los Eryns. */
  (function () {
    var nodes = [].slice.call(document.querySelectorAll('#svLoop .sv-node')), line = $('svLoopLine'), cap = $('svLoopCap'), sum = $('svLoopSum'), i = -1, vis = false, total = 0;
    var CAPS = ['Cazás y cae una Jewel of Bless.', 'La publicás en el Mercado de Vessara y le ponés precio en Eryns.', 'Te desconectás. Tu publicación queda en custodia y sigue a la venta.', 'Alguien la compra y los Eryns quedan en tu cuenta. Sin poner un peso.'];
    function go(k) { i = k; nodes.forEach(function (n, j) { n.classList.toggle('on', j === k); n.classList.toggle('done', j < k); var em = n.querySelector('em'); if (em) em.style.opacity = j === k ? 1 : 0; });
      line.style.width = (k / 3 * 100) + '%'; cap.classList.add('fade'); setTimeout(function () { cap.textContent = CAPS[k]; cap.classList.remove('fade'); }, 200);
      if (k === 3) { var from = total, to = total + 1140, t0 = null; total = to;
        (function st(t) { t0 = t0 || t; var q = Math.min(1, (t - t0) / 800); sum.textContent = fmt(Math.round(from + (to - from) * q)); if (q < 1) requestAnimationFrame(st); })(performance.now()); } }
    if (reduce) { go(3); return; }
    go(0);
    watch($('svLoop'), function (v) { vis = v; }, .4);
    setInterval(function () { if (vis) go((i + 1) % 4); }, 2300);
  })();

  /* Destacar publicación: la vidriera reordena y marca la tuya. */
  (function () {
    var f = $('svFeat'), sw = $('svFeatSw'), rows = [].slice.call(document.querySelectorAll('#svVlist .sv-vrow')), note = $('svVnote'), on = false, auto = !reduce, vis = false, H = 68;
    function layout() { var order = on ? ['me', 'a', 'b'] : ['a', 'b', 'me']; rows.forEach(function (r) { r.style.top = (order.indexOf(r.dataset.k) * H) + 'px'; r.classList.toggle('star', on && r.dataset.k === 'me'); });
      note.textContent = on ? 'Primera en Destacados, y un vendedor la ofrece en la plaza.' : 'Tu publicación espera en la vidriera, junto a las demás.'; }
    function set(v) { on = v; sw.setAttribute('aria-pressed', v); sw.textContent = v ? 'Destacada' : 'Destacar publicación'; layout(); }
    sw.addEventListener('click', function () { auto = false; set(!on); });
    layout();
    watch(f, function (v) { vis = v; }, .2);
    var phase = 0; if (!reduce) setInterval(function () { if (!auto || !vis) return; phase = (phase + 1) % 5; if (phase === 1) set(true); else if (phase === 0) set(false); }, 3200);
  })();

})();

/* Clases: selector tipo agentes (7 pestañas + ficha grande). */
(function () {
  var box = document.getElementById('svPick'); if (!box) return;
  var tabs = [].slice.call(box.querySelectorAll('.sv-pick-tab')), panes = [].slice.call(box.querySelectorAll('.sv-pick-pane'));
  box.classList.add('js');
  function show(i, focus) {
    tabs.forEach(function (t, k) { var on = k === i; t.setAttribute('aria-selected', on); t.tabIndex = on ? 0 : -1; });
    panes.forEach(function (p, k) { p.classList.toggle('is-on', k === i); });
    box.style.setProperty('--sys', panes[i].getAttribute('data-sys'));
    if (focus) tabs[i].focus();
  }
  tabs.forEach(function (t, i) {
    t.addEventListener('click', function () { show(i); });
    t.addEventListener('keydown', function (e) {
      var k = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
      if (e.key === 'Home') { e.preventDefault(); show(0, true); } else if (e.key === 'End') { e.preventDefault(); show(tabs.length - 1, true); }
      else if (k) { e.preventDefault(); show((i + k + tabs.length) % tabs.length, true); }
    });
  });
})();

/* Furia: mini combate. Cargá pegándole al Golem, desatá con Shift y encadená muertes. */
(function () {
  var A = document.getElementById('svArena'); if (!A) return;
  var q = function (id) { return document.getElementById(id); };
  var stage = q('svArStage'), mob = q('svArMob'), hp = q('svArHp'), pops = q('svArPops'), fill = q('svArFill'), txt = q('svArTxt'),
      shift = q('svArShift'), combo = q('svArCombo'), hint = q('svArHint'), end = q('svArEnd'), hero = q('svArHero'),
      power = q('svArPower'), cur = A.querySelector('.sv-ar-cls [aria-pressed=true]'), steps = [].slice.call(q('svFSteps').children);
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var CHARGE_HITS = 8, HP_CHARGE = 4, HP_FURIA = 2, T0 = 8, TMAX = 12, BONUS = 2;
  var state, charge, life, maxLife, time, n, best, lastT, raf, inView = false, dying = false;
  var LV = [[20, 'Masacre'], [12, 'Imparable'], [5, 'Combo']];
  function lvl(k) { for (var i = 0; i < LV.length; i++) if (k >= LV[i][0]) return LV[i][1]; return ''; }
  function step(i) { steps.forEach(function (li, k) { li.classList.toggle('on', k === i); li.classList.toggle('done', k < i); }); }
  function look() { var f = state === 'furia' && cur && cur.dataset.furia; A.classList.toggle('has-fart', !!f); var src = f ? cur.dataset.furia : (cur && cur.dataset.img); if (src && hero.getAttribute('src') !== src) hero.src = src; }
  function setState(s) { state = s; A.classList.toggle('is-ready', s === 'ready'); A.classList.toggle('is-furia', s === 'furia'); A.classList.toggle('is-end', s === 'end'); shift.disabled = s !== 'ready'; look(); }
  function bar(p, label) { fill.style.width = Math.max(0, Math.min(100, p)) + '%'; txt.textContent = label; }
  function setLife(v) { life = v; hp.style.width = (life / maxLife * 100) + '%'; }
  function pop(cls, text) {
    var e = document.createElement('span'); e.className = 'sv-ar-pop ' + cls; e.textContent = text;
    e.style.left = (54 + Math.random() * 30) + '%'; e.style.top = (22 + Math.random() * 30) + '%';
    pops.appendChild(e); setTimeout(function () { e.remove(); }, 800);
  }
  function shake() { if (reduce) return; stage.classList.remove('shake'); void stage.offsetWidth; stage.classList.add('shake'); }
  function reset() {
    cancelAnimationFrame(raf); charge = 0; n = 0; dying = false; maxLife = HP_CHARGE; setLife(maxLife);
    mob.classList.remove('dead'); end.hidden = true; hint.classList.remove('off'); hint.textContent = 'Hacé clic en el Golem para pegarle';
    combo.querySelector('b').textContent = 'x0'; combo.querySelector('span').textContent = '';
    setState('charge'); bar(0, 'Furia 0%'); step(0);
  }
  function kill() {
    dying = true; mob.classList.add('dead');
    if (state === 'furia') {
      n++; best = Math.max(best, n);
      time = Math.min(TMAX, time + BONUS); pop('time', '+' + BONUS + 's');
      var b = combo.querySelector('b'), sp = combo.querySelector('span'), l = lvl(n);
      b.textContent = 'x' + n; combo.classList.remove('bump'); void combo.offsetWidth; combo.classList.add('bump');
      if (l && sp.textContent !== l) { sp.textContent = l; combo.classList.remove('lv'); void combo.offsetWidth; combo.classList.add('lv'); shake(); }
      step(n >= 12 ? 5 : n >= 5 ? 4 : 3);
    }
    setTimeout(function () {
      if (state === 'end') return;
      maxLife = state === 'furia' ? HP_FURIA : HP_CHARGE; setLife(maxLife);
      mob.classList.remove('dead'); mob.classList.remove('spawn'); void mob.offsetWidth; mob.classList.add('spawn'); dying = false;
    }, 300);
  }
  function hit() {
    if (dying || state === 'end') return;
    if (state !== 'ready') hint.classList.add('off');
    mob.classList.remove('hit'); void mob.offsetWidth; mob.classList.add('hit');
    clearTimeout(mob._t); mob._t = setTimeout(function () { mob.classList.remove('hit'); }, 140);
    var crit = state === 'furia' && Math.random() < .45, dmg = Math.round((crit ? 3200 : 1400) + Math.random() * 900);
    pop(crit ? 'crit' : '', (crit ? '¡' : '') + dmg.toLocaleString('es-AR') + (crit ? '!' : ''));
    if (state === 'charge') {
      charge = Math.min(CHARGE_HITS, charge + 1); bar(charge / CHARGE_HITS * 100, 'Furia ' + Math.round(charge / CHARGE_HITS * 100) + '%');
      if (charge >= CHARGE_HITS) { setState('ready'); bar(100, 'Furia lista'); step(1); hint.textContent = 'Apretá Shift'; hint.classList.remove('off'); }
    }
    setLife(life - 1); if (life <= 0) kill();
  }
  function unleash() {
    if (state !== 'ready') return;
    setState('furia'); time = T0; n = 0; best = 0; lastT = performance.now(); step(2); hint.classList.add('off'); shake();
    maxLife = HP_FURIA; setLife(Math.min(life, maxLife));
    (function tick(t) {
      var dt = (t - lastT) / 1000; lastT = t; time -= dt;
      bar(time / TMAX * 100, 'Furia ' + Math.max(0, time).toFixed(1) + 's');
      if (time <= 0) return finish();
      raf = requestAnimationFrame(tick);
    })(lastT);
  }
  function finish() {
    setState('end'); bar(0, 'Furia 0%');
    q('svArEndN').textContent = 'x' + best; q('svArEndL').textContent = lvl(best) || 'Seguí practicando';
    end.hidden = false; q('svArAgain').focus({preventScroll: true});
  }
  mob.addEventListener('pointerdown', function (e) { e.preventDefault(); hit(); });
  mob.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); hit(); } });
  shift.addEventListener('click', unleash);
  q('svArAgain').addEventListener('click', reset);
  document.addEventListener('keydown', function (e) { if (e.key === 'Shift' && inView && state === 'ready') unleash(); });
  if ('IntersectionObserver' in window) new IntersectionObserver(function (es) { inView = es[0].isIntersecting; }, {threshold: .3}).observe(A); else inView = true;
  [].slice.call(A.querySelectorAll('.sv-ar-cls button')).forEach(function (b, i, all) {
    b.addEventListener('click', function () {
      all.forEach(function (x) { x.setAttribute('aria-pressed', x === b); });
      cur = b; look();
      power.querySelector('b').textContent = b.dataset.power; power.querySelector('span').textContent = b.dataset.desc;
    });
  });
  if (cur && cur.dataset.furia) { var pre = new Image(); pre.src = cur.dataset.furia; }
  reset();
})();

/* Mercado: tira de destacados que se mueve + el pregonero va cambiando el cartel. */
(function () {
  var f = document.getElementById('svFeat'), run = document.getElementById('svTick'); if (!f || !run) return;
  var sw = document.getElementById('svFeatSw'), crier = f.querySelector('.sv-crier'), img = crier.querySelector('img'), sign = f.querySelector('.sv-crier-sign'),
      sIt = document.getElementById('svSignIt'), sPr = document.getElementById('svSignPr');
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var base = [].slice.call(run.children);
  base.forEach(function (n) { var c = n.cloneNode(true); run.appendChild(c); });
  function speed() { run.style.setProperty('--dur', Math.max(20, run.scrollWidth / 2 / 45) + 's'); }
  /* El cartel de la imagen ocupa x 6,9%–92,4% y y 2,7%–17,3% del dibujo. */
  function fit() { var w = img.clientWidth, h = img.clientHeight; if (!w) return; sign.style.setProperty('--sw', (w * .855) + 'px'); sign.style.setProperty('--sh', (h * .146) + 'px'); sign.style.top = (crier.clientHeight - h + h * .027) + 'px'; sign.style.left = (img.offsetLeft + w * .069 + w * .855 / 2) + 'px'; }
  img.complete ? fit() : img.addEventListener('load', fit); window.addEventListener('resize', fit);
  var on = false, i = 0;
  function items() { return base.filter(function (n) { return on || !n.classList.contains('me'); }); }
  function show(n) { var t = n.querySelector('b').textContent, p = n.dataset.pr;
    if (reduce) { sIt.textContent = t; sPr.textContent = p; return; }
    sign.classList.remove('flip'); void sign.offsetWidth; sign.classList.add('flip');
    setTimeout(function () { sIt.textContent = t; sPr.textContent = p; }, 250); }
  function sync() { var v = sw.getAttribute('aria-pressed') === 'true'; if (v === on) return; on = v; f.classList.toggle('is-on', on);
    [].slice.call(run.querySelectorAll('.me')).forEach(function (m) { m.classList.remove('flash'); void m.offsetWidth; m.classList.add('flash'); });
    speed(); if (on) { i = 0; show(items()[0]); } }
  new MutationObserver(sync).observe(sw, {attributes: true, attributeFilter: ['aria-pressed']});
  sync(); speed();
  if (!reduce) setInterval(function () { var l = items(); i = (i + 1) % l.length; show(l[i]); }, 2600);
})();

/* Free o VIP: la sección cambia de modo; números que ruedan, vidriera, AutoPost y candados que se rompen. */
(function () {
  var S = document.getElementById('vip'); if (!S || !S.classList.contains('sv-vp')) return;
  var q = function (id) { return document.getElementById(id); };
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var vip = false, touched = false, timers = [];
  var V = {exp: [15, 20], drop: [25, 30], slots: [3, 10], ex: [0, 4]};
  function later(ms, f) { timers.push(setTimeout(f, reduce ? 0 : ms)); }
  function roll(el, from, to, suf) {
    if (reduce) { el.textContent = to + suf; return; }
    var t0 = performance.now(), d = 600;
    (function st(t) { var k = Math.min(1, (t - t0) / d), v = Math.round(from + (to - from) * (1 - Math.pow(1 - k, 3))); el.textContent = v + suf; if (k < 1) requestAnimationFrame(st); })(t0);
  }
  var slots = q('svVpSlots'); for (var i = 0; i < 10; i++) slots.appendChild(document.createElement('span'));
  var slotS = [].slice.call(slots.children), locks = [].slice.call(S.querySelectorAll('.sv-vp-perk')), chat = q('svVpChat'), chatT;
  var POSTS = ['Nyx: vendo Jewel of Soul x10 · 120 Eryns', 'Brann: armo party para Blood Castle 19:00', 'Kael: compro Wings of Storm, mandá oferta', 'Nyx: vendo Jewel of Soul x10 · 120 Eryns'];
  function line(t, c) { var sp = document.createElement('span'); if (c) sp.className = c; sp.textContent = t; chat.appendChild(sp); while (chat.children.length > 4) chat.firstChild.remove(); }
  function chatRun() {
    clearInterval(chatT); chat.innerHTML = ''; line('Vos: /autopost');
    if (!vip) { line('[Sistema] Solo las cuentas VIP pueden usar este comando.', 'no'); return; }
    line('[Sistema] Publicación automática activada.', 'ok'); var k = 0;
    if (!reduce) chatT = setInterval(function () { line(POSTS[k++ % POSTS.length], 'post'); }, 2200);
  }
  function apply(prev) {
    timers.forEach(clearTimeout); timers = [];
    S.dataset.mode = vip ? 'vip' : 'free';
    q('svVpFree').setAttribute('aria-pressed', !vip); q('svVpVip').setAttribute('aria-pressed', vip);
    if (!reduce && prev !== undefined) { S.classList.remove('wipe'); void S.offsetWidth; S.classList.add('wipe'); }
    var a = vip ? 1 : 0, b = vip ? 0 : 1;
    roll(q('svVpExp'), V.exp[b], V.exp[a], 'x'); roll(q('svVpDrop'), V.drop[b], V.drop[a], '%');
    roll(q('svVpSlotsN'), V.slots[b], V.slots[a], ''); roll(q('svVpEx'), V.ex[b], V.ex[a], '');
    q('svVpExpBig').textContent = vip ? '20x' : '15x'; q('svVpDropBig').textContent = vip ? '30%' : '25%';
    q('svVpExpNote').textContent = vip ? '+33% de experiencia' : 'Ritmo base';
    q('svVpDropNote').textContent = vip ? '+20% de drop' : 'Drop base';
    q('svVpSlotsBig').textContent = vip ? '10' : '3';
    S.style.setProperty('--exp-d', vip ? '2.2s' : '3s');
    slotS.forEach(function (s, k) { var on = k < (vip ? 10 : 3); s.className = ''; if (on) later(k * 70, function () { s.className = 'on'; }); else s.className = 'lock'; });
    locks.forEach(function (p, k) { p.classList.remove('open', 'break'); if (vip) { later(350 + k * 380, function () { p.classList.add('break'); }); later(650 + k * 380, function () { p.classList.add('open'); }); } });
    chatRun();
  }
  function choose(v) { touched = true; if (v === vip) return; vip = v; apply(!v); }
  q('svVpFree').addEventListener('click', function () { choose(false); });
  q('svVpVip').addEventListener('click', function () { choose(true); });
  apply();
  if ('IntersectionObserver' in window && !reduce) new IntersectionObserver(function (e, o) { if (e[0].isIntersecting) { o.disconnect(); setTimeout(function () { if (!touched) { vip = true; apply(false); } }, 1800); } }, {threshold: .6}).observe(q('svVpFree').parentNode);
})();
