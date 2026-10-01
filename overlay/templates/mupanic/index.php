<?php
if(!defined('access') or !access) die();
include('inc/template.functions.php');

$serverInfoCache = LoadCacheData('server_info.cache');
if(is_array($serverInfoCache) && isset($serverInfoCache[1][0])) {
    $srvInfo = explode("|", $serverInfoCache[1][0]);
}
$onlinePlayers = isset($srvInfo[3]) ? (int)$srvInfo[3] : 0;

if(!isset($_REQUEST['page'])) $_REQUEST['page'] = '';
if(!isset($_REQUEST['subpage'])) $_REQUEST['subpage'] = '';

$isHome = ($_REQUEST['page'] === '');
$isLogged = isLoggedIn();

$serverSeason = mupanicServerValue('server_info_season', 'Season 6');
$serverExp = mupanicServerValue('server_info_exp', '—');
$serverMasterExp = mupanicServerValue('server_info_masterexp', '—');
$serverDrop = mupanicServerValue('server_info_drop', '—');
$topResetPlayers = $isHome ? mupanicTopResetPlayers(5) : array();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="theme-color" content="#111111"/>
    <title><?php $handler->websiteTitle(); ?></title>
    <meta name="description" content="<?php config('website_meta_description'); ?>"/>
    <meta name="keywords" content="<?php config('website_meta_keywords'); ?>"/>
    <meta property="og:type" content="website"/>
    <meta property="og:title" content="<?php $handler->websiteTitle(); ?>"/>
    <meta property="og:description" content="<?php config('website_meta_description'); ?>"/>
    <meta property="og:url" content="<?php echo __BASE_URL__; ?>"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>style.css" rel="stylesheet">
    <script>var baseUrl = '<?php echo __BASE_URL__; ?>';</script>
</head>
<body class="<?php echo $isHome ? 'is-home' : 'is-inner'; ?>">

<header class="site-header">
    <div class="shell nav-shell">
        <a class="brand" href="<?php echo __BASE_URL__; ?>">
            <span class="brand-mark">MP</span>
            <span class="brand-copy"><strong>MU PANIC</strong><small>SEASON 6 · ARGENTINA</small></span>
        </a>

        <button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false">
            <span></span><span></span>
        </button>

        <nav class="main-nav">
            <a href="<?php echo __BASE_URL__; ?>">Inicio</a>
            <a href="<?php echo __BASE_URL__; ?>information/">Guías</a>
            <a href="<?php echo __BASE_URL__; ?>rankings/">Rankings</a>
            <a href="<?php echo __BASE_URL__; ?>downloads/">Descargas</a>
        </nav>

        <div class="nav-actions">
            <div class="online-pill"><i></i><strong><?php echo number_format($onlinePlayers); ?></strong><span>online</span></div>
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

<main>
<?php if($isHome) { ?>

<section class="hero" data-parallax-hero>
    <div class="hero-left">
        <div class="hero-left-inner reveal">
            <div class="hero-eyebrow"><span>MU ONLINE</span><i></i><span><?php echo htmlspecialchars($serverSeason); ?></span></div>
            <h1>Volvé a sentir<br>que <em>progresar</em><br>vale la pena.</h1>
            <p>MU PANIC combina la base clásica que conocés con una progresión más clara, economía con valor y objetivos que te empujan a seguir.</p>

            <div class="hero-actions">
                <a class="button primary button-shine" href="<?php echo __BASE_URL__; ?>downloads/">Jugar ahora <b>↗</b></a>
                <a class="button secondary" href="<?php echo __BASE_URL__; ?>information/">Ver cómo empezar</a>
            </div>

            <div class="hero-facts">
                <div><strong data-count="<?php echo (int)$onlinePlayers; ?>"><?php echo number_format($onlinePlayers); ?></strong><span>online ahora</span></div>
                <div><strong><?php echo htmlspecialchars($serverExp); ?></strong><span>experiencia</span></div>
                <div><strong><?php echo htmlspecialchars($serverDrop); ?></strong><span>drop</span></div>
            </div>
        </div>
    </div>

    <div class="hero-visual">
        <div class="hero-art" data-parallax-layer></div>
        <div class="hero-art-shade"></div>
        <div class="hero-light-sweep"></div>
        <div class="particles" data-particles aria-hidden="true"></div>

        <div class="hero-status-card reveal">
            <div class="status-head">
                <div><small>SERVIDOR EN VIVO</small><strong>MU PANIC</strong></div>
                <span class="status-live"><i></i> ONLINE</span>
            </div>
            <div class="status-grid">
                <div><small>JUGADORES</small><strong><?php echo number_format($onlinePlayers); ?></strong></div>
                <div><small>VERSIÓN</small><strong><?php echo htmlspecialchars($serverSeason); ?></strong></div>
            </div>
            <a href="<?php echo __BASE_URL__; ?>rankings/">Ver rankings <span>→</span></a>
        </div>

        <div class="hero-caption">LORENCIA · CONTINENTE DE MU</div>
    </div>
</section>

<section class="dna-section">
    <div class="shell">
        <div class="dna-rail reveal">
            <div class="dna-label"><span>SERVER DNA</span><strong>Los números que importan.</strong></div>
            <div class="dna-item"><small>SEASON</small><strong><?php echo htmlspecialchars($serverSeason); ?></strong></div>
            <div class="dna-item"><small>EXP</small><strong><?php echo htmlspecialchars($serverExp); ?></strong></div>
            <div class="dna-item"><small>MASTER EXP</small><strong><?php echo htmlspecialchars($serverMasterExp); ?></strong></div>
            <div class="dna-item"><small>DROP</small><strong><?php echo htmlspecialchars($serverDrop); ?></strong></div>
            <div class="dna-item"><small>REGIÓN</small><strong>Argentina</strong></div>
            <a class="dna-link" href="<?php echo __BASE_URL__; ?>information/">Ver configuración <span>↗</span></a>
        </div>

        <div class="dna-tags reveal">
            <span>Reset por etapas</span>
            <span>Master Reset</span>
            <span>Zen con valor</span>
            <span>Primeras alas vía Chaos</span>
            <span>Icarus requiere alas</span>
            <span>Spots diseñados por mapa</span>
        </div>
    </div>
</section>

<section class="start-section">
    <div class="shell">
        <div class="section-heading reveal">
            <span class="kicker">EMPEZÁ ACÁ</span>
            <h2>Entrá al server sin perder tiempo.</h2>
            <p>Cuatro accesos directos para hacer lo importante antes de abrir el cliente.</p>
        </div>

        <div class="start-grid">
            <a class="start-card reveal hover-tilt" href="<?php echo __BASE_URL__; ?>downloads/"><span class="step">01</span><div><small>PRIMERO</small><strong>Descargá el cliente</strong><p>Todo lo necesario para instalar y entrar.</p></div><b>↗</b></a>
            <?php if($isLogged) { ?>
                <a class="start-card reveal hover-tilt" href="<?php echo __BASE_URL__; ?>usercp/"><span class="step">02</span><div><small>CUENTA</small><strong>Gestioná tu personaje</strong><p>Panel de usuario, resets y opciones de cuenta.</p></div><b>↗</b></a>
            <?php } else { ?>
                <a class="start-card reveal hover-tilt" href="<?php echo __BASE_URL__; ?>register/"><span class="step">02</span><div><small>CUENTA</small><strong>Creá tu usuario</strong><p>Registrate y dejá lista tu cuenta para jugar.</p></div><b>↗</b></a>
            <?php } ?>
            <a class="start-card reveal hover-tilt" href="<?php echo __BASE_URL__; ?>information/"><span class="step">03</span><div><small>GUÍAS</small><strong>Entendé qué hacer</strong><p>Mapas, progresión, sistemas y prioridades.</p></div><b>↗</b></a>
            <a class="start-card reveal hover-tilt" href="<?php echo __BASE_URL__; ?>rankings/"><span class="step">04</span><div><small>COMPETENCIA</small><strong>Mirá quién está arriba</strong><p>Rankings y referencia del progreso real.</p></div><b>↗</b></a>
        </div>
    </div>
</section>

<section class="world-section" data-world-journey>
    <div class="shell world-shell">
        <div class="world-copy reveal">
            <span class="kicker light">TU VIAJE</span>
            <h2>El continente no es un menú.<br>Es tu progresión.</h2>
            <p>La ruta se va endureciendo con vos. Cada etapa tiene mapas, spots y objetivos propios.</p>
            <a href="<?php echo __BASE_URL__; ?>information/">Abrir guía completa <span>→</span></a>
        </div>

        <div class="world-map reveal">
            <div class="world-map-bg"></div>
            <div class="world-route">
                <div class="world-route-line"><span data-route-progress></span></div>

                <a class="world-node n1 active" href="<?php echo __BASE_URL__; ?>information/"><i></i><div><small>01</small><strong>Lorencia / Noria</strong><span>Inicio · primer equipo</span></div></a>
                <a class="world-node n2" href="<?php echo __BASE_URL__; ?>information/"><i></i><div><small>02</small><strong>Devias / Dungeon</strong><span>Leveleo · Zen · transición</span></div></a>
                <a class="world-node n3" href="<?php echo __BASE_URL__; ?>information/"><i></i><div><small>03</small><strong>Atlans / Lost Tower</strong><span>Farmeo serio · mejores drops</span></div></a>
                <a class="world-node n4" href="<?php echo __BASE_URL__; ?>information/"><i></i><div><small>04</small><strong>Tarkan / Icarus</strong><span>Alas · daño · requisitos</span></div></a>
                <a class="world-node n5" href="<?php echo __BASE_URL__; ?>information/"><i></i><div><small>05</small><strong>Kanturu / Raklion</strong><span>Party · endgame · competencia</span></div></a>
                <a class="world-node n6" href="<?php echo __BASE_URL__; ?>information/"><i></i><div><small>06</small><strong>Swamp / Karutan</strong><span>Objetivos disputados</span></div></a>
            </div>
        </div>
    </div>
</section>

<section class="why-section">
    <div class="shell">
        <div class="why-layout">
            <div class="why-intro reveal">
                <span class="kicker light">POR QUÉ MU PANIC</span>
                <h2>No queremos otro server que se sienta igual.</h2>
                <p>La gracia está en que cada etapa tenga una razón de ser: dónde levelear, qué guardar, qué vender y cuándo vale la pena avanzar.</p>
                <a href="<?php echo __BASE_URL__; ?>information/">Conocé los sistemas <span>→</span></a>
            </div>
            <div class="why-cards">
                <article class="why-card why-card-bright reveal hover-tilt"><span>01</span><small>PROGRESIÓN</small><h3>Mapas con propósito.</h3><p>No saltás contenido porque sí. Cada zona acompaña una etapa de crecimiento.</p></article>
                <article class="why-card reveal hover-tilt"><span>02</span><small>ECONOMÍA</small><h3>El Zen importa.</h3><p>Farmear y vender vuelve a tener peso real dentro de tus decisiones.</p></article>
                <article class="why-card reveal hover-tilt"><span>03</span><small>ENDGAME</small><h3>Siempre hay otro objetivo.</h3><p>Reset, Master Reset, eventos, rankings y zonas para competir.</p></article>
            </div>
        </div>
    </div>
</section>

<section class="ranking-preview">
    <div class="shell">
        <div class="ranking-head reveal">
            <div><span class="kicker">RANKING EN VIVO</span><h2>Los que están marcando el ritmo.</h2></div>
            <a href="<?php echo __BASE_URL__; ?>rankings/">Ver ranking completo <span>↗</span></a>
        </div>

        <div class="ranking-board reveal">
            <div class="ranking-board-head"><span>#</span><span>PERSONAJE</span><span>RESET</span><span>NIVEL</span></div>
            <?php if(is_array($topResetPlayers) && count($topResetPlayers) > 0) { ?>
                <?php foreach($topResetPlayers as $player) { ?>
                    <div class="ranking-row">
                        <span class="rank-position"><?php echo (int)$player['position']; ?></span>
                        <span class="rank-name"><i></i><?php echo htmlspecialchars($player['name']); ?></span>
                        <strong><?php echo number_format((int)$player['resets']); ?></strong>
                        <span><?php echo number_format((int)$player['level']); ?></span>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="ranking-empty">El ranking se está actualizando. Volvé a revisar en unos minutos.</div>
            <?php } ?>
        </div>
    </div>
</section>

<section class="live-section">
    <div class="shell live-shell reveal">
        <div class="live-copy"><span class="kicker light">AHORA MISMO</span><h2>El server está vivo.</h2><p>Estado real, jugadores conectados y accesos directos para volver al juego.</p></div>
        <div class="live-number"><strong data-count="<?php echo (int)$onlinePlayers; ?>"><?php echo number_format($onlinePlayers); ?></strong><span>jugador<?php echo $onlinePlayers === 1 ? '' : 'es'; ?> online</span></div>
        <div class="live-meta">
            <div><small>ESTADO</small><strong><i></i> Online</strong></div>
            <div><small>EXP</small><strong><?php echo htmlspecialchars($serverExp); ?></strong></div>
            <div><small>DROP</small><strong><?php echo htmlspecialchars($serverDrop); ?></strong></div>
        </div>
        <div class="live-actions"><a class="button-shine" href="<?php echo __BASE_URL__; ?>downloads/">Descargar cliente</a><a href="<?php echo __BASE_URL__; ?>rankings/">Ver rankings</a></div>
    </div>
</section>

<section class="news-section">
    <div class="shell">
        <div class="section-heading compact reveal"><span class="kicker">NOVEDADES</span><h2>Qué está pasando en MU PANIC.</h2></div>
        <div class="module-surface home-module reveal"><?php $handler->loadModule($_REQUEST['page'],$_REQUEST['subpage']); ?></div>
    </div>
</section>

<?php } else { ?>

<section class="inner-hero">
    <div class="shell inner-head">
        <div><span class="kicker"><?php echo strtoupper($_REQUEST['page']); ?></span><h1><?php echo mupanicPageTitle($_REQUEST['page'], $_REQUEST['subpage']); ?></h1></div>
        <a href="<?php echo __BASE_URL__; ?>">← Inicio</a>
    </div>
</section>

<section class="inner-content">
    <div class="shell">
        <?php if($_REQUEST['page'] == 'usercp' && $_REQUEST['subpage'] != '') { ?>
            <div class="account-layout">
                <aside class="account-nav"><div class="account-nav-head"><small>TU CUENTA</small><strong>Panel de usuario</strong></div><?php templateBuildUsercp(); ?></aside>
                <div class="module-surface"><?php $handler->loadModule($_REQUEST['page'],$_REQUEST['subpage']); ?></div>
            </div>
        <?php } else { ?>
            <div class="module-surface"><?php $handler->loadModule($_REQUEST['page'],$_REQUEST['subpage']); ?></div>
        <?php } ?>
    </div>
</section>

<?php } ?>
</main>

<footer class="footer-modern">
    <div class="shell footer-main">
        <div>
            <a class="brand" href="<?php echo __BASE_URL__; ?>"><span class="brand-mark">MP</span><span class="brand-copy"><strong>MU PANIC</strong><small>SEASON 6 · ARGENTINA</small></span></a>
            <p>Un MU clásico con una progresión más clara, economía con valor y objetivos que importan.</p>
        </div>
        <div class="footer-links">
            <div><small>JUGAR</small><a href="<?php echo __BASE_URL__; ?>downloads/">Descargas</a><a href="<?php echo __BASE_URL__; ?>information/">Guías</a></div>
            <div><small>CUENTA</small><?php if($isLogged) { ?><a href="<?php echo __BASE_URL__; ?>usercp/">Mi cuenta</a><?php } else { ?><a href="<?php echo __BASE_URL__; ?>register/">Crear cuenta</a><?php } ?><a href="<?php echo __BASE_URL__; ?>rankings/">Rankings</a></div>
        </div>
    </div>
    <div class="shell footer-bottom"><span>© <?php echo date('Y'); ?> MU PANIC</span><span>MU Online y sus marcas pertenecen a sus respectivos titulares.</span></div>
</footer>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js"></script>
<script src="<?php echo __PATH_TEMPLATE_JS__; ?>main.js"></script>
</body>
</html>
