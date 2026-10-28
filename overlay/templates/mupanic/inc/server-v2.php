<?php
/**
 * MU PANIC · El servidor (estilo nuevo).
 * Vende el server en profundidad: rates y reset dinámico, clases, PvP, Furia, juego limpio,
 * eventos en vivo (mismos datos que el Atlas), Free vs VIP y preguntas.
 * Las demos y el reloj de eventos los maneja js/server-v2.js.
 */
if(!defined('access') || !access) die();
$sv = function($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$svBase = __BASE_URL__;
$svGem = __PATH_TEMPLATE__.'img/votar/votar-gema-64-4559844ddcec.webp';

// Eventos con horario fijo, en hora Argentina, leídos del mismo origen que el Atlas.
require_once __DIR__.'/atlas-event-runtime.php';
$svEvents = [];
$svGuides = json_decode((string)@file_get_contents(__DIR__.'/atlas-event-guides.json'), true) ?: [];
try { $svSource = panicAtlasEvents(); } catch(Throwable $e) { $svSource = []; }
foreach(($svSource['events'] ?? []) as $e) {
    if(empty($e['enabled']) || ($e['mode'] ?? '') !== 'scheduled' || preg_match('/^arena-/', (string)$e['id'])) continue;
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
    $svEvents[] = [
        'n' => (string)($svGuides[$e['id']]['displayName'] ?? $e['name']),
        'g' => $group,
        'd' => ((int)round((float)$e['durationMinutes'])) > 0 ? (int)round((float)$e['durationMinutes']) : 10,
        't' => $times,
    ];
}

$svClasses = [
  ['bk','Blade Knight','La primera línea','Aguanta, abre camino y castiga de cerca. Donde entra el BK, la party lo sigue.','Coloso','Resiste el aturdimiento y parte de cada golpe atraviesa la defensa rival.','#29c8ff'],
  ['sm','Soul Master','Poder en área','Limpia oleadas y controla el campo con magia. Indispensable para farmear y para sostener una línea.','Cataclismo','Maná infinito mientras dura la Furia y más golpes excelentes.','#9b6bff'],
  ['elfa','Elfa','El corazón de la party','Sus bendiciones de daño y defensa cambian una pelea. Una buena Elfa vale por dos personajes.','Certera','Sus flechas atraviesan la defensa y aciertan más críticos.','#36e07f'],
  ['mg','Magic Gladiator','Espada y magia','Combina golpe y hechizo para adaptarse a cada rival. Versátil en caza y en duelo.','Duelista','Más chance de doble daño y más golpes excelentes.','#ff3fa4'],
  ['dl','Dark Lord','El que comanda','Lidera la party con su cuervo y su caballo. Su presencia hace más fuertes a los suyos.','Emperador','Más críticos y chance de triple daño.','#ff4655'],
  ['su','Summoner','Maldición y control','Debilita al rival y lo castiga a distancia. La pelea se decide antes de que te alcancen.','Rompealmas','Parte de su daño atraviesa el escudo SD del rival.','#c58bff'],
  ['rf','Rage Fighter','Golpe a golpe','Combos cuerpo a cuerpo que no dan respiro. Ideal para quien pelea de frente.','Vampiro','Cada golpe le devuelve vida.','#ff7a1a'],
];
$svImg = __PATH_TEMPLATE__.'img/';
$svArt = ['bk'=>'inicio/inicio-bk-carga-01b160ca5b33.webp','elfa'=>'inicio/inicio-elfa-1378c4409a7b.webp','mg'=>'inicio/inicio-mg-68edd6affa37.webp','dl'=>'votar/votar-darklord-3cd38766111c.webp','su'=>'inicio/inicio-summoner-e2e8ada192ae.webp','rf'=>'inicio/inicio-furia-d7c277cb6fe1.webp'];
$svColorName = ['bk'=>'cian','sm'=>'violeta','elfa'=>'verde','mg'=>'magenta','dl'=>'rojo','su'=>'lila','rf'=>'naranja'];
$svReveal = function($lines) { return implode('<br>', array_map(function($l){ return '<span class="pui-reveal">'.$l.'</span>'; }, $lines)); };
?>
<div class="sv pui" id="sv-page">

  <section class="sv-hero" aria-labelledby="sv-title">
    <div class="sv-hero-block" aria-hidden="true"><span class="sv-hero-word">MU PANIC</span></div>
    <div class="sv-hero-art"><span class="h2-art-slot" style="--sys:#ff3fa4" aria-hidden="true"><i>MG</i><small>Arte: Magic Gladiator · magenta</small></span></div>
    <div class="pui-wrap sv-hero-copy">
      <p class="pui-kicker">El servidor</p>
      <h1 class="pui-h1" id="sv-title">Un MU para<br>quedarse</h1>
      <p class="pui-lead">Rates pensados para durar, siete clases con un rol claro y un PvP que se gana peleando. Esto es lo que te espera adentro.</p>
      <div class="sv-actions">
        <span class="pui-btn-wrap"><a class="pui-btn pui-btn--lg" href="<?php echo $sv($svBase.'register/'); ?>">Crear cuenta</a></span>
        <a class="pui-btn pui-btn--ghost" href="#eventos">Ver los eventos de hoy</a>
      </div>
    </div>
  </section>

  <div class="sv-facts">
    <dl class="pui-wrap sv-facts-grid">
      <div class="pui-stat"><dt>Experiencia</dt><dd>15x</dd></div>
      <div class="pui-stat"><dt>Master EXP</dt><dd>10x</dd></div>
      <div class="pui-stat"><dt>Drop general</dt><dd>25%</dd></div>
      <div class="pui-stat"><dt>Reset</dt><dd>400<small>nivel</small></dd></div>
      <div class="pui-stat"><dt>Clases</dt><dd>7</dd></div>
    </dl>
  </div>

  <!-- Reset dinámico -->
  <section class="pui-band pui-band--bone sv-reset" aria-labelledby="sv-reset-t">
    <span class="pui-band-word" aria-hidden="true">RESET</span>
    <div class="pui-wrap sv-split">
      <div>
        <span class="pui-hud"><i></i></span>
        <p class="pui-kicker">Reset dinámico</p>
        <h2 class="pui-h2" id="sv-reset-t"><?php echo $svReveal(['Cada reset te','deja más fuerte']); ?></h2>
        <p class="pui-lead">Los puntos que recibís crecen a medida que avanzás. Llegar más lejos siempre vale la pena.</p>
        <ol class="sv-ladder" id="svLadder" aria-label="Puntos por reset según la etapa">
          <li><b>200</b><span>puntos · resets 1 a 4</span></li>
          <li><b>250</b><span>puntos · resets 5 a 9</span></li>
          <li><b>300</b><span>puntos · resets 10 a 14</span></li>
          <li><b>350</b><span>puntos · resets 15 a 20</span></li>
        </ol>
        <a class="pui-link" href="<?php echo $sv($svBase.'info/#rates'); ?>">Ver los rates completos ↗︎</a>
      </div>
      <div class="sv-sim pui-rise" id="svSim">
        <div class="sv-sim-top"><label for="svSimR" class="pui-label">Simulá tu recorrido</label><output id="svSimOut" for="svSimR">0<small> resets</small></output></div>
        <input type="range" id="svSimR" min="0" max="20" step="1" value="0">
        <div class="sv-sim-ticks" aria-hidden="true"><span>0</span><span>5</span><span>10</span><span>15</span><span>20</span></div>
        <div class="sv-sim-res">
          <div><small>Puntos de reset acumulados</small><b id="svSimPts">0</b></div>
          <div><small>Tu próximo reset suma</small><b id="svSimNext">+200</b></div>
          <div class="sv-sim-total"><small>Total para repartir, con los puntos de nivel 400</small><b id="svSimTot">1.995</b><em>DW, DK, Elfa y SU · <span id="svSimTot2">2.793</span> MG, DL y RF</em></div>
        </div>
        <p class="sv-sim-note">Los puntos del árbol Master se suman aparte.</p>
      </div>
    </div>
  </section>

  <!-- Clases -->
  <section class="sv-classes" aria-labelledby="sv-cls-t">
    <div class="pui-wrap">
      <header class="sv-head">
        <div><p class="pui-kicker">Tu clase, tu rol</p><h2 class="pui-h2" id="sv-cls-t"><?php echo $svReveal(['Siete clases.','Ninguna sobra.']); ?></h2></div>
        <p class="pui-lead">El balance está pensado para party contra party y guerras de guild. Cada clase gana cuando cumple su rol, y ninguna barre sola.</p>
      </header>
      <div class="sv-roster pui-rise">
        <?php foreach($svClasses as $c) { ?>
        <article class="sv-class" style="--sys:<?php echo $sv($c[6]); ?>">
          <?php if(isset($svArt[$c[0]])) { ?><span class="sv-class-art" aria-hidden="true"><span class="sv-class-word"><?php echo $sv($c[1]); ?></span><img src="<?php echo $sv($svImg.$svArt[$c[0]]); ?>" alt="" loading="lazy" decoding="async"></span>
          <?php } else { ?><span class="h2-art-slot sv-class-art" aria-hidden="true"><i><?php echo $sv(strtoupper($c[0])); ?></i><small>Arte: <?php echo $sv($c[1].' · '.$svColorName[$c[0]]); ?></small></span><?php } ?>
          <div class="sv-class-body">
            <span class="pui-tag" style="background:color-mix(in srgb,var(--sys) 22%,transparent);color:var(--sys)"><?php echo $sv($c[2]); ?></span>
            <h3 class="pui-h3"><?php echo $sv($c[1]); ?></h3>
            <p><?php echo $sv($c[3]); ?></p>
            <p class="sv-class-furia"><b>Furia · <?php echo $sv($c[4]); ?></b><?php echo $sv($c[5]); ?></p>
          </div>
        </article>
        <?php } ?>
      </div>
      <div class="sv-foot"><p>Y cuando la barra se llena, cada clase despierta su propia Furia en PvP.</p><a class="pui-link" href="<?php echo $sv($svBase.'info/'); ?>">Conocé cada clase en el Atlas ↗︎</a></div>
    </div>
  </section>

  <!-- PvP -->
  <section class="pui-band pui-band--bone sv-pvp" aria-labelledby="sv-pvp-t">
    <span class="pui-band-word" aria-hidden="true">PVP</span>
    <div class="pui-wrap">
      <header class="sv-head">
        <div><p class="pui-kicker">PvP</p><h2 class="pui-h2" id="sv-pvp-t"><?php echo $svReveal(['Leé la pelea','antes de perderla']); ?></h2></div>
        <p class="pui-lead">Cada jugador lleva su vida y su escudo a la vista. Esta es la barra que vas a ver sobre cada rival, paso a paso.</p>
      </header>
      <div class="sv-demo-grid">
        <div class="sv-stage pui-corners">
          <div class="sv-unit" role="img" aria-label="Barra de vida y escudo de un jugador">
            <div class="sv-floaters" id="svFloat"></div>
            <div class="sv-callout" id="svCo"><span id="svCoT"></span><i></i></div>
            <div class="sv-bar"><div class="sv-bar-in"><span class="sv-life" id="svLife"></span><span class="sv-sd" id="svSd"></span><span class="sv-lost" id="svLost"></span></div></div>
            <div class="sv-plate"><span class="sv-plate-g">Nexo</span><b>Varkor</b></div>
          </div>
          <div class="sv-legend"><span style="--k:#3cb43a">Vida</span><span style="--k:#e7c56d">Escudo (SD)</span><span style="--k:#fff">Daño recién recibido</span><span style="--k:#8a1a14">Daño marcado</span></div>
        </div>
        <ol class="sv-steps" id="svPvpSteps">
          <li><button type="button"><span class="k">01</span><span><b>Vida y escudo</b><span class="t">Verde es tu vida. El tramo dorado es tu escudo (SD), pegado al final de la vida.</span></span><i class="pg"></i></button></li>
          <li><button type="button"><span class="k">02</span><span><b>El escudo absorbe</b><span class="t">Mientras tengas SD, el escudo se lleva casi todo el golpe y la vida apenas se mueve.</span></span><i class="pg"></i></button></li>
          <li><button type="button"><span class="k">03</span><span><b>Escudo roto</b><span class="t">Sin SD, cada golpe va directo a la vida. Ahí empieza la pelea de verdad.</span></span><i class="pg"></i></button></li>
          <li><button type="button"><span class="k">04</span><span><b>Cada golpe se ve</b><span class="t">Lo que perdés brilla en blanco y queda marcado en rojo un instante. En un combo se acumula.</span></span><i class="pg"></i></button></li>
          <li><button type="button"><span class="k">05</span><span><b>Zona de peligro</b><span class="t">Por debajo del 30%, la vida late en rojo. Momento de cuidarte o de rematar.</span></span><i class="pg"></i></button></li>
          <li><button type="button"><span class="k">06</span><span><b>La poción justa</b><span class="t">Una poción de escudo lo recarga al instante. Cada poción tiene su espera: gana quien la usa a tiempo.</span></span><i class="pg"></i></button></li>
        </ol>
      </div>
      <div class="sv-notes pui-rise">
        <div class="pui-card"><h3 class="pui-h3">De un vistazo, quién es quién</h3><p>La placa del nombre lleva una franja de color.</p><p class="sv-stripes"><span style="--k:#3fb53a">Tu personaje</span><span style="--k:#3f7fd6">Tu guild y aliados</span><span style="--k:#c8372a">Guild rival</span></p></div>
        <div class="pui-card"><h3 class="pui-h3">Donde se pelea, se ve</h3><p>Las barras aparecen en todas las zonas de caza y se ocultan en las ciudades, para que la zona segura quede limpia.</p></div>
        <div class="pui-card"><h3 class="pui-h3">La arena de Lorencia</h3><p>Un octógono en el centro de la ciudad, con las barras siempre visibles, para resolver las cuentas pendientes.</p></div>
      </div>
    </div>
  </section>

  <!-- Furia -->
  <section class="sv-furia" id="furia" aria-labelledby="sv-fur-t" style="--sys:#ff7a1a">
    <div class="pui-wrap sv-split">
      <div>
        <p class="pui-kicker">Sistema exclusivo</p>
        <h2 class="pui-h2" id="sv-fur-t"><?php echo $svReveal(['Así se juega','la Furia']); ?></h2>
        <p class="pui-lead">La barra que se llena peleando y explota con <kbd>Shift</kbd>. De la primera carga a la Masacre.</p>
        <ol class="sv-fsteps pui-rise">
          <li><span>01</span><div><b>Cargala peleando</b><p>Cada golpe que das llena la barra, contra monstruos y contra jugadores. En PvP carga más rápido, y la carga no se pierde.</p></div></li>
          <li><span>02</span><div><b>Furia lista</b><p>Con la barra llena, la calavera despierta. Soltá Shift cuando quieras: la Furia te espera.</p></div></li>
          <li><span>03</span><div><b>Desatala</b><p>Tu personaje se enciende: más velocidad de ataque, más velocidad al caminar, aura de Furia y daño extra a monstruos.</p></div></li>
          <li><span>04</span><div><b>No pares</b><p>La barra se consume mientras dura. Cada enemigo que cae te devuelve segundos, hasta un máximo.</p></div></li>
          <li><span>05</span><div><b>Encadená el combo</b><p>Cada muerte suma al contador. A las 5 es COMBO, a las 12 IMPARABLE y a las 20, MASACRE.</p></div></li>
          <li><span>06</span><div><b>Tu clase, tu poder</b><p>Cada clase despierta un poder propio, contra jugadores y contra monstruos. Vale en todo el continente: arenas, eventos y Castle Siege.</p></div></li>
        </ol>
      </div>
      <div class="sv-furia-side">
        <div class="sv-furia-art" aria-hidden="true"><span>FURIA</span><img src="<?php echo $sv($svImg.$svArt['rf']); ?>" alt="" width="1380" height="1570" loading="lazy" decoding="async"></div>
        <ul class="sv-chips"><li>Velocidad de ataque +20%</li><li>Más velocidad al caminar</li><li>Daño extra a monstruos</li><li>Aura de Furia</li></ul>
        <ol class="sv-combo" aria-label="Niveles del combo"><li><b>5</b>Combo</li><li><b>12</b>Imparable</li><li><b>20</b>Masacre</li></ol>
      </div>
    </div>
  </section>

  <!-- Juego limpio -->
  <section class="pui-band pui-band--bone sv-fair" aria-labelledby="sv-fair-t">
    <span class="pui-band-word" aria-hidden="true">LIMPIO</span>
    <div class="pui-wrap">
      <header class="sv-head"><div><p class="pui-kicker">Juego limpio</p><h2 class="pui-h2" id="sv-fair-t"><?php echo $svReveal(['Lo que tenés,','te lo ganaste']); ?></h2></div></header>
      <ul class="sv-pledges pui-rise">
        <li class="pui-card"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M14.5 3.5l6 6-9 9-4-4z"/><path d="M7.5 14.5L3 19l2 2 4.5-4.5"/></svg><h3 class="pui-h3">Tu personaje se arma jugando</h3><p>Equipo, joyas, alas y frutas salen del juego. Las frutas no se compran: se fabrican.</p></li>
        <li class="pui-card pui-card--gem"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v10M9 9.5c0-1 1.3-1.8 3-1.8s3 .8 3 1.8-1.3 1.6-3 2-3 1-3 2 1.3 1.8 3 1.8 3-.8 3-1.8"/></svg><h3 class="pui-h3">Los Eryns también se ganan</h3><p>Vendé tu drop en el Mercado de Vessara y cobrá en Eryns sin poner un peso.</p></li>
        <li class="pui-card"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 3l7.5 3v5.5c0 4.6-3.2 8.2-7.5 9.5-4.3-1.3-7.5-4.9-7.5-9.5V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/></svg><h3 class="pui-h3">Un cliente protegido</h3><p>El launcher revisa tus archivos y detecta programas de trampa antes y durante la partida.</p></li>
        <li class="pui-card"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 4v16M5 20h14M6 8h12M6 8l-3 6h6zM18 8l-3 6h6z"/></svg><h3 class="pui-h3">Balance que se cuida</h3><p>Cada clase tiene su rol, y el equipo sigue de cerca cada pelea para que siga siendo pareja.</p></li>
      </ul>
      <div class="sv-fair-demos">
        <div class="sv-demo">
          <h3 class="pui-h3">Un cliente protegido</h3>
          <p>Cada vez que apretás JUGAR, el launcher revisa tus archivos y busca programas de trampa. Y mientras jugás, lo sigue vigilando.</p>
          <div class="sv-guard" id="svGuard" role="img" aria-label="El launcher verifica los archivos, detecta un programa de trampa y bloquea JUGAR hasta que se cierra">
            <div class="sv-guard-top"><span class="sv-guard-pill" id="svGuardPill"><b>Guard</b> activo</span><span class="sv-guard-srv"><i></i>Servidor online</span></div>
            <div class="sv-guard-toast" id="svGuardToast"><b>Guard detuvo el inicio</b>Se detectó Cheat Engine. El juego no se abre mientras siga en uso.</div>
            <div class="sv-guard-status"><span class="t" id="svGuardT">Listo para jugar</span><div class="bar"><i id="svGuardBar"></i></div><span class="s" id="svGuardS">No hay actualizaciones nuevas.</span></div>
            <span class="pui-btn sv-guard-play" id="svGuardPlay">Jugar</span>
          </div>
        </div>
        <div class="sv-demo">
          <h3 class="pui-h3">Los Eryns se ganan jugando</h3>
          <p>Lo que cazás tiene valor. Publicalo en el Mercado de Vessara y cobrá en Eryns, aunque estés desconectado.</p>
          <div class="sv-loop" id="svLoop">
            <div class="sv-loop-line"><i id="svLoopLine"></i></div>
            <div class="sv-loop-nodes">
              <div class="sv-node"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 2l7 7-7 13-7-13z"/><path d="M5 9h14M12 2v20"/></svg><em>¡Drop!</em></span><b>Cazás</b></div>
              <div class="sv-node"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 9l2-5h14l2 5"/><path d="M3 9h18v2a3 3 0 01-6 0 3 3 0 01-6 0 3 3 0 01-6 0z"/><path d="M5 13v7h14v-7"/></svg><em>1.200 Eryns</em></span><b>Publicás</b></div>
              <div class="sv-node"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M20 14.5A8 8 0 019.5 4a8 8 0 1010.5 10.5z"/></svg><em>En venta</em></span><b>Desconectado</b></div>
              <div class="sv-node"><span class="ic"><img src="<?php echo $sv($svGem); ?>" alt="" width="58" height="64"><em>¡Vendido!</em></span><b>Cobrás</b></div>
            </div>
            <p class="sv-loop-cap" id="svLoopCap">Cazás y cae una Jewel of Bless.</p>
            <div class="sv-loop-sum"><span>Ganado jugando</span><b class="pui-num" id="svLoopSum">0</b><span>Eryns</span></div>
          </div>
        </div>
      </div>
      <div class="sv-feat" id="svFeat">
        <div>
          <h3 class="pui-h3">Destacá tu publicación</h3>
          <p>Pagá el destacado y un miembro de la Cuadrilla Rota sale a ofrecer tu objeto por Loren Market, con su cartel. En la web, tu publicación sube a Destacados.</p>
          <button type="button" class="pui-btn pui-btn--dark sv-feat-switch" id="svFeatSw" aria-pressed="false">Destacar publicación</button>
        </div>
        <span class="h2-art-slot" style="--sys:#ffc24a" aria-hidden="true"><i>LOREN</i><small>Arte: la Cuadrilla Rota en Loren Market · dorado</small></span>
        <div class="sv-vitrina">
          <div class="pui-tabs sv-vtabs" aria-hidden="true"><span class="pui-tab is-active">Destacados</span><span class="pui-tab">Todas</span><span class="pui-tab">Eryns</span><span class="pui-tab">Zen</span></div>
          <div class="sv-vlist" id="svVlist">
            <div class="sv-vrow" data-k="a"><span><b>Chromatic Staff +15</b><em>Juanita</em></span><span class="pr"><img src="<?php echo $sv($svGem); ?>" alt="" width="58" height="64">900</span></div>
            <div class="sv-vrow" data-k="b"><span><b>Hurricane Boots +15</b><em>PANIC</em></span><span class="pr zen">1.000.000 Zen</span></div>
            <div class="sv-vrow" data-k="me"><span><b>Hurricane Armor +15</b><em>Tu publicación</em></span><span class="pr"><img src="<?php echo $sv($svGem); ?>" alt="" width="58" height="64">1.200</span><span class="pui-tag pui-tag--red sv-badge">Destacado</span></div>
          </div>
          <p class="sv-vnote" id="svVnote">Tu publicación espera en la vidriera, junto a las demás.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Eventos en vivo -->
  <section class="sv-events pui-tear" id="eventos" aria-labelledby="sv-ev-t">
    <div class="pui-wrap">
      <header class="sv-head">
        <div><p class="pui-kicker">Eventos en vivo</p><h2 class="pui-h2" id="sv-ev-t"><?php echo $svReveal(['El continente','no para']); ?></h2></div>
        <p class="pui-lead">Horarios de Argentina. Se actualiza solo.</p>
      </header>
      <div class="sv-clock">
        <div class="sv-next" aria-live="polite">
          <span class="pui-tag" id="svNxState"><i></i><span>Próximo evento</span></span>
          <h3 class="pui-h2" id="svNxName">—</h3>
          <p class="sv-next-when" id="svNxWhen"></p>
          <div class="sv-count" aria-label="Cuenta regresiva">
            <div><b class="pui-num" id="svCh">00</b><small>horas</small></div><span>:</span>
            <div><b class="pui-num" id="svCm">00</b><small>minutos</small></div><span>:</span>
            <div><b class="pui-num" id="svCs">00</b><small>segundos</small></div>
          </div>
        </div>
        <div class="sv-agenda">
          <div class="pui-tabs" role="group" aria-label="Filtrar eventos">
            <button type="button" class="pui-tab is-active" data-g="all" aria-pressed="true">Todos</button>
            <button type="button" class="pui-tab" data-g="classic" aria-pressed="false">Clásicos</button>
            <button type="button" class="pui-tab" data-g="invasion" aria-pressed="false">Invasiones</button>
            <button type="button" class="pui-tab" data-g="custom" aria-pressed="false">Competencias</button>
          </div>
          <ul class="sv-evlist" id="svEvList"></ul>
        </div>
        <div class="sv-day">
          <div class="sv-ruler" id="svRuler" aria-hidden="true"></div>
          <div class="sv-ruler-legend"><span>Clásicos</span><span class="i">Invasiones</span><span class="c">Competencias</span></div>
        </div>
      </div>
      <div class="sv-foot"><p>Doppelganger, Imperial Guardian, Kanturu y Raklion se juegan cuando cumplís las condiciones de acceso.</p><a class="pui-link" href="<?php echo $sv($svBase.'info/#eventos'); ?>">Ver premios y requisitos en el Atlas ↗︎</a></div>
    </div>
  </section>

  <!-- Free vs VIP -->
  <section class="pui-band pui-band--bone sv-vip" id="vip" aria-labelledby="sv-vip-t">
    <span class="pui-band-word" aria-hidden="true">VIP</span>
    <div class="pui-wrap">
      <header class="sv-head">
        <div><p class="pui-kicker">Membresía</p><h2 class="pui-h2" id="sv-vip-t"><?php echo $svReveal(['Free o VIP']); ?></h2></div>
        <p class="pui-lead">Todo MU PANIC se juega en Free. El VIP te ahorra tiempo, te deja avanzar más rápido y suma comodidades. Probá el cambio.</p>
      </header>
      <div class="sv-vtoggle" role="group" aria-label="Elegí el tipo de cuenta">
        <button type="button" class="pui-btn pui-btn--dark" id="svVFree" aria-pressed="true">Cuenta Free</button>
        <button type="button" class="pui-btn pui-btn--ghost" id="svVVip" aria-pressed="false">VIP MU PANIC</button>
      </div>
      <div class="sv-vgrid pui-rise">
        <div class="sv-vcard"><span class="sv-vk">Experiencia</span><div class="sv-vv"><span id="svVExp">15x</span><small id="svVExpS">ritmo base</small></div><div class="sv-vexp"><i id="svVExpBar"></i></div><div class="sv-vrow2"><span>Nivel <b id="svVLvl">312</b></span><span id="svVExpT">Subís a tu ritmo.</span></div></div>
        <div class="sv-vcard"><span class="sv-vk">Drop general</span><div class="sv-vv"><span id="svVDrop">25%</span><small id="svVDropS">drop base</small></div><div class="sv-vdrop"><span class="sv-mob" id="svVMob">☠</span><div class="sv-floor" id="svVFloor"></div></div><div class="sv-vrow2"><span>Joyas al suelo <b id="svVJw">0</b></span></div></div>
        <div class="sv-vcard"><span class="sv-vk">Publicaciones en Vessara</span><div class="sv-vv"><span id="svVSlotsN">3</span><small>a la venta al mismo tiempo</small></div><div class="sv-vslots" id="svVSlots"></div><p id="svVSlotsT">Tres objetos en la vidriera a la vez.</p></div>
        <div class="sv-vcard"><span class="pui-tag pui-tag--red sv-vlock">Solo VIP</span><span class="sv-vk">Helper offline</span><div class="sv-voff" id="svVOff"></div><p id="svVOffT">Cuando te desconectás, tu personaje se queda quieto.</p></div>
        <div class="sv-vcard"><span class="pui-tag pui-tag--red sv-vlock">Solo VIP</span><span class="sv-vk">Banco de joyas automático</span><div class="sv-vbank"><div class="sv-vbox"><div class="sv-inv" id="svVInv"></div>Inventario</div><div class="sv-lane" id="svVLane"><span>Depositás a mano</span></div><div class="sv-vbox"><b class="pui-num" id="svVBank">0</b>Banco</div></div><p id="svVBankT">Las joyas ocupan tu inventario hasta que las guardás vos.</p></div>
        <div class="sv-vcard"><span class="sv-vk">Tienda</span><div class="sv-vv"><span id="svVDisc">0%</span><small>de descuento en todo</small></div><div class="sv-vprice"><img src="<?php echo $sv($svGem); ?>" alt="" width="58" height="64"><s>1.000</s><b id="svVPrice">1.000</b></div><p id="svVDiscT">Pagás el precio de lista.</p></div>
        <div class="sv-vcard"><span class="pui-tag pui-tag--red sv-vlock">Solo VIP</span><span class="sv-vk">Baúl extra</span><div class="sv-vchests"><span></span><span></span><span class="vipc"></span></div><p id="svVChestT">Usás los baúles de tu cuenta.</p></div>
        <div class="sv-vcard"><span class="pui-tag pui-tag--red sv-vlock">Solo VIP</span><span class="sv-vk">AutoPost</span><div class="sv-vchat" id="svVChat"></div><p id="svVChatT">Tus anuncios en el chat los escribís cada vez.</p></div>
      </div>
      <div class="sv-foot"><p>El VIP se consigue con Eryns, desde la Tienda del Hub F11.</p><a class="pui-link" href="<?php echo $sv($svBase.'info/#rates'); ?>">Ver todas las tasas en el Atlas ↗︎</a></div>
    </div>
  </section>

  <!-- Preguntas -->
  <section class="sv-faq" aria-labelledby="sv-faq-t">
    <div class="pui-wrap sv-split">
      <div><p class="pui-kicker">Antes de entrar</p><h2 class="pui-h2" id="sv-faq-t"><?php echo $svReveal(['Preguntas','rápidas']); ?></h2></div>
      <div class="sv-faq-list">
        <details open><summary>¿Es pay to win?</summary><p>No. Lo que define a tu personaje (equipo, joyas, alas y frutas) se consigue jugando. La tienda acompaña tu recorrido, pero no reemplaza el juego.</p></details>
        <details><summary>¿Cómo consigo Eryns?</summary><p>Jugando y comerciando: vendé tu drop en el Mercado de Vessara. También podés recargarlos desde la web.</p></details>
        <details><summary>¿Necesito una PC potente?</summary><p>No. El launcher tiene un modo rendimiento que baja los efectos con un clic.</p></details>
        <details><summary>¿Está todo en castellano?</summary><p>Sí: el juego, la web, el launcher y el soporte.</p></details>
        <details><summary>¿Puedo empezar hoy?</summary><p>Sí. Creá tu cuenta, descargá el cliente y entrá: el launcher se encarga de mantenerte actualizado.</p></details>
      </div>
    </div>
  </section>

  <!-- Cierre -->
  <section class="sv-close" aria-labelledby="sv-end-t">
    <span class="sv-close-word" aria-hidden="true">TU CLASE</span>
    <div class="pui-wrap">
      <p class="pui-kicker">Tu lugar ya está esperando</p>
      <h2 class="pui-h1" id="sv-end-t">Elegí tu clase.<br>El resto se gana.</h2>
      <div class="sv-actions">
        <span class="pui-btn-wrap"><a class="pui-btn pui-btn--lg" href="<?php echo $sv($svBase.'register/'); ?>">Crear cuenta</a></span>
        <a class="pui-btn pui-btn--ghost" href="<?php echo $sv($svBase.'downloads/'); ?>">Descargar</a>
        <a class="pui-btn pui-btn--ghost" href="<?php echo $sv($discordInvite ?? 'https://discord.gg/fP4Mxcsee'); ?>" target="_blank" rel="noopener noreferrer">Entrar al Discord</a>
      </div>
    </div>
  </section>

  <script type="application/json" id="sv-events"><?php echo json_encode($svEvents, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE); ?></script>
</div>
