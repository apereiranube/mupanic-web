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
            <span class="brand-copy">
                <strong>MU PANIC</strong>
                <small>SEASON 6 · ARGENTINA</small>
            </span>
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

<section class="hero">
    <div class="hero-left">
        <div class="hero-left-inner reveal">
            <div class="hero-eyebrow"><span>MU ONLINE</span><i></i><span>SEASON 6</span></div>
            <h1>Volvé a sentir<br>que <em>progresar</em><br>vale la pena.</h1>
            <p>MU PANIC combina la base clásica que conocés con una progresión más clara, economía con valor y objetivos que te empujan a seguir.</p>

            <div class="hero-actions">
                <a class="button primary" href="<?php echo __BASE_URL__; ?>downloads/">Jugar ahora <b>↗</b></a>
                <a class="button secondary" href="<?php echo __BASE_URL__; ?>information/">Ver cómo empezar</a>
            </div>

            <div class="hero-facts">
                <div><strong><?php echo number_format($onlinePlayers); ?></strong><span>online ahora</span></div>
                <div><strong>S6</strong><span>progresión clásica</span></div>
                <div><strong>AR</strong><span>servidor en Argentina</span></div>
            </div>
        </div>
    </div>

    <div class="hero-visual">
        <div class="hero-art"></div>
        <div class="hero-art-shade"></div>

        <div class="hero-status-card reveal">
            <div class="status-head">
                <div>
                    <small>SERVIDOR EN VIVO</small>
                    <strong>MU PANIC</strong>
                </div>
                <span class="status-live"><i></i> ONLINE</span>
            </div>
            <div class="status-grid">
                <div><small>JUGADORES</small><strong><?php echo number_format($onlinePlayers); ?></strong></div>
                <div><small>VERSIÓN</small><strong>Season 6</strong></div>
            </div>
            <a href="<?php echo __BASE_URL__; ?>rankings/">Ver rankings <span>→</span></a>
        </div>

        <div class="hero-caption">LORENCIA · CONTINENTE DE MU</div>
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
            <a class="start-card reveal" href="<?php echo __BASE_URL__; ?>downloads/">
                <span class="step">01</span>
                <div><small>PRIMERO</small><strong>Descargá el cliente</strong><p>Todo lo necesario para instalar y entrar.</p></div>
                <b>↗</b>
            </a>
            <?php if($isLogged) { ?>
                <a class="start-card reveal" href="<?php echo __BASE_URL__; ?>usercp/">
                    <span class="step">02</span>
                    <div><small>CUENTA</small><strong>Gestioná tu personaje</strong><p>Panel de usuario, resets y opciones de cuenta.</p></div>
                    <b>↗</b>
                </a>
            <?php } else { ?>
                <a class="start-card reveal" href="<?php echo __BASE_URL__; ?>register/">
                    <span class="step">02</span>
                    <div><small>CUENTA</small><strong>Creá tu usuario</strong><p>Registrate y dejá lista tu cuenta para jugar.</p></div>
                    <b>↗</b>
                </a>
            <?php } ?>
            <a class="start-card reveal" href="<?php echo __BASE_URL__; ?>information/">
                <span class="step">03</span>
                <div><small>GUÍAS</small><strong>Entendé qué hacer</strong><p>Mapas, progresión, sistemas y prioridades.</p></div>
                <b>↗</b>
            </a>
            <a class="start-card reveal" href="<?php echo __BASE_URL__; ?>rankings/">
                <span class="step">04</span>
                <div><small>COMPETENCIA</small><strong>Mirá quién está arriba</strong><p>Rankings y referencia del progreso real.</p></div>
                <b>↗</b>
            </a>
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
                <article class="why-card why-card-bright reveal">
                    <span>01</span>
                    <small>PROGRESIÓN</small>
                    <h3>Mapas con propósito.</h3>
                    <p>No saltás contenido porque sí. Cada zona acompaña una etapa de crecimiento.</p>
                </article>
                <article class="why-card reveal">
                    <span>02</span>
                    <small>ECONOMÍA</small>
                    <h3>El Zen importa.</h3>
                    <p>Farmear y vender vuelve a tener peso real dentro de tus decisiones.</p>
                </article>
                <article class="why-card reveal">
                    <span>03</span>
                    <small>ENDGAME</small>
                    <h3>Siempre hay otro objetivo.</h3>
                    <p>Reset, Master Reset, eventos, rankings y zonas para competir.</p>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="journey-section">
    <div class="shell">
        <div class="section-heading compact reveal">
            <span class="kicker">TU CAMINO</span>
            <h2>De tu primer login al endgame.</h2>
        </div>

        <div class="journey-grid">
            <div class="journey-map reveal">
                <div class="journey-map-art"></div>
                <div class="journey-map-overlay">
                    <span>ETAPA 01</span>
                    <strong>Aprendé el ritmo del server.</strong>
                    <p>Entrá, elegí tu clase y empezá a conocer dónde vale la pena levelear.</p>
                </div>
            </div>

            <div class="journey-list">
                <div class="journey-item reveal">
                    <span>02</span>
                    <div><small>CRECIMIENTO</small><strong>Buscá equipo, Zen y mejores spots.</strong></div>
                </div>
                <div class="journey-item reveal">
                    <span>03</span>
                    <div><small>RESET</small><strong>Convertí progreso en una cuenta más fuerte.</strong></div>
                </div>
                <div class="journey-item reveal">
                    <span>04</span>
                    <div><small>MASTER RESET</small><strong>Entrá en la parte seria de la competencia.</strong></div>
                </div>
                <div class="journey-item reveal">
                    <span>05</span>
                    <div><small>ENDGAME</small><strong>Rankings, eventos y objetivos disputados.</strong></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="live-section">
    <div class="shell live-shell reveal">
        <div class="live-copy">
            <span class="kicker light">AHORA MISMO</span>
            <h2>El server está vivo.</h2>
            <p>Estado real, jugadores conectados y accesos directos para volver al juego.</p>
        </div>

        <div class="live-number">
            <strong><?php echo number_format($onlinePlayers); ?></strong>
            <span>jugador<?php echo $onlinePlayers === 1 ? '' : 'es'; ?> online</span>
        </div>

        <div class="live-meta">
            <div><small>ESTADO</small><strong><i></i> Online</strong></div>
            <div><small>VERSIÓN</small><strong>Season 6</strong></div>
            <div><small>REGIÓN</small><strong>Argentina</strong></div>
        </div>

        <div class="live-actions">
            <a href="<?php echo __BASE_URL__; ?>downloads/">Descargar cliente</a>
            <a href="<?php echo __BASE_URL__; ?>rankings/">Ver rankings</a>
        </div>
    </div>
</section>

<section class="news-section">
    <div class="shell">
        <div class="section-heading compact reveal">
            <span class="kicker">NOVEDADES</span>
            <h2>Qué está pasando en MU PANIC.</h2>
        </div>
        <div class="module-surface home-module reveal">
            <?php $handler->loadModule($_REQUEST['page'],$_REQUEST['subpage']); ?>
        </div>
    </div>
</section>

<?php } else { ?>

<section class="inner-hero">
    <div class="shell inner-head">
        <div>
            <span class="kicker"><?php echo strtoupper($_REQUEST['page']); ?></span>
            <h1><?php echo mupanicPageTitle($_REQUEST['page'], $_REQUEST['subpage']); ?></h1>
        </div>
        <a href="<?php echo __BASE_URL__; ?>">← Inicio</a>
    </div>
</section>

<section class="inner-content">
    <div class="shell">
        <?php if($_REQUEST['page'] == 'usercp' && $_REQUEST['subpage'] != '') { ?>
            <div class="account-layout">
                <aside class="account-nav">
                    <div class="account-nav-head">
                        <small>TU CUENTA</small>
                        <strong>Panel de usuario</strong>
                    </div>
                    <?php templateBuildUsercp(); ?>
                </aside>
                <div class="module-surface">
                    <?php $handler->loadModule($_REQUEST['page'],$_REQUEST['subpage']); ?>
                </div>
            </div>
        <?php } else { ?>
            <div class="module-surface">
                <?php $handler->loadModule($_REQUEST['page'],$_REQUEST['subpage']); ?>
            </div>
        <?php } ?>
    </div>
</section>

<?php } ?>
</main>

<footer class="footer-modern">
    <div class="shell footer-main">
        <div>
            <a class="brand" href="<?php echo __BASE_URL__; ?>">
                <span class="brand-mark">MP</span>
                <span class="brand-copy"><strong>MU PANIC</strong><small>SEASON 6 · ARGENTINA</small></span>
            </a>
            <p>Un MU clásico con una progresión más clara, economía con valor y objetivos que importan.</p>
        </div>
        <div class="footer-links">
            <div><small>JUGAR</small><a href="<?php echo __BASE_URL__; ?>downloads/">Descargas</a><a href="<?php echo __BASE_URL__; ?>information/">Guías</a></div>
            <div><small>CUENTA</small><?php if($isLogged) { ?><a href="<?php echo __BASE_URL__; ?>usercp/">Mi cuenta</a><?php } else { ?><a href="<?php echo __BASE_URL__; ?>register/">Crear cuenta</a><?php } ?><a href="<?php echo __BASE_URL__; ?>rankings/">Rankings</a></div>
        </div>
    </div>
    <div class="shell footer-bottom">
        <span>© <?php echo date('Y'); ?> MU PANIC</span>
        <span>MU Online y sus marcas pertenecen a sus respectivos titulares.</span>
    </div>
</footer>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js"></script>
<script src="<?php echo __PATH_TEMPLATE_JS__; ?>main.js"></script>
</body>
</html>
