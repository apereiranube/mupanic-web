<?php
// Homepage continuation. Descriptions use the actual F11 menu; these are previews,
// never a second game client or fabricated player/account data.
$hubTabs = [
    ['inicio', 'Inicio', 'Tu centro de control.', 'El próximo evento, tu bando y los accesos que más usás. Todo empieza en F11.'],
    ['personaje', 'Personaje', 'Tu progreso, a mano.', 'Consultá la información de tu personaje y encontrá tus recompensas.'],
    ['fractura', 'La Fractura', 'Tu bando también juega.', 'Seguí la guerra entre Nexo y Caos desde el Hub.'],
    ['eventos', 'Eventos', 'Elegí tu próxima batalla.', 'Consultá la agenda y las invasiones sin salir del juego.'],
    ['mercado', 'Mercado', 'Lo que encontrás tiene destino.', 'Accedé al mercado de Vessara para comprar y vender entre jugadores.'],
    ['juegos', 'Juegos', 'Cambiá de ritmo.', 'Ocho minijuegos para hacer una pausa del leveo. Los encontrás en F11 → Juegos.'],
    ['ayuda', 'Ayuda', 'Conocé todo lo que podés hacer.', 'Las herramientas del servidor, reunidas en un mismo lugar.'],
];
$homeGames = [
    ['ruleta','Ruleta','La rueda también tiene su momento.'],
    ['tragamonedas','Tragamonedas','Tres rodillos. Una nueva combinación.'],
    ['mayor-menor','Mayor o Menor','La próxima carta cambia la partida.'],
    ['dragon','Vuelo del Dragón','Decidí cuándo cobrar antes de que caiga.'],
    ['joyas','Lluvia de Joyas','Seguí el recorrido de cada joya.'],
    ['kalima','Tesoro de Kalima','¿Una más o cobrás?'],
    ['torre','Torre del Caos','¿Subís otro piso o te llevás lo conseguido?'],
    ['carrera','Carrera','Elegí tu monstruo y seguí la carrera.'],
];
?>
<section class="ph-workshop ph-client" id="hub" aria-labelledby="home-client-title">
    <div class="ph-client-heading ph-wrap">
        <div><p class="ph-eyebrow">UN CLIENTE CON IDENTIDAD PROPIA</p><h2 id="home-client-title">Todo MU PANIC,<br>a una tecla.</h2></div>
        <p>Tu personaje, tu bando, el mercado y tu próxima partida.<br>Un Hub propio para tener todo a mano.</p>
    </div>
    <div class="ph-client-stage ph-wrap">
        <div class="ph-client-key"><?php $homePicture('client-crafted', 'La tecla F11 de acceso al Hub de MU PANIC'); ?></div>
        <figure class="ph-hub-preview"><img src="<?php echo $homeArt; ?>hub-real-v4.webp" alt="Hub F11 real de MU PANIC: Inicio, Personaje, La Fractura, Eventos, Mercado, Tienda, Juegos y Ayuda" width="1536" height="1024" loading="lazy" decoding="async"><figcaption>EL HUB QUE ENCONTRÁS DENTRO DEL JUEGO</figcaption></figure>
    </div>
    <div class="ph-hub-tour ph-wrap" data-home-tabs>
        <nav class="ph-world-tabs" aria-label="Descubrí el Hub">
            <?php foreach($hubTabs as $tab) { ?><a href="#hub-<?php echo $tab[0]; ?>" data-world-tab="hub-<?php echo $tab[0]; ?>"><?php echo $tab[1]; ?></a><?php } ?>
        </nav>
        <div class="ph-world-panels">
            <?php foreach($hubTabs as $tab) { ?><div id="hub-<?php echo $tab[0]; ?>" data-world-panel><h3><?php echo $tab[2]; ?></h3><p><?php echo $tab[3]; ?></p></div><?php } ?>
        </div>
        <p class="ph-client-foot">Hub F11 <span>·</span> Árbol Master renovado <span>·</span> Interfaces propias</p>
    </div>
</section>

<section class="ph-scene ph-market-world" data-scene-fx="market" aria-labelledby="home-market-title">
    <?php $homePicture('market-world', 'Vessara y los jugadores comerciando equipo en el mercado de Loren Market', false, 'v4'); ?>
    <div class="ph-scene-content ph-wrap"><div class="ph-copy">
        <p class="ph-eyebrow">EL MERCADO DE LOS JUGADORES</p>
        <h2 id="home-market-title">Vessara.<br>El mundo negocia.</h2>
        <p>Lo que encontrás jugando puede ser justo lo que otro busca. Comprá y vendé equipo entre jugadores.</p>
        <a class="ph-link" href="<?php echo __BASE_URL__; ?>market/">Entrá al mercado <span aria-hidden="true">↗</span></a>
    </div></div>
    <div class="ph-market-trail ph-wrap"><span>ARMAS</span><span>ARMADURAS</span><span>ALAS</span><span>JOYAS</span><span>Y MUCHO MÁS</span></div>
</section>

<section class="ph-scene ph-events" data-scene-fx="events" aria-labelledby="home-events-title">
    <picture class="ph-art"><img src="<?php echo __PATH_TEMPLATE__; ?>img/atlas/editorial/blood-castle.webp" alt="El puente y las puertas del Castillo Sangriento de MU PANIC" width="1536" height="1024" loading="lazy" decoding="async"></picture>
    <div class="ph-scene-content ph-wrap"><div class="ph-copy">
        <p class="ph-eyebrow">EVENTOS E INVASIONES</p><h2 id="home-events-title">La próxima batalla<br>ya tiene hora.</h2>
        <ol class="ph-event-list" data-home-events>
            <?php foreach($homeEvents as $index => $event) { ?><li><a href="<?php echo __BASE_URL__; ?>info/#eventos"><span><?php echo $homeEscape($event['nombre']); ?></span><time datetime="<?php echo $homeEscape($event['proximo']); ?>"><?php echo $homeEscape(panicHomeEventLabel($event['proximo'])); ?></time><b aria-hidden="true">↗</b></a></li><?php } ?>
        </ol>
        <?php if(!$homeEvents) { ?><p data-home-events-empty>Descubrí los desafíos que te esperan.</p><?php } ?>
        <a class="ph-link" href="<?php echo __BASE_URL__; ?>info/#eventos">Conocé los eventos <span aria-hidden="true">↗</span></a>
    </div></div>
</section>

<section class="ph-games" id="minijuegos" aria-labelledby="home-games-title">
    <div class="ph-scene ph-games-scene" data-scene-fx="casino">
        <?php $homePicture('games-hall', 'Sala de minijuegos de MU PANIC con tragamonedas, cartas y la mesa de ruleta', false, 'v4'); ?>
        <div class="ph-scene-content ph-wrap"><div class="ph-copy">
            <p class="ph-eyebrow">OCHO MINIJUEGOS · DENTRO DE MU PANIC</p>
            <h2 id="home-games-title">Tu próxima partida<br>no necesita un spot.</h2>
            <p>Hacé una pausa del leveo.<br>Hay mucho más para jugar en el Hub F11.</p>
            <a href="#elegi-minijuego" class="ph-link">Encontrá tu próxima partida <span aria-hidden="true">↓</span></a>
        </div></div>
        <div class="ph-roulette-plane" aria-hidden="true"><img src="<?php echo $homeArt; ?>roulette-wheel-v4.webp" width="1024" height="1024" alt="" loading="lazy" decoding="async"></div>
    </div>
    <div class="ph-games-tour ph-wrap" id="elegi-minijuego" data-home-tabs>
        <p class="ph-eyebrow">ELEGÍ CÓMO SEGUIR</p>
        <nav class="ph-world-tabs" aria-label="Los ocho minijuegos">
            <?php foreach($homeGames as $game) { ?><a href="#juego-<?php echo $game[0]; ?>" data-world-tab="juego-<?php echo $game[0]; ?>"><?php echo $game[1]; ?></a><?php } ?>
        </nav>
        <div class="ph-world-panels">
            <?php foreach($homeGames as $game) { ?><div id="juego-<?php echo $game[0]; ?>" data-world-panel><h3><?php echo $game[1]; ?></h3><p><?php echo $game[2]; ?></p></div><?php } ?>
        </div>
        <p class="ph-games-access"><kbd>F11</kbd> <span>Juegos</span><b aria-hidden="true">↗</b> Abrí el Hub dentro del juego.</p>
    </div>
</section>
