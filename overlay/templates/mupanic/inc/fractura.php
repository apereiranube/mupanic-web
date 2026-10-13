<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/fractura-data.php';
$fractura=panicFracturaRead();
$orders=[
    1=>['name'=>'Custodios del Sello','native'=>'Duprian','word'=>'Preservar','quote'=>'Hay heridas que no deben volver a abrirse.','copy'=>'Contener la energía de la Fractura. Proteger el continente, aun cuando el precio sea renunciar a su poder.','anchor'=>'custodios','targets'=>['Vepar','Death Tree','Splinter Wolf','Blood Assassin','Sapi-Duo']],
    2=>['name'=>'Herederos de la Fractura','native'=>'Vanert','word'=>'Dominar','quote'=>'El poder no desaparece cuando lo encerrás.','copy'=>'Comprender la energía de la Fractura y hacerla propia. Convertir la herida del continente en la fuerza de una nueva era.','anchor'=>'herederos','targets'=>['Silver Valkyrie','Death Rider','Iron Rider','Burning Lava Giant','Ghost Napin']]
];
$levels=[240,300,341,361,381]; $rewards=[100,200,500,1000,2000];
$a=$fractura ? (float)$fractura['factions'][1]['points'] : 0;
$b=$fractura ? (float)$fractura['factions'][2]['points'] : 0;
$lead=$fractura ? panicFracturaCompare($fractura['factions'][1]['points'],$fractura['factions'][2]['points']) : 0;
$share=$a+$b>0 ? round(100*$a/($a+$b),1) : 50;
?>
<div class="fractura-page" data-fractura-url="<?php echo htmlspecialchars(__BASE_URL__.'fractura/?fractura_data=1',ENT_QUOTES,'UTF-8'); ?>">
    <header class="fractura-heading"><div><span class="eyebrow">VULCANUS / DOS ÓRDENES, UNA HERIDA</span><h1>La Fractura</h1></div><p>El mismo origen.<br>Dos destinos irreconciliables.</p></header>
    <section class="fractura-arena" aria-label="Las dos órdenes de la Fractura">
        <div class="fractura-scar" aria-hidden="true"><span></span></div>
        <?php foreach($orders as $family=>$order) { ?>
        <article class="fractura-order fractura-order-<?php echo $family; ?>">
            <div class="fractura-art" aria-hidden="true"></div>
            <div class="fractura-order-copy">
                <span class="fractura-native">GENS <?php echo $order['native']; ?></span>
                <div class="fractura-crest" aria-hidden="true">
                    <?php if($family===1) { ?><svg viewBox="0 0 80 90" fill="none"><path d="M10 13 40 3 70 13v38L40 84 10 51Z"/><path d="M21 22 40 15 59 22v25L40 68 21 47Z"/><path d="M40 25v30M29 40h22"/><circle cx="40" cy="40" r="17"/></svg><?php } else { ?><svg viewBox="0 0 80 90" fill="none"><path d="m39 3-27 20 7 32 23 29 28-31-5-32Z"/><path d="m41 5-9 25 16 12-18 13 10 27M17 26l14 4M49 43l17-15M29 55l-10 3"/><path d="m43 21-4 11 12 11-12 12 3 12"/></svg><?php } ?>
                </div>
                <span class="fractura-motto"><?php echo $order['word']; ?></span>
                <h2><?php echo $order['name']; ?></h2>
                <p class="fractura-quote">«<?php echo $order['quote']; ?>»</p>
                <a class="fractura-order-link" href="#<?php echo $order['anchor']; ?>">Conocé la orden <span aria-hidden="true">↓</span></a>
            </div>
            <div class="fractura-counter">
                <span>CONTRIBUCIÓN DE LA ORDEN</span>
                <strong data-fractura-points="<?php echo $family; ?>"><?php echo $fractura ? panicFracturaNumber($fractura['factions'][$family]['points']) : '—'; ?></strong>
                <div><span>PUNTOS GENS</span><small><b data-fractura-members="<?php echo $family; ?>"><?php echo $fractura ? panicFracturaNumber($fractura['factions'][$family]['members']) : '—'; ?></b> personajes afiliados</small></div>
            </div>
        </article>
        <?php } ?>
        <div class="fractura-center-seal" aria-hidden="true"><span>UNA</span><strong>FRACTURA</strong><span>DOS VOLUNTADES</span></div>
    </section>
    <section class="fractura-balance" aria-labelledby="fractura-balance-title">
        <div class="fractura-balance-head"><h2 id="fractura-balance-title">El pulso de la disputa</h2><p data-fractura-lead><?php echo !$fractura ? 'Esperando la contribución del servidor' : ($a+$b===0.0 ? 'Las órdenes todavía no registran puntos' : ($lead===0 ? 'Las dos órdenes están igualadas' : ($lead>0 ? 'Custodios del Sello al frente' : 'Herederos de la Fractura al frente'))); ?></p></div>
        <div class="fractura-balance-labels"><span>Custodios <b data-fractura-share="1"><?php echo $fractura && $a+$b>0 ? number_format($share,1,',','.').'%' : '—'; ?></b></span><span><b data-fractura-share="2"><?php echo $fractura && $a+$b>0 ? number_format(100-$share,1,',','.').'%' : '—'; ?></b> Herederos</span></div>
        <div class="fractura-balance-track<?php echo !$fractura || $a+$b===0.0 ? ' is-empty' : ''; ?>" data-fractura-track><span style="width:<?php echo $share; ?>%" data-fractura-bar></span></div>
        <div class="fractura-update"><span class="fractura-status-dot<?php echo !$fractura || !empty($fractura['stale']) ? ' is-offline' : ''; ?>" data-fractura-dot aria-hidden="true"></span><p data-fractura-status role="status"><?php if(!$fractura) { echo 'Los puntos no están disponibles en este momento.'; } else { echo !empty($fractura['stale']) ? 'Última lectura disponible · ' : 'Última consulta · '; echo gmdate('d/m/Y H:i:s',$fractura['updated']-3*3600).' (Argentina)'; } ?></p><button type="button" data-fractura-refresh hidden>Actualizar ↻</button></div>
        <p class="fractura-counter-note">Contribución Gens acumulada de todos los personajes de cada orden, obtenida mediante cacerías y PvP. Los puntos guardados por el servidor se consultan cada minuto.</p>
    </section>
    <section class="fractura-origin" aria-labelledby="fractura-origin-title"><span class="eyebrow">LA HERIDA SIGUE ABIERTA</span><h2 id="fractura-origin-title">La Fractura deja<br><em>otra huella.</em></h2><p>Los Ocho respondieron a la Fractura. En Vulcanus, esa misma herida vuelve a manifestarse entre cenizas, ruinas y criaturas alteradas. Frente a ella, dos órdenes comparten una certeza: el continente ya cambió. Lo que no comparten es qué hacer con su poder.</p><div class="fractura-origin-foot"><span>PvP entre facciones en Vulcanus</span><span>Vessara permanece neutral</span></div></section>
    <section class="fractura-paths" aria-label="Identidad y cacerías de cada orden">
        <?php foreach($orders as $family=>$order) { ?>
        <article class="fractura-path fractura-path-<?php echo $family; ?>" id="<?php echo $order['anchor']; ?>">
            <span class="eyebrow">LA SENDA DE LOS <?php echo $family===1 ? 'CUSTODIOS' : 'HEREDEROS'; ?></span><h2><?php echo $order['name']; ?></h2><p><?php echo $order['copy']; ?></p>
            <div class="fractura-native-note">Dentro del juego: <strong>Gens <?php echo $order['native']; ?></strong> · Ingreso desde nivel 50.</div>
            <h3>Cacerías de la orden</h3><p class="fractura-mission-intro">Cada objetivo requiere 50 bajas. Elegí las misiones disponibles según tu nivel: no forman una cadena obligatoria.</p>
            <table class="fractura-missions"><caption class="sr-only">Cacerías de <?php echo $order['name']; ?></caption><thead><tr><th>Nivel mínimo</th><th>Objetivo</th><th>Puntos</th></tr></thead><tbody><?php foreach($order['targets'] as $i=>$target) { ?><tr><td><?php echo $levels[$i]; ?></td><td><?php echo $target; ?></td><td>+<?php echo panicFracturaNumber($rewards[$i]); ?></td></tr><?php } ?></tbody></table>
        </article>
        <?php } ?>
    </section>
    <section class="fractura-frontier"><span class="eyebrow">TU HISTORIA EMPIEZA ANTES DE VULCANUS</span><h2>Elegí tu orden.<br>Construí tu fuerza.</h2><p>Afiliate desde nivel 50 hablando con el representante de tu Gens dentro del juego. Las primeras cacerías empiezan en nivel 240, con objetivos como Vepar y Silver Valkyrie en Atlans. Vulcanus te espera más adelante.</p><a href="<?php echo __BASE_URL__; ?>info/" class="btn btn-primary">Explorar el Atlas PANIC <span aria-hidden="true">↗</span></a></section>
</div>
