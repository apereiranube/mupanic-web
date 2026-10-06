<?php
if(!defined('access') || !access) die();

$serverImage = function($name) {
    return __PATH_TEMPLATE__.'img/server/'.$name;
};
$panicSystems = [
    'chronicles' => [
        'art' => 'chronicles-d6291c69cf6b.webp',
        'category' => 'PROGRESIÓN',
        'title' => 'Crónicas del Conquistador',
        'short' => 'Cada conquista deja su marca.',
        'tagline' => 'Tu progreso no se mide solo en level. También se escribe en todo lo que conquistás.',
        'kind' => 'Achievements',
        'intro' => 'Un recorrido de hitos que continúa más allá del level.',
        'state' => 'Configurado',
        'alt' => 'Salón de logros con escudos y trofeos',
        'facts' => [['Objetivos', 'Metas e hitos'], ['Recorrido', 'Progreso extendido'], ['En paralelo', 'Nuevas conquistas'], ['Acceso', 'Dentro del cliente']],
    ],
    'hero-path' => [
        'art' => 'hero-path-5331edd1e310.webp',
        'category' => 'RECOMPENSAS',
        'title' => 'Camino del Héroe',
        'short' => 'Una nueva meta en cada etapa.',
        'tagline' => 'Cada etapa que superás te acerca a una nueva recompensa.',
        'kind' => 'Battle Pass',
        'intro' => 'Objetivos y recompensas acompañan tu avance por etapas.',
        'state' => 'Configurado',
        'alt' => 'Guerrero ascendiendo hacia un altar',
        'facts' => [['Progreso', 'Por etapas'], ['Desafío', 'Completar objetivos'], ['Destino', 'Recompensas'], ['Acceso', 'Dentro del juego']],
    ],
    'daily' => [
        'art' => 'daily-e36b93392874.webp',
        'category' => 'CONSTANCIA',
        'title' => 'Tributo Diario',
        'short' => 'El regreso también tiene su recompensa.',
        'tagline' => 'Volvé cada día. El continente también recompensa la constancia.',
        'kind' => 'Daily Reward',
        'intro' => 'Una recompensa diaria para acompañar la continuidad.',
        'state' => 'Configurado',
        'alt' => 'Calendario ceremonial y cofre',
        'facts' => [['Frecuencia', 'Cada día'], ['Tributo', 'Recompensa diaria'], ['Recorrido', 'Continuidad'], ['Acceso', 'Interfaz del cliente']],
    ],
    'fortune' => [
        'art' => 'fortune-a300dc40b62a.webp',
        'category' => 'FORTUNA',
        'title' => 'Fortuna del Caos',
        'short' => 'El caos decide. Vos girás.',
        'tagline' => 'El caos decide. Vos girás.',
        'kind' => 'Ruleta',
        'intro' => 'Girás y retirás el premio desde la propia interfaz.',
        'state' => 'Operativo',
        'alt' => 'Ruleta de bronce rodeada de energía roja',
        'facts' => [['Por giro', '300 WCoin C'], ['Premios', 'Definidos por el servidor'], ['Retiro', 'Desde la interfaz'], ['Acceso', 'Dentro del juego']],
    ],
    'vault' => [
        'art' => 'vault-47074095857a.webp',
        'category' => 'RESERVA',
        'title' => 'Bóveda Arcana',
        'short' => 'Más espacio para tu próxima conquista.',
        'tagline' => 'Tus joyas no tienen por qué vivir apretadas en el inventario.',
        'kind' => 'Jewel Bank',
        'intro' => 'Una reserva de joyas asociada a tu cuenta.',
        'state' => 'Probado · operativo',
        'alt' => 'Cámara arcana con joyas y cofres',
        'facts' => [['Reserva', 'Depósito de joyas'], ['Alcance', 'Nivel de cuenta'], ['Inventario', 'Libera espacio'], ['Prueba', 'Probado y operativo']],
    ],
    'vip' => [
        'art' => 'vip-66179e5e17f8.webp',
        'category' => 'MEMBRESÍA',
        'title' => 'Sello Imperial',
        'short' => 'Un sello para acompañar tu recorrido.',
        'tagline' => 'Una membresía pensada para acompañar tu recorrido.',
        'kind' => 'VIP',
        'intro' => 'Beneficios temporales definidos por la configuración vigente.',
        'state' => 'Disponible',
        'alt' => 'Corona y sello imperial sobre un altar',
        'facts' => [['Beneficios', 'Según configuración'], ['Vigencia', 'Temporal'], ['Estado', 'Visible en juego y web'], ['Sistema', 'Membresía VIP']],
    ],
    'nexus' => [
        'art' => 'nexus-4a6cbef57b72.webp',
        'detail_art' => 'hub-f11-03b5aefe024d.webp',
        'category' => 'CENTRO DE MANDO',
        'title' => 'Nexo PANIC',
        'short' => 'Tu mundo, reunido en un solo lugar.',
        'tagline' => 'El punto donde el cliente se vuelve centro de mando.',
        'kind' => 'Hub F11',
        'intro' => 'Abrí F11 para consultar tu progreso y entrar a los sistemas del servidor.',
        'state' => 'Operativo',
        'alt' => 'Centro de mando fantástico con portales',
        'detail_alt' => 'Hub F11: datos personales ocultos y barras de progreso ilustrativas',
        'facts' => [['Tu progreso', 'Nivel, resets y estadísticas del personaje.'], ['Accesos rápidos', 'Logros, Battle Pass y recompensa diaria.'], ['Más herramientas', 'Ruleta, VIP, rankings y eventos.'], ['En el cliente', 'Un centro de mando propio de MU PANIC.']],
    ],
];
$systemEscape = function($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); };
?>

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

<section class="server-features" id="sistemas" aria-labelledby="server-systems-title">
    <div class="shell">
        <header class="server-features-heading">
            <span class="eyebrow">MÁS ALLÁ DEL LEVEL</span>
            <h2 id="server-systems-title">Siempre hay<br><em>algo más que buscar.</em></h2>
            <p>Siete formas de seguir escribiendo tu historia.</p>
        </header>
        <div class="server-features-grid">
        <?php $systemNumber = 0; foreach($panicSystems as $id => $system): $systemNumber++; ?>
            <article class="server-feature-card<?php echo $id === 'nexus' ? ' server-feature-card-nexus' : ''; ?>">
                <img class="server-feature-art" src="<?php echo $serverImage('systems/'.$system['art']); ?>" alt="<?php echo $systemEscape($system['alt']); ?>" width="1536" height="1024" loading="lazy">
                <div class="server-feature-copy">
                    <span class="server-feature-category"><?php echo $system['category']; ?></span>
                    <h3><?php echo $system['title']; ?></h3>
                    <p><?php echo $system['short']; ?></p>
                    <button type="button" data-system-open="<?php echo $id; ?>" aria-haspopup="dialog" aria-controls="server-feature-dialog" aria-label="Ver sistema: <?php echo $systemEscape($system['title']); ?>">Ver sistema <span aria-hidden="true">→</span></button>
                </div>
                <span class="server-feature-number" aria-hidden="true"><?php echo sprintf('%02d', $systemNumber); ?></span>
            </article>
        <?php endforeach; ?>
        </div>
    </div>
    <dialog class="server-feature-dialog" id="server-feature-dialog" data-system-modal>
        <button type="button" class="server-feature-close" data-system-close aria-label="Cerrar sistema" autofocus>×</button>
        <?php foreach($panicSystems as $id => $system): ?>
        <article class="server-feature-panel" data-system-panel="<?php echo $id; ?>" hidden>
            <figure class="server-feature-visual">
                <img src="<?php echo $serverImage('systems/'.($system['detail_art'] ?? $system['art'])); ?>" alt="<?php echo $systemEscape($system['detail_alt'] ?? $system['alt']); ?>" width="<?php echo $id === 'nexus' ? 1522 : 1536; ?>" height="<?php echo $id === 'nexus' ? 1033 : 1024; ?>" loading="lazy">
                <figcaption>MU PANIC <span><?php echo $id === 'nexus' ? 'Datos ocultos · barras ilustrativas' : 'Arte conceptual'; ?></span></figcaption>
            </figure>
            <div class="server-feature-content">
                <span class="server-feature-category"><?php echo $system['category']; ?></span>
                <h2 id="system-title-<?php echo $id; ?>"><?php echo $system['title']; ?></h2>
                <p class="server-feature-tagline" id="system-description-<?php echo $id; ?>"><?php echo $system['tagline']; ?></p>
                <p class="server-feature-intro"><?php echo $system['intro']; ?></p>
                <dl class="server-feature-facts">
                <?php foreach($system['facts'] as $index => $fact): ?>
                    <div<?php echo $id === 'fortune' && $index === 0 ? ' class="server-feature-price"' : ''; ?>><dt><?php echo $fact[0]; ?></dt><dd><?php echo $fact[1]; ?><?php if($id === 'fortune' && $index === 0) { ?><small>por giro</small><?php } ?></dd></div>
                <?php endforeach; ?>
                </dl>
                <footer class="server-feature-status"><span><?php echo $system['kind']; ?></span><span class="server-feature-state"><?php echo $system['state']; ?></span></footer>
            </div>
        </article>
        <?php endforeach; ?>
    </dialog>
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
