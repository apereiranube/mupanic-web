<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/fractura-data.php';
$fractura=panicFracturaRead();
$orders=[
  2=>['name'=>'Caos','leader'=>'Varkor','title'=>'Heraldo de Caos','anchor'=>'caos','word'=>'Liberar','quote'=>'Toda ruptura es una puerta al poder.','creed'=>'Romper los límites del poder.','copy'=>'Varkor vio una puerta donde otros vieron una herida. Para él, contener la Fractura es condenar al continente a temer su propia fuerza. Sus seguidores buscan atravesar el límite, dominar lo que despierta y construir una nueva era sobre sus cenizas.','promise'=>'Si creés que el poder debe conquistarse, tu lugar está en Caos.'],
  1=>['name'=>'Nexo','leader'=>'Aldren','title'=>'Guardián de Nexo','anchor'=>'nexo','word'=>'Preservar','quote'=>'Mientras permanezcamos unidos, el mundo resistirá.','creed'=>'Mantener unido el mundo.','copy'=>'Aldren conoce el precio de una herida que nunca termina de cerrar. Su juramento es mantener unido el continente. Nexo reúne a quienes están dispuestos a enfrentar la Fractura, resistir su llamado y proteger aquello que todavía puede salvarse.','promise'=>'Si tu fuerza nace de proteger a los tuyos, tu lugar está en Nexo.']
];
$caos=$fractura?(float)$fractura['factions'][2]['points']:0;
$nexo=$fractura?(float)$fractura['factions'][1]['points']:0;
$share=$caos+$nexo>0?round(100*$caos/($caos+$nexo),1):50;
$lead=$fractura?panicFracturaCompare($fractura['factions'][2]['points'],$fractura['factions'][1]['points']):0;
$art=__PATH_TEMPLATE__.'img/fractura/';
function panicFracturaCrest($id) {
    $file=$id===2?'caos':'nexo';
    return '<img src="'.htmlspecialchars(__PATH_TEMPLATE__.'img/fractura/'.$file.'-emblema-v32.svg',ENT_QUOTES,'UTF-8').'" width="80" height="80" alt="">';
}
?>
<link rel="stylesheet" href="<?php echo __PATH_TEMPLATE__; ?>css/fractura-hall.css?v=<?php echo substr(hash_file('sha256',__DIR__.'/../css/fractura-hall.css'),0,12); ?>">
<div class="fractura-page" data-fractura-url="<?php echo htmlspecialchars(__BASE_URL__.'fractura/?fractura_data=1',ENT_QUOTES,'UTF-8'); ?>">
  <div class="fractura-world" aria-hidden="true"><img src="<?php echo $art; ?>vulcanus-world-v3.webp" alt="" width="1983" height="793"><div class="fractura-world-veil"></div></div>
  <header class="fractura-heading"><span class="eyebrow">EL DESPERTAR DE VULCANUS</span><h1>La <em>Fractura.</em></h1><p>Un continente herido. Dos fuerzas enfrentadas.<br><strong>¿De qué lado vas a escribir tu historia?</strong></p></header>
  <section class="fractura-battle" aria-label="Varkor y Aldren ante la Fractura" data-fractura-scene>
    <div class="fractura-lava-light" aria-hidden="true"></div><div class="fractura-fault" aria-hidden="true"></div>
    <div class="fractura-fog" aria-hidden="true"><i></i><i></i><i></i></div>
    <div class="fractura-battle-art" aria-hidden="true"><img src="<?php echo $art; ?>aldren-varkor-v2.webp" alt="" width="1536" height="1024" fetchpriority="high"></div>
    <div class="fractura-magic magic-caos" aria-hidden="true"><i></i><i></i><i></i></div><div class="fractura-magic magic-nexo" aria-hidden="true"><i></i><i></i><i></i></div>
    <canvas class="fractura-particles" data-fractura-particles aria-hidden="true"></canvas>
    <button class="fractura-effects" type="button" data-fractura-effects aria-pressed="false" hidden>Pausar escena Ⅱ</button>
    <div class="fractura-leaders"><?php foreach($orders as $id=>$o) { ?><div class="fractura-leader faction-<?php echo $id; ?>"><span><?php echo $o['title']; ?></span><strong><?php echo $o['leader']; ?></strong><p>«<?php echo $o['quote']; ?>»</p></div><?php } ?></div>
    <div class="fractura-choose-hint"><span>LA FRACTURA NOS DIVIDE</span><strong>Tu decisión nos define.</strong></div>
  </section>
  <section class="fractura-scoreboard" aria-label="Contribución y victorias oficiales por bando">
    <?php foreach($orders as $id=>$o) { ?>
    <article class="fractura-score faction-<?php echo $id; ?>"><div class="fractura-altar-art" aria-hidden="true"></div><div class="fractura-altar-glow" aria-hidden="true"></div>
      <div class="fractura-altar-screen" aria-hidden="true"></div><div class="fractura-altar-content"><header><div><span><?php echo $o['creed']; ?></span><h2><?php echo strtoupper($o['name']); ?></h2></div></header>
      <div class="fractura-total"><strong data-fractura-points="<?php echo $id; ?>"><?php echo $fractura?panicFracturaNumber($fractura['factions'][$id]['points']):'—'; ?></strong><span>PUNTOS ACUMULADOS</span></div>
      <footer><span><b data-fractura-members="<?php echo $id; ?>"><?php echo $fractura?panicFracturaNumber($fractura['factions'][$id]['members']):'—'; ?></b> <span data-fractura-member-label="<?php echo $id; ?>"><?php echo $fractura && $fractura['factions'][$id]['members']==='1'?'combatiente':'combatientes'; ?></span></span></footer></div><a class="fractura-altar-link" href="#<?php echo $o['anchor']; ?>">Conocé tu bando <b aria-hidden="true">↗</b></a>
    </article>
    <?php } ?>
  </section>
  <section class="fractura-balance" aria-labelledby="fractura-balance-title">
    <div class="fractura-balance-head"><div><span class="eyebrow">EL PULSO DE LA DISPUTA</span><h2 id="fractura-balance-title">Cada punto inclina la balanza.</h2></div><p data-fractura-lead><?php echo !$fractura?'Elegí a quién seguir':($caos+$nexo===0.0?'La historia todavía está por escribirse':($lead===0?'Caos y Nexo están igualados':($lead>0?'Caos lleva la delantera':'Nexo lleva la delantera'))); ?></p></div>
    <div class="fractura-balance-caption">Registro acumulado: contribución de los combatientes + victorias oficiales. La competencia semanal se muestra en el Salón de la Fama.</div><div class="fractura-balance-labels"><span><small>CAOS</small> <b data-fractura-share="2"><?php echo $fractura && $caos+$nexo>0?number_format($share,1,',','.').'%':'—'; ?></b></span><span><b data-fractura-share="1"><?php echo $fractura && $caos+$nexo>0?number_format(100-$share,1,',','.').'%':'—'; ?></b> <small>NEXO</small></span></div>
    <div class="fractura-balance-forge"><div class="fractura-balance-ornament" aria-hidden="true"></div><div class="fractura-balance-track<?php echo !$fractura || $caos+$nexo===0.0?' is-empty':''; ?>" data-fractura-track><span style="width:<?php echo $share; ?>%" data-fractura-bar><i class="fractura-energy-clash" aria-hidden="true"></i></span></div></div>
    <p class="fractura-score-status" data-fractura-status role="status"<?php if($fractura && empty($fractura['stale'])) echo ' hidden'; ?>><?php echo !$fractura?'Los puntos no están disponibles en este momento.':(!empty($fractura['stale'])?'El marcador está temporalmente sin actualizar.':''); ?></p>
  </section>
  <p class="fh-entry"><a href="#salon-de-la-fama">Competencia semanal · Salón de la Fama ↗</a></p>
  <nav class="fractura-section-nav" aria-label="Explorar Fractura"><a href="#vulcanus">01 <span>El territorio</span></a><a href="#boss-fractura">02 <span>El boss</span></a><a href="#historia-fractura">03 <span>La historia</span></a><a href="#caos">04 <span>Elegí tu bando</span></a></nav>
  <section class="fractura-territory" id="vulcanus" aria-labelledby="vulcanus-title"><span class="eyebrow">EL TERRITORIO EN DISPUTA</span><h2 id="vulcanus-title">Vulcanus.<br><em>Donde los juramentos arden.</em></h2><p>La lava recorre los caminos que alguna vez unieron estas ruinas. Bajo sus puentes, la Fractura respira. Aquí, Caos y Nexo dejan de ser promesas: cada encuentro exige defender lo que elegiste.</p><div class="fractura-territory-oaths"><span>Dos bandos enfrentados</span><span>Una herida que sigue creciendo</span><span>Un poder que reclama su precio</span></div></section>
  <section class="fractura-boss" id="boss-fractura" aria-labelledby="boss-fractura-title"><div class="fractura-boss-aura" aria-hidden="true"></div><div class="fractura-boss-figure"><img src="<?php echo $art; ?>lord-of-fracture-v2.webp" alt="Señor de la Fractura, demonio de enormes alas rojas y armadura de fuego" width="1672" height="941" loading="lazy"></div><div class="fractura-boss-copy"><span class="eyebrow">EL PODER TOMÓ FORMA</span><h2 id="boss-fractura-title">Señor de<br>la Fractura.</h2><p class="fractura-boss-quote">La herida del mundo<br>ya tiene un rostro.</p><p>No lleva el sello de Aldren. No escucha el llamado de Varkor. Se alza sobre Vulcanus como una voluntad nacida del abismo: aquello que ambos reconocieron, pero que ninguno puede reclamar.</p><p>Para Nexo, su presencia anuncia lo que debe contenerse. Para Caos, revela hasta dónde puede llegar el poder. Frente a sus alas, todo juramento deberá demostrar su fuerza.</p><p><strong>Todos los días a las 22:30 (hora Argentina).</strong> La invasión dura hasta 30 minutos. En la derrota oficial, el último golpe otorga <strong>1.000 puntos al bando</strong> de quien lo mata y <strong>500 de contribución al personaje</strong>. Reconectá para cargar el premio individual.</p></div></section>
  <?php include __DIR__.'/fractura-hall.php'; ?>
  <section class="fractura-origin" id="historia-fractura" aria-labelledby="fractura-origin-title"><span class="eyebrow">03 / UNA HERIDA. DOS RESPUESTAS.</span><h2 id="fractura-origin-title">El mundo se quebró.<br><em>Sus voluntades también.</em></h2><p>Los Ocho respondieron a la Fractura. Su huella sigue abierta, y Vulcanus es otra manifestación de esa herida. Cuando el fuego volvió a recorrer las ruinas, Aldren y Varkor reconocieron el mismo poder. Pero cada uno vio un destino distinto.</p><p>Aldren reunió a quienes juraron mantener unido el continente. Varkor llamó a quienes estaban dispuestos a romper sus límites. Así se alzaron <strong>Nexo</strong> y <strong>Caos</strong>.</p><div class="fractura-origin-foot"><span>Vulcanus enfrenta a los bandos</span><span>Vessara permanece neutral</span></div></section>
  <section class="fractura-paths" aria-label="El juramento de cada bando">
  <?php foreach($orders as $id=>$o) { ?><article class="fractura-path faction-<?php echo $id; ?>" id="<?php echo $o['anchor']; ?>"><header><div class="fractura-emblem"><?php echo panicFracturaCrest($id); ?></div><div><span class="eyebrow"><?php echo $o['leader']; ?> / <?php echo $o['title']; ?></span><h2><?php echo $o['name']; ?></h2></div></header><blockquote>«<?php echo $o['quote']; ?>»</blockquote><p><?php echo $o['copy']; ?></p><strong class="fractura-promise"><?php echo $o['promise']; ?></strong><a href="#unirse-fractura" class="fractura-join-link">Quiero pertenecer a <?php echo $o['name']; ?> <span aria-hidden="true">→</span></a></article><?php } ?>
  </section>
  <section class="fractura-enlist" id="unirse-fractura" aria-labelledby="fractura-enlist-title"><span class="eyebrow">04 / EL JURAMENTO LO HACÉS DENTRO DEL JUEGO</span><h2 id="fractura-enlist-title">Tu fuerza cuenta.<br><em>Tu bando te espera.</em></h2><div class="fractura-enlist-steps"><p><b>01</b><strong>Alcanzá el nivel 50</strong><span>Desde ese momento podés dar el primer paso hacia tu bando.</span></p><p><b>02</b><strong>Elegí a quién seguir</strong><span>Conocé el juramento de Aldren y el llamado de Varkor. Elegí el bando que querés defender.</span></p><p><b>03</b><strong>Hacé valer tu nombre</strong><span>Fortalecé a tu personaje y preparate para defender tu juramento en Vulcanus.</span></p></div><a href="<?php echo __BASE_URL__; ?>downloads/" class="btn btn-primary">Comenzar mi historia <span aria-hidden="true">↗</span></a><a href="<?php echo __BASE_URL__; ?>info/" class="fractura-atlas-link">Preparar mi recorrido en el Atlas →</a></section>
</div>

