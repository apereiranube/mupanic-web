<?php
/**
 * MU PANIC · La Fractura (estilo nuevo).
 * Datos: inc/fractura-week-data.php (semana en curso + historial de ganadores, solo SELECT),
 * inc/fractura-ranks.php (laureados vigentes) y fractura-guide.json (premios de respaldo).
 * El marcador se refresca con el endpoint fractura/?fractura_week=1 desde js/fractura-v2.js.
 *
 * Arte: todavía no hay. Cada lugar para arte es un .fr-slot vacío con su tamaño reservado.
 * Cuando llegue, se copia a img/fractura-v2/<nombre>-<hash de 12>.webp (o .png/.svg) y aparece solo:
 *   Ya están: fractura-aldren 1021x1300 · fractura-varkor 990x1300 · fractura-grieta 266x1300 · fractura-boss 1139x1300.
 *   fractura-vulcanus 1600x1000 (fondo de la sección Vulcanus).
 *   Faltan: fractura-emblema-nexo / fractura-emblema-caos 1024x1024
 *   · fractura-insignia-nexo / fractura-insignia-caos 512x512 (historial del Salón de la Fama).
 */
if(!defined('access') || !access) die();
require_once __DIR__.'/fractura-data.php';
require_once __DIR__.'/fractura-week-data.php';
require_once __DIR__.'/fractura-ranks.php';

$fr = function($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$frBase = __BASE_URL__;
$frReveal = function($lines) { return implode('<br>', array_map(function($l){ return '<span class="pui-reveal">'.$l.'</span>'; }, $lines)); };
$frDate = function($unix, $format) { return (new DateTimeImmutable('@'.(int)$unix))->setTimezone(new DateTimeZone('America/Argentina/Buenos_Aires'))->format($format); };

// Arte nuevo con nombre por contenido: img/fractura-v2/<nombre>-<hash12>.<ext>. Si no está, el lugar queda vacío.
$frArtDir = __DIR__.'/../img/fractura-v2/';
$frArtFiles = is_dir($frArtDir) ? (scandir($frArtDir) ?: []) : [];
$frArt = function($name) use ($frArtFiles) {
    foreach($frArtFiles as $f) if(preg_match('/^'.preg_quote($name, '/').'-[0-9a-f]{12}\.(webp|png|svg)$/D', $f)) return __PATH_TEMPLATE__.'img/fractura-v2/'.$f;
    return null;
};
$frSlot = function($cls, $name, $w, $h, $alt = '', $eager = false) use ($frArt, $fr) {
    $src = $frArt($name);
    $img = $src ? '<img src="'.$fr($src).'" alt="'.$fr($alt).'" width="'.$w.'" height="'.$h.'" decoding="async"'.($eager ? ' fetchpriority="high"' : ' loading="lazy"').'>' : '';
    return '<div class="fr-slot '.$cls.($src ? ' has-art' : '').'" data-art="'.$fr($name).' '.$w.'x'.$h.'"'.($alt === '' ? ' aria-hidden="true"' : '').'>'.$img.'</div>';
};

// Semana en curso e historial (null = no disponible; nunca se inventa un marcador).
$contest = panicFracturaWeekRead();
$week = panicFracturaWeeklyLivePayload($contest);
$history = is_array($contest) ? ($contest['history'] ?? []) : null;
$honors = panicFracturaDistinctionsRead();
$nexo = $week ? $week['factions'][1]['points'] : '0';
$caos = $week ? $week['factions'][2]['points'] : '0';
$total = (float)$nexo + (float)$caos;
$nexoShare = $total > 0 ? round(100 * (float)$nexo / $total, 1) : 50;
$lead = $week ? panicFracturaCompare($nexo, $caos) : 0; // >0 Nexo, <0 Caos
$frState = !$week ? 'El marcador no está disponible'
    : (!$week['enabled'] ? 'Competencia semanal pausada'
    : ($week['isClosed'] ? ($lead === 0 ? 'Semana cerrada en empate' : ($lead > 0 ? 'Nexo ganó la semana' : 'Caos ganó la semana'))
    : ($total == 0 ? 'La semana está por disputarse' : ($lead === 0 ? 'Nexo y Caos están igualados' : ($lead > 0 ? 'Nexo lleva la delantera' : 'Caos lleva la delantera')))));
$frLeadCls = !$week || $total == 0 || $lead === 0 ? 'is-even' : ($lead > 0 ? 'is-nexo' : 'is-caos');
$frShare = function($v) use ($week, $total) { return $week && $total > 0 ? number_format($v, 1, ',', '.').'%' : '—'; };
$frDiff = $week ? ($lead === 0 ? '0' : panicFracturaNumber((string)abs((float)$nexo - (float)$caos))) : '—';
$bossPoints = $contest ? panicFracturaNumber($contest['current']['bossPoints']) : '1.000';

// Premios del cierre: configuración del juego si se puede leer; si no, la referencia aprobada.
$frRewards = (json_decode((string)@file_get_contents(__DIR__.'/fractura-guide.json'), true) ?: [])['rewards'] ?? null;
try {
    $db = Connection::Database('MuOnline');
    if($db) $frRewards = panicFracturaWeekRules($db->query_fetch("SELECT Result AS result,Contribution AS contribution,Fragments AS fragments,Bless AS bless,Soul AS soul,Chaos AS chaos FROM [MuOnline43].[dbo].[MUPanicFracturaWeekRulesV34]"));
} catch(Throwable $e) { /* referencia aprobada */ }
$frNum = function($n) { return number_format((int)$n, 0, ',', '.'); };

$frOrders = [
  1 => ['key'=>'nexo','name'=>'Nexo','leader'=>'Aldren','title'=>'Guardián de Nexo','word'=>'Preservar','quote'=>'Mientras permanezcamos unidos, el mundo resistirá.','creed'=>'Mantener unido el mundo.','copy'=>'Aldren conoce el precio de una herida que nunca termina de cerrar. Nexo reúne a quienes enfrentan la Fractura, resisten su llamado y protegen lo que todavía puede salvarse.','promise'=>'Si tu fuerza nace de proteger a los tuyos, tu lugar está en Nexo.'],
  2 => ['key'=>'caos','name'=>'Caos','leader'=>'Varkor','title'=>'Heraldo de Caos','word'=>'Liberar','quote'=>'Toda ruptura es una puerta al poder.','creed'=>'Romper los límites del poder.','copy'=>'Varkor vio una puerta donde otros vieron una herida. Sus seguidores buscan atravesar el límite, dominar lo que despierta y levantar una nueva era sobre las cenizas.','promise'=>'Si creés que el poder se conquista, tu lugar está en Caos.'],
];
?>
<div class="fr pui <?php echo $frLeadCls; ?>" id="fr-page" data-fractura-url="<?php echo $fr($frBase.'fractura/?fractura_week=1'); ?>">

  <!-- Portada -->
  <section class="fr-hero" aria-labelledby="fr-title">
    <div class="fr-hero-split" aria-hidden="true"><i class="fr-hero-nexo"></i><i class="fr-hero-caos"></i><?php echo $frSlot('fx-crack', 'fractura-grieta', 266, 1300); ?></div>
    <div class="fr-hero-stage">
      <?php echo $frSlot('fr-hero-fig fr-hero-fig--aldren', 'fractura-aldren', 1021, 1300, 'Aldren, Guardián de Nexo', true); ?>
      <?php echo $frSlot('fr-hero-fig fr-hero-fig--varkor', 'fractura-varkor', 990, 1300, 'Varkor, Heraldo de Caos', true); ?>
    </div>
    <div class="fr-hero-copy">
      <div class="pui-wrap">
        <p class="pui-kicker">El despertar de Vulcanus</p>
        <h1 class="pui-h1 fr-hero-title" id="fr-title">La <span>Fractura</span></h1>
      </div>
    </div>
  </section>

  <!-- Marcador de la semana -->
  <section class="fr-score" id="marcador-semanal" aria-labelledby="fr-score-t">
    <div class="pui-wrap">
      <p class="fr-hero-lead">Un continente herido. Dos fuerzas enfrentadas.<br><strong>¿De qué lado vas a escribir tu historia?</strong></p>
      <div class="fr-actions">
        <span class="pui-btn-wrap"><a class="pui-btn pui-btn--lg" href="#bandos">Elegí tu bando</a></span>
        <a class="pui-btn pui-btn--ghost" href="#salon-de-la-fama">Salón de la Fama</a>
      </div>
      <h2 class="pui-sr" id="fr-score-t">Marcador de la semana</h2>
      <div class="fr-board" data-fr-board>
        <?php foreach([1, 2] as $id) { $o = $frOrders[$id]; ?>
        <div class="fr-board-side fr-board-side--<?php echo $o['key']; ?>">
          <span class="fr-board-tag"><?php echo $o['leader']; ?> · <?php echo $o['word']; ?></span>
          <span class="fr-board-chip" data-fr-chip><?php echo $week && $week['isClosed'] ? 'Ganó la semana' : 'Al frente'; ?></span>
          <b class="fr-board-name"><?php echo $o['name']; ?></b>
          <strong class="fr-board-pts pui-num" data-fr-points="<?php echo $id; ?>"><?php echo $week ? panicFracturaNumber($week['factions'][$id]['points']) : '—'; ?></strong>
          <span class="fr-board-pct"><b data-fr-share="<?php echo $id; ?>"><?php echo $frShare($id === 1 ? $nexoShare : 100 - $nexoShare); ?></b> de los puntos</span>
        </div>
        <?php if($id === 1) { ?>
        <div class="fr-board-mid">
          <span class="fr-board-vs" aria-hidden="true">VS</span>
          <b class="fr-board-state" data-fr-lead><?php echo $frState; ?></b>
          <span class="fr-board-close" data-fr-close><?php echo $week ? $fr(panicFracturaWeekCloseLabel($week['closeUnix'])) : '&nbsp;'; ?></span>
        </div>
        <?php } } ?>
      </div>
      <div class="fr-beam" data-fr-beam aria-hidden="true">
        <div class="fr-beam-track<?php echo !$week || $total == 0 ? ' is-empty' : ''; ?>" data-fr-track><span class="fr-beam-nexo" data-fr-bar style="width:<?php echo $nexoShare; ?>%"></span><i class="fr-beam-mid"></i></div>
      </div>
      <p class="fr-board-foot"><span>Boss oficial + PvP válido en Vulcanus</span><span class="fr-board-status" data-fr-status role="status"><?php echo !$week ? 'Los puntos no están disponibles en este momento.' : ''; ?></span></p>
    </div>
  </section>

  <!-- Cómo se suman puntos -->
  <section class="fr-band fr-band--bone fr-points" aria-labelledby="fr-points-t">
    <div class="pui-wrap">
      <header class="fr-head">
        <div><p class="pui-kicker">La competencia de esta semana</p><h2 class="pui-h2" id="fr-points-t"><?php echo $frReveal(['Cada punto inclina','la balanza']); ?></h2></div>
        <p class="pui-lead">Solo dos cosas mueven el marcador de tu bando. Al cierre, el que suma más gana la semana.</p>
      </header>
      <div class="fr-ways">
        <article class="fr-way fr-way--caos pui-rise">
          <span class="fr-way-n">01</span>
          <h3>Boss oficial</h3>
          <p class="fr-way-big"><b class="pui-num"><?php echo $bossPoints; ?></b> puntos al bando</p>
          <p>El Señor de la Fractura aparece todos los días a las <strong>22:30 (hora Argentina)</strong>. El último golpe da los puntos al bando de quien lo mata y <strong>500 de contribución</strong> a ese personaje.</p>
        </article>
        <article class="fr-way fr-way--nexo pui-rise">
          <span class="fr-way-n">02</span>
          <h3>PvP válido en Vulcanus</h3>
          <p class="fr-way-big"><b class="pui-num">100%</b> de lo que gana tu personaje</p>
          <p>Cada baja válida contra el otro bando suma a tu bando la misma contribución que recibe tu personaje. A la víctima no se le restan puntos.</p>
        </article>
        <article class="fr-way fr-way--off pui-rise">
          <span class="fr-way-n">✕</span>
          <h3>Las misiones no suman</h3>
          <p>Dan contribución personal y suben tu rango, pero no mueven el marcador semanal. Sí son necesarias para cobrar el premio.</p>
        </article>
      </div>
      <dl class="fr-live">
        <div class="fr-live-it fr-live-it--nexo"><dt>Nexo</dt><dd class="pui-num" data-fr-points="1"><?php echo $week ? panicFracturaNumber($nexo) : '—'; ?></dd></div>
        <div class="fr-live-it fr-live-it--caos"><dt>Caos</dt><dd class="pui-num" data-fr-points="2"><?php echo $week ? panicFracturaNumber($caos) : '—'; ?></dd></div>
        <div class="fr-live-it"><dt>Diferencia</dt><dd class="pui-num" data-fr-diff><?php echo $frDiff; ?></dd></div>
        <div class="fr-live-it fr-live-it--close"><dt>Cierre</dt><dd data-fr-close-short><?php echo $week ? $fr($frDate($week['closeUnix'], 'd/m · H:i')) : '—'; ?> <small>ARG</small></dd></div>
      </dl>
      <?php if(is_array($frRewards)) { ?>
      <div class="fr-prizes">
        <h3 class="fr-sub">Al cierre, cada bando cobra</h3>
        <div class="fr-prize-row">
          <?php foreach(['W'=>'Bando ganador','T'=>'Empate','L'=>'Bando perdedor'] as $k => $label) { $r = $frRewards[$k] ?? null; if(!$r) continue; ?>
          <div class="fr-prize fr-prize--<?php echo strtolower($k); ?>">
            <span><?php echo $label; ?></span>
            <b class="pui-num"><?php echo $frNum($r['contribution']); ?></b><small>de contribución</small>
            <ul><?php foreach(['fragments'=>'Fragmentos de vínculo','bless'=>'Bless','soul'=>'Soul','chaos'=>'Chaos'] as $f => $n) { if((int)$r[$f] > 0) echo '<li>'.$frNum($r[$f]).' × '.$n.'</li>'; } ?></ul>
          </div>
          <?php } ?>
        </div>
        <p class="fr-prize-note">Para cobrar, tu personaje tiene que haber completado y cobrado al menos dos misiones diarias en la semana. Un premio por cuenta. Lo reclamás con Aldren o Varkor.</p>
      </div>
      <?php } ?>
    </div>
  </section>

  <!-- Vulcanus -->
  <section class="fr-band fr-vulcanus" id="vulcanus" aria-labelledby="fr-vul-t">
    <?php echo $frSlot('fr-vul-bg', 'fractura-vulcanus', 1600, 1000, 'Vulcanus: ruinas, puentes sobre ríos de lava, volcanes y la grieta de la Fractura'); ?>
    <div class="fr-lava" aria-hidden="true"><?php
      // Fragmentos de lava (GPT) que salen de la grieta: x/y de salida en %, tamaño px, duración, retraso, giro.
      $frLava = [[1,80,96,30,11,0,-18],[4,86,92,26,9,-3.2,12],[2,77,99,36,13,-6.1,20],[6,90,95,22,10,-1.7,-10],[5,83,98,40,14,-8.4,8],[11,88,97,20,8,-4.6,-24],[8,79,94,24,12,-10.2,16],[3,85,99,30,10,-7.3,-6]];
      foreach($frLava as $l) { $src = $frArt(sprintf('fractura-lava-%02d', $l[0])); if(!$src) continue; ?><span class="fr-lava-f" style="left:<?php echo $l[1]; ?>%;top:<?php echo $l[2]; ?>%;--w:<?php echo $l[3]; ?>px;--d:<?php echo $l[4]; ?>s;--dl:<?php echo $l[5]; ?>s;--r:<?php echo $l[6]; ?>deg"><img src="<?php echo $fr($src); ?>" alt="" loading="lazy" decoding="async"></span><?php }
    ?></div>
    <div class="pui-wrap fr-vul-wrap">
      <div class="fr-vul-copy">
        <p class="pui-kicker">El territorio en disputa</p>
        <h2 class="pui-h2" id="fr-vul-t"><?php echo $frReveal(['Vulcanus']); ?></h2>
        <p class="fr-tagline">Donde los juramentos arden.</p>
        <p class="pui-lead">La lava recorre los caminos que alguna vez unieron estas ruinas. Bajo sus puentes, la Fractura respira. Acá Caos y Nexo dejan de ser promesas: cada encuentro exige defender lo que elegiste.</p>
        <ul class="fr-facts">
          <li><b>Dos bandos</b><span>Solo entra quien juró por uno.</span></li>
          <li><b>PvP que cuenta</b><span>Cada baja válida suma a tu bando.</span></li>
          <li><b>Un boss diario</b><span>22:30 · hasta 30 minutos.</span></li>
        </ul>
      </div>
    </div>
  </section>

  <!-- Señor de la Fractura -->
  <section class="fr-band fr-boss" id="boss-fractura" aria-labelledby="fr-boss-t">
    <div class="fr-boss-block" aria-hidden="true"></div>
    <div class="pui-wrap fr-boss-grid">
      <?php echo $frSlot('fr-boss-art', 'fractura-boss', 1139, 1300, 'Señor de la Fractura'); ?>
      <article class="fr-enemy pui-rise">
        <header class="fr-enemy-top"><span>Jefe de Vulcanus</span><span>Amenaza máxima</span></header>
        <div class="fr-enemy-body">
          <p class="pui-kicker">El poder tomó forma</p>
          <h2 class="fr-enemy-name" id="fr-boss-t"><?php echo $frReveal(['Señor de','la Fractura']); ?></h2>
          <p class="fr-enemy-title">La herida del mundo ya tiene un rostro.</p>
          <p class="fr-enemy-lore">No lleva el sello de Aldren ni escucha el llamado de Varkor. Es lo que ambos reconocieron y ninguno puede reclamar. Frente a sus alas, todo juramento tiene que demostrar su fuerza.</p>
          <dl class="fr-enemy-stats">
            <div><dt>Aparece</dt><dd>Todos los días · 22:30 ARG</dd></div>
            <div><dt>Invasión</dt><dd>Hasta 30 minutos</dd></div>
            <div><dt>Último golpe</dt><dd><?php echo $bossPoints; ?> al bando · 500 al personaje</dd></div>
            <div><dt>Territorio</dt><dd>Vulcanus</dd></div>
          </dl>
        </div>
      </article>
    </div>
  </section>

  <!-- La historia -->
  <section class="fr-band fr-band--bone fr-story" id="historia-fractura" aria-labelledby="fr-story-t">
    <div class="pui-wrap">
      <header class="fr-head">
        <div><p class="pui-kicker">Una herida. Dos respuestas.</p><h2 class="pui-h2" id="fr-story-t"><?php echo $frReveal(['La historia']); ?></h2></div>
        <p class="pui-lead">Vessara permanece neutral. Vulcanus, no.</p>
      </header>
      <ol class="fr-beats">
        <li class="fr-beat pui-rise"><span class="fr-beat-n">01</span><h3>El mundo se quebró</h3><p>Los Ocho respondieron a la Fractura y su huella sigue abierta. Cuando el fuego volvió a recorrer Vulcanus, dos voluntades reconocieron el mismo poder.</p></li>
        <li class="fr-beat fr-beat--nexo pui-rise"><span class="fr-beat-n">02</span><h3>Aldren eligió preservar</h3><p>Reunió a quienes juraron mantener unido el continente. Así nació Nexo.</p></li>
        <li class="fr-beat fr-beat--caos pui-rise"><span class="fr-beat-n">03</span><h3>Varkor eligió liberar</h3><p>Llamó a quienes estaban dispuestos a romper sus límites. Así nació Caos.</p></li>
      </ol>
    </div>
  </section>

  <!-- Elegí tu bando -->
  <section class="fr-band fr-sides" id="bandos" aria-labelledby="fr-sides-t">
    <div class="pui-wrap">
      <header class="fr-head">
        <div><p class="pui-kicker">Tu decisión te define</p><h2 class="pui-h2" id="fr-sides-t"><?php echo $frReveal(['Elegí tu bando']); ?></h2></div>
        <p class="pui-lead">Desde nivel 50 podés jurar lealtad dentro del juego. Elegí bien: vas a defender ese juramento en Vulcanus.</p>
      </header>
      <div class="fr-agents">
        <?php foreach([1, 2] as $id) { $o = $frOrders[$id]; ?>
        <article class="fr-agent fr-agent--<?php echo $o['key']; ?>" id="<?php echo $o['key']; ?>">
          <?php echo $frSlot('fr-agent-emblem', 'fractura-emblema-'.$o['key'], 1024, 1024); ?>
          <div class="fr-agent-copy">
            <span class="fr-agent-tag"><?php echo $o['leader']; ?> · <?php echo $o['title']; ?></span>
            <h3 class="fr-agent-name"><?php echo $o['name']; ?></h3>
            <span class="fr-agent-word"><?php echo $o['word']; ?></span>
            <blockquote class="fr-agent-quote">«<?php echo $o['quote']; ?>»</blockquote>
            <p class="fr-agent-creed"><?php echo $o['creed']; ?></p>
            <p class="fr-agent-text"><?php echo $o['copy']; ?></p>
            <strong class="fr-agent-promise"><?php echo $o['promise']; ?></strong>
            <a class="fr-agent-link" href="#unirse-fractura">Quiero pertenecer a <?php echo $o['name']; ?> <span aria-hidden="true">→</span></a>
          </div>
        </article>
        <?php } ?>
      </div>
    </div>
  </section>

  <!-- Cómo unirte -->
  <section class="fr-band fr-band--bone fr-join" id="unirse-fractura" aria-labelledby="fr-join-t">
    <div class="pui-wrap">
      <header class="fr-head">
        <div><p class="pui-kicker">El juramento se hace en el juego</p><h2 class="pui-h2" id="fr-join-t"><?php echo $frReveal(['Cómo unirte']); ?></h2></div>
      </header>
      <ol class="fr-steps">
        <li class="pui-rise"><span class="fr-step-n">01</span><h3>Llegá a nivel 50</h3><p>Desde ese momento podés dar el primer paso hacia tu bando.</p></li>
        <li class="pui-rise"><span class="fr-step-n">02</span><h3>Hablá con Aldren o Varkor</h3><p>Escuchá su juramento y elegí a quién seguir.</p></li>
        <li class="pui-rise"><span class="fr-step-n">03</span><h3>Defendé tu bando</h3><p>Entrá a Vulcanus, peleá por los tuyos y sumá puntos cada semana.</p></li>
      </ol>
    </div>
  </section>

  <!-- Salón de la Fama -->
  <section class="fr-band fr-hall" id="salon-de-la-fama" aria-labelledby="fr-hall-t">
    <div class="pui-wrap">
      <header class="fr-head">
        <div><p class="pui-kicker">Las victorias dejan huella</p><h2 class="pui-h2" id="fr-hall-t"><?php echo $frReveal(['Salón de la Fama']); ?></h2></div>
        <p class="pui-lead">Cada semana cerrada queda escrita acá, con el bando que se impuso.</p>
      </header>
      <h3 class="fr-sub">Semanas cerradas</h3>
      <?php if($history) { ?>
      <ol class="fr-matches" data-fr-pager data-size="6">
        <?php foreach($history as $h) { $w = (int)$h['winnerFamily']; $wk = $w === 1 ? 'nexo' : ($w === 2 ? 'caos' : 'tie'); ?>
        <li class="fr-match fr-match--<?php echo $wk; ?>" data-fr-item>
          <?php echo $w ? $frSlot('fr-match-badge', 'fractura-insignia-'.$wk, 512, 512) : '<div class="fr-slot fr-match-badge fr-match-badge--tie" aria-hidden="true"></div>'; ?>
          <div class="fr-match-res"><b><?php echo $w === 1 ? 'Victoria de Nexo' : ($w === 2 ? 'Victoria de Caos' : 'Empate'); ?></b><span>Semana del <?php echo $fr($frDate(strtotime($h['weekStart'].' 12:00:00 UTC'), 'd/m')); ?> · cerró el <?php echo $fr($frDate($h['closeUnix'], 'd/m/Y')); ?></span></div>
          <div class="fr-match-score"><span class="fr-match-pts fr-match-pts--nexo<?php echo $w === 1 ? ' is-win' : ''; ?>"><small>Nexo</small><b class="pui-num"><?php echo panicFracturaNumber($h['nexoPoints']); ?></b></span><i aria-hidden="true">—</i><span class="fr-match-pts fr-match-pts--caos<?php echo $w === 2 ? ' is-win' : ''; ?>"><b class="pui-num"><?php echo panicFracturaNumber($h['caosPoints']); ?></b><small>Caos</small></span></div>
        </li>
        <?php } ?>
      </ol>
      <?php } else { ?>
      <p class="fr-empty"><?php echo $history === null ? 'El historial no está disponible en este momento.' : 'El primer bando vencedor se escribe acá al cierre de la semana.'; ?></p>
      <?php } ?>

      <h3 class="fr-sub">Laureados vigentes</h3>
      <?php if($honors) { ?>
      <ul class="fr-laureates" data-fr-pager data-size="12" data-size-sm="6">
        <?php foreach($honors as $h) { $k = $h['family'] === 1 ? 'nexo' : 'caos'; ?>
        <li class="fr-laureate fr-laureate--<?php echo $k; ?>" data-fr-item><b><?php echo $fr($h['name']); ?></b><span><?php echo $h['family'] === 1 ? 'Laureado del Nexo' : 'Laureado de Caos'; ?> · <?php echo $fr($h['rank']); ?></span><small>Hasta el <?php echo $fr($frDate($h['untilUnix'], 'd/m')); ?></small></li>
        <?php } ?>
      </ul>
      <?php } else { ?>
      <p class="fr-empty"><?php echo $honors === null ? 'Los laureados no están disponibles en este momento.' : 'Los laureados aparecen cuando el bando ganador cobra su premio.'; ?></p>
      <?php } ?>
    </div>
  </section>

  <!-- Cierre -->
  <section class="fr-cta" aria-labelledby="fr-cta-t">
    <div class="fr-hero-split" aria-hidden="true"><i class="fr-hero-nexo"></i><i class="fr-hero-caos"></i></div>
    <div class="pui-wrap fr-cta-in">
      <h2 class="pui-h2" id="fr-cta-t">Tu bando te espera</h2>
      <div class="fr-actions">
        <span class="pui-btn-wrap fr-cta-wrap"><a class="pui-btn pui-btn--lg pui-btn--dark" href="<?php echo $fr($frBase.'downloads/'); ?>">Comenzar mi historia <span aria-hidden="true">↗︎</span></a></span>
        <a class="fr-cta-link" href="<?php echo $fr($frBase.'info/#fractura'); ?>">Reglas completas en el Atlas →</a>
      </div>
    </div>
  </section>
</div>
