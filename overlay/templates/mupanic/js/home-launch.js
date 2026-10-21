(function () {
  'use strict';
  var home = document.querySelector('[data-panic-home]');
  if (!home) return;
  var endpoint;
  try { endpoint = new URL(home.getAttribute('data-launcher-url'), location.href); }
  catch (error) { return; }
  if (endpoint.origin !== location.origin) return;
  var state = home.querySelector('[data-home-state]');
  var online = home.querySelector('[data-home-online]');
  var list = home.querySelector('[data-home-events]');
  var labels = {online: 'Online', offline: 'Offline', mantenimiento: 'Mantenimiento'};
  var busy = false;
  var timer;
  var format = new Intl.DateTimeFormat('es-AR', {timeZone: 'America/Argentina/Buenos_Aires', day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit', hourCycle:'h23'});
  function schedule() { clearTimeout(timer); timer = setTimeout(refresh, 60000); }
  function valid(data) {
    return data && Object.prototype.hasOwnProperty.call(labels, data.servidor) &&
      Number.isSafeInteger(data.online) && data.online >= 0 && Array.isArray(data.eventos) && data.eventos.length <= 10 &&
      data.eventos.every(function (event) {
        return event && typeof event.nombre === 'string' && event.nombre.length > 0 && event.nombre.length <= 150 &&
          typeof event.proximo === 'string' && /(?:Z|[+-]\d{2}:\d{2})$/.test(event.proximo) && Number.isFinite(Date.parse(event.proximo));
      });
  }
  function fitOnline(value) {
    // Fit the formatted count (including thousands separators) inside the real aperture.
    online.style.setProperty('--core-count-size', Math.min(23, 74 / Math.max(1, value.length)) + 'cqw');
  }
  fitOnline(online.textContent.trim());
  function show(data) {
    state.textContent = labels[data.servidor];
    state.setAttribute('data-state', data.servidor);
    var value = data.online.toLocaleString('es-AR');
    fitOnline(value);
    if (online.textContent !== value) {
      online.textContent = value;
      if (state.closest('.ph-status').classList.contains('is-stats-live') && !matchMedia('(prefers-reduced-motion: reduce)').matches && online.animate) {
        online.animate([{transform:'translateY(5px)',opacity:.35},{transform:'translateY(0)',opacity:1}],{duration:650,easing:'cubic-bezier(.2,.8,.2,1)'});
      }
    }
    state.closest('.ph-status').dataset.serverState = data.servidor;
    var fragment = document.createDocumentFragment();
    var events = data.eventos.filter(function (event) { return Date.parse(event.proximo) > Date.now(); })
      .sort(function (a, b) { return Date.parse(a.proximo) - Date.parse(b.proximo); }).slice(0, 3);
    events.forEach(function (event) {
      var row = document.createElement('li');
      var link = document.createElement('a');
      link.href = new URL('info/#eventos', endpoint.href.replace(/api\/launcher\.php$/, '')).href;
      var name = document.createElement('span'); name.textContent = event.nombre;
      var time = document.createElement('time'); time.dateTime = event.proximo; time.textContent = format.format(new Date(event.proximo)) + ' hs';
      var arrow = document.createElement('b'); arrow.setAttribute('aria-hidden', 'true'); arrow.textContent = '↗';
      link.append(name, time, arrow); row.append(link); fragment.append(row);
    });
    list.replaceChildren(fragment);
    updateCountdown();
    var empty = home.querySelector('[data-home-events-empty]');
    if (!events.length && !empty) {
      empty = document.createElement('p'); empty.setAttribute('data-home-events-empty', '');
      empty.textContent = 'Consultá el calendario de eventos del servidor.';
      list.after(empty);
    }
    if (empty) empty.hidden = events.length > 0;
  }
  function updateCountdown() {
    var first = list.querySelector('li:first-child a');
    if (!first) return;
    var time = first.querySelector('time');
    if (!time) return;
    var value = Math.max(0, Math.ceil((Date.parse(time.dateTime) - Date.now()) / 1000));
    var countdown = first.querySelector('[data-event-countdown]');
    if (!countdown) {
      countdown = document.createElement('strong');
      countdown.setAttribute('data-event-countdown', '');
      countdown.setAttribute('aria-label', 'Tiempo hasta el próximo evento');
      first.append(countdown);
    }
    var hours = Math.floor(value / 3600), minutes = Math.floor(value % 3600 / 60), seconds = value % 60;
    countdown.textContent = hours > 24 ? 'EN ' + Math.floor(hours / 24) + ' DÍAS' :
      'EN ' + [hours, minutes, seconds].map(function (n) { return String(n).padStart(2, '0'); }).join(':');
  }
  updateCountdown();
  setInterval(function () { if (!document.hidden) updateCountdown(); }, 1000);
  async function refresh() {
    if (busy) return;
    if (document.hidden || navigator.onLine === false) { schedule(); return; }
    busy = true;
    var controller = new AbortController();
    var timeout = setTimeout(function () { controller.abort(); }, 10000);
    try {
      var response = await fetch(endpoint.href, {cache:'no-store', credentials:'omit', signal:controller.signal});
      if (!response.ok) throw new Error('Unavailable');
      var data = await response.json();
      if (!valid(data)) throw new Error('Invalid payload');
      show(data);
    } catch (error) {
      state.textContent = 'Sin verificar'; state.removeAttribute('data-state');
      state.closest('.ph-status').dataset.serverState = 'unknown';
    } finally {
      clearTimeout(timeout); busy = false; schedule();
    }
  }
  document.addEventListener('visibilitychange', function () { if (!document.hidden) refresh(); });
  window.addEventListener('online', refresh);
  refresh();
})();

// Accessible feature tours. Without JavaScript every description remains readable.
(function () {
  'use strict';
  document.querySelectorAll('[data-home-tabs]').forEach(function (tour, tourIndex) {
    var links = Array.from(tour.querySelectorAll('[data-world-tab]'));
    var panels = Array.from(tour.querySelectorAll('[data-world-panel]'));
    if (!links.length || links.length !== panels.length) return;
    tour.querySelector('nav').setAttribute('role', 'tablist');
    function select(index) {
      links.forEach(function (link, i) {
        link.setAttribute('aria-selected', String(i === index));
        link.tabIndex = i === index ? 0 : -1;
        panels[i].hidden = i !== index;
        panels[i].inert = i !== index;
        var caption=tour.querySelector('[data-hub-caption="'+panels[i].id+'"]');
        if(caption){caption.hidden=i!==index;caption.inert=i!==index;}
      });

    }
    links.forEach(function (link, index) {
      link.id = 'world-tab-' + tourIndex + '-' + index;
      link.setAttribute('role', 'tab');
      link.setAttribute('aria-controls', panels[index].id);
      panels[index].setAttribute('role', 'tabpanel');
      panels[index].setAttribute('aria-labelledby', link.id);
      panels[index].tabIndex = 0;
      link.addEventListener('click', function (event) { event.preventDefault(); select(index); });
      link.addEventListener('keydown', function (event) {
        var next;
        if (event.key === 'ArrowRight') next = (index + 1) % links.length;
        else if (event.key === 'ArrowLeft') next = (index + links.length - 1) % links.length;
        else if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = links.length - 1;
        if (next === undefined) return;
        event.preventDefault(); select(next); links[next].focus();
      });
    });
    select(0);
  });
})();

// Scene-local energy, fire and sparks. No camera zoom, image distortion or scroll handler.
(function () {
  'use strict';
  var reduced = matchMedia('(prefers-reduced-motion: reduce)');
  var scenes = [], frame = 0, last = 0;
  document.querySelectorAll('[data-scene-fx]').forEach(function (section) {
    var art = section.querySelector('.ph-art');
    if (!art) return;
    var canvas = document.createElement('canvas');
    canvas.className = 'ph-scene-fx'; canvas.setAttribute('aria-hidden', 'true');
    section.append(canvas);
    var ctx = canvas.getContext('2d');
    if (!ctx) { canvas.remove(); return; }
    var scene = {section:section, art:art, canvas:canvas, ctx:ctx, visible:false, width:0, height:0, type:section.dataset.sceneFx};
    function resize() {
      var image = art.querySelector('img');
      var r = image.getBoundingClientRect(), parent = section.getBoundingClientRect();
      var ratio = Math.min(devicePixelRatio || 1, 1.5);
      scene.width = r.width; scene.height = r.height;
      canvas.width = Math.round(r.width * ratio); canvas.height = Math.round(r.height * ratio);
      canvas.style.width = r.width + 'px'; canvas.style.height = r.height + 'px';
      canvas.style.left = (r.left - parent.left) + 'px'; canvas.style.top = (r.top - parent.top) + 'px';
      ctx.setTransform(ratio, 0, 0, ratio, 0, 0);

    }
    if ('ResizeObserver' in window) new ResizeObserver(resize).observe(art);
    art.querySelector('img').addEventListener('load', resize); resize();
    scenes.push(scene);
  });
  function sync() {
    var enabled = !reduced.matches && !document.hidden;
    scenes.forEach(function (s) { s.section.classList.toggle('is-in-view', enabled && s.visible); });
    if (enabled && scenes.some(function (s) { return s.visible; })) {
      if (!frame) frame = requestAnimationFrame(draw);
    } else {
      cancelAnimationFrame(frame); frame = 0;
      scenes.forEach(function (s) { s.ctx.clearRect(0, 0, s.width, s.height); });
    }
  }
  function glow(s, x, y, radius, color, strength) {
    var c = s.ctx, g = c.createRadialGradient(x, y, 0, x, y, radius);
    g.addColorStop(0, 'rgba(' + color + ',' + strength + ')'); g.addColorStop(1, 'rgba(' + color + ',0)');
    c.fillStyle = g; c.fillRect(x-radius, y-radius, radius*2, radius*2);
  }
  function render(s, t) {
    var c = s.ctx, w = s.width, h = s.height, mobile = w <= 760;
    c.clearRect(0, 0, w, h);
    if (!w || !h) return;
    c.globalCompositeOperation = 'lighter';
    if (s.type === 'combat') {
      var x = w * (mobile ? .55 : .755), y = h * (mobile ? .40 : .30);
      var beat = Math.pow(Math.max(0, Math.sin(t * 2.4)), 10);
      glow(s, x, y, w * .085, '101,224,163', .08 + beat * .34);
      for (var k = 0; k < 18; k++) {
        var progress = (t * .45 + k / 18) % 1, angle = k * 2.399;
        var distance = progress * w * .13;
        c.globalAlpha = (1-progress) * .75;
        c.strokeStyle = k % 3 ? '#e7bf7e' : '#8cf1c0'; c.lineWidth = 1;
        c.beginPath(); c.moveTo(x + Math.cos(angle)*distance, y + Math.sin(angle)*distance*.7);
        c.lineTo(x + Math.cos(angle)*(distance+5), y + Math.sin(angle)*(distance+5)*.7); c.stroke();
      }
    } else {
      var points = s.type === 'market' ? (mobile ? [[.79,.25],[.66,.40],[.975,.35]] : [[.87,.14],[.63,.32],[.49,.44]]) :
        s.type === 'events' ? [[.50,.49],[.86,.40],[.28,.51]] :
        s.type === 'fractura' ? [[.61,.65],[.88,.54]] : mobile ? [[.45,.12],[.93,.20]] : [[.69,.10],[.94,.08]];
      points.forEach(function (point, index) {
        var x = point[0]*w, y = point[1]*h;
        var cool = s.type === 'fractura' && index === 0;
        glow(s, x, y, w*.032, cool ? '77,145,255' : '255,145,57', .13 + .06*Math.sin(t*4+index));
        for (var j=0; j<6; j++) {
          var p = (t*(.19+j*.008)+j/6+index*.2)%1;
          c.globalAlpha = Math.sin(p*Math.PI)*.65;
          c.fillStyle = cool ? '#8bb9ff' : '#edb66c';
          c.beginPath(); c.arc(x+Math.sin(j*11.2+p*3)*w*.012, y-p*h*.11, 0.7+j%2*.4, 0, Math.PI*2); c.fill();
        }
      });
    }
    c.globalAlpha = 1; c.globalCompositeOperation = 'source-over';
  }
  function draw(now) {
    frame = 0;
    if (now-last > 40) {
      scenes.forEach(function (s) { if(s.visible) render(s, now/1000); }); last = now;
    }
    if (!reduced.matches && !document.hidden && scenes.some(function (s) {return s.visible;})) frame = requestAnimationFrame(draw);
  }
  if ('IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) { scenes.forEach(function (s) {if(s.section === entry.target) s.visible = entry.isIntersecting;}); }); sync();
    }, {threshold:.04});
    scenes.forEach(function (s) { observer.observe(s.section); });
  } else { scenes.forEach(function (s) {s.visible = true;}); }
  reduced.addEventListener('change', sync); document.addEventListener('visibilitychange', sync); sync();
})();

// Actual Furia game effects, automatically static with reduced motion and offscreen.
(function () {
  'use strict';
  var section = document.querySelector('[data-furia-showcase]');
  if (!section) return;
  var videos = Array.from(section.querySelectorAll('[data-furia-video]'));
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  var paused = reduced.matches;
  var visible = false;
  function sync() {
    var play = visible && !document.hidden && !paused;
    section.classList.toggle('is-animating', play);
    videos.forEach(function (video) {
      if (play) {
        var webm = Boolean(video.canPlayType('video/webm; codecs="vp9"'));
        if (video.hasAttribute('data-furia-alpha') && !webm) return;
        if (!video.getAttribute('src')) video.src = webm ? video.getAttribute('data-src') : video.getAttribute('data-fallback');
        video.muted = true;
        video.play().catch(function () {});
      } else video.pause();
    });
  }
  document.addEventListener('visibilitychange', sync);
  reduced.addEventListener('change', function () { paused = reduced.matches; sync(); });
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      visible = entries[0].isIntersecting; sync();
    }, {threshold:0.05}).observe(section.querySelector('.ph-furia-demo'));
  } else { visible = true; sync(); }
  sync();
})();

// Sky movement is a separate atmospheric layer; the game characters keep their source art.
(function () {
  'use strict';
  var hero = document.querySelector('.ph-hero');
  if (!hero) return;
  var visible = false, reduced = matchMedia('(prefers-reduced-motion: reduce)');
  function sync() { hero.classList.toggle('is-sky-live', visible && !document.hidden && !reduced.matches); }
  if ('IntersectionObserver' in window) new IntersectionObserver(function (entries) { visible = entries[0].isIntersecting; sync(); }, {threshold:.04}).observe(hero);
  else { visible = true; sync(); }
  document.addEventListener('visibilitychange',sync); reduced.addEventListener('change',sync);
})();

// Stats animate only while visible. Numeric content always remains the real API value.
(function () {
  'use strict';
  var section = document.querySelector('.ph-status');
  if (!section) return;
  var reduced = matchMedia('(prefers-reduced-motion: reduce)'), visible = false;
  function sync() {
    section.classList.toggle('is-stats-live', visible && !document.hidden && !reduced.matches);
    if (visible) section.classList.add('has-stats-entered');
  }
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) { visible = entries[0].isIntersecting; sync(); }, {threshold:.15}).observe(section);
  } else { visible = true; sync(); }
  document.addEventListener('visibilitychange', sync);
  reduced.addEventListener('change', sync);
})();

// Real combat video: static fallback on reduced motion, failure or blocked autoplay.
(function () {
  'use strict';
  var video = document.querySelector('[data-combat-video]');
  if (!video) return;
  var art = video.parentElement;
  var reduced = matchMedia('(prefers-reduced-motion: reduce)');
  var visible = false;
  function wanted() { return visible && !document.hidden && !reduced.matches; }
  function sync() {
    if (!wanted()) {
      video.pause();
      if (reduced.matches) art.classList.remove('is-video-ready');
      return;
    }
    if (!video.getAttribute('src')) video.src = video.getAttribute('data-src');
    video.muted = true;
    video.play().catch(function () { art.classList.remove('is-video-ready'); });
  }
  video.addEventListener('playing', function () {
    if (wanted()) art.classList.add('is-video-ready');
    else video.pause();
  });
  video.addEventListener('error', function () { art.classList.remove('is-video-ready'); });
  document.addEventListener('visibilitychange', sync);
  reduced.addEventListener('change', sync);
  if ('IntersectionObserver' in window) new IntersectionObserver(function (entries) {
    visible = entries[0].isIntersecting; sync();
  }, {threshold:0.04}).observe(art);
  else { visible = true; sync(); }
})();
// Move the live simulator into the dialog: the same native buttons keep working.
(function () {
 'use strict';
 var dialog=document.querySelector('[data-hub-lightbox]');
 var simulator=document.querySelector('[data-hub-simulator]');
 var stage=document.querySelector('[data-hub-stage]');
 var button=document.querySelector('[data-hub-enlarge]');
 if(!dialog||!simulator||!button||typeof dialog.showModal!=='function')return;
 var content=dialog.querySelector('[data-hub-lightbox-content]');
 button.addEventListener('click',function(){
  stage.style.height=stage.getBoundingClientRect().height+'px';
  content.appendChild(simulator);
  dialog.showModal();dialog.scrollTop=0;dialog.scrollLeft=0;
 });
 dialog.querySelector('button').addEventListener('click',function(){dialog.close();});
 dialog.addEventListener('click',function(event){if(event.target===dialog){var r=dialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)dialog.close();}});
 dialog.addEventListener('close',function(){stage.appendChild(simulator);stage.style.height='';button.focus({preventScroll:true});});
})();
// The illustrated preview exposes the client's functions without test-account data.
(function(){
 'use strict';
 document.querySelectorAll('[data-hub-go]').forEach(function(button){button.addEventListener('click',function(){var tab=document.querySelector('[data-world-tab="'+button.dataset.hubGo+'"]');if(tab)tab.click();});});
 document.querySelectorAll('[data-hub-detail]').forEach(function(button){button.addEventListener('click',function(){var output=button.closest('[data-world-panel]').querySelector('[data-hub-detail-output]');if(output)output.textContent=button.dataset.hubDetail;});});
})();
