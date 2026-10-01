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
    <meta name="theme-color" content="#070707"/>
    <title><?php $handler->websiteTitle(); ?></title>
    <meta name="description" content="<?php config('website_meta_description'); ?>"/>
    <meta name="keywords" content="<?php config('website_meta_keywords'); ?>"/>
    <meta property="og:type" content="website"/>
    <meta property="og:title" content="<?php $handler->websiteTitle(); ?>"/>
    <meta property="og:description" content="<?php config('website_meta_description'); ?>"/>
    <meta property="og:url" content="<?php echo __BASE_URL__; ?>"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>style.css" rel="stylesheet">
    <script>var baseUrl = '<?php echo __BASE_URL__; ?>';</script>
</head>
<body class="<?php echo $isHome ? 'is-home' : 'is-inner'; ?>">

<div class="ambient" aria-hidden="true">
    <div class="ambient-grid"></div>
    <div class="ambient-glow ambient-glow-a"></div>
    <div class="ambient-glow ambient-glow-b"></div>
</div>

<header class="topbar">
    <div class="shell nav-shell">
        <a class="brand" href="<?php echo __BASE_URL__; ?>" aria-label="MU PANIC Inicio">
            <span class="brand-symbol">P!</span>
            <span class="brand-word">
                <small>MU ONLINE</small>
                <strong>MU PANIC</strong>
            </span>
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
            <a class="live-mini" href="<?php echo __BASE_URL__; ?>rankings/" aria-label="Ver rankings">
                <span class="pulse-dot"></span>
                <strong><?php echo number_format($onlinePlayers); ?></strong>
                <span>online</span>
            </a>
            <?php if($isLogged) { ?>
                <a class="nav-link-button" href="<?php echo __BASE_URL__; ?>usercp/">Mi cuenta</a>
                <a class="nav-cta" href="<?php echo __BASE_URL__; ?>logout/">Salir</a>
            <?php } else { ?>
                <a class="nav-link-button" href="<?php echo __BASE_URL__; ?>login/">Ingresar</a>
                <a class="nav-cta" href="<?php echo __BASE_URL__; ?>register/">Crear cuenta</a>
            <?php } ?>
        </div>
    </div>
</header>

<main>
<?php if($isHome) { ?>
    <section class="hero">
        <div class="hero-wordmark" aria-hidden="true">PANIC</div>

        <div class="shell hero-layout">
            <div class="hero-copy reveal">
                <div class="hero-kicker">
                    <span class="pulse-dot"></span>
                    <span>SEASON 6</span>
                    <i></i>
                    <span>ARGENTINA</span>
                </div>

                <h1>Volvé por MU.<br><em>Quedate por el progreso.</em></h1>

                <p class="hero-lead">
                    Un servidor donde cada etapa tiene algo para hacer: levelear, armarte, mover la economía y competir.
                    Sin perderte entre veinte menús antes de empezar a jugar.
                </p>

                <div class="hero-actions">
                    <a class="button button-hot" href="<?php echo __BASE_URL__; ?>downloads/">
                        <span>Descargar cliente</span><b>↗</b>
                    </a>
                    <?php if($isLogged) { ?>
                        <a class="button button-dark" href="<?php echo __BASE_URL__; ?>usercp/">Ir a mi cuenta</a>
                    <?php } else { ?>
                        <a class="button button-dark" href="<?php echo __BASE_URL__; ?>register/">Crear cuenta</a>
                    <?php } ?>
                </div>

                <div class="hero-proof">
                    <div>
                        <span class="proof-value"><?php echo number_format($onlinePlayers); ?></span>
                        <span class="proof-label">jugador<?php echo $onlinePlayers === 1 ? '' : 'es'; ?> conectado<?php echo $onlinePlayers === 1 ? '' : 's'; ?></span>
                    </div>
                    <div>
                        <span class="proof-value">S6</span>
                        <span class="proof-label">base clásica</span>
                    </div>
                    <div>
                        <span class="proof-value proof-live"><i></i>LIVE</span>
                        <span class="proof-label">servidor activo</span>
                    </div>
                </div>
            </div>

            <div class="hero-art reveal" aria-hidden="true">
                <div class="rift">
                    <div class="rift-core"></div>
                    <div class="rift-ring rift-ring-one"></div>
                    <div class="rift-ring rift-ring-two"></div>
                    <div class="rift-slice rift-slice-a"></div>
                    <div class="rift-slice rift-slice-b"></div>
                    <div class="rift-slice rift-slice-c"></div>
                    <div class="rift-title">
                        <span>CONTINENTE DE MU</span>
                        <strong>PANIC</strong>
                    </div>
                </div>

                <div class="floating-chip chip-online">
                    <span class="pulse-dot"></span>
                    <div><small>AHORA</small><strong><?php echo number_format($onlinePlayers); ?> online</strong></div>
                </div>

                <div class="floating-chip chip-objective">
                    <small>PRIMER OBJETIVO</small>
                    <strong>Prepará tu primer reset</strong>
                </div>
            </div>
        </div>

        <div class="shell quick-start reveal">
            <div class="quick-title">
                <small>ARRANCÁ EN 2 MINUTOS</small>
                <strong>Todo lo importante, a mano.</strong>
            </div>
            <a href="<?php echo __BASE_URL__; ?>downloads/"><span>01</span><strong>Descargar</strong><small>Cliente y archivos</small></a>
            <?php if($isLogged) { ?>
                <a href="<?php echo __BASE_URL__; ?>usercp/"><span>02</span><strong>Mi cuenta</strong><small>Gestioná tu personaje</small></a>
            <?php } else { ?>
                <a href="<?php echo __BASE_URL__; ?>register/"><span>02</span><strong>Crear cuenta</strong><small>Entrá al servidor</small></a>
            <?php } ?>
            <a href="<?php echo __BASE_URL__; ?>information/"><span>03</span><strong>Guía rápida</strong><small>Qué hacer primero</small></a>
            <a href="<?php echo __BASE_URL__; ?>rankings/"><span>04</span><strong>Rankings</strong><small>Quién está arriba</small></a>
        </div>
    </section>

    <section class="editorial-section">
        <div class="shell">
            <div class="editorial-head reveal">
                <span class="section-tag">LA IDEA</span>
                <h2>MU clásico, pero con <em>razones para seguir jugando.</em></h2>
                <p>No queremos que la web te venda humo. Queremos que entiendas rápido qué vas a hacer adentro.</p>
            </div>

            <div class="experience-grid">
                <article class="experience-feature reveal">
                    <div class="feature-index">01</div>
                    <div class="feature-copy">
                        <span class="section-tag">PROGRESIÓN</span>
                        <h3>Cada mapa es una etapa, no un trámite.</h3>
                        <p>Leveleo, spots, equipo y resets están pensados para que avanzar se sienta como avanzar, no como esperar un contador.</p>
                    </div>
                    <div class="feature-meter" aria-hidden="true">
                        <span></span><span></span><span></span><span></span><span></span>
                    </div>
                </article>

                <article class="experience-card reveal">
                    <span class="section-tag">ECONOMÍA</span>
                    <h3>Que el Zen vuelva a importar.</h3>
                    <p>Farmear, vender y decidir dónde gastar forma parte del progreso. La economía no está de decoración.</p>
                    <div class="card-foot"><span>ZEN</span><span>DROPS</span><span>TRADE</span></div>
                </article>

                <article class="experience-card experience-card-alt reveal">
                    <span class="section-tag">COMPETENCIA</span>
                    <h3>El endgame tiene nombre propio.</h3>
                    <p>Rankings, resets, Master Reset, eventos y zonas disputadas para que siempre exista un próximo objetivo.</p>
                    <a href="<?php echo __BASE_URL__; ?>rankings/">Ver rankings <b>→</b></a>
                </article>
            </div>
        </div>
    </section>

    <section class="route-section">
        <div class="shell">
            <div class="route-intro reveal">
                <span class="section-tag">TU RUTA</span>
                <h2>Entrar es fácil.<br>Entender qué sigue, también.</h2>
                <p>La web acompaña el juego: empezás, aprendés lo necesario y después elegís cuánto querés competir.</p>
            </div>

            <div class="route-list">
                <a class="route-step reveal" href="<?php echo __BASE_URL__; ?>downloads/">
                    <span class="route-num">01</span>
                    <div><small>ENTRÁ</small><strong>Descargá y conectate</strong></div>
                    <p>Cliente, instalación y solución de problemas comunes.</p>
                    <b>↗</b>
                </a>
                <a class="route-step reveal" href="<?php echo __BASE_URL__; ?>information/">
                    <span class="route-num">02</span>
                    <div><small>CRECÉ</small><strong>Elegí dónde progresar</strong></div>
                    <p>Mapas, spots y prioridades para no avanzar a ciegas.</p>
                    <b>↗</b>
                </a>
                <a class="route-step reveal" href="<?php echo __BASE_URL__; ?>information/">
                    <span class="route-num">03</span>
                    <div><small>ARMATE</small><strong>Equipo, alas y economía</strong></div>
                    <p>Qué vale la pena buscar, guardar, vender y mejorar.</p>
                    <b>↗</b>
                </a>
                <a class="route-step reveal" href="<?php echo __BASE_URL__; ?>rankings/">
                    <span class="route-num">04</span>
                    <div><small>COMPETÍ</small><strong>Medite contra el servidor</strong></div>
                    <p>Rankings y objetivos para cuando ya no alcanza con levelear.</p>
                    <b>↗</b>
                </a>
            </div>
        </div>
    </section>

    <section class="pulse-section">
        <div class="shell pulse-panel reveal">
            <div class="pulse-copy">
                <span class="section-tag">MU PANIC LIVE</span>
                <h2>El servidor, sin vueltas.</h2>
                <p>Estado real y accesos directos. Lo que necesitás antes de abrir el cliente.</p>
            </div>

            <div class="pulse-stats">
                <div class="pulse-stat">
                    <small>ESTADO</small>
                    <strong><i class="pulse-dot"></i> Online</strong>
                </div>
                <div class="pulse-stat">
                    <small>JUGADORES</small>
                    <strong><?php echo number_format($onlinePlayers); ?></strong>
                </div>
                <div class="pulse-stat">
                    <small>VERSIÓN</small>
                    <strong>Season 6</strong>
                </div>
                <div class="pulse-stat">
                    <small>REGIÓN</small>
                    <strong>Argentina</strong>
                </div>
            </div>

            <div class="pulse-actions">
                <a href="<?php echo __BASE_URL__; ?>rankings/">Rankings <span>→</span></a>
                <a href="<?php echo __BASE_URL__; ?>information/">Guías <span>→</span></a>
            </div>
        </div>
    </section>

    <section class="news-section">
        <div class="shell">
            <div class="news-head reveal">
                <div>
                    <span class="section-tag">ACTUALIDAD</span>
                    <h2>Qué está pasando en MU PANIC</h2>
                </div>
                <p>Cambios, anuncios y novedades del servidor.</p>
            </div>

            <div class="module-surface home-module reveal">
                <?php $handler->loadModule($_REQUEST['page'],$_REQUEST['subpage']); ?>
            </div>
        </div>
    </section>

<?php } else { ?>

    <section class="inner-hero">
        <div class="shell inner-hero-grid">
            <div>
                <span class="section-tag">MU PANIC / <?php echo strtoupper($_REQUEST['page']); ?></span>
                <h1><?php echo mupanicPageTitle($_REQUEST['page'], $_REQUEST['subpage']); ?></h1>
            </div>
            <a class="inner-back" href="<?php echo __BASE_URL__; ?>">← Volver al inicio</a>
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
        <div class="footer-branding">
            <a class="brand" href="<?php echo __BASE_URL__; ?>">
                <span class="brand-symbol">P!</span>
                <span class="brand-word"><small>MU ONLINE</small><strong>MU PANIC</strong></span>
            </a>
            <p>Season 6. Progresión con sentido, economía con valor y un camino claro desde el primer login.</p>
        </div>

        <div class="footer-nav">
            <div>
                <small>JUGAR</small>
                <a href="<?php echo __BASE_URL__; ?>downloads/">Descargar</a>
                <?php if(!$isLogged) { ?><a href="<?php echo __BASE_URL__; ?>register/">Crear cuenta</a><?php } ?>
                <a href="<?php echo __BASE_URL__; ?>information/">Guías</a>
            </div>
            <div>
                <small>COMPETIR</small>
                <a href="<?php echo __BASE_URL__; ?>rankings/">Rankings</a>
                <?php if($isLogged) { ?><a href="<?php echo __BASE_URL__; ?>usercp/">Mi cuenta</a><?php } ?>
                <?php if(config('language_switch_active',true)) templateLanguageSelector(); ?>
            </div>
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
