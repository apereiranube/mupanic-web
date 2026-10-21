<?php
/**
 * MU PANIC · El servidor
 * Página de venta: reset dinámico, clases, PvP, Furia, juego limpio,
 * eventos en vivo (datos reales del Atlas), Free vs VIP y preguntas.
 */
if(!defined('access') or !access) die();
$srvBase = __BASE_URL__;
$srvImg = __PATH_TEMPLATE__.'img/';
$srvV3 = $srvImg.'server/v3/';

// Eventos con horario fijo, en hora Argentina, leídos del mismo origen que el Atlas.
require_once(__DIR__.'/../templates/mupanic/inc/atlas-event-runtime.php');
$srvEvents = [];
$srvEventSource = panicAtlasEvents();
$srvGuides = json_decode((string)@file_get_contents(__DIR__.'/../templates/mupanic/inc/atlas-event-guides.json'), true) ?: [];
$srvArt = [
    'blood-castle' => 'atlas/editorial/blood-castle.webp',
    'devil-square' => 'atlas/editorial/devil-square.webp',
    'chaos-castle' => 'server/v3/chaos-castle-06b2a2662c5f.webp',
    'pandora' => 'atlas/editorial/pandora.webp',
    'invasion-9' => 'fractura/vulcanus-world-v3.webp',
];
foreach(($srvEventSource['events'] ?? []) as $e) {
    if(empty($e['enabled']) || ($e['mode'] ?? '') !== 'scheduled' || preg_match('/^arena-/', $e['id'])) continue;
    $group = in_array($e['group'], ['classic','invasion','custom'], true) ? $e['group'] : ($e['group'] === 'boss' ? 'classic' : null);
    if(!$group) continue;
    $times = [];
    foreach($e['schedule'] as $row) {
        // Solo horarios diarios (sin año, mes, día ni día de semana fijo).
        if($row[0] !== -1 || $row[1] !== -1 || $row[2] !== -1 || $row[3] !== -1) continue;
        foreach(($row[4] === -1 ? range(0, 23) : [$row[4]]) as $h) $times[] = sprintf('%02d:%02d', $h, $row[5]);
    }
    if(!$times) continue;
    $times = array_values(array_unique($times)); sort($times);
    $art = $srvArt[$e['id']] ?? null;
    if(!$art) {
        $found = glob(__DIR__.'/../templates/mupanic/img/atlas/events/'.$e['id'].'-*.webp');
        $art = $found ? 'atlas/events/'.basename($found[0]) : 'atlas/editorial/pvp-arena.webp';
    }
    $name = $srvGuides[$e['id']]['displayName'] ?? $e['name'];
    $srvEvents[] = [
        'n' => (string)$name,
        'g' => $group,
        'd' => ((int)round((float)$e['durationMinutes'])) > 0 ? (int)round((float)$e['durationMinutes']) : 10,
        't' => $times,
        'i' => $art,
    ];
}
?>
<div id="srv-page" class="srv-page" data-img="<?php echo $srvImg; ?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Barlow+Condensed:wght@500;600;700&display=swap">
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><filter id="humo" x="-30%" y="-30%" width="160%" height="160%"><feTurbulence type="fractalNoise" baseFrequency="0.012 0.02" numOctaves="4" seed="7" result="n"/><feDisplacementMap in="SourceGraphic" in2="n" scale="140" xChannelSelector="R" yChannelSelector="G"/><feGaussianBlur stdDeviation="10"/></filter></svg>
  <!-- 1 · Portada -->
  <section class="scene hero sc sc-1">
    <div class="sbg v-l" aria-hidden="true"><div class="stick"><img loading="lazy" decoding="async" src="<?php echo $srvV3; ?>srv-01-portada-6144d14c00f7.webp" alt="" style="object-position:68% 50%"></div></div>
    <div class="content"><div class="wrap"><div class="copy">
      <p class="eyebrow">El servidor · Season 6 · Argentina</p>
      <h1><span class="glint">Un MU para<br>quedarse.</span></h1>
      <p class="lead">Rates pensados para durar, siete clases con un rol claro y un PvP que se gana peleando. Esto es lo que te espera adentro.</p>
      <div class="actions">
        <a class="btn" href="<?php echo $srvBase; ?>register/">Crear cuenta <span>↗</span></a>
        <a class="btn ghost" href="#eventos">Ver los eventos de hoy <span>↓</span></a>
      </div>
    </div></div></div>
    <div class="wrap hero-facts">
      <dl class="facts rv">
        <div><dt>Experiencia</dt><dd data-to="15" data-suf="x">15x</dd></div>
        <div><dt>Master EXP</dt><dd data-to="10" data-suf="x">10x</dd></div>
        <div><dt>Drop general</dt><dd data-to="25" data-suf="%">25%</dd></div>
        <div><dt>Reset</dt><dd><span data-to="400">400</span><small>nivel</small></dd></div>
        <div><dt>Clases</dt><dd>7</dd></div>
      </dl>
    </div>
  </section>


  <section class="lscene sc sc-2" aria-labelledby="num-t">
    <div class="sbg v-l" aria-hidden="true"><div class="stick"><img loading="lazy" decoding="async" src="<?php echo $srvV3; ?>srv-02-reset-41441b5a6209.webp" alt="" style="object-position:64% 50%"></div></div>
    <img loading="lazy" decoding="async" class="sjoin" src="<?php echo $srvV3; ?>join-01-escalinata-d6a280d6ab09.webp" alt="" aria-hidden="true">
    <div class="wrap">
      <div class="ladder">
        <div class="rv">
          <p class="eyebrow">Reset dinámico</p>
          <h2 id="num-t">Cada reset te deja más fuerte.</h2>
          <p class="note">Los puntos que recibís crecen a medida que avanzás. Llegar más lejos siempre vale la pena.</p>
          <a class="link" href="<?php echo $srvBase; ?>info/#rates" style="margin-top:18px">Ver los rates completos <span>↗</span></a>
        </div>
        <div class="ladder-r">
        <ol aria-label="Puntos por reset según la etapa" data-anim id="ladderOl">
          <li><div class="pil" aria-hidden="true"><img loading="lazy" decoding="async" class="p-off" src="<?php echo $srvV3; ?>pilar-1-off-85369d0ff2da.webp" alt=""><img loading="lazy" decoding="async" class="p-on" src="<?php echo $srvV3; ?>pilar-1-on-c615de19301c.webp" alt=""></div><b data-to="200">200</b><span>puntos · resets 1 a 4</span></li>
          <li><div class="pil" aria-hidden="true"><img loading="lazy" decoding="async" class="p-off" src="<?php echo $srvV3; ?>pilar-2-off-28e2692c419d.webp" alt=""><img loading="lazy" decoding="async" class="p-on" src="<?php echo $srvV3; ?>pilar-2-on-f85f37eacbb8.webp" alt=""></div><b data-to="250">250</b><span>puntos · resets 5 a 9</span></li>
          <li><div class="pil" aria-hidden="true"><img loading="lazy" decoding="async" class="p-off" src="<?php echo $srvV3; ?>pilar-3-off-b38c3d20c50c.webp" alt=""><img loading="lazy" decoding="async" class="p-on" src="<?php echo $srvV3; ?>pilar-3-on-5785fc68212b.webp" alt=""></div><b data-to="300">300</b><span>puntos · resets 10 a 14</span></li>
          <li><div class="pil" aria-hidden="true"><img loading="lazy" decoding="async" class="p-off" src="<?php echo $srvV3; ?>pilar-4-off-99a79ee5f03a.webp" alt=""><img loading="lazy" decoding="async" class="p-on" src="<?php echo $srvV3; ?>pilar-4-on-fa2f49278e20.webp" alt=""></div><b data-to="350">350</b><span>puntos · resets 15 a 20</span></li>
        </ol>
        <div class="sim rv" id="sim">
          <div class="sim-top"><label for="simR">Simulá tu recorrido</label><output id="simOut" for="simR">0<small>resets</small></output></div>
          <input type="range" id="simR" min="0" max="20" step="1" value="0" aria-label="Cantidad de resets">
          <div class="sim-ticks"><span>0</span><span>5</span><span>10</span><span>15</span><span>20</span></div>
          <div class="sim-res"><div><small>Puntos de reset acumulados</small><b id="simPts">0</b></div><div><small>Tu próximo reset suma</small><b id="simNext">+200</b></div><div><small>Total para repartir, con los puntos de nivel 400</small><b id="simTot">1.995</b><em class="sim-cls">DW, DK, Elfa y SU · <span id="simTot2">2.793</span> MG, DL y RF</em></div></div>
          <p class="sim-note">Los puntos del árbol Master se suman aparte.</p>
        </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 3 · Clases -->
  <section class="classes sc sc-3" aria-labelledby="cls-t">
    <div class="sbg v-c" aria-hidden="true"><div class="stick"><img loading="lazy" decoding="async" src="<?php echo $srvV3; ?>srv-03-galeria-c61958bcb911.webp" alt="" style="object-position:50% 50%"></div></div>
    <img loading="lazy" decoding="async" class="sjoin" src="<?php echo $srvV3; ?>join-02-estandartes-a76377851e07.webp" alt="" aria-hidden="true">
    <div class="wrap">
      <header class="rv">
        <div>
          <p class="eyebrow">Tu clase, tu rol</p>
          <h2 id="cls-t">Siete clases. Ninguna sobra.</h2>
        </div>
        <p>El balance está pensado para party contra party y guerras de guild. Cada clase gana cuando cumple su rol, y ninguna barre sola.</p>
      </header>
      <div class="roster rv d1" id="roster">
        <article class="cls on"><span class="prog"></span><img loading="lazy" decoding="async" src="<?php echo $srvV3; ?>clase-bk-1085daefba9e.webp" alt=""><div class="txt"><span class="role">La primera línea</span><h3>Blade Knight</h3><p>Aguanta, abre camino y castiga de cerca. Donde entra el BK, la party lo sigue.</p></div><button aria-label="Blade Knight"></button></article>
        <article class="cls"><span class="prog"></span><img loading="lazy" decoding="async" src="<?php echo $srvV3; ?>clase-sm-31f914308cb4.webp" alt=""><div class="txt"><span class="role">Poder en área</span><h3>Soul Master</h3><p>Limpia oleadas y controla el campo con magia. Indispensable para farmear y para sostener una línea.</p></div><button aria-label="Soul Master"></button></article>
        <article class="cls heart"><span class="prog"></span><img loading="lazy" decoding="async" src="<?php echo $srvV3; ?>clase-elfa-4dbf809fe80f.webp" alt=""><div class="txt"><span class="role">El corazón de la party</span><h3>Elfa</h3><p>Sus bendiciones de daño y defensa cambian una pelea. Una buena Elfa vale por dos personajes.</p></div><button aria-label="Elfa"></button></article>
        <article class="cls"><span class="prog"></span><img loading="lazy" decoding="async" src="<?php echo $srvV3; ?>clase-mg-0a974d1debb4.webp" alt=""><div class="txt"><span class="role">Espada y magia</span><h3>Magic Gladiator</h3><p>Combina golpe y hechizo para adaptarse a cada rival. Versátil en caza y en duelo.</p></div><button aria-label="Magic Gladiator"></button></article>
        <article class="cls"><span class="prog"></span><img loading="lazy" decoding="async" src="<?php echo $srvV3; ?>clase-dl-cb83792de129.webp" alt=""><div class="txt"><span class="role">El que comanda</span><h3>Dark Lord</h3><p>Lidera la party con su cuervo y su caballo. Su presencia hace más fuertes a los suyos.</p></div><button aria-label="Dark Lord"></button></article>
        <article class="cls"><span class="prog"></span><img loading="lazy" decoding="async" src="<?php echo $srvV3; ?>clase-su-2e8d2595d210.webp" alt=""><div class="txt"><span class="role">Maldición y control</span><h3>Summoner</h3><p>Debilita al rival y lo castiga a distancia. La pelea se decide antes de que te alcancen.</p></div><button aria-label="Summoner"></button></article>
        <article class="cls"><span class="prog"></span><img loading="lazy" decoding="async" src="<?php echo $srvV3; ?>clase-rf-2166a1f67a6d.webp" alt=""><div class="txt"><span class="role">Golpe a golpe</span><h3>Rage Fighter</h3><p>Combos cuerpo a cuerpo que no dan respiro. Ideal para quien pelea de frente.</p></div><button aria-label="Rage Fighter"></button></article>
      </div>
      <div class="foot">
        <p>Y cuando la barra se llena, cada clase despierta su propia Furia en PvP.</p>
        <a class="link" href="<?php echo $srvBase; ?>info/">Conocé cada clase en el Atlas <span>↗</span></a>
      </div>
    </div>
  </section>

  <!-- 4 · PvP -->
  <section class="pvp2 sc sc-4" aria-labelledby="pvp-t">
    <div class="sbg v-l" aria-hidden="true"><div class="stick"><img loading="lazy" decoding="async" src="<?php echo $srvV3; ?>srv-04-arena-bb4ee0c6adee.webp" alt="" style="object-position:80% 50%"></div></div>
    <img loading="lazy" decoding="async" class="sjoin" src="<?php echo $srvV3; ?>join-03-arena-a7c16a4f3268.webp" alt="" aria-hidden="true">
    <div class="wrap">
      <header class="rv">
        <div>
          <p class="eyebrow">PvP</p>
          <h2 id="pvp-t">Leé la pelea antes de perderla.</h2>
        </div>
        <p>Cada jugador lleva su vida y su escudo a la vista. Esta es la barra que vas a ver sobre cada rival, paso a paso.</p>
      </header>
      <div class="pvp-grid rv d1">
        <div class="stage">
          <img loading="lazy" decoding="async" class="stage-bg" src="<?php echo $srvImg; ?>atlas/editorial/pvp-arena.webp" alt="">
          <div class="unit" role="img" aria-label="Barra de vida y escudo de un jugador">
            <div class="floaters" id="floaters"></div>
            <div class="callout" id="callout"><span id="callout-t"></span><i></i></div>
            <div class="bar" id="bar"><div class="bar-f"><div class="bar-in"><span class="b-life" id="bLife"></span><span class="b-sd" id="bSd"></span><span class="b-lost" id="bLost"></span></div></div></div>
            <div class="plate2"><span class="g">Nexo</span><span class="rule"></span><b>Varkor</b></div>
          </div>
          <div class="stage-cap"><span class="sw" style="--k:#3cb43a">Vida</span><span class="sw" style="--k:#e7c56d">Escudo (SD)</span><span class="sw" style="--k:#fff">Daño recién recibido</span><span class="sw" style="--k:#5a0d0a">Daño marcado</span></div>
        </div>
        <ol class="steps" id="steps">
          <li><button type="button" id="st-1"><span class="k">1</span><span><b>Vida y escudo</b><span class="t">Verde es tu vida. El tramo dorado es tu escudo (SD), pegado al final de la vida.</span></span><i class="pg"></i></button></li>
          <li><button type="button" id="st-2"><span class="k">2</span><span><b>El escudo absorbe</b><span class="t">Mientras tengas SD, el escudo se lleva casi todo el golpe y la vida apenas se mueve.</span></span><i class="pg"></i></button></li>
          <li><button type="button" id="st-3"><span class="k">3</span><span><b>Escudo roto</b><span class="t">Sin SD, cada golpe va directo a la vida. Ahí empieza la pelea de verdad.</span></span><i class="pg"></i></button></li>
          <li><button type="button" id="st-4"><span class="k">4</span><span><b>Cada golpe se ve</b><span class="t">Lo que perdés brilla en blanco y queda marcado en rojo un instante. En un combo se acumula.</span></span><i class="pg"></i></button></li>
          <li><button type="button" id="st-5"><span class="k">5</span><span><b>Zona de peligro</b><span class="t">Por debajo del 30%, la vida late en rojo. Momento de cuidarte o de rematar.</span></span><i class="pg"></i></button></li>
          <li><button type="button" id="st-6"><span class="k">6</span><span><b>La poción justa</b><span class="t">Una poción de escudo lo recarga al instante. Cada poción tiene su espera: gana quien la usa a tiempo.</span></span><i class="pg"></i></button></li>
        </ol>
      </div>
      <div class="pvp-notes">
        <div class="rv"><h3>De un vistazo, quién es quién</h3><p>La placa del nombre lleva una franja de color.</p><p class="stripes"><span style="--k:#3fb53a">Tu personaje</span><span style="--k:#3f7fd6">Tu guild y aliados</span><span style="--k:#c8372a">Guild rival</span></p></div>
        <div class="rv d1"><h3>Donde se pelea, se ve</h3><p>Las barras aparecen en todas las zonas de caza y se ocultan en las ciudades, para que la zona segura quede limpia.</p></div>
        <div class="rv d2"><h3>La arena de Lorencia</h3><p>Un octógono en el centro de la ciudad, con las barras siempre visibles, para resolver las cuentas pendientes.</p></div>
      </div>
    </div>
  </section>

  <!-- 4b · Furia -->
  <section class="furia2 sc sc-5" id="furia" aria-labelledby="fur-t">
    <div class="sbg v-l" aria-hidden="true"><div class="stick"><img loading="lazy" decoding="async" src="<?php echo $srvV3; ?>srv-05-furia-090af60271dd.webp" alt="" style="object-position:78% 50%"></div></div>
    <img loading="lazy" decoding="async" class="sjoin" src="<?php echo $srvV3; ?>join-04-lava-914601fe4486.webp" alt="" aria-hidden="true">
    <div class="wrap">
      <header class="rv">
        <div>
          <p class="eyebrow">Sistema exclusivo</p>
          <h2 id="fur-t">Así se juega la Furia.</h2>
        </div>
        <p>La barra que se llena peleando y explota con Shift. Mirá cómo funciona, de la primera carga a la Masacre.</p>
      </header>
      <div class="pvp-grid rv d1">
        <div class="fstage" id="fstage">
          <img loading="lazy" decoding="async" class="fstage-bg" src="<?php echo $srvImg; ?>home/furia-world-v3.webp" alt="">
          <div class="floaters" id="ffloat"></div>
          <div class="fcore" role="img" aria-label="Barra de Furia del juego">
            <div class="callout" id="fco"><span id="fco-t"></span><i></i></div>
            <p class="fready" id="fready">FURIA LISTA <span aria-hidden="true">—</span> <kbd id="fkey">SHIFT</kbd></p>
            <div class="fbar" id="fbar">
              <div class="energy"><img loading="lazy" decoding="async" src="<?php echo $srvImg; ?>home/furia-ui-energy-v3.webp" alt=""><video src="<?php echo $srvImg; ?>home/furia-energy-v3.webm" muted loop playsinline autoplay></video></div>
              <img loading="lazy" decoding="async" class="frame" src="<?php echo $srvImg; ?>home/furia-ui-frame-v3.webp" alt="">
              <video class="aura" src="<?php echo $srvImg; ?>home/furia-aura-v3.webm" muted loop playsinline autoplay></video>
              <img loading="lazy" decoding="async" class="skull" src="<?php echo $srvImg; ?>home/furia-ui-skull-v3.webp" alt="">
              <img loading="lazy" decoding="async" class="eyes" src="<?php echo $srvImg; ?>home/furia-ui-eyes-v3.webp" alt="">
            </div>
            <div class="fchips" id="fchips"><span>Velocidad de ataque +20%</span><span>Más velocidad al caminar</span><span>Daño extra a monstruos</span><span>Aura de Furia</span></div>
            <div class="fcombo"><span class="fdigits" id="fdig"></span><span class="flabel" id="flabel"></span><img loading="lazy" decoding="async" class="fmasacre" id="fmas" src="<?php echo $srvImg; ?>home/furia-ui-masacre-v3.webp" alt="" hidden></div>
            <div class="fclasses" id="fcls"><span>Blade Knight</span><span>Soul Master</span><span>Elfa</span><span>Magic Gladiator</span><span>Dark Lord</span><span>Summoner</span><span>Rage Fighter</span></div>
            <div class="fpow" id="fpow" aria-live="polite"><span class="fpow-c" id="fpow-c"></span><b id="fpow-n"></b><span class="fpow-d" id="fpow-d"></span></div>
          </div>
        </div>
        <ol class="steps" id="fsteps">
          <li><button type="button" id="fs-1"><span class="k">1</span><span><b>Cargala peleando</b><span class="t">Cada golpe que das llena la barra, contra monstruos y contra jugadores. En PvP carga más rápido, y la carga no se pierde.</span></span><i class="pg"></i></button></li>
          <li><button type="button" id="fs-2"><span class="k">2</span><span><b>Furia lista</b><span class="t">Con la barra llena, la calavera despierta. Soltá Shift cuando quieras: la Furia te espera.</span></span><i class="pg"></i></button></li>
          <li><button type="button" id="fs-3"><span class="k">3</span><span><b>Desatala</b><span class="t">Tu personaje se enciende: más velocidad de ataque, más velocidad al caminar, aura roja y daño extra a monstruos.</span></span><i class="pg"></i></button></li>
          <li><button type="button" id="fs-4"><span class="k">4</span><span><b>No pares</b><span class="t">La barra se consume mientras dura. Cada enemigo que cae te devuelve segundos, hasta un máximo.</span></span><i class="pg"></i></button></li>
          <li><button type="button" id="fs-5"><span class="k">5</span><span><b>Encadená el combo</b><span class="t">Cada muerte suma al contador. A las 5 es COMBO, a las 12 IMPARABLE y a las 20, MASACRE.</span></span><i class="pg"></i></button></li>
          <li><button type="button" id="fs-6"><span class="k">6</span><span><b>Tu clase, tu poder</b><span class="t">Cada clase despierta un poder propio, contra jugadores y contra monstruos. Vale en todo el continente: arenas, eventos y Castle Siege.</span></span><i class="pg"></i></button></li>
        </ol>
      </div>
    </div>
  </section>

  <!-- 5 · Juego limpio -->
  <section class="fair sc sc-6" aria-labelledby="fair-t">
    <div class="sbg v-c" aria-hidden="true"><div class="stick"><img loading="lazy" decoding="async" src="<?php echo $srvV3; ?>srv-06-mercado-c2e867804708.webp" alt="" style="object-position:50% 50%"></div></div>
    <img loading="lazy" decoding="async" class="sjoin" src="<?php echo $srvV3; ?>join-05-humo-4c2383781466.webp" alt="" aria-hidden="true">
    <div class="wrap grid">
      <div class="rv">
        <p class="eyebrow">Juego limpio</p>
        <h2 id="fair-t">Lo que tenés, te lo ganaste.</h2>
        <img loading="lazy" decoding="async" class="seal" src="<?php echo $srvImg; ?>market/vessara-seal-v2.svg" alt="">
      </div>
      <ul class="pledges" data-anim>
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M14.5 3.5l6 6-9 9-4-4z"/><path d="M7.5 14.5L3 19l2 2 4.5-4.5"/></svg><h3>Tu personaje se arma jugando</h3><p>Equipo, joyas, alas y frutas salen del juego. Las frutas no se compran: se fabrican.</p></li>
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v10M9 9.5c0-1 1.3-1.8 3-1.8s3 .8 3 1.8-1.3 1.6-3 2-3 1-3 2 1.3 1.8 3 1.8 3-.8 3-1.8"/></svg><h3>Los Eryns también se ganan</h3><p>Vendé tu drop en el Mercado de Vessara y cobrá en Eryns sin poner un peso.</p></li>
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M12 3l7.5 3v5.5c0 4.6-3.2 8.2-7.5 9.5-4.3-1.3-7.5-4.9-7.5-9.5V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/></svg><h3>Un cliente protegido</h3><p>El launcher revisa tus archivos y detecta programas de trampa antes y durante la partida.</p></li>
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M12 4v16M5 20h14M6 8h12M6 8l-3 6h6zM18 8l-3 6h6z"/></svg><h3>Balance que se cuida</h3><p>Cada clase tiene su rol, y el equipo sigue de cerca cada pelea para que siga siendo pareja.</p></li>
      </ul>
    </div>
    <div class="wrap fair-demos">
      <div class="demo rv">
        <h3>Un cliente protegido</h3>
        <p>Cada vez que apretás JUGAR, el launcher revisa tus archivos y busca programas de trampa. Y mientras jugás, lo sigue vigilando.</p>
        <div class="lch2" id="lch" role="img" aria-label="Launcher de MU PANIC: verifica archivos, detecta un programa de trampa y bloquea JUGAR hasta que se cierra">
          <img loading="lazy" decoding="async" class="lshell" src="<?php echo $srvV3; ?>launcher-d51fd19d08ce.webp" alt="">
          <div class="side">
            <img loading="lazy" decoding="async" class="logo" src="<?php echo $srvImg; ?>brand/mu-panic-crystal-bronze-matte-v2.webp" alt="MU PANIC">
            <ul>
              <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-7 9 7v9H3z"/><path d="M9 20v-6h6v6"/></svg>Sitio web</li>
              <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="8" r="4"/><path d="M3 20c1-4 4-6 7-6s6 2 7 6M18 8v6M15 11h6"/></svg>Crear cuenta</li>
              <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z"/></svg>Comprar Eryns<em>+20%</em></li>
              <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M10 20V4M16 20v-8M22 20H2"/></svg>Rankings</li>
              <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6l6-2 6 2 6-2v14l-6 2-6-2-6 2z"/><path d="M9 4v14M15 6v14"/></svg>Atlas del mundo</li>
              <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="1"/><path d="M3 10h18"/></svg>Mercado de Vessara</li>
            </ul>
            <div class="disc"><b>DISCORD</b><span>Sumate a la comunidad</span></div>
          </div>
          <div class="top">
            <div class="gpill" id="lPill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7.5 3v5.5c0 4.6-3.2 8.2-7.5 9.5-4.3-1.3-7.5-4.9-7.5-9.5V6z"/></svg><span id="lPillT"><b>Guard</b> activo</span></div>
            <div class="srv"><i></i><b>Servidor</b> online · 1 jugando</div>
            <div class="win"><span>–</span><span>×</span></div>
          </div>
          <div class="main">
            <p class="kick">TEMPORADA 6 · ARGENTINA</p>
            <h4>EL CAOS <span>DESPIERTA</span><br>EN LORENCIA</h4>
            <p class="desc">Resets, clanes y guerras de castillo con balance pensado para party. Entrá, armá tu grupo y reclamá tu lugar en el ranking.</p>
            <div class="nextev"><div><small>PRÓXIMO EVENTO</small><b>PLAZA DEL DIABLO</b></div><div class="cd"><span><b>00</b><i>HS</i></span><span><b>16</b><i>MIN</i></span><span><b>17</b><i>SEG</i></span></div></div>
            <ul class="news"><li><time>16 OCT</time>Party rebalanceada: jugar en grupo rinde más</li><li><time>15 OCT</time>Nuevas skins: Moon Sword, Larkan y Crescent Axe</li></ul>
          </div>
          <div class="toast" id="lToast"><b>Guard detuvo el inicio</b>Se detectó Cheat Engine. El juego no se abre mientras siga en uso.</div>
          <div class="status"><span class="t" id="lT">Listo para jugar</span><div class="bar"><i id="lBar"></i></div><span class="s" id="lS">No hay actualizaciones nuevas.</span></div>
          <div class="play"><span class="lbl-play">JUGAR</span><span class="lbl-lock">BLOQUEADO</span></div>
        </div>
      </div>
      <div class="demo rv d1">
        <h3>Los Eryns se ganan jugando</h3>
        <p>Lo que cazás tiene valor. Publicalo en el Mercado de Vessara y cobrá en Eryns, aunque estés desconectado.</p>
        <div class="loop" id="loop">
          <div class="lnodes">
            <div class="lline"><i id="lLine"></i></div>
            <span class="ltoken" id="lTok"></span>
            <div class="lnode"><span class="lic"><img loading="lazy" decoding="async" src="<?php echo $srvImg; ?>atlas/drops/jewel-bless-2c80b97e4d75.webp" alt=""><span class="ltag">¡Drop!</span></span><b>Cazás</b></div>
            <div class="lnode"><span class="lic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M3 9l2-5h14l2 5"/><path d="M3 9h18v2a3 3 0 01-6 0 3 3 0 01-6 0 3 3 0 01-6 0z"/><path d="M5 13v7h14v-7"/><path d="M10 20v-4h4v4"/></svg><span class="ltag">1.200 Eryns</span></span><b>Publicás</b></div>
            <div class="lnode"><span class="lic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M20 14.5A8 8 0 019.5 4a8 8 0 1010.5 10.5z"/><path d="M15 4h3l-3 3h3M19 9h2l-2 2h2"/></svg><span class="ltag">En venta</span></span><b>Desconectado</b></div>
            <div class="lnode"><span class="lic"><img loading="lazy" decoding="async" class="ecoin" src="<?php echo $srvImg; ?>market/eryns-vessara-v1.webp" alt=""><span class="ltag">¡Vendido!</span></span><b>Cobrás</b></div>
            <div class="coins" id="coins"></div>
          </div>
          <p class="lcap" id="lCap"></p>
          <div class="lsum"><span>Ganado jugando</span><b id="lSum">0</b><span>Eryns</span></div>
        </div>
      </div>
    </div>
    <div class="wrap">
      <div class="feat rv" id="feat">
        <div class="feat-copy">
          <div>
            <h3>Destacá tu publicación</h3>
            <p>Pagá el destacado y un miembro de la Cuadrilla Rota sale a ofrecer tu objeto por Loren Market, con su cartel. En la web, tu publicación sube a Destacados.</p>
          </div>
          <button type="button" class="fswitch" id="fswitch" aria-pressed="false"><i></i>Destacar publicación</button>
        </div>
        <div class="fpanel">
          <small>En Loren Market</small>
          <div class="plaza3d" id="plaza3d" role="img" aria-label="Plaza de Loren Market en bloques: los vendedores de la Cuadrilla Rota caminan y ofrecen objetos">
            <canvas id="pz"></canvas>
            <div class="pz-tags" id="pzTags"></div>
            <p class="pz-hint">Arrastrá para girar la plaza</p>
          </div>
        </div>
        <div class="fpanel">
          <small>En la web</small>
          <div class="vitrina">
            <div class="vtabs"><span class="act">Destacados</span><span>Todas</span><span>Eryns</span><span>Zen</span></div>
            <div class="vlist" id="vlist">
              <div class="vrow" data-k="a"><span class="ic">◇</span><span><b>Chromatic Staff +15</b><em>Juanita</em></span><span class="pr"><img loading="lazy" decoding="async" src="<?php echo $srvImg; ?>market/eryns-vessara-v1.webp" alt="">900</span></div>
              <div class="vrow" data-k="b"><span class="ic">◇</span><span><b>Hurricane Boots +15</b><em>PANIC</em></span><span class="pr">1.000.000 Zen</span></div>
              <div class="vrow" data-k="me"><span class="ic">◆</span><span><b>Hurricane Armor +15</b><em>Tu publicación</em></span><span class="pr"><img loading="lazy" decoding="async" src="<?php echo $srvImg; ?>market/eryns-vessara-v1.webp" alt="">1.200</span><span class="badge">Destacado</span></div>
            </div>
            <p class="vnote" id="vnote">Tu publicación espera en la vidriera, junto a las demás.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 6 · Eventos en vivo. En el template: los datos salen del mismo JSON que usa el Atlas (script[data-event-data]). -->
  <section class="events sc sc-7" id="eventos" aria-labelledby="ev-t">
    <div class="sbg v-l" aria-hidden="true"><div class="stick"><img loading="lazy" decoding="async" src="<?php echo $srvV3; ?>srv-07-eventos-5176d4ff2349.webp" alt="" style="object-position:75% 50%"></div></div>
    <img loading="lazy" decoding="async" class="sjoin" src="<?php echo $srvV3; ?>join-06-nubes-565824298475.webp" alt="" aria-hidden="true">
    <div class="wrap">
      <header class="rv">
        <div>
          <p class="eyebrow">Eventos en vivo</p>
          <h2 id="ev-t">El continente no para.</h2>
        </div>
        <p class="tz">Horarios de Argentina · se actualiza solo</p>
      </header>
      <div class="clock rv d1">
        <div class="next" aria-live="polite">
          <img id="nx-img" src="" alt="">
          <div class="in">
            <span class="state" id="nx-state"><i></i><span>Próximo evento</span></span>
            <h3 id="nx-name">—</h3>
            <p class="when" id="nx-when"></p>
            <div class="count" id="nx-count" aria-label="Cuenta regresiva">
              <div><b id="c-h">00</b><small>horas</small></div><span class="sep">:</span>
              <div><b id="c-m">00</b><small>minutos</small></div><span class="sep">:</span>
              <div><b id="c-s">00</b><small>segundos</small></div>
            </div>
          </div>
        </div>
        <div class="agenda">
          <div class="filters" role="group" aria-label="Filtrar eventos">
            <button type="button" id="f-all" data-g="all" aria-pressed="true">Todos</button>
            <button type="button" id="f-classic" data-g="classic" aria-pressed="false">Clásicos</button>
            <button type="button" id="f-invasion" data-g="invasion" aria-pressed="false">Invasiones</button>
            <button type="button" id="f-custom" data-g="custom" aria-pressed="false">Competencias</button>
          </div>
          <ul class="list" id="ev-list"></ul>
        </div>
        <div class="day">
          <div class="ruler" id="ruler" aria-hidden="true"><div class="track"></div></div>
          <div class="legend"><span>Clásicos</span><span class="i">Invasiones</span><span class="c">Competencias</span></div>
        </div>
      </div>
      <div class="foot">
        <p>Doppelganger, Imperial Guardian, Kanturu y Raklion se juegan cuando cumplís las condiciones de acceso.</p>
        <a class="link" href="<?php echo $srvBase; ?>info/#eventos">Ver premios y requisitos en el Atlas <span>↗</span></a>
      </div>
    </div>
  </section>

  <!-- 6b · Free vs VIP -->
  <section class="vip sc sc-8" id="vip" aria-labelledby="vip-t">
    <div class="sbg v-c" aria-hidden="true"><div class="stick"><img loading="lazy" decoding="async" src="<?php echo $srvV3; ?>srv-08-tesoro-5b82e4129d10.webp" alt="" style="object-position:50% 50%"></div></div>
    <img loading="lazy" decoding="async" class="sjoin" src="<?php echo $srvV3; ?>join-07-oro-92e4b9da9cf9.webp" alt="" aria-hidden="true">
    <div class="wrap">
      <header class="rv">
        <div>
          <p class="eyebrow">Membresía</p>
          <h2 id="vip-t">Free o VIP.</h2>
        </div>
        <p>Todo MU PANIC se juega en Free. El VIP te ahorra tiempo, te deja avanzar más rápido y suma comodidades. Probá el cambio.</p>
      </header>
      <div class="vtoggle rv d1" role="group" aria-label="Elegí el tipo de cuenta">
        <button type="button" id="v-free" aria-pressed="true">Cuenta Free</button>
        <button type="button" id="v-vip" aria-pressed="false"><img loading="lazy" decoding="async" class="seal" src="<?php echo $srvV3; ?>sello-vip-86780f136c99.webp" alt="">VIP MU PANIC</button>
      </div>
      <div class="vgrid rv d1">
        <div class="vcard gain">
          <span class="vk">Experiencia</span>
          <div class="vv"><span id="vExp">15x</span><small id="vExpS">ritmo base</small></div>
          <div class="vexp"><i id="vExpBar"></i></div>
          <div class="vexp-row"><span>Nivel <b id="vLvl">312</b></span><span id="vExpT">Subís a tu ritmo.</span></div>
        </div>
        <div class="vcard gain">
          <span class="vk">Drop general</span>
          <div class="vv"><span id="vDrop">25%</span><small id="vDropS">drop base</small></div>
          <div class="vdrop"><span class="mob" id="vMob">☠</span><div class="floor" id="vFloor"></div></div>
          <div class="vexp-row"><span>Joyas al suelo <b id="vJw">0</b></span><span id="vDropT"></span></div>
        </div>
        <div class="vcard gain">
          <span class="vk">Publicaciones en Vessara</span>
          <div class="vv"><span id="vSlotsN">3</span><small>a la venta al mismo tiempo</small></div>
          <div class="vslots" id="vSlots"></div>
          <p id="vSlotsT">Tres objetos en la vidriera a la vez.</p>
        </div>
        <div class="vcard gain">
          <span class="vlock"><img loading="lazy" decoding="async" class="seal" src="<?php echo $srvV3; ?>sello-vip-86780f136c99.webp" alt="">Solo VIP</span>
          <span class="vk">Helper offline</span>
          <div class="voff">
            <span class="moon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M20 14.5A8 8 0 019.5 4a8 8 0 1010.5 10.5z"/></svg></span>
            <div class="log" id="vOff"></div>
          </div>
          <p id="vOffT">Cuando te desconectás, tu personaje se queda quieto.</p>
        </div>
        <div class="vcard gain">
          <span class="vlock"><img loading="lazy" decoding="async" class="seal" src="<?php echo $srvV3; ?>sello-vip-86780f136c99.webp" alt="">Solo VIP</span>
          <span class="vk">Banco de joyas automático</span>
          <div class="vbank">
            <div class="vbox"><div id="vInv"></div>Inventario</div>
            <div class="lane" id="vLane"><span class="hand">Depositás a mano</span></div>
            <div class="vbox"><div style="display:grid;place-items:center"><span class="cnt" id="vBank">0</span></div>Banco</div>
          </div>
          <p id="vBankT">Las joyas ocupan tu inventario hasta que las guardás vos.</p>
        </div>
        <div class="vcard gain">
          <span class="vk">Tienda</span>
          <div class="vv"><span id="vDisc">0%</span><small>de descuento en todo</small></div>
          <div class="vprice"><span class="tagp"><img loading="lazy" decoding="async" src="<?php echo $srvImg; ?>market/eryns-vessara-v1.webp" alt=""><s>1.000</s><b id="vPrice">1.000</b></span><span class="off">−10%</span></div>
          <p id="vDiscT">Pagás el precio de lista.</p>
        </div>
        <div class="vcard gain">
          <span class="vlock"><img loading="lazy" decoding="async" class="seal" src="<?php echo $srvV3; ?>sello-vip-86780f136c99.webp" alt="">Solo VIP</span>
          <span class="vk">Baúl extra</span>
          <div class="vchests"><span class="vchest"></span><span class="vchest"></span><span class="vchest vipc" title="Baúl VIP"></span></div>
          <p id="vChestT">Usás los baúles de tu cuenta.</p>
        </div>
        <div class="vcard gain">
          <span class="vlock"><img loading="lazy" decoding="async" class="seal" src="<?php echo $srvV3; ?>sello-vip-86780f136c99.webp" alt="">Solo VIP</span>
          <span class="vk">AutoPost</span>
          <div class="vchat" id="vChat"></div>
          <p id="vChatT">Tus anuncios en el chat los escribís cada vez.</p>
        </div>
      </div>
      <div class="vfoot">
        <p>El VIP se consigue con Eryns, desde la Tienda del Hub F11.</p>
        <a class="link" href="<?php echo $srvBase; ?>info/#rates">Ver todas las tasas en el Atlas <span>↗</span></a>
      </div>
    </div>
  </section>

  <!-- 7 · Preguntas -->
  <section class="faq sc sc-9" aria-labelledby="faq-t">
    <div class="sbg v-l" aria-hidden="true"><div class="stick"><img loading="lazy" decoding="async" src="<?php echo $srvV3; ?>srv-09-taberna-1cccd30c3f3e.webp" alt="" style="object-position:78% 50%"></div></div>
    <img loading="lazy" decoding="async" class="sjoin" src="<?php echo $srvV3; ?>join-08-pergamino-7696e85656f3.webp" alt="" aria-hidden="true">
    <div class="wrap grid">
      <div class="rv">
        <p class="eyebrow">Antes de entrar</p>
        <h2 id="faq-t">Preguntas rápidas.</h2>
      </div>
      <div>
        <details open><summary>¿Es pay to win?</summary><p>No. Lo que define a tu personaje (equipo, joyas, alas y frutas) se consigue jugando. La tienda acompaña tu recorrido, pero no reemplaza el juego.</p></details>
        <details><summary>¿Cómo consigo Eryns?</summary><p>Jugando y comerciando: vendé tu drop en el Mercado de Vessara. También podés recargarlos desde la web.</p></details>
        <details><summary>¿Necesito una PC potente?</summary><p>No. El launcher tiene un modo rendimiento que baja los efectos con un clic.</p></details>
        <details><summary>¿Está todo en castellano?</summary><p>Sí: el juego, la web, el launcher y el soporte.</p></details>
        <details><summary>¿Puedo empezar hoy?</summary><p>Sí. Creá tu cuenta, descargá el cliente y entrá: el launcher se encarga de mantenerte actualizado.</p></details>
      </div>
    </div>
  </section>

  <!-- 8 · Cierre -->
  <section class="scene sv-close sc sc-10" aria-labelledby="end-t">
    <div class="sbg v-c" aria-hidden="true"><div class="stick"><img loading="lazy" decoding="async" src="<?php echo $srvV3; ?>srv-10-portal-171cbada9245.webp" alt="" style="object-position:50% 55%"></div></div>
    <img loading="lazy" decoding="async" class="sjoin" src="<?php echo $srvV3; ?>join-09-niebla-91178de8ff70.webp" alt="" aria-hidden="true">
    <div class="content"><div class="wrap"><div class="copy">
      <p class="eyebrow">Tu lugar ya está esperando</p>
      <h2 id="end-t">Elegí tu clase. El resto se gana.</h2>
      <div class="actions">
        <a class="btn" href="<?php echo $srvBase; ?>register/">Crear cuenta <span>↗</span></a>
        <a class="btn ghost" href="<?php echo $srvBase; ?>downloads/">Descargar <span>↓</span></a>
        <a class="btn ghost" href="https://discord.gg/fP4Mxcsee">Entrar al Discord <span>↗</span></a>
      </div>
    </div></div></div>
  </section>

<script type="application/json" id="srv-events"><?php echo json_encode($srvEvents, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE); ?></script>
</div>
