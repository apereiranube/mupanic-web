<?php
if(!defined('access') || !access) die();

$serverImage = function($name) {
    return __PATH_TEMPLATE__.'img/server/'.$name;
};
?>

<section class="server-landing-hero" aria-labelledby="server-hero-title" data-server-scene>
    <div class="server-landing-hero-art" style="--server-hero:url('<?php echo $serverImage('server-hero.webp'); ?>')" aria-hidden="true"></div>
    <div class="server-landing-hero-shade" aria-hidden="true"></div>
    <div class="shell server-landing-hero-inner">
        <div class="server-landing-kicker">MU PANIC / SEASON 6 / ARGENTINA</div>
        <div class="server-landing-copy">
            <span class="eyebrow">UN CONTINENTE. MUCHAS FORMAS DE CONQUISTARLO.</span>
            <h1 id="server-hero-title">No venís<br>a correr.<br><em>Venís a conquistar.</em></h1>
            <p>MU PANIC está pensado para que cada etapa tenga valor: mapas con propósito, economía útil, objetivos paralelos y sistemas que siguen importando después del level.</p>
            <div class="server-landing-actions">
                <a class="button primary" href="<?php echo __BASE_URL__; ?>register/">Crear cuenta <span aria-hidden="true">↗</span></a>
                <a class="text-link" href="#identidad">Conocer el servidor <span aria-hidden="true">↓</span></a>
            </div>
        </div>
        <div class="server-landing-scroll">01 / EL SERVIDOR <span aria-hidden="true">↓</span></div>
    </div>
</section>

<section class="server-identity" id="identidad" aria-labelledby="server-identity-title">
    <div class="shell server-identity-layout">
        <div class="server-identity-copy">
            <span class="eyebrow">POR QUÉ MU PANIC</span>
            <h2 id="server-identity-title">El progreso tiene<br><em>que sentirse.</em></h2>
            <p>No queremos que todos los mapas sean iguales ni que el objetivo sea únicamente sumar resets. El servidor se organiza alrededor de decisiones: dónde farmear, qué guardar, qué mejorar y qué perseguir después.</p>
        </div>
        <figure class="server-identity-art">
            <img src="<?php echo $serverImage('server-world.webp'); ?>" alt="Un grupo de aventureros observando distintas regiones del continente">
            <figcaption>El continente cambia con tu progreso.</figcaption>
        </figure>
        <div class="server-pillars">
            <article>
                <span>01</span>
                <div><h3>Mapas con propósito</h3><p>Spots, Zen, joyas y objetivos distribuidos para que avanzar también signifique elegir dónde jugar.</p></div>
            </article>
            <article>
                <span>02</span>
                <div><h3>Etapas que importan</h3><p>Reset, Master Reset y zonas de mayor exigencia forman un recorrido, no una carrera sin contexto.</p></div>
            </article>
            <article>
                <span>03</span>
                <div><h3>Más que levelear</h3><p>Logros, recompensas, membresía, ruleta y sistemas de cuenta agregan objetivos paralelos.</p></div>
            </article>
            <article>
                <span>04</span>
                <div><h3>Información real</h3><p>Atlas concentra mapas, mobs, spots, drops y referencias para que puedas planear tu siguiente paso.</p></div>
            </article>
        </div>
    </div>
</section>

<section class="server-progression" aria-labelledby="server-progression-title">
    <div class="server-progression-art">
        <img src="<?php echo $serverImage('server-progression.webp'); ?>" alt="Aventureros contemplando las distintas regiones del mundo">
    </div>
    <div class="shell server-progression-layout">
        <div class="server-progression-copy">
            <span class="eyebrow">TU RECORRIDO</span>
            <h2 id="server-progression-title">El level 400<br><em>no es el final.</em></h2>
            <p>Tu personaje atraviesa etapas. El objetivo es que siempre exista una próxima mejora razonable: mejor equipo, otro mapa, una combinación pendiente, un nuevo reset o un objetivo de cuenta.</p>
        </div>
        <div class="server-progression-steps">
            <article><b>01</b><small>ORIGEN</small><strong>Construí tu personaje</strong><p>Primer equipo, Zen, habilidades y mapas iniciales.</p></article>
            <article><b>02</b><small>ASCENSO</small><strong>Ganate el próximo mapa</strong><p>Joyas, alas, mejores spots y decisiones de equipo.</p></article>
            <article><b>03</b><small>CONQUISTA</small><strong>Hacé valer tu progreso</strong><p>Reset, Master Reset, endgame y objetivos paralelos.</p></article>
        </div>
    </div>
</section>

<section class="server-systems-new" id="sistemas" aria-labelledby="server-systems-title">
    <div class="shell">
        <header class="server-section-heading">
            <div>
                <span class="eyebrow">SISTEMAS PROPIOS DE TU AVENTURA</span>
                <h2 id="server-systems-title">Siempre hay<br><em>algo más que buscar.</em></h2>
            </div>
            <p>Los nombres cuentan una historia; debajo te mostramos qué sistema representa cada uno para que sea fácil entenderlo.</p>
        </header>

        <div class="server-feature-stage">
            <figure class="server-feature-image">
                <img src="<?php echo $serverImage('server-systems.webp'); ?>" alt="Salón fantástico con portales, recompensas y sistemas de progresión">
            </figure>
            <div class="server-feature-copy">
                <span class="eyebrow">EL NEXO DE TU CUENTA</span>
                <h3>Nexo PANIC</h3>
                <p>El Hub F11 reúne accesos, eventos, estadísticas y funciones del cliente para que no tengas que memorizar cada comando o ventana.</p>
                <span class="server-system-plain">En el juego: Hub F11</span>
            </div>
        </div>

        <div class="server-system-cards">
            <article>
                <span class="server-system-icon"><?php echo mupanicGlyph('crest'); ?></span>
                <small>LOGROS</small>
                <h3>Crónicas del Conquistador</h3>
                <p>Convertí tu recorrido en hitos y objetivos que quedan registrados.</p>
            </article>
            <article>
                <span class="server-system-icon"><?php echo mupanicGlyph('sword'); ?></span>
                <small>BATTLE PASS</small>
                <h3>Camino del Héroe</h3>
                <p>Una ruta de recompensas que acompaña lo que ya hacés dentro del servidor.</p>
            </article>
            <article>
                <span class="server-system-icon"><?php echo mupanicGlyph('party'); ?></span>
                <small>DAILY REWARD</small>
                <h3>Tributo Diario</h3>
                <p>Volvé, reclamá tu premio y mantené activa una cadena de beneficios.</p>
            </article>
            <article>
                <span class="server-system-icon"><?php echo mupanicGlyph('gem'); ?></span>
                <small>RULETA</small>
                <h3>Fortuna del Caos</h3>
                <p>Usá WCoin C y probá tu suerte con una selección de premios definida por el servidor.</p>
            </article>
            <article>
                <span class="server-system-icon"><?php echo mupanicGlyph('gem'); ?></span>
                <small>JEWEL BANK</small>
                <h3>Bóveda Arcana</h3>
                <p>Guardá joyas a nivel de cuenta y prepará mejoras sin saturar tu inventario.</p>
            </article>
            <article>
                <span class="server-system-icon"><?php echo mupanicGlyph('wings'); ?></span>
                <small>VIP</small>
                <h3>Sello Imperial</h3>
                <p>Beneficios temporales de cuenta con estado visible desde la web.</p>
            </article>
        </div>
    </div>
</section>

<section class="server-atlas-showcase" aria-labelledby="server-atlas-title">
    <div class="server-atlas-background">
        <img src="<?php echo $serverImage('server-atlas.webp'); ?>" alt="Un estratega observando un mapa fantástico del continente">
    </div>
    <div class="shell server-atlas-content">
        <span class="eyebrow">CONOCÉ ANTES DE ARRIESGAR</span>
        <h2 id="server-atlas-title">Tu próximo objetivo<br><em>empieza en el Atlas.</em></h2>
        <p>Mapas, spots, mobs, drops, recompensas y rutas de progresión en una referencia construida alrededor del servidor real.</p>
        <a class="button primary" href="<?php echo __BASE_URL__; ?>info/">Abrir Atlas <span aria-hidden="true">↗</span></a>
    </div>
</section>

<section class="server-events-new" aria-labelledby="server-events-title">
    <div class="shell">
        <header class="server-section-heading">
            <div>
                <span class="eyebrow">EL CONTINENTE SE MUEVE</span>
                <h2 id="server-events-title">Eventos,<br><em>invasiones y bosses.</em></h2>
            </div>
            <p>Estamos auditando cada evento antes de publicarlo como parte definitiva del servidor. La presentación puede ser épica; la información tiene que ser cierta.</p>
        </header>

        <article class="server-event-cinematic">
            <figure>
                <img src="<?php echo $serverImage('server-events.webp'); ?>" alt="Una fortaleza sitiada durante una invasión nocturna">
            </figure>
            <div>
                <span class="server-validation-badge">EN VALIDACIÓN</span>
                <small>EVENTOS CLÁSICOS + CONTENIDO DEL SERVIDOR</small>
                <h3>Cuando suena la alarma,<br>el mapa deja de ser el mismo.</h3>
                <p>Blood Castle, Devil Square, Chaos Castle, invasiones y bosses están dentro del conjunto que estamos revisando. Horarios, recompensas y requisitos aparecerán acá cuando estén probados de punta a punta.</p>
            </div>
        </article>
    </div>
</section>

<section class="server-final-cta" aria-labelledby="server-final-title">
    <div class="server-final-art">
        <img src="<?php echo $serverImage('server-portals.webp'); ?>" alt="Aventureros ascendiendo hacia portales en una ciudadela">
    </div>
    <div class="shell server-final-content">
        <span class="eyebrow">EL CONTINENTE YA ESTÁ AHÍ</span>
        <h2 id="server-final-title">Elegí cómo<br><em>querés conquistarlo.</em></h2>
        <div class="server-final-actions">
            <a class="button primary" href="<?php echo __BASE_URL__; ?>register/">Crear cuenta <span aria-hidden="true">↗</span></a>
            <a class="button server-button-ghost" href="<?php echo __BASE_URL__; ?>downloads/">Descargar cliente <span aria-hidden="true">↓</span></a>
            <a class="text-link" href="<?php echo htmlspecialchars($discordInvite ?? 'https://discord.gg/fP4Mxcsee', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Entrar al Discord <span aria-hidden="true">↗</span></a>
        </div>
    </div>
</section>
