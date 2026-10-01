<?php
if(!defined('access') or !access) die();
include('inc/template.functions.php');

$serverInfoCache = LoadCacheData('server_info.cache');
if(is_array($serverInfoCache)) {
    $srvInfo = explode("|", $serverInfoCache[1][0]);
}
$onlinePlayers = isset($srvInfo[3]) ? (int)$srvInfo[3] : 0;

if(!isset($_REQUEST['page'])) $_REQUEST['page'] = '';
if(!isset($_REQUEST['subpage'])) $_REQUEST['subpage'] = '';

$isHome = ($_REQUEST['page'] === '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title><?php $handler->websiteTitle(); ?></title>
    <meta name="description" content="<?php config('website_meta_description'); ?>"/>
    <meta name="keywords" content="<?php config('website_meta_keywords'); ?>"/>
    <meta property="og:type" content="website"/>
    <meta property="og:title" content="<?php $handler->websiteTitle(); ?>"/>
    <meta property="og:description" content="<?php config('website_meta_description'); ?>"/>
    <meta property="og:url" content="<?php echo __BASE_URL__; ?>"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>style.css" rel="stylesheet">
    <script>var baseUrl = '<?php echo __BASE_URL__; ?>';</script>
</head>
<body class="<?php echo $isHome ? 'is-home' : 'is-inner'; ?>">
<div class="site-bg" aria-hidden="true">
    <div class="orb orb-a"></div>
    <div class="orb orb-b"></div>
    <div class="grid-glow"></div>
</div>

<header class="topbar">
    <div class="wrap nav-wrap">
        <a class="brand" href="<?php echo __BASE_URL__; ?>" aria-label="MU PANIC Inicio">
            <span class="brand-mark">MP</span>
            <span class="brand-copy"><strong>MU PANIC</strong><small>SEASON 6</small></span>
        </a>

        <button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false">
            <span></span><span></span>
        </button>

        <nav class="main-nav" aria-label="Navegación principal">
            <a href="<?php echo __BASE_URL__; ?>">Inicio</a>
            <a href="<?php echo __BASE_URL__; ?>information/">Guías</a>
            <a href="<?php echo __BASE_URL__; ?>rankings/">Rankings</a>
            <a href="<?php echo __BASE_URL__; ?>downloads/">Descargas</a>
        </nav>

        <div class="nav-actions">
            <span class="status-pill"><i></i><?php echo number_format($onlinePlayers); ?> online</span>
            <?php if(isLoggedIn()) { ?>
                <a class="btn-ghost" href="<?php echo __BASE_URL__; ?>usercp/">Mi cuenta</a>
                <a class="btn-primary-ui" href="<?php echo __BASE_URL__; ?>logout/">Salir</a>
            <?php } else { ?>
                <a class="btn-ghost" href="<?php echo __BASE_URL__; ?>login/">Ingresar</a>
                <a class="btn-primary-ui" href="<?php echo __BASE_URL__; ?>register/">Crear cuenta</a>
            <?php } ?>
        </div>
    </div>
</header>

<main>
<?php if($isHome) { ?>
    <section class="hero">
        <div class="wrap hero-grid">
            <div class="hero-copy">
                <div class="eyebrow"><span></span> MU ONLINE · SEASON 6</div>
                <h1>Tu próxima aventura empieza <em>sabiendo qué hacer.</em></h1>
                <p class="hero-lead">Un servidor pensado para progresar con sentido, entender cada sistema y disfrutar cada etapa sin perderte en el camino.</p>
                <div class="hero-actions">
                    <a class="btn-primary-ui btn-lg-ui" href="<?php echo __BASE_URL__; ?>downloads/">Descargar cliente</a>
                    <a class="btn-ghost btn-lg-ui" href="<?php echo __BASE_URL__; ?>information/">Guía para empezar <span>→</span></a>
                </div>
                <div class="hero-metrics">
                    <div><strong><?php echo number_format($onlinePlayers); ?></strong><span>conectados ahora</span></div>
                    <div><strong>Season 6</strong><span>progresión clásica</span></div>
                    <div><strong>Online</strong><span>estado del servidor</span></div>
                </div>
            </div>

            <div class="hero-visual">
                <div class="world-card">
                    <div class="world-top">
                        <span class="live-dot"></span>
                        <span>MU PANIC LIVE</span>
                        <small>Argentina</small>
                    </div>
                    <div class="world-scene">
                        <div class="sigil">MU</div>
                        <div class="scene-copy">
                            <span>CONTINENTE DE MU</span>
                            <strong>Construí tu camino.<br>Dominá tu build.</strong>
                        </div>
                    </div>
                    <div class="world-bottom">
                        <span>Servidor activo</span>
                        <span><?php echo number_format($onlinePlayers); ?> jugador<?php echo $onlinePlayers === 1 ? '' : 'es'; ?> online</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="pathways">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <span class="kicker">EMPEZÁ POR ACÁ</span>
                    <h2>No necesitás saber todo para arrancar.</h2>
                </div>
                <p>Elegí el camino que más se parece a vos y te mostramos qué conviene hacer primero.</p>
            </div>

            <div class="path-grid">
                <a class="path-card" href="<?php echo __BASE_URL__; ?>information/">
                    <span class="path-num">01</span>
                    <h3>Soy nuevo en MU</h3>
                    <p>Clases, mapas, primeras alas, economía y cómo llegar bien preparado al primer reset.</p>
                    <span class="path-link">Empezar de cero →</span>
                </a>
                <a class="path-card featured" href="<?php echo __BASE_URL__; ?>information/">
                    <span class="path-badge">RECOMENDADO</span>
                    <span class="path-num">02</span>
                    <h3>Ya jugué MU antes</h3>
                    <p>Qué cambia en MU PANIC, cómo progresa el equipo y qué sistemas importan en cada etapa.</p>
                    <span class="path-link">Ver diferencias →</span>
                </a>
                <a class="path-card" href="<?php echo __BASE_URL__; ?>rankings/">
                    <span class="path-num">03</span>
                    <h3>Quiero competir</h3>
                    <p>Rankings, resets, endgame, eventos y los objetivos que separan una buena cuenta de una top.</p>
                    <span class="path-link">Ir al ranking →</span>
                </a>
            </div>
        </div>
    </section>

    <section class="server-strip">
        <div class="wrap strip-grid">
            <div class="strip-title">
                <span class="live-dot"></span>
                <div><small>SERVIDOR EN VIVO</small><strong>MU PANIC</strong></div>
            </div>
            <div class="strip-stat"><small>Estado</small><strong class="ok">Online</strong></div>
            <div class="strip-stat"><small>Versión</small><strong>Season 6</strong></div>
            <div class="strip-stat"><small>Jugadores</small><strong><?php echo number_format($onlinePlayers); ?></strong></div>
            <a class="strip-link" href="<?php echo __BASE_URL__; ?>rankings/">Ver rankings →</a>
        </div>
    </section>

    <section class="content-section">
        <div class="wrap">
            <div class="section-head compact">
                <div>
                    <span class="kicker">ACTUALIDAD</span>
                    <h2>Noticias del servidor</h2>
                </div>
            </div>
            <div class="module-card home-module">
                <?php $handler->loadModule($_REQUEST['page'],$_REQUEST['subpage']); ?>
            </div>
        </div>
    </section>
<?php } else { ?>
    <section class="inner-hero">
        <div class="wrap">
            <span class="kicker">MU PANIC</span>
            <h1><?php echo mupanicPageTitle($_REQUEST['page'], $_REQUEST['subpage']); ?></h1>
        </div>
    </section>

    <section class="content-section inner-content">
        <div class="wrap">
            <?php if($_REQUEST['page'] == 'usercp' && $_REQUEST['subpage'] != '') { ?>
                <div class="account-layout">
                    <aside class="account-nav">
                        <div class="account-nav-head">
                            <small>CUENTA</small>
                            <strong>Panel de usuario</strong>
                        </div>
                        <?php templateBuildUsercp(); ?>
                    </aside>
                    <div class="module-card">
                        <?php $handler->loadModule($_REQUEST['page'],$_REQUEST['subpage']); ?>
                    </div>
                </div>
            <?php } else { ?>
                <div class="module-card">
                    <?php $handler->loadModule($_REQUEST['page'],$_REQUEST['subpage']); ?>
                </div>
            <?php } ?>
        </div>
    </section>
<?php } ?>
</main>

<footer class="footer-modern">
    <div class="wrap footer-grid">
        <div>
            <div class="brand footer-brand"><span class="brand-mark">MP</span><span class="brand-copy"><strong>MU PANIC</strong><small>SEASON 6</small></span></div>
            <p>Una experiencia de MU Online con progresión clara, sistemas entendibles y objetivos que valen la pena.</p>
        </div>
        <div class="footer-links">
            <a href="<?php echo __BASE_URL__; ?>information/">Guías</a>
            <a href="<?php echo __BASE_URL__; ?>rankings/">Rankings</a>
            <a href="<?php echo __BASE_URL__; ?>downloads/">Descargas</a>
            <?php if(config('language_switch_active',true)) templateLanguageSelector(); ?>
        </div>
    </div>
    <div class="wrap footer-bottom">
        <span>© <?php echo date('Y'); ?> MU PANIC</span>
        <span>MU Online es propiedad de sus respectivos titulares.</span>
    </div>
</footer>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js"></script>
<script src="<?php echo __PATH_TEMPLATE_JS__; ?>main.js"></script>
</body>
</html>
