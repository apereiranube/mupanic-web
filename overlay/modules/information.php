<?php
if(!defined('access') || !access) die();
?>
<section class="server-story" aria-labelledby="server-story-title">
    <div class="server-story-hero">
        <div class="server-story-copy">
            <span class="eyebrow">MU PANIC / EL SERVIDOR</span>
            <h2 id="server-story-title">No venís a correr.<br><em>Venís a conquistar.</em></h2>
            <p>MU PANIC está pensado como un recorrido: mapas con propósito, economía con peso, sistemas paralelos y objetivos que siguen existiendo después del level.</p>
            <div class="server-story-actions">
                <a class="button primary" href="<?php echo __BASE_URL__; ?>register/">Crear cuenta <span aria-hidden="true">↗</span></a>
                <a class="text-link" href="<?php echo __BASE_URL__; ?>info/">Explorar Atlas <span aria-hidden="true">→</span></a>
            </div>
        </div>
        <div class="server-story-visual" aria-hidden="true">
            <div class="server-story-glow"></div>
            <img src="<?php echo __PATH_TEMPLATE__; ?>img/conquest-v61.webp" alt="">
        </div>
    </div>

    <div class="server-proof-grid">
        <article><span>01</span><h3>Cada mapa tiene un propósito.</h3><p>Spots, Zen, joyas y objetivos se reparten por el continente para que avanzar también signifique elegir dónde jugar.</p></article>
        <article><span>02</span><h3>El progreso tiene etapas.</h3><p>Reset, Master Reset y zonas de mayor exigencia construyen un recorrido que acompaña a tu personaje.</p></article>
        <article><span>03</span><h3>Hay vida más allá del level.</h3><p>Logros, recompensas, ruleta, VIP, Jewel Bank y herramientas propias del cliente suman objetivos paralelos.</p></article>
        <article><span>04</span><h3>Sabés dónde estás parado.</h3><p>Atlas reúne mapas, mobs, spots, drops y progresión para que puedas planear tu próximo objetivo.</p></article>
    </div>
</section>

<section class="server-systems" aria-labelledby="server-systems-title">
    <header class="server-section-head">
        <span class="eyebrow">SISTEMAS QUE TE HACEN VOLVER</span>
        <h2 id="server-systems-title">Más que subir<br><em>de nivel.</em></h2>
        <p>Cada sistema agrega una forma distinta de progresar, competir o preparar tu cuenta.</p>
    </header>

    <div class="server-system-grid">
        <article class="server-system-card">
            <span class="server-system-icon"><?php echo mupanicGlyph('crest'); ?></span>
            <small>PROGRESIÓN</small>
            <h3>Crónicas del Conquistador</h3>
            <p>Completá objetivos, acumulá hitos y convertí tu recorrido por el continente en una colección de logros.</p>
        </article>
        <article class="server-system-card">
            <span class="server-system-icon"><?php echo mupanicGlyph('sword'); ?></span>
            <small>RECOMPENSAS</small>
            <h3>Camino del Héroe</h3>
            <p>Avanzá por una ruta de recompensas mientras jugás y sumá nuevos objetivos a tu progreso habitual.</p>
        </article>
        <article class="server-system-card">
            <span class="server-system-icon"><?php echo mupanicGlyph('party'); ?></span>
            <small>CADA DÍA</small>
            <h3>Tributo Diario</h3>
            <p>Volvé, reclamá tu recompensa y mantené viva una cadena de beneficios pensada para acompañar tu cuenta.</p>
        </article>
        <article class="server-system-card">
            <span class="server-system-icon"><?php echo mupanicGlyph('gem'); ?></span>
            <small>FORTUNA</small>
            <h3>Fortuna del Caos</h3>
            <p>Probá tu suerte con una selección de premios definida por el servidor y una mecánica pensada para WCoin C.</p>
        </article>
        <article class="server-system-card">
            <span class="server-system-icon"><?php echo mupanicGlyph('gem'); ?></span>
            <small>INVENTARIO</small>
            <h3>Bóveda de Joyas</h3>
            <p>Guardá tus joyas en una reserva de cuenta y evitá cargar el inventario cada vez que preparás una mejora.</p>
        </article>
        <article class="server-system-card">
            <span class="server-system-icon"><?php echo mupanicGlyph('wings'); ?></span>
            <small>MEMBRESÍA</small>
            <h3>Sello Imperial</h3>
            <p>Activá beneficios temporales de cuenta y consultá desde la web el estado de tu membresía VIP.</p>
        </article>
        <article class="server-system-card">
            <span class="server-system-icon"><?php echo mupanicGlyph('crest'); ?></span>
            <small>DENTRO DEL JUEGO</small>
            <h3>Portal PANIC</h3>
            <p>Abrí el Hub con F11 y concentrá accesos, eventos, estadísticas y funciones del cliente en un solo lugar.</p>
        </article>
        <article class="server-system-card server-system-card-featured">
            <span class="server-system-icon"><?php echo mupanicGlyph('wings'); ?></span>
            <small>EXPLORACIÓN</small>
            <h3>Atlas</h3>
            <p>Consultá mapas, mobs, spots, drops y rutas de progresión antes de decidir cuál va a ser tu próxima conquista.</p>
            <a href="<?php echo __BASE_URL__; ?>info/">Abrir Atlas <span aria-hidden="true">↗</span></a>
        </article>
    </div>
</section>

<section class="server-events-preview" aria-labelledby="server-events-title">
    <header class="server-section-head">
        <span class="eyebrow">EL CONTINENTE SE MUEVE</span>
        <h2 id="server-events-title">Eventos e<br><em>invasiones.</em></h2>
        <p>Esta parte todavía está en validación. Los horarios, requisitos y recompensas definitivas se publicarán cuando cerremos las pruebas de cada evento.</p>
    </header>

    <div class="server-event-feature">
        <div class="server-event-art"><img src="<?php echo __PATH_TEMPLATE__; ?>img/devias.webp" alt=""></div>
        <div class="server-event-copy">
            <span class="server-event-status">EN PRUEBAS</span>
            <small>EVENTOS CLÁSICOS Y PROPIOS</small>
            <h3>Cuando suena la alarma,<br>el mapa cambia.</h3>
            <p>Blood Castle, Devil Square, Chaos Castle, invasiones y bosses forman parte del conjunto que estamos terminando de auditar. Solo vamos a publicar como activo lo que esté probado de punta a punta.</p>
            <a class="text-link" href="<?php echo __BASE_URL__; ?>info/#sistemas">Ver sistemas confirmados <span aria-hidden="true">→</span></a>
        </div>
    </div>
</section>
