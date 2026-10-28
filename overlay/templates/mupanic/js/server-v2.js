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
    function render() {
      var now = nowArt(), occ = occurrences(now), nx = occ[0];
      if (nx) {
        var live = nx.start <= now, target = live ? nx.end : nx.start, left = Math.max(0, (target - now) * 60);
        var st = $('svNxState'); st.classList.toggle('live', live); st.lastElementChild.textContent = live ? 'En curso ahora' : 'Próximo evento';
        $('svNxName').textContent = nx.e.n;
        $('svNxWhen').textContent = (live ? 'Termina a las ' + pad(Math.floor(nx.end % 1440 / 60)) + ':' + pad(nx.end % 60) : 'Empieza a las ' + nx.t) + ' · ' + GN[nx.e.g] + ' · dura ' + nx.e.d + ' min';
        [['svCh', Math.floor(left / 3600)], ['svCm', Math.floor(left % 3600 / 60)], ['svCs', Math.floor(left % 60)]].forEach(function (x) {
          var el = $(x[0]), v = pad(x[1]);
          if (el.textContent !== v) { el.textContent = v; if (!reduce) { el.classList.remove('tick'); void el.offsetWidth; el.classList.add('tick'); } }
        });
      }
      var rows = occ.filter(function (o) { return filter === 'all' || o.e.g === filter; }).slice(0, 10);
      $('svEvList').innerHTML = rows.length ? rows.map(function (o) {
        var live = o.start <= now;
        return '<li class="' + (live ? 'live' : '') + (o.tom ? ' tomorrow' : '') + '"><time>' + o.t + '</time><span class="n">' + esc(o.e.n) + '<span class="g">' + GN[o.e.g] + (o.tom ? ' · mañana' : '') + '</span></span><span class="rel">' + (live ? 'En curso' : rel(o.start - now)) + '</span></li>';
      }).join('') : '<li class="empty">No hay eventos de esta categoría en las próximas 24 horas.</li>';
    }
    function ruler() {
      var now = nowArt(), h = '<div class="track"></div>';
      for (var i = 0; i <= 24; i += 3) h += '<span class="tick" style="left:' + (i / 24 * 100) + '%">' + pad(i) + '</span>';
      EVENTS.forEach(function (e) { e.t.forEach(function (t) { var m = toMin(t);
        h += '<span class="ev ' + (e.g === 'invasion' ? 'inv' : e.g === 'custom' ? 'cus' : '') + (m + e.d < now ? ' done' : '') + (m > now && m - now <= 60 ? ' soon' : '') + '" style="left:' + (m / 1440 * 100) + '%"></span>';
      }); });
      h += '<span class="now" style="left:' + (now / 1440 * 100) + '%"></span>';
      $('svRuler').innerHTML = h;
    }
    var tabs = [].slice.call(ROOT.querySelectorAll('.sv-agenda [data-g]'));
    tabs.forEach(function (b) { b.addEventListener('click', function () {
      filter = b.dataset.g;
      tabs.forEach(function (x) { x.setAttribute('aria-pressed', x === b); x.classList.toggle('is-active', x === b); });
      render();
    }); });
    render(); ruler(); setInterval(render, 1000); setInterval(ruler, 60000);
  })();

  /* Simulador de resets (200/250/300/350 puntos por etapa). */
  (function () {
    var r = $('svSimR'), out = $('svSimOut'), pts = $('svSimPts'), nxt = $('svSimNext'), lis = [].slice.call($('svLadder').children), shown = 0, raf, auto = !reduce, played = false;
    var LV = 1995, LV2 = 2793;
    function per(n) { return n <= 4 ? 200 : n <= 9 ? 250 : n <= 14 ? 300 : 350; }
    function total(n) { var t = 0; for (var i = 1; i <= n; i++) t += per(i); return t; }
    function animTo(v) { cancelAnimationFrame(raf); var from = shown, t0 = null; (function st(t) { t0 = t0 || t; var k = Math.min(1, (t - t0) / 350); shown = Math.round(from + (v - from) * k); pts.textContent = fmt(shown); if (k < 1) raf = requestAnimationFrame(st); })(performance.now()); }
    function upd() {
      var n = +r.value; r.style.setProperty('--p', (n / 20 * 100) + '%'); out.firstChild.nodeValue = n;
      var st = n === 0 ? -1 : (n <= 4 ? 0 : n <= 9 ? 1 : n <= 14 ? 2 : 3), nx = n >= 20 ? 3 : (n + 1 <= 4 ? 0 : n + 1 <= 9 ? 1 : n + 1 <= 14 ? 2 : 3);
      lis.forEach(function (li, i) { li.classList.toggle('reached', i <= st); li.classList.toggle('now', i === nx); });
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

  /* Free vs VIP. */
  (function () {
    var sec = $('vip'), bF = $('svVFree'), bV = $('svVVip'), vip = false, auto = !reduce, vis = false;
    var JW = ['#6fb4ff', '#ffc24a', '#ff4655', '#3fd394'];
    var slots = $('svVSlots'); for (var i = 0; i < 10; i++) slots.appendChild(document.createElement('span'));
    var slotS = [].slice.call(slots.children);
    var inv = $('svVInv'); JW.forEach(function (c) { var j = document.createElement('i'); j.style.setProperty('--c', c); inv.appendChild(j); });
    function txt(id, t) { $(id).textContent = t; }
    function chatLine(t, c) { var ch = $('svVChat'), sp = document.createElement('span'); if (c) sp.className = c; sp.textContent = t; ch.appendChild(sp); while (ch.children.length > 3) ch.firstChild.remove(); }
    function chatReset() { $('svVChat').innerHTML = ''; chatLine('Vos: /autopost'); if (vip) chatLine('[Sistema] Publicación automática activada.'); else chatLine('[Sistema] Solo las cuentas VIP pueden usar este comando.', 'no'); }
    var offT = [];
    function offLog() { offT.forEach(clearTimeout); offT = []; var lg = $('svVOff');
      if (!vip) { lg.innerHTML = '<span class="in">Desconectado</span><span class="in">Sin actividad</span>'; return; }
      var Lg = ['Desconectado · Helper activo', 'Cazando en tu spot…', '+ experiencia', '+ Jewel of Bless'];
      lg.innerHTML = Lg.map(function (t, k) { return '<span' + (k > 1 ? ' class="gold"' : '') + '>' + t + '</span>'; }).join('');
      [].slice.call(lg.children).forEach(function (s, k) { offT.push(setTimeout(function () { s.classList.add('in'); }, reduce ? 0 : 200 + k * 450)); });
    }
    function apply() { sec.classList.toggle('is-vip', vip); bF.setAttribute('aria-pressed', !vip); bV.setAttribute('aria-pressed', vip);
      bF.className = 'pui-btn ' + (vip ? 'pui-btn--ghost' : 'pui-btn--dark'); bV.className = 'pui-btn ' + (vip ? 'pui-btn--dark' : 'pui-btn--ghost');
      txt('svVExp', vip ? '20x' : '15x'); txt('svVExpS', vip ? '+33% más rápido' : 'ritmo base'); txt('svVExpT', vip ? 'Cada nivel llega antes.' : 'Subís a tu ritmo.');
      txt('svVDrop', vip ? '30%' : '25%'); txt('svVDropS', vip ? '+20% más drops' : 'drop base');
      txt('svVSlotsN', vip ? '10' : '3'); txt('svVSlotsT', vip ? 'Diez objetos vendiéndose a la vez, aunque no estés.' : 'Tres objetos en la vidriera a la vez.');
      slotS.forEach(function (s, k) { var on = k < (vip ? 10 : 3); setTimeout(function () { s.className = on ? 'on' : 'lock'; }, on && !reduce ? k * 60 : 0); });
      txt('svVOffT', vip ? 'Te desconectás y tu personaje sigue cazando con el MU Helper.' : 'Cuando te desconectás, tu personaje se queda quieto.');
      txt('svVBankT', vip ? 'Las joyas que juntás van solas al banco. Tu inventario queda libre.' : 'Las joyas ocupan tu inventario hasta que las guardás vos.');
      txt('svVDisc', vip ? '10%' : '0%'); txt('svVPrice', vip ? '900' : '1.000'); txt('svVDiscT', vip ? 'Todo lo de la Tienda te sale un 10% menos.' : 'Pagás el precio de lista.');
      txt('svVChestT', vip ? 'Un baúl reservado para tu cuenta. Si el VIP vence, lo que guardaste sigue ahí para retirarlo.' : 'Usás los baúles de tu cuenta.');
      txt('svVChatT', vip ? '/autopost publica tu anuncio solo, cada 10 minutos, mientras jugás.' : 'Tus anuncios en el chat los escribís cada vez.');
      chatReset(); offLog(); }
    function choose(v) { auto = false; vip = v; apply(); }
    bF.addEventListener('click', function () { choose(false); }); bV.addEventListener('click', function () { choose(true); });
    apply();
    if (reduce) { $('svVExpBar').style.width = '60%'; return; }
    watch(sec, function (v) { vis = v; }, .2);
    var CH = ['Kael: alguien para Kalima?', 'Nyx: vendo Jewel of Soul', 'Brann: party BC 22:40', 'Lira: gg'];
    var exp = 0, lvl = 312, last = performance.now(), dropAcc = 0, jw = 0, bankAcc = 0, bank = 0, logAcc = 0, chatAcc = 0, chatN = 0, autoT = 0;
    (function loop(t) { requestAnimationFrame(loop); var dt = Math.min(.05, (t - last) / 1000); last = t; if (!vis) return;
      exp += dt * (vip ? 20 : 15) * 1.6; if (exp >= 100) { exp -= 100; lvl++; $('svVLvl').textContent = lvl; }
      $('svVExpBar').style.width = exp + '%';
      dropAcc += dt * (vip ? 30 : 25) / 25;
      if (dropAcc >= 1.15) { dropAcc = 0; var m = $('svVMob'); m.classList.remove('hit'); void m.offsetWidth; m.classList.add('hit');
        var fl = $('svVFloor'), j = document.createElement('i'); j.className = 'jw'; j.style.setProperty('--c', JW[jw % 4]); j.style.left = (6 + (jw * 23) % 84) + '%'; fl.appendChild(j); if (fl.children.length > 7) fl.firstChild.remove(); jw++; $('svVJw').textContent = jw; }
      if (vip) { bankAcc += dt; if (bankAcc > 1.1) { bankAcc = 0; var f = document.createElement('i'); f.className = 'fly'; f.style.setProperty('--c', JW[bank % 4]); $('svVLane').appendChild(f);
        (function (n) { setTimeout(function () { n.remove(); bank++; $('svVBank').textContent = bank; }, 900); })(f); } }
      logAcc += dt; if (vip && logAcc > 4.5) { logAcc = 0; offLog(); }
      chatAcc += dt; if (chatAcc > 1.4) { chatAcc = 0; chatN++; if (vip && chatN % 3 === 0) chatLine('[AutoPost] Vos: Vendo Hurricane Armor +15 · 1.200 Eryns', 'ap'); else chatLine(CH[chatN % CH.length]); }
      if (auto) { autoT += dt; if (autoT > 6) { autoT = 0; vip = !vip; apply(); } }
    })(last);
  })();
})();
