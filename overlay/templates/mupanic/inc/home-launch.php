<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/home-data.php';
$homeEscape = function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$homeArt = __PATH_TEMPLATE__.'img/home/';
$homeEvents = panicHomeEvents();
?>
<div class="panic-experience panic-home" data-panic-home data-launcher-url="<?php echo $homeEscape(__BASE_URL__.'api/launcher.php'); ?>">
    <section class="px-hero" aria-labelledby="panic-home-title">
        <picture class="px-scene px-hero-scene">
            <source media="(max-width:700px)" srcset="<?php echo $homeArt; ?>hero-rf-bk-v1-960.webp">
            <img src="<?php echo $homeArt; ?>hero-rf-bk-v1.webp" alt="Rage Fighter y Blade Knight en combate" width="1672" height="941" fetchpriority="high">
        </picture>
        <div class="px-wrap px-hero-content">
            <p class="px-kicker">SEASON 6 · DESARROLLO PROPIO</p>
            <h1 id="panic-home-title">Esto es<br>MU <em>PANIC.</em></h1>
            <p class="px-lead">Eventos propios. Mascotas evolutivas.<br>Un cliente renovado para jugar diferente.</p>
            <div class="px-actions">
                <a class="px-button" href="<?php echo __BASE_URL__.($isLogged ? 'usercp/' : 'register/'); ?>"><?php echo $isLogged ? 'MI CUENTA' : 'CREAR CUENTA'; ?> <span aria-hidden="true">↗</span></a>
                <a class="px-link" href="<?php echo __BASE_URL__; ?>downloads/">DESCARGAR <span aria-hidden="true">↓</span></a>
            </div>
            <a class="px-explore" href="#descubri-panic">DESCUBRÍ LO QUE CREAMOS <span aria-hidden="true">↓</span></a>
        </div>
    </section>

    <section class="px-status px-wrap" aria-label="Datos del servidor">
        <dl class="px-facts">
            <div><dt>ESTADO</dt><dd data-home-state>Sin verificar</dd></div>
            <div><dt>JUGADORES ONLINE</dt><dd data-home-online><?php echo $onlinePlayers === null ? '—' : number_format($onlinePlayers, 0, ',', '.'); ?></dd></div>
            <div><dt>VERSIÓN</dt><dd><?php echo $homeEscape($serverSeason); ?></dd></div>
            <div><dt>EXPERIENCIA BASE</dt><dd><?php echo $homeEscape($serverExp); ?></dd></div>
            <div><dt>MASTER EXP</dt><dd><?php echo $homeEscape($serverMasterExp); ?></dd></div>
            <div><dt>DROP GENERAL</dt><dd><?php echo $homeEscape($serverDrop); ?></dd></div>
        </dl>
        <div class="px-status-foot"><span data-home-update role="status">Conectados: último registro disponible. Horarios de Argentina.</span><a href="<?php echo __BASE_URL__; ?>info/#rates">VER RATES Y CONDICIONES ↗</a></div>
        <noscript><p class="px-note">El estado en vivo requiere JavaScript. Los conectados corresponden al último registro del servidor.</p></noscript>
    </section>

    <section class="px-feature px-fractura" id="descubri-panic" aria-labelledby="home-fractura-title">
        <picture class="px-scene">
            <source media="(max-width:700px)" srcset="<?php echo $homeArt; ?>fractura-nexo-caos-v1-960.webp">
            <img src="<?php echo $homeArt; ?>fractura-nexo-caos-v1.webp" alt="Aldren y Varkor frente al Señor de la Fractura" width="1672" height="941" loading="lazy" decoding="async">
        </picture>
        <div class="px-wrap px-feature-content"><div class="px-copy">
            <p class="px-kicker">LA FRACTURA · GUERRA DE BANDOS</p>
            <h2 id="home-fractura-title"><span class="px-blue">Nexo</span><br>contra <span class="px-red">Caos.</span></h2>
            <p>Dos bandos. Un territorio en disputa.<br>Elegí de qué lado vas a pelear.</p>
            <a class="px-link" href="<?php echo __BASE_URL__; ?>fractura/">DESCUBRÍ LA FRACTURA <span aria-hidden="true">↗</span></a>
        </div></div>
    </section>

    <section class="px-feature px-companions" aria-labelledby="home-companions-title">
        <picture class="px-scene">
            <source media="(max-width:700px)" srcset="<?php echo $homeArt; ?>companions-four-v1-960.webp">
            <img src="<?php echo $homeArt; ?>companions-four-v1.webp" alt="Elyndra, Aurion, Vaerion y Grum: los compañeros de MU PANIC" width="1672" height="941" loading="lazy" decoding="async">
        </picture>
        <div class="px-wrap px-feature-content px-copy-right"><div class="px-copy">
            <p class="px-kicker">MASCOTAS · VÍNCULOS DE PANIC</p>
            <h2 id="home-companions-title">No peleás<br><em>solo.</em></h2>
            <p>Conocé las tres líneas de mascotas evolutivas y a Grum, el compañero más particular del continente.</p>
            <a class="px-link" href="<?php echo __BASE_URL__; ?>information/#mascotas">CONOCÉ A TUS COMPAÑEROS <span aria-hidden="true">↗</span></a>
        </div></div>
    </section>

    <section class="px-client px-wrap" aria-labelledby="home-client-title">
        <div class="px-client-copy"><p class="px-kicker">HECHO PARA MU PANIC</p><h2 id="home-client-title">También cambia<br><em>cómo jugás.</em></h2><p>Hub F11, árbol Master e interfaces renovadas. Los sistemas del servidor, a tu alcance desde el juego.</p><a class="px-link" href="<?php echo __BASE_URL__; ?>information/#cliente">EXPLORÁ LAS MEJORAS <span aria-hidden="true">↗</span></a></div>
        <figure class="px-client-figure"><div class="px-client-shot"><img src="<?php echo $homeArt; ?>hub-client-20261020.webp" alt="Captura real del Hub F11 actual: eventos, Fractura, Mercado, Tienda y Juegos" width="1920" height="1080" loading="lazy" decoding="async"></div><figcaption>HUB F11 · CAPTURA REAL DEL CLIENTE</figcaption><a class="px-capture-link" href="<?php echo $homeArt; ?>hub-client-20261020.webp" target="_blank" rel="noopener noreferrer">AMPLIAR CAPTURA ↗</a></figure>
    </section>

    <section class="px-activity px-wrap" aria-label="Actividad del servidor">
        <div class="px-events"><p class="px-kicker">LA PRÓXIMA BATALLA</p><h2>Próximos eventos.</h2><ol class="px-event-list" data-home-events>
            <?php foreach($homeEvents as $event) { ?><li><a href="<?php echo __BASE_URL__; ?>info/#eventos"><span><?php echo $homeEscape($event['nombre']); ?></span><time datetime="<?php echo $homeEscape($event['proximo']); ?>"><?php echo $homeEscape(panicHomeEventLabel($event['proximo'])); ?></time><b aria-hidden="true">↗</b></a></li><?php } ?>
        </ol><?php if(!$homeEvents) { ?><p data-home-events-empty>Consultá el calendario de eventos del servidor.</p><?php } ?><a class="px-link" href="<?php echo __BASE_URL__; ?>info/#eventos">VER CALENDARIO <span aria-hidden="true">↗</span></a><small class="px-note">Hora de Argentina · Los eventos manuales se anuncian por separado.</small></div>
        <div class="px-market"><p class="px-kicker">ECONOMÍA ENTRE JUGADORES</p><h2>Mercado Vessara.</h2><div class="px-jewels" aria-hidden="true"><img src="<?php echo __PATH_TEMPLATE__; ?>img/atlas/drops/jewel-bless-2c80b97e4d75.webp" alt="" width="512" height="512" loading="lazy"><img src="<?php echo __PATH_TEMPLATE__; ?>img/atlas/drops/jewel-soul-05f3890104b2.webp" alt="" width="512" height="512" loading="lazy"><img src="<?php echo __PATH_TEMPLATE__; ?>img/atlas/drops/jewel-chaos-809384516512.webp" alt="" width="512" height="512" loading="lazy"></div><p>Lo que conseguís también mueve el mundo.<br>Comprá y vendé con otros jugadores.</p><a class="px-link" href="<?php echo __BASE_URL__; ?>market/">EXPLORÁ EL MERCADO <span aria-hidden="true">↗</span></a></div>
    </section>

    <section class="px-games px-wrap" aria-labelledby="home-games-title"><div class="px-copy"><p class="px-kicker">OTRA FORMA DE PASARLA BIEN</p><h2 id="home-games-title">Un descanso.<br><em>Ocho juegos.</em></h2><p>Ruleta, Vuelo del Dragón, Torre del Caos y más. Encontralos dentro del Hub F11.</p><a class="px-link" href="<?php echo __BASE_URL__; ?>information/#minijuegos">CONOCÉ LOS MINIJUEGOS <span aria-hidden="true">↗</span></a></div><figure class="px-games-figure"><div class="px-games-shot"><img src="<?php echo $homeArt; ?>games-client-20261020.webp" alt="Los ocho minijuegos en la sala actual de MU PANIC" width="1920" height="1080" loading="lazy" decoding="async"></div><figcaption>SALA DE JUEGOS · CAPTURA REAL DEL CLIENTE</figcaption><a class="px-capture-link" href="<?php echo $homeArt; ?>games-client-20261020.webp" target="_blank" rel="noopener noreferrer">AMPLIAR CAPTURA ↗</a></figure></section>

    <section class="px-final px-wrap" aria-labelledby="home-play-title"><p class="px-kicker">EL SIGUIENTE PASO ES TUYO</p><h2 id="home-play-title">Entrá a <em>MU PANIC.</em></h2><div class="px-actions"><a class="px-button" href="<?php echo __BASE_URL__.($isLogged ? 'usercp/' : 'register/'); ?>"><?php echo $isLogged ? 'MI CUENTA' : 'CREAR CUENTA'; ?> <span aria-hidden="true">↗</span></a><a class="px-link" href="<?php echo __BASE_URL__; ?>downloads/">DESCARGAR <span aria-hidden="true">↓</span></a><a class="px-link" href="<?php echo $homeEscape($discordInvite); ?>" target="_blank" rel="noopener noreferrer">DISCORD <span aria-hidden="true">↗</span></a></div></section>
</div>
