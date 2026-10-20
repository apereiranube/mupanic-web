<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/home-data.php';
$homeEscape = function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$homeArt = __PATH_TEMPLATE__.'img/home/';
$homeEvents = panicHomeEvents();
// Keep intrinsic image dimensions in markup; no fixed cover/crop viewport.
$homePicture = function($name, $alt, $priority = false) use ($homeArt, $homeEscape) {
    $dimensions = getimagesize(__DIR__.'/../img/home/'.$name.'-v3.webp');
    $mobile = in_array($name, ['hero-connected','fractura-connected','companions-connected'],true);
    $mobileDimensions = getimagesize(__DIR__.'/../img/home/'.$name.'-v3'.($mobile ? '-mobile' : '-960').'.webp');
    ?><picture class="ph-art"><source media="(max-width:760px)" srcset="<?php echo $homeArt.$name; ?>-v3<?php echo $mobile ? '-mobile' : '-960'; ?>.webp" width="<?php echo (int)$mobileDimensions[0]; ?>" height="<?php echo (int)$mobileDimensions[1]; ?>"><img src="<?php echo $homeArt.$name; ?>-v3.webp" alt="<?php echo $homeEscape($alt); ?>" width="<?php echo (int)$dimensions[0]; ?>" height="<?php echo (int)$dimensions[1]; ?>" <?php echo $priority ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"'; ?>></picture><?php
};
?>
<div class="panic-home" data-panic-home data-launcher-url="<?php echo $homeEscape(__BASE_URL__.'api/launcher.php'); ?>" data-home-version="connected-v3">
    <section class="ph-scene ph-hero" aria-labelledby="home-title">
        <?php $homePicture('hero-connected', 'Rage Fighter con Phoenix Soul y Blade Knight de MU PANIC en combate', true); ?>
        <div class="ph-scene-content ph-wrap"><div class="ph-copy">
            <p class="ph-eyebrow">SEASON 6 · DESARROLLO PROPIO</p>
            <h1 id="home-title">Esto es<br>MU PANIC.</h1>
            <p class="ph-lead">Sistemas propios.<br>Una experiencia distinta.</p>
            <div class="ph-actions"><a class="ph-button" href="<?php echo __BASE_URL__.($isLogged ? 'usercp/' : 'register/'); ?>"><?php echo $isLogged ? 'MI CUENTA' : 'CREAR CUENTA'; ?> <span aria-hidden="true">↗</span></a><a class="ph-button ph-button-outline" href="<?php echo __BASE_URL__; ?>downloads/"><span aria-hidden="true">↓</span> DESCARGAR</a></div>
        </div></div>
    </section>

    <section class="ph-status ph-wrap" aria-label="Datos del servidor">
        <dl class="ph-facts">
            <div class="ph-state"><dt>ESTADO DEL SERVIDOR</dt><dd data-home-state>Sin verificar</dd></div>
            <div><dt>JUGADORES ONLINE</dt><dd data-home-online><?php echo $onlinePlayers === null ? '—' : number_format($onlinePlayers, 0, ',', '.'); ?></dd></div>
            <div><dt>EXPERIENCIA</dt><dd><?php echo $homeEscape($serverExp); ?></dd></div>
            <div><dt>MASTER EXP</dt><dd><?php echo $homeEscape($serverMasterExp); ?></dd></div>
            <div><dt>DROP GENERAL</dt><dd><?php echo $homeEscape($serverDrop); ?></dd></div>
        </dl>
        <div class="ph-status-note"><span data-home-update role="status">Conectados: último registro disponible.</span><a href="<?php echo __BASE_URL__; ?>info/#rates">RATES Y CONDICIONES <span aria-hidden="true">↗</span></a></div>
        <noscript><p class="ph-note">El estado en vivo requiere JavaScript.</p></noscript>
    </section>

    <section class="ph-scene ph-fractura" aria-labelledby="home-fractura-title">
        <?php $homePicture('fractura-connected', 'Aldren y Varkor ante el Señor de la Fractura, los protagonistas de Nexo y Caos'); ?>
        <div class="ph-scene-content ph-wrap"><div class="ph-copy">
            <p class="ph-eyebrow">LA FRACTURA</p>
            <h2 id="home-fractura-title">Dos bandos.<br>Una guerra propia.</h2>
            <p>Nexo o Caos. Tu bando, tus batallas,<br>tu lugar en la historia.</p>
            <a class="ph-link" href="<?php echo __BASE_URL__; ?>fractura/">Conocé la Fractura <span aria-hidden="true">↗</span></a>
        </div></div>
    </section>

    <section class="ph-scene ph-companions" aria-labelledby="home-companions-title">
        <?php $homePicture('companions-connected', 'Elyndra, Aurion, Vaerion y Grum, los cuatro compañeros de MU PANIC'); ?>
        <div class="ph-scene-content ph-wrap"><div class="ph-copy">
            <p class="ph-eyebrow">TRES MASCOTAS EVOLUTIVAS Y UN COMPAÑERO ÚNICO</p>
            <h2 id="home-companions-title">No avanzás solo.</h2>
            <p class="ph-companion-names">Elyndra · Aurion · Vaerion · Grum</p>
            <a class="ph-link" href="<?php echo __BASE_URL__; ?>info/#vinculos">Conocé tus compañeros <span aria-hidden="true">↗</span></a>
        </div></div>
    </section>

    <div class="ph-workshop ph-wrap">
        <section class="ph-client" aria-labelledby="home-client-title">
            <div class="ph-workshop-copy"><p class="ph-eyebrow">UN CLIENTE CON IDENTIDAD PROPIA</p><h2 id="home-client-title">Cada detalle,<br>renovado.</h2><p>Rediseñamos las herramientas que usás todos los días.</p></div>
            <?php $homePicture('client-crafted', 'La tecla F11 y los detalles de la interfaz propia de MU PANIC'); ?>
            <ul class="ph-client-labels" aria-label="Mejoras del cliente"><li>HUB F11</li><li>ÁRBOL MASTER</li><li>INTERFAZ PROPIA</li></ul>
        </section>
        <section class="ph-market" aria-labelledby="home-market-title">
            <div class="ph-workshop-copy"><p class="ph-eyebrow">ECONOMÍA ENTRE JUGADORES</p><h2 id="home-market-title">Vessara.</h2><p>El mercado de los jugadores.<br>Comprá y vendé lo que conseguís jugando.</p></div>
            <?php $homePicture('market-crafted', 'Las joyas Bless, Soul y Chaos junto al equipo de un puesto del Mercado Vessara'); ?>
            <a class="ph-link" href="<?php echo __BASE_URL__; ?>market/">Explorá el mercado <span aria-hidden="true">↗</span></a>
        </section>
    </div>

    <section class="ph-events ph-wrap" aria-labelledby="home-events-title">
        <div class="ph-events-heading"><div><p class="ph-eyebrow">LA PRÓXIMA BATALLA</p><h2 id="home-events-title">Siempre pasa algo.</h2></div><a class="ph-link" href="<?php echo __BASE_URL__; ?>info/#eventos">Ver calendario <span aria-hidden="true">↗</span></a></div>
        <ol class="ph-event-list" data-home-events>
            <?php foreach($homeEvents as $event) { ?><li><a href="<?php echo __BASE_URL__; ?>info/#eventos"><span><?php echo $homeEscape($event['nombre']); ?></span><time datetime="<?php echo $homeEscape($event['proximo']); ?>"><?php echo $homeEscape(panicHomeEventLabel($event['proximo'])); ?></time><b aria-hidden="true">↗</b></a></li><?php } ?>
        </ol>
        <?php if(!$homeEvents) { ?><p data-home-events-empty>Consultá el calendario de eventos del servidor.</p><?php } ?>
        <p class="ph-note">Horarios de Argentina · Los eventos manuales se anuncian por separado.</p>
    </section>

    <section class="ph-scene ph-games" aria-labelledby="home-games-title">
        <?php $homePicture('games-connected', 'Vuelo del Dragón, Torre del Caos y Tesoro de Kalima, parte de los minijuegos de MU PANIC'); ?>
        <div class="ph-scene-content ph-wrap"><div class="ph-copy">
            <p class="ph-eyebrow">OTRA FORMA DE JUGAR · HUB F11</p>
            <h2 id="home-games-title">Tu próxima partida<br>no necesita un spot.</h2>
            <p>Hacé una pausa del leveo y descubrí<br>los ocho minijuegos del Hub F11.</p>
            <details class="ph-games-details"><summary class="ph-link">EXPLORAR MINIJUEGOS <span aria-hidden="true">+</span></summary><div class="ph-games-disclosure"><p>Entrá al juego y abrí F11 → Juegos.</p><ul><li>Ruleta</li><li>Tragamonedas</li><li>Mayor o Menor</li><li>Vuelo del Dragón</li><li>Lluvia de Joyas</li><li>Tesoro de Kalima</li><li>Torre del Caos</li><li>Carrera</li></ul></div></details>
        </div></div>
    </section>
</div>
