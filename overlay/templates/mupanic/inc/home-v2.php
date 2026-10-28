<?php
/**
 * MU PANIC · Inicio (estilo nuevo). Engancha sin explotar de info: portada, estado en vivo,
 * lo que tiene MU PANIC que no tiene otro MU, y el llamado a jugar.
 * Los datos en vivo (estado, online, rates, eventos) los trae js/home-v2.js desde api/launcher.php?home=1.
 */
if(!defined('access') || !access) die();
require_once __DIR__.'/home-data.php';
$h2e = function($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$h2img = __PATH_TEMPLATE__.'img/inicio/';
$h2art = ['furia'=>'inicio-furia-d7c277cb6fe1.webp','duo'=>'inicio-duo-81503589e13d.webp','bk'=>'inicio-bk-7627b8a7503e.webp','elfa'=>'inicio-elfa-1378c4409a7b.webp'];
$h2events = panicHomeEvents();
$h2play = __BASE_URL__.($isLogged ? 'downloads/' : 'register/');
$h2aviso = json_decode((string)@file_get_contents(__DIR__.'/aviso-home.json'), true);
$h2avisoMsgs = is_array($h2aviso['mensajes'] ?? null) ? array_values(array_filter(array_map('strval', $h2aviso['mensajes']), 'strlen')) : [];
$h2avisoHasta = !empty($h2aviso['hasta']) ? strtotime($h2aviso['hasta']) : false;
$h2avisoOn = !empty($h2aviso['activo']) && $h2avisoMsgs && ($h2avisoHasta === false || time() < $h2avisoHasta);
$h2systems = [
  ['furia','Furia','Cargala peleando y desatala con Shift: más velocidad, más daño y un poder propio de tu clase.','#furia','Rage Fighter · naranja','#ff7a1a'],
  ['fractura','La Fractura','Nexo o Caos. Elegí tu bando y peleá una guerra que no existe en ningún otro MU.',__BASE_URL__.'fractura/','Nexo vs Caos','#29c8ff'],
  ['vinculos','Compañeros','Tres mascotas evolutivas y un compañero único que te acompañan en la aventura.',__BASE_URL__.'info/#vinculos','Elyndra, Aurion, Vaerion y Grum · verde','#36e07f'],
  ['mercado','Mercado de Vessara','Comprá y vendé equipo con otros jugadores, en Zen o Eryns, desde el juego y la web.',__BASE_URL__.'market/','Vessara · dorado','#ffc24a'],
  ['hub','Hub F11','Tu personaje, la Fractura, eventos, mercado y tienda: todo MU PANIC a una tecla.','#hub','Summoner · violeta','#9b6bff'],
  ['juegos','Minijuegos','Ocho juegos dentro del cliente para cambiar de ritmo entre leveo y leveo.','#hub','Magic Gladiator · magenta','#ff3fa4'],
];
$h2games = ['Ruleta','Tragamonedas','Mayor o Menor','Vuelo del Dragón','Lluvia de Joyas','Tesoro de Kalima','Torre del Caos','Carrera'];
?>
<div class="h2 pui" data-panic-home data-launcher-url="<?php echo $h2e(__BASE_URL__.'api/launcher.php?home=1'); ?>">

  <?php if($h2avisoOn) { ?>
  <a class="h2-aviso" href="<?php echo $h2e(__BASE_URL__.ltrim((string)($h2aviso['link'] ?? 'downloads/'), '/')); ?>">
    <span class="h2-aviso-tag">Ahora</span>
    <span class="h2-aviso-run"><span class="h2-aviso-track"><?php for($r=0;$r<4;$r++) foreach($h2avisoMsgs as $m) { ?><span<?php if($r) echo ' aria-hidden="true"'; ?>><?php echo $h2e($m); ?></span><?php } ?></span></span>
  </a>
  <?php } ?>

  <section class="h2-hero" aria-labelledby="h2-title">
    <div class="h2-hero-block" aria-hidden="true"><span class="h2-hero-word">MU PANIC</span></div>
    <div class="h2-hero-cast" aria-hidden="true">
      <img class="h2-cast-duo" src="<?php echo $h2e($h2img.$h2art['duo']); ?>" alt="" width="1330" height="1182" decoding="async" fetchpriority="high">
    </div>
    <div class="pui-wrap h2-hero-copy">
      <p class="pui-kicker">Servidor argentino de MU Online</p>
      <h1 class="pui-h1" id="h2-title">Tu leyenda<br>empieza acá</h1>
      <p class="pui-lead">Sistemas propios que no vas a encontrar en otro MU: Furia, la Fractura, compañeros y el Mercado de Vessara.</p>
      <div class="h2-actions">
        <span class="pui-btn-wrap"><a class="pui-btn pui-btn--lg" href="<?php echo $h2e($h2play); ?>">Jugá gratis</a></span>
        <a class="pui-btn pui-btn--ghost" href="<?php echo $h2e(__BASE_URL__.'information/'); ?>">Conocé el servidor</a>
      </div>
      <dl class="h2-rates">
        <div class="pui-stat"><dt>Experiencia</dt><dd data-home-rate="experience"><?php echo $h2e($serverExp); ?></dd></div>
        <div class="pui-stat"><dt>Master</dt><dd data-home-rate="master"><?php echo $h2e($serverMasterExp); ?></dd></div>
        <div class="pui-stat"><dt>Drop</dt><dd data-home-rate="drop"><?php echo $h2e($serverDrop); ?></dd></div>
      </dl>
    </div>
  </section>

  <section class="h2-live" aria-label="El servidor ahora" data-server-state="unknown">
    <span class="pui-slash pui-slash--fine h2-live-slash" aria-hidden="true"></span>
    <div class="pui-wrap h2-live-grid">
      <div class="h2-live-item"><span class="pui-label">Servidor</span><b class="h2-state" data-home-state>Sin verificar</b></div>
      <div class="h2-live-item"><span class="pui-label">Jugadores online</span><b class="pui-num h2-online" data-home-online>—</b></div>
      <div class="h2-live-item h2-live-events"><span class="pui-label">Próximos eventos</span>
        <ol class="h2-events" data-home-events><?php foreach($h2events as $ev) { ?><li><a href="<?php echo $h2e(__BASE_URL__.'info/#eventos'); ?>"><span><?php echo $h2e($ev['nombre']); ?></span><time datetime="<?php echo $h2e($ev['proximo']); ?>"><?php echo $h2e(panicHomeEventLabel($ev['proximo'])); ?></time></a></li><?php } ?></ol>
        <?php if(!$h2events) { ?><p class="h2-events-empty" data-home-events-empty>Consultá el calendario de eventos.</p><?php } ?>
      </div>
    </div>
  </section>

  <section class="pui-band pui-band--bone h2-systems" aria-labelledby="h2-systems-title">
    <span class="pui-band-word" aria-hidden="true">PROPIO</span>
    <div class="pui-wrap">
      <header class="h2-head"><span class="pui-hud"><i></i></span><p class="pui-kicker">Solo en MU PANIC</p><h2 class="pui-h2" id="h2-systems-title"><span class="pui-reveal">Lo que no tiene</span><br><span class="pui-reveal">ningún otro MU</span></h2></header>
      <div class="h2-system-grid pui-rise">
        <?php foreach($h2systems as $s) { ?>
        <a class="h2-system" href="<?php echo $h2e($s[3]); ?>" style="--sys:<?php echo $h2e($s[5]); ?>">
          <?php if(isset($h2art[$s[0]])) { ?><span class="h2-card-art" aria-hidden="true"><img src="<?php echo $h2e($h2img.$h2art[$s[0]]); ?>" alt="" loading="lazy" decoding="async"></span>
          <?php } else { ?><span class="h2-art-slot" aria-hidden="true"><i><?php echo $h2e($s[1]); ?></i><small>Arte: <?php echo $h2e($s[4]); ?></small></span><?php } ?>
          <span class="h2-system-body"><b class="pui-h3"><?php echo $h2e($s[1]); ?></b><span><?php echo $h2e($s[2]); ?></span></span>
        </a>
        <?php } ?>
      </div>
    </div>
  </section>

  <section class="h2-feature h2-furia" id="furia" aria-labelledby="h2-furia-title" style="--sys:#ff7a1a">
    <div class="pui-wrap h2-feature-grid">
      <div>
        <p class="pui-kicker">Sistema exclusivo</p>
        <h2 class="pui-h2" id="h2-furia-title"><span class="pui-reveal">Desatá</span><br><span class="pui-reveal">la Furia</span></h2>
        <ol class="h2-steps pui-rise">
          <li><span>01</span><div><b>Cargala</b><p>Contra monstruos y en PvP. La carga no se pierde.</p></div></li>
          <li><span>02</span><div><b>Desatala</b><p>Apretá <kbd>Shift</kbd>: más velocidad, más daño y tu poder de clase.</p></div></li>
          <li><span>03</span><div><b>No pares</b><p>Cada muerte suma tiempo. Encadená combos hasta la MASACRE.</p></div></li>
        </ol>
      </div>
      <div class="h2-feature-art h2-furia-art" aria-hidden="true"><span class="h2-feature-word">FURIA</span><img src="<?php echo $h2e($h2img.$h2art['furia']); ?>" alt="" width="1380" height="1570" loading="lazy" decoding="async"></div>
    </div>
  </section>

  <section class="h2-split pui-tear" aria-labelledby="h2-fractura-title">
    <div class="h2-split-side h2-nexo"><span>Nexo</span></div>
    <div class="h2-split-side h2-caos"><span>Caos</span></div>
    <div class="pui-wrap h2-split-copy">
      <p class="pui-kicker">La Fractura</p>
      <h2 class="pui-h2" id="h2-fractura-title"><span class="pui-reveal">Dos bandos.</span><br><span class="pui-reveal">Una guerra propia.</span></h2>
      <p class="pui-lead">Nexo o Caos. Tu bando, tus batallas, tu lugar en el salón de la fama.</p>
      <a class="pui-btn" href="<?php echo $h2e(__BASE_URL__.'fractura/'); ?>">Conocé la Fractura</a>
    </div>
  </section>

  <section class="pui-band pui-band--bone h2-feature h2-vinculos" aria-labelledby="h2-vinculos-title" style="--sys:#36e07f">
    <div class="pui-wrap h2-feature-grid h2-feature-grid--rev">
      <span class="h2-art-slot h2-art-wide" aria-hidden="true"><i>COMPAÑEROS</i><small>Arte: Elyndra, Aurion, Vaerion y Grum · verde</small></span>
      <div>
        <p class="pui-kicker">Tres mascotas evolutivas y un compañero único</p>
        <h2 class="pui-h2" id="h2-vinculos-title"><span class="pui-reveal">No avanzás solo</span></h2>
        <ul class="h2-names"><li>Elyndra</li><li>Aurion</li><li>Vaerion</li><li>Grum</li></ul>
        <a class="pui-btn pui-btn--dark" href="<?php echo $h2e(__BASE_URL__.'info/#vinculos'); ?>">Conocé a tus compañeros</a>
      </div>
    </div>
  </section>

  <section class="h2-feature h2-hub" id="hub" aria-labelledby="h2-hub-title" style="--sys:#9b6bff">
    <div class="pui-wrap h2-feature-grid">
      <div>
        <p class="pui-kicker">Un cliente con identidad propia</p>
        <h2 class="pui-h2" id="h2-hub-title"><span class="pui-reveal">Todo MU PANIC,</span><br><span class="pui-reveal">a una tecla</span></h2>
        <p class="pui-lead">Abrí <kbd>F11</kbd> y tenés tu personaje, la Fractura, los eventos, el mercado, la tienda y ocho minijuegos.</p>
        <ul class="h2-chips"><?php foreach($h2games as $g) { ?><li><?php echo $h2e($g); ?></li><?php } ?></ul>
      </div>
      <span class="h2-art-slot h2-art-tall" aria-hidden="true"><i>F11</i><small>Arte: Summoner con paneles del Hub · violeta</small></span>
    </div>
  </section>

  <section class="pui-band pui-band--bone h2-feature h2-vessara" aria-labelledby="h2-vessara-title" style="--sys:#ffc24a">
    <div class="pui-wrap h2-feature-grid h2-feature-grid--rev">
      <span class="h2-art-slot h2-art-tall" aria-hidden="true"><i>VESSARA</i><small>Arte: Vessara, reina mercader · dorado</small></span>
      <div>
        <p class="pui-kicker">El mercado de los jugadores</p>
        <h2 class="pui-h2" id="h2-vessara-title"><span class="pui-reveal">Lo que encontrás</span><br><span class="pui-reveal">tiene valor</span></h2>
        <p class="pui-lead">Buscá el equipo que te falta, publicá lo que conseguís jugando y cobrá en Zen o Eryns.</p>
        <a class="pui-btn pui-btn--dark" href="<?php echo $h2e(__BASE_URL__.'market/'); ?>">Explorá el mercado</a>
      </div>
    </div>
  </section>

  <section class="h2-community" aria-labelledby="h2-community-title">
    <div class="pui-wrap h2-community-grid">
      <div>
        <p class="pui-kicker">Comunidad</p>
        <h2 class="pui-h2" id="h2-community-title"><span class="pui-reveal">Jugá acompañado</span></h2>
        <p class="pui-lead">Buscá party, enterate de los eventos y pedí ayuda en nuestro Discord.</p>
      </div>
      <a class="pui-btn pui-btn--lg h2-discord" href="<?php echo $h2e($discordInvite); ?>" target="_blank" rel="noopener noreferrer">Entrar al Discord</a>
    </div>
  </section>

  <section class="h2-cta pui-tear pui-tear--bottom" aria-labelledby="h2-cta-title">
    <span class="pui-slash h2-cta-slash" aria-hidden="true"></span>
    <span class="h2-cta-word" aria-hidden="true">JUGÁ</span>
    <div class="pui-wrap">
      <h2 class="pui-h1" id="h2-cta-title">Tu historia<br>empieza hoy</h2>
      <ol class="h2-cta-steps"><li><span>01</span>Creá tu cuenta</li><li><span>02</span>Descargá el juego</li><li><span>03</span>Entrá a jugar</li></ol>
      <span class="pui-btn-wrap"><a class="pui-btn pui-btn--lg" href="<?php echo $h2e($h2play); ?>">Jugá gratis</a></span>
    </div>
  </section>
</div>
