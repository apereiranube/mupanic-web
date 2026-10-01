<?php
if(!defined('access') or !access) die();
include('inc/template.functions.php');

$serverInfoCache = LoadCacheData('server_info.cache');
if(is_array($serverInfoCache) && isset($serverInfoCache[1][0])) {
    $srvInfo = explode("|", $serverInfoCache[1][0]);
}
$onlinePlayers = isset($srvInfo[3]) && is_numeric($srvInfo[3]) ? max(0, (int)$srvInfo[3]) : null;

if(!isset($_REQUEST['page'])) $_REQUEST['page'] = '';
if(!isset($_REQUEST['subpage'])) $_REQUEST['subpage'] = '';

$isHome = ($_REQUEST['page'] === '');
$cacheTime = isset($serverInfoCache[0][0]) && is_numeric($serverInfoCache[0][0]) ? (int)$serverInfoCache[0][0] : null;
$onlineCharacters = array();
if($isHome && function_exists('loadCache')) {
    $cachedCharacters = loadCache('online_characters.cache');
    if(is_array($cachedCharacters)) {
        foreach($cachedCharacters as $name) {
            if(is_string($name) && preg_match('/^[A-Za-z0-9_]{1,10}$/D', $name)) $onlineCharacters[] = $name;
        }
        $onlineCharacters = array_slice(array_unique($onlineCharacters), 0, 24);
    }
}
$isLogged = isLoggedIn();

$serverSeason = 'Season 6'; // MU PANIC UP43: editorial identity, independent of legacy CMS title.
$serverExp = mupanicServerValue('server_info_exp', '—');
$serverMasterExp = mupanicServerValue('server_info_masterexp', '—');
$serverDrop = mupanicServerValue('server_info_drop', '—');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="theme-color" content="#0b1113"/>
    <title><?php echo $isHome ? 'MU PANIC · Season 6 · Argentina' : htmlspecialchars(mupanicPageTitle($_REQUEST['page'], $_REQUEST['subpage'])).' · MU PANIC'; ?></title>
    <meta name="description" content="MU PANIC. MU Online Season 6 en Argentina: progresión por etapas, Zen con valor y un continente por conquistar. Creá tu cuenta y descargá el cliente."/>
    <meta name="keywords" content="MU PANIC, MU Online, Season 6, Argentina, Louis UP43"/>
    <meta property="og:type" content="website"/>
    <meta property="og:title" content="MU PANIC · Season 6"/>
    <meta property="og:description" content="Tu historia en el continente de MU. Descubrí MU PANIC, Season 6 en Argentina."/>
    <meta property="og:url" content="<?php echo __BASE_URL__; ?>"/>
    <?php if($isHome) { ?><link rel="preload" as="image" href="<?php echo __PATH_TEMPLATE__; ?>img/knight-v6.webp" fetchpriority="high"><?php } ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>style.css?v=6.0" rel="stylesheet">
    <script>var baseUrl = '<?php echo __BASE_URL__; ?>';</script>
    <noscript><style>@media(max-width:900px){.site-header{position:static}.nav-shell{height:auto;min-height:74px;flex-wrap:wrap;padding:15px 0}.main-nav{display:flex;flex-wrap:wrap;width:100%;order:3;padding:15px 0 0}.main-nav .mobile-account{display:block}.main-nav a{padding:9px;font-size:12px}.menu-toggle{display:none}.nav-actions{margin-left:auto}}</style></noscript>
</head>
<body class="<?php echo $isHome ? 'is-home' : 'is-inner'; ?>">

<header class="site-header">
    <div class="shell nav-shell">
        <a class="brand" href="<?php echo __BASE_URL__; ?>">
            <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 40 48"><path d="M3 38V9l17 15L37 9v29L20 47z M3 9V2l17 15L37 2v7 M20 24v23"/></svg></span>
            <span class="brand-copy"><strong>MU PANIC</strong><small>EL CONTINENTE TE ESPERA</small></span>
        </a>

        <button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="main-navigation">
            <span></span><span></span>
        </button>

        <nav class="main-nav" id="main-navigation" aria-label="Navegación principal">
            <a href="<?php echo __BASE_URL__; ?>#continente">El continente</a>
            <a href="<?php echo __BASE_URL__; ?>info/">Guías</a>
            <a href="<?php echo __BASE_URL__; ?>rankings/">Rankings</a>
            <a href="<?php echo __BASE_URL__; ?>downloads/">Descargas</a>
            <a class="mobile-account" href="<?php echo __BASE_URL__; ?><?php echo $isLogged ? 'usercp/' : 'login/'; ?>"><?php echo $isLogged ? 'Mi cuenta' : 'Ingresar'; ?></a>
        </nav>

        <div class="nav-actions">

            <?php if($isLogged) { ?>
                <a class="account-link" href="<?php echo __BASE_URL__; ?>usercp/">Mi cuenta</a>
                <a class="nav-cta" href="<?php echo __BASE_URL__; ?>logout/">Salir</a>
            <?php } else { ?>
                <a class="account-link" href="<?php echo __BASE_URL__; ?>login/">Ingresar</a>
                <a class="nav-cta" href="<?php echo __BASE_URL__; ?>register/">Crear cuenta</a>
            <?php } ?>
        </div>
    </div>
</header>

<a class="skip-link" href="#main-content">Saltar al contenido</a>
<main id="main-content">
<?php if($isHome) { ?>

<section class="panic-hero" aria-labelledby="hero-title" data-scene>
    <div class="scene-art hero-art" aria-hidden="true" data-depth></div>
    <div class="hero-character" aria-hidden="true"><img src="<?php echo __PATH_TEMPLATE__; ?>img/knight-v6.webp" alt="" width="2048" height="3072" fetchpriority="high"><div class="blade-aura"></div></div>
    <div class="hero-atmosphere" aria-hidden="true"></div>
    <div class="embers" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
    <div class="scene-fog" aria-hidden="true"></div>
    <button class="scene-control" type="button" aria-pressed="false" aria-label="Pausar animación">Ⅱ <span>Pausar escena</span></button>
    <button class="sound-control" type="button" aria-pressed="false" aria-label="Activar sonido ambiente">♪ <span>Ambiente apagado</span></button>
    <div class="hero-frame shell">
        <div class="hero-overline"><span class="eyebrow">MU ONLINE / ARGENTINA</span><span class="edition">UNA NUEVA HISTORIA.<br>EL MISMO CONTINENTE.</span></div>
        <div class="hero-content">
            <p class="eyebrow hero-pretitle">EL CONTINENTE TE ESTÁ LLAMANDO</p>
            <h1 id="hero-title"><span>MU</span><span>PANIC<span class="title-period">.</span></span></h1>
            <div class="hero-summary"><span class="fine-rule" aria-hidden="true"></span><p>Volvé al lugar donde todo empezó.<br>Hacé que cada conquista cuente.</p></div>
            <div class="hero-actions"><a class="button primary" href="<?php echo __BASE_URL__; ?>downloads/">Entrar al juego <span aria-hidden="true">↗</span></a><a class="text-link" href="<?php echo __BASE_URL__; ?>register/">Crear cuenta <span aria-hidden="true">→</span></a></div>
        </div>
        <div class="hero-bottom"><a class="scroll-cue" href="#continente"><span class="scroll-line" aria-hidden="true"></span>EXPLORÁ EL CONTINENTE</a><span class="scene-caption">01 / EL UMBRAL</span></div>
    </div>
</section>

<section class="server-dossier" aria-label="Información del servidor" data-status-url="<?php echo htmlspecialchars(__BASE_URL__); ?>">
    <div class="shell">
        <div class="hud-heading"><span class="eyebrow">LA BASE DE TU AVENTURA</span><span>EXPLORÁ LOS SISTEMAS <span aria-hidden="true">↘</span></span></div>
        <div class="server-hud">
            <details class="hud-item hud-season"><summary><span class="hud-icon" aria-hidden="true"><?php echo mupanicGlyph('crest'); ?></span><span class="hud-label">EL CONTINENTE</span><strong><?php echo $serverSeason; ?></strong><small>Louis UP43 · Argentina</small><span class="hud-toggle" aria-hidden="true">+</span></summary><div class="hud-content"><p>El continente clásico de MU. Elegí tu clase y prepará tu recorrido desde Lorencia y Noria hasta las zonas de conquista.</p><a href="<?php echo __BASE_URL__; ?>info/#primeros-pasos">Elegí tu primer objetivo ↗</a></div></details>
            <details class="hud-item"><summary><span class="hud-icon" aria-hidden="true"><?php echo mupanicGlyph('sword'); ?></span><span class="hud-label">EXPERIENCIA</span><strong><?php echo htmlspecialchars($serverExp); ?></strong><small>Tu camino al siguiente nivel</small><span class="hud-toggle" aria-hidden="true">+</span></summary><div class="hud-content"><p>La experiencia normal impulsa los niveles de tu personaje. El mapa, tu equipo y la party acompañan cada etapa.</p><a href="<?php echo __BASE_URL__; ?>info/#progresion">Explorá la progresión ↗</a></div></details>
            <details class="hud-item"><summary><span class="hud-icon" aria-hidden="true"><?php echo mupanicGlyph('wings'); ?></span><span class="hud-label">MASTER EXP</span><strong><?php echo htmlspecialchars($serverMasterExp); ?></strong><small>La siguiente etapa</small><span class="hud-toggle" aria-hidden="true">+</span></summary><div class="hud-content"><p>La experiencia Master corresponde a la progresión Master de tu personaje. Revisá los sistemas y requisitos antes de tu próximo salto.</p><a href="<?php echo __BASE_URL__; ?>info/#sistemas">Conocé los sistemas ↗</a></div></details>
            <details class="hud-item"><summary><span class="hud-icon" aria-hidden="true"><?php echo mupanicGlyph('gem'); ?></span><span class="hud-label">DROP</span><strong><?php echo htmlspecialchars($serverDrop); ?></strong><small>Equipá tu próxima conquista</small><span class="hud-toggle" aria-hidden="true">+</span></summary><div class="hud-content"><p>Este es el valor general de drop publicado por el servidor. Cada objeto, evento o combinación puede tener sus propias condiciones.</p><a href="<?php echo __BASE_URL__; ?>info/#sistemas">Prepará tus mejoras ↗</a></div></details>
            <details class="hud-item hud-online"><summary><span class="hud-icon" aria-hidden="true"><?php echo mupanicGlyph('party'); ?></span><span class="hud-label">CONECTADOS</span><strong data-online-count><?php echo $onlinePlayers === null ? '—' : number_format($onlinePlayers); ?></strong><small data-online-note><?php echo $onlinePlayers === null ? 'Sin datos disponibles' : 'Último registro del servidor'; ?></small><span class="hud-toggle" aria-hidden="true">+</span></summary><div class="hud-content"><p>Conexiones registradas por el servidor. La lista de personajes proviene de un registro independiente y puede actualizarse en otro momento.</p><div class="online-roster" data-online-roster><?php if(count($onlineCharacters)) { foreach($onlineCharacters as $name) { ?><span><?php echo htmlspecialchars($name); ?></span><?php } } else { ?><p>La lista de personajes todavía no está disponible.</p><?php } ?></div></div></details>
        </div>
        <div class="hud-footer"><p role="status" data-status-message data-cache-time="<?php echo $cacheTime ?: ''; ?>"><span class="status-dot" aria-hidden="true"></span><?php echo $cacheTime ? 'Registro: '.gmdate('H:i', $cacheTime).' UTC' : 'Sin hora de registro disponible'; ?></p><button class="status-refresh" type="button">Actualizar registro <span aria-hidden="true">↻</span></button><a class="text-link" href="<?php echo __BASE_URL__; ?>info/">Guía del servidor <span aria-hidden="true">↗</span></a></div>
    </div>
</section>

<section class="continent" id="continente" aria-labelledby="continent-title">
    <div class="shell continent-intro reveal"><span class="eyebrow">02 / EL VIAJE</span><h2 id="continent-title">No se hereda<br>una leyenda.<em>Se construye.</em></h2><p>Desde tu primera arma hasta las zonas que se disputan en party. Tu historia avanza con cada mapa.</p></div>
    <div class="journey">
        <div class="journey-stage" aria-hidden="true" data-ambience="0">
            <div class="journey-backdrop journey-origin is-active" data-chapter-art="0"></div>
            <div class="journey-backdrop journey-ascent" data-chapter-art="1"></div>
            <div class="journey-backdrop journey-conquest" data-chapter-art="2"></div>
            <div class="journey-weather"></div><div class="journey-vignette"></div><span class="journey-word" data-journey-word>ORIGEN</span>
            <div class="journey-indicator"><span data-journey-index>01</span><div><i data-journey-progress></i></div><span>03</span></div>
        </div>
        <div class="journey-chapters shell">
            <article class="journey-chapter" data-chapter="0" data-word="ORIGEN" aria-labelledby="origin-title">
                <div class="chapter-copy"><span class="chapter-emblem" aria-hidden="true"><?php echo mupanicGlyph('sword'); ?></span><span class="eyebrow">I / EL PRIMER PASO</span><h3 id="origin-title">Todo empieza<br>con una espada.</h3><p>Lorencia y Noria. El primer equipo, los primeros spots y la decisión de seguir un poco más.</p><div class="chapter-detail"><span>TU OBJETIVO</span><strong>Construí tu personaje.</strong><p>Elegí tu clase, conocé tus habilidades y prepará el equipo para salir de las zonas iniciales.</p></div><a class="text-link" href="<?php echo __BASE_URL__; ?>info/#primeros-pasos">Consultá la guía de inicio <span aria-hidden="true">→</span></a></div>
            </article>
            <article class="journey-chapter" data-chapter="1" data-word="ASCENSO" aria-labelledby="ascent-title">
                <div class="chapter-copy"><span class="chapter-emblem" aria-hidden="true"><?php echo mupanicGlyph('wings'); ?></span><span class="eyebrow">II / GANATE TUS ALAS</span><h3 id="ascent-title">El próximo mapa<br>se gana.</h3><p>Devias, Dungeon, Atlans y Lost Tower. Después, Tarkan e Icarus: el recorrido exige más de tu personaje.</p><div class="chapter-detail"><span>TU OBJETIVO</span><strong>Equipá. Farmeá. Avanzá.</strong><p>Guardá Zen, prepará tus combinaciones en la Chaos Machine y conseguí tus primeras alas. Icarus las requiere.</p></div><a class="text-link" href="<?php echo __BASE_URL__; ?>info/#progresion">Conocé la progresión <span aria-hidden="true">→</span></a></div>
            </article>
            <article class="journey-chapter" data-chapter="2" data-word="CONQUISTA" aria-labelledby="conquest-title">
                <div class="chapter-copy"><span class="chapter-emblem" aria-hidden="true"><?php echo mupanicGlyph('crest'); ?></span><span class="eyebrow">III / BUSCÁ TU LUGAR</span><h3 id="conquest-title">Llegar es sólo<br>el comienzo.</h3><p>Kanturu, Raklion, Swamp y Karutan. Mejores objetivos, spots disputados y una razón para reunir a tu party.</p><div class="chapter-detail"><span>TU OBJETIVO</span><strong>Hacé valer tu progreso.</strong><p>Reset por etapas y Master Reset. Prepará tu siguiente objetivo y consultá los requisitos antes de dar el salto.</p></div><a class="text-link" href="<?php echo __BASE_URL__; ?>info/#sistemas">Explorá los sistemas <span aria-hidden="true">→</span></a></div>
            </article>
        </div>
    </div>
</section>

<section class="panic-manifesto" aria-labelledby="manifesto-title">
    <div class="shell manifesto-layout"><span class="eyebrow">03 / LA ESENCIA PANIC</span><div><h2 id="manifesto-title">Tu tiempo.<br>Tu equipo.<br><em>Tu conquista.</em></h2><p>Un continente conocido. Decisiones que importan.</p></div><div class="manifesto-notes"><article><span>01</span><div><h3>El Zen tiene peso.</h3><p>Farmear, guardar y vender forman parte del progreso. Pensá tu próxima mejora.</p></div></article><article><span>02</span><div><h3>Cada etapa tiene un destino.</h3><p>Spots diseñados por mapa y objetivos para avanzar con tu personaje.</p></div></article><article><span>03</span><div><h3>La aventura se comparte.</h3><p>Armá tu party. Encontrá tu guild. Volvé por esa conquista que todavía te falta.</p></div></article></div></div>
</section>

<section class="play-gateway" id="empezar" aria-labelledby="play-title">
    <div class="gateway-art" aria-hidden="true"></div><div class="shell gateway-layout"><div class="gateway-copy"><span class="eyebrow">04 / TU HISTORIA EMPIEZA ACÁ</span><h2 id="play-title">Nos vemos<br><em>en Lorencia.</em></h2><p>Prepará tu cuenta y el cliente.<br>El siguiente paso lo das dentro del juego.</p></div><div class="launch-steps"><a href="<?php echo __BASE_URL__; ?><?php echo $isLogged ? 'usercp/' : 'register/'; ?>"><span>01</span><div><small><?php echo $isLogged ? 'TU PANEL' : 'TU IDENTIDAD'; ?></small><strong><?php echo $isLogged ? 'Abrir mi cuenta' : 'Crear mi cuenta'; ?></strong></div><b aria-hidden="true">↗</b></a><a class="launch-download" href="<?php echo __BASE_URL__; ?>downloads/"><span>02</span><div><small>EL CLIENTE / PC</small><strong>Descargar MU PANIC</strong></div><b aria-hidden="true">↓</b></a><a href="<?php echo __BASE_URL__; ?>info/"><span>03</span><div><small>ANTES DE ENTRAR</small><strong>Leer la guía del servidor</strong></div><b aria-hidden="true">↗</b></a></div></div>
</section>

<?php } else { ?>

<section class="inner-hero">
    <div class="shell inner-head">
        <div><span class="eyebrow"><?php echo htmlspecialchars(strtoupper($_REQUEST['page'])); ?></span><h1><?php echo htmlspecialchars(mupanicPageTitle($_REQUEST['page'], $_REQUEST['subpage'])); ?></h1></div>
        <a href="<?php echo __BASE_URL__; ?>">← Inicio</a>
    </div>
</section>

<section class="inner-content">
    <div class="shell">
        <?php if($_REQUEST['page'] == 'usercp' && $_REQUEST['subpage'] != '') { ?>
            <div class="account-layout">
                <aside class="account-nav"><div class="account-nav-head"><small>TU CUENTA</small><strong>Panel de usuario</strong></div><?php templateBuildUsercp(); ?></aside>
                <div class="module-surface"><?php $handler->loadModule($_REQUEST['page'], $_REQUEST['subpage']); ?></div>
            </div>
        <?php } else { ?>
            <div class="module-surface"><?php
                if($_REQUEST['page'] === 'info') {
                    include('inc/guide.php');
                } elseif($_REQUEST['page'] === 'downloads') {
                    ob_start();
                    $handler->loadModule($_REQUEST['page'], $_REQUEST['subpage']);
                    $downloadsMarkup = ob_get_clean();
                    if(stripos($downloadsMarkup, '<a ') === false && stripos($downloadsMarkup, 'alert') === false) {
                        echo '<div class="download-notice"><span class="eyebrow">CLIENTE PARA PC</span><h2>La descarga todavía no está publicada.</h2><p>Cuando el cliente esté disponible, encontrarás los enlaces en esta sección. Mientras tanto, podés preparar tu cuenta y conocer la guía del servidor.</p><a class="btn btn-primary" href="'.__BASE_URL__.'register/">Crear cuenta</a> <a href="'.__BASE_URL__.'info/">Leer la guía →</a></div>';
                    } else {
                        echo $downloadsMarkup;
                    }
                } else {
                    $handler->loadModule($_REQUEST['page'], $_REQUEST['subpage']);
                }
                ?></div>
        <?php } ?>
    </div>
</section>

<?php } ?>
</main>

<footer class="footer-modern">
    <div class="shell footer-main">
        <div>
            <a class="brand" href="<?php echo __BASE_URL__; ?>"><span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 40 48"><path d="M3 38V9l17 15L37 9v29L20 47z M3 9V2l17 15L37 2v7 M20 24v23"/></svg></span><span class="brand-copy"><strong>MU PANIC</strong><small>EL CONTINENTE TE ESPERA</small></span></a>
            <p>Tu historia en el continente de MU.</p>
        </div>
        <div class="footer-links">
            <div><small>JUGAR</small><a href="<?php echo __BASE_URL__; ?>downloads/">Descargas</a><a href="<?php echo __BASE_URL__; ?>info/">Guías</a></div>
            <div><small>CUENTA</small><?php if($isLogged) { ?><a href="<?php echo __BASE_URL__; ?>usercp/">Mi cuenta</a><?php } else { ?><a href="<?php echo __BASE_URL__; ?>register/">Crear cuenta</a><?php } ?><a href="<?php echo __BASE_URL__; ?>rankings/">Rankings</a></div>
        </div>
    </div>
    <div class="shell footer-bottom"><span>© <?php echo date('Y'); ?> MU PANIC</span><span>MU Online y sus marcas pertenecen a sus respectivos titulares.</span></div>
</footer>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js"></script>
<script src="<?php echo __PATH_TEMPLATE_JS__; ?>main.js?v=6.0"></script>
</body>
</html>
