<?php
if(!defined('access') or !access) die();
require_once __DIR__.'/inc/launch-runtime.php';
$launchConfig = require __DIR__.'/inc/launch-config.php';
if(panicLaunchVisible(__BASE_URL__, $_REQUEST['page'] ?? '', $_GET['preview'] ?? '', $launchConfig)) {
    require __DIR__.'/inc/launch-page.php';
    return;
}
include('inc/template.functions.php');

$serverInfoCache = LoadCacheData('server_info.cache');
if(is_array($serverInfoCache) && isset($serverInfoCache[1][0])) {
    $srvInfo = explode("|", $serverInfoCache[1][0]);
}
$onlinePlayers = isset($srvInfo[3]) && is_numeric($srvInfo[3]) ? max(0, (int)$srvInfo[3]) : null;

if(!isset($_REQUEST['page'])) $_REQUEST['page'] = '';
if(!isset($_REQUEST['subpage'])) $_REQUEST['subpage'] = '';

// Legacy WebEngine purchase routes are intentionally disabled in MU PANIC.
// Anyone opening an old/bookmarked URL is sent to the official WCoin C recharge flow.
$legacyPurchaseRoute =
    ($_REQUEST['page'] === 'donation') ||
    ($_REQUEST['page'] === 'usercp' && in_array($_REQUEST['subpage'], ['buyzen','buycredits','donation'], true));

if($legacyPurchaseRoute) {
    $target = isLoggedIn() ? __BASE_URL__.'usercp/recharge/' : __BASE_URL__.'login/';
    header('Location: '.$target, true, 302);
    exit;
}
if($_REQUEST['page'] === 'usercp') {
    require_once(__DIR__.'/inc/account.php');
    $GLOBALS['lang'] = array_replace(is_array($GLOBALS['lang'] ?? null) ? $GLOBALS['lang'] : [], require(__DIR__.'/inc/account-locale.php'));
}

if($_REQUEST['page']==='usercp' && $_REQUEST['subpage']==='shopadmin') {
    header('Cache-Control: private, no-store, max-age=0');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow');
    require_once __DIR__.'/inc/recharge-management.php';
    if(!panicRechargeAdminAllowed()) { http_response_code(403); echo 'Acceso no autorizado.'; exit; }
}

// The scenic live screens expose the current official week through a distinct payload.
if($_REQUEST['page']==='fractura' && ($_GET['fractura_week'] ?? '')==='1') {
    require_once __DIR__.'/inc/fractura-week-data.php';
    header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
    $weekly=panicFracturaWeeklyLivePayload(panicFracturaWeekRead());
    if(!$weekly) { http_response_code(503); echo '{"available":false}'; }
    else echo json_encode($weekly,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    exit;
}

// Public faction totals use the existing CMS SQL connection, before markup.
if($_REQUEST['page']==='fractura' && ($_GET['fractura_data'] ?? '')==='1') {
    require_once __DIR__.'/inc/fractura-data.php';
    header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
    $fractura=panicFracturaRead();
    if(!$fractura) { http_response_code(503); echo '{"available":false}'; }
    else echo json_encode($fractura,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    exit;
}

// Public market data: fixed SELECT queries, no game operations or account data.
if($_REQUEST['page']==='market' && ($_GET['market_data'] ?? '')==='1') {
    require_once __DIR__.'/inc/market-data.php';
    header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
    $market=panicMarketRead();
    if(!$market) { http_response_code(503);echo '{"available":false}'; }
    else echo json_encode($market,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    exit;
}

// Public active listings, isolated from confirmed purchase history.
if($_REQUEST['page']==='market' && ($_GET['market_listings'] ?? '')==='1') {
    require_once __DIR__.'/inc/market-listings-data.php';
    header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
    $listings=panicMarketListingsRead(
        $_GET['listing_page']??1, $_GET['currency']??'all', $_GET['search']??'',
        $_GET['sort']??'recent'
    );
    if(!$listings) {http_response_code(503);echo '{"available":false}';}
    else echo json_encode($listings,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    exit;
}

// Status route runs before markup and only reads the signed-in account's ledger.
if($_REQUEST['page']==='usercp' && $_REQUEST['subpage']==='recharge' && ($_GET['shop_status'] ?? '')==='1') {
    header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
    if(!isLoggedIn()) { http_response_code(401); echo '{"orders":{}}'; exit; }
    require_once __DIR__.'/inc/recharge-orders.php';
    try {
        $store=new PanicRechargeOrders();
        $history=$store->history((string)($_SESSION['username'] ?? ''),max(1,min(500,(int)($_GET['compras'] ?? 1))));
        $status=[]; foreach($history['orders'] as $order) $status[$order['id']]=panicRechargeStatus($order);
        echo json_encode(['orders'=>$status],JSON_THROW_ON_ERROR);
    } catch(Throwable $exception) { http_response_code(503); echo '{"orders":{}}'; }
    exit;
}

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
$community = require(__DIR__.'/inc/community-config.php');
$discordInvite = $community['invite'];

$serverSeason = 'Season 6'; // MU PANIC UP43: editorial identity, independent of legacy CMS title.
// Curated public gameplay snapshot; CMS information defaults are not authoritative.
require_once(__DIR__.'/inc/atlas-runtime.php');
$publicBalance = panicAtlasBalance();
$serverExp = $publicBalance['accounts'][0]['experience'].'x';
$serverMasterExp = $publicBalance['accounts'][0]['master'].'x';
$serverDrop = $publicBalance['accounts'][0]['drop'].'%';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="theme-color" content="#0b1113"/>
    <title><?php echo $isHome ? 'MU PANIC · Season 6 · Argentina' : htmlspecialchars(mupanicPageTitle($_REQUEST['page'], $_REQUEST['subpage'])).' · MU PANIC'; ?></title>
    <meta name="description" content="MU PANIC: guerra Nexo contra Caos, mascotas evolutivas, Mercado Vessara, ocho minijuegos y un cliente renovado. Esto es MU PANIC."/>
    <meta name="keywords" content="MU PANIC, MU Online, Season 6, Argentina, Louis UP43"/>
    <meta property="og:type" content="website"/>
    <meta property="og:title" content="MU PANIC · Season 6"/>
    <meta property="og:description" content="Sistemas propios. Una experiencia distinta. Descubrí MU PANIC."/>
    <meta property="og:url" content="<?php echo __BASE_URL__; ?>"/>
    <?php if($_REQUEST['page']==='fractura') { ?><link rel="preload" as="image" href="<?php echo __PATH_TEMPLATE__; ?>img/fractura/aldren-varkor-v2.webp" fetchpriority="high"><?php } ?>
    <?php if($isHome) { ?><link rel="preload" as="image" href="<?php echo __PATH_TEMPLATE__; ?>img/home/hero-rf-mg-v10.webp" fetchpriority="high"><?php } ?>
    <?php if(!$isHome) { ?><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css"><?php } ?>
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>style.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/style.css'), 0, 12); ?>" rel="stylesheet">
    <?php if(!$isHome) { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>atlas.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/atlas.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <?php if($isHome) { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>home-launch.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/home-launch.css'),0,12); ?>" rel="stylesheet"><?php } ?>
    <?php if($_REQUEST['page'] === 'info') { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>atlas-vessara.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/atlas-vessara.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <?php if($_REQUEST['page'] === 'information') { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>information.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/information.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <?php if($_REQUEST['page'] === 'rankings') { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>rankings.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/rankings.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <?php if($_REQUEST['page'] === 'profile') { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>profiles.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/profiles.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <?php if($_REQUEST['page'] === 'usercp') { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>account.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/account.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <?php if($_REQUEST['page'] === 'usercp' && $_REQUEST['subpage']==='vip') { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>recharge.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/recharge.css'), 0, 12); ?>" rel="stylesheet"><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>vip.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/vip.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <?php if($_REQUEST['page'] === 'usercp' && in_array($_REQUEST['subpage'],['recharge','shopadmin'],true)) { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>recharge.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/recharge.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <script>var baseUrl = '<?php echo __BASE_URL__; ?>';</script>
    <?php if($_REQUEST['page']==='market') { ?><link rel="stylesheet" href="<?php echo __PATH_TEMPLATE_CSS__; ?>market.css?v=<?php echo substr(hash_file('sha256',__DIR__.'/css/market.css'),0,12); ?>"><?php } ?>
    <?php if($_REQUEST['page']==='fractura') { ?><link rel="stylesheet" href="<?php echo __PATH_TEMPLATE_CSS__; ?>fractura.css?v=<?php echo substr(hash_file('sha256',__DIR__.'/css/fractura.css'),0,12); ?>"><?php } ?>
    <style>/* Fractura adds one primary destination. Keep narrow desktop headers legible. */
@media(min-width:901px) and (max-width:1100px){.site-header .brand-crystal .brand-logo{width:120px}.site-header .main-nav .nav-main-link,.site-header .main-nav .nav-discord{font-size:10px;padding-left:4px;padding-right:4px}.site-header .nav-shell{gap:8px}.site-header .nav-actions{gap:8px}.site-header .account-link{font-size:10px}.site-header .nav-cta{font-size:9px;padding-left:8px;padding-right:8px}}
</style>
    <noscript><style>@media(max-width:900px){.site-header{position:static}.nav-shell{height:auto;min-height:74px;flex-wrap:wrap;padding:15px 0}.main-nav{display:flex;flex-wrap:wrap;width:100%;order:3;padding:15px 0 0}.main-nav .mobile-account{display:block}.main-nav a{padding:9px;font-size:12px}.menu-toggle{display:none}.nav-actions{margin-left:auto}}</style></noscript>
<?php if($isHome) { ?><noscript><style>@media(max-width:1100px){.is-home-launch .site-header{position:static}.is-home-launch .nav-shell{height:auto;flex-wrap:wrap;padding-block:14px}.is-home-launch .main-nav{display:flex;position:static;flex-basis:100%;flex-direction:row;flex-wrap:wrap;order:4;max-height:none;padding:12px 0 0;background:none;border:0}.is-home-launch .main-nav a{min-height:38px}.is-home-launch .menu-toggle{display:none}.is-home-launch .ph-hero>.ph-art{margin-top:0}}</style></noscript><?php } ?>
</head>
<body class="<?php echo $isHome ? 'is-home-launch ' : ''; ?><?php echo $_REQUEST['page']==='fractura' ? 'is-fractura ' : ''; ?><?php echo $_REQUEST['page']==='market' ? 'is-market ' : ''; ?><?php echo $isHome ? 'is-home' : 'is-inner'.($_REQUEST['page'] === 'info' ? ' is-wiki' : ($_REQUEST['page'] === 'rankings' ? ' is-rankings' : ($_REQUEST['page'] === 'profile' ? ' is-profile' : ($_REQUEST['page'] === 'usercp' ? ' is-account' : ($_REQUEST['page'] === 'information' ? ' is-server-info' : ''))))); ?>">

<header class="site-header">
    <div class="shell nav-shell">
        <a class="brand brand-crystal" href="<?php echo __BASE_URL__; ?>">
            <img class="brand-logo" src="<?php echo __PATH_TEMPLATE__; ?>img/brand/mu-panic-crystal-bronze-matte-v2.webp" alt="MU PANIC" width="960" height="320" decoding="async">
        </a>

        <button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="main-navigation">
            <span></span><span></span>
        </button>

        <nav class="main-nav" id="main-navigation" aria-label="Navegación principal">
            <a class="nav-main-link<?php echo $isHome ? ' is-current' : ''; ?>" href="<?php echo __BASE_URL__; ?>"<?php echo $isHome ? ' aria-current="page"' : ''; ?>><span>Inicio</span></a>
            <a class="nav-main-link<?php echo $_REQUEST['page'] === 'information' ? ' is-current' : ''; ?>" href="<?php echo __BASE_URL__; ?>information/"<?php echo $_REQUEST['page'] === 'information' ? ' aria-current="page"' : ''; ?>><span>El servidor</span></a>
            <a class="nav-main-link<?php echo $_REQUEST['page']==='market' ? ' is-current' : ''; ?>" href="<?php echo __BASE_URL__; ?>market/"<?php echo $_REQUEST['page']==='market' ? ' aria-current="page"' : ''; ?>><span>Mercado</span></a>
            <a class="nav-main-link<?php echo $_REQUEST['page']==='fractura' ? ' is-current' : ''; ?>" href="<?php echo __BASE_URL__; ?>fractura/"<?php echo $_REQUEST['page']==='fractura' ? ' aria-current="page"' : ''; ?>><span>Fractura</span></a>
            <a class="nav-main-link<?php echo $_REQUEST['page'] === 'info' ? ' is-current' : ''; ?>" href="<?php echo __BASE_URL__; ?>info/"<?php echo $_REQUEST['page'] === 'info' ? ' aria-current="page"' : ''; ?>><span>Atlas</span></a>
            <a class="nav-main-link<?php echo $_REQUEST['page'] === 'rankings' ? ' is-current' : ''; ?>" href="<?php echo __BASE_URL__; ?>rankings/"<?php echo $_REQUEST['page'] === 'rankings' ? ' aria-current="page"' : ''; ?>><span>Rankings</span></a>
            <a class="nav-main-link<?php echo $_REQUEST['page'] === 'downloads' ? ' is-current' : ''; ?>" href="<?php echo __BASE_URL__; ?>downloads/"<?php echo $_REQUEST['page'] === 'downloads' ? ' aria-current="page"' : ''; ?>><span>Descargas</span></a>
            <a class="nav-discord" href="https://discord.com/channels/<?php echo rawurlencode($community['guildId']); ?>" target="_blank" rel="noopener noreferrer"><span>Discord</span><b aria-hidden="true">↗</b></a>
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

<?php include __DIR__.'/inc/home-launch.php'; ?>

<?php } else { ?>

<?php if($_REQUEST['page'] === 'rankings') { include(__DIR__.'/inc/rankings-hero.php'); } elseif($_REQUEST['page'] === 'info') { include(__DIR__.'/inc/atlas-hero.php'); } elseif(!in_array($_REQUEST['page'],['information','market','fractura'],true)) { ?>
<section class="inner-hero">
    <div class="shell inner-head">
        <div><span class="eyebrow"><?php echo $_REQUEST['page'] === 'profile' ? ($_REQUEST['subpage'] === 'guild' ? 'GUILD / MU PANIC' : 'PERSONAJE / MU PANIC') : ($_REQUEST['page'] === 'usercp' ? 'TU CUENTA / MU PANIC' : htmlspecialchars(strtoupper($_REQUEST['page']))); ?></span><h1><?php echo htmlspecialchars(mupanicPageTitle($_REQUEST['page'], $_REQUEST['subpage'])); ?></h1></div>
        <?php if($_REQUEST['page'] === 'profile') { ?><a class="profile-back" href="<?php echo __BASE_URL__; ?>rankings/">← Volver al ranking</a><?php } elseif($_REQUEST['page'] === 'usercp' && $_REQUEST['subpage'] !== '') { ?><a href="<?php echo __BASE_URL__; ?>usercp/">← Mi cuenta</a><?php } else { ?><a href="<?php echo __BASE_URL__; ?>">← Inicio</a><?php } ?>
    </div>
</section>
<?php } ?>

<section class="inner-content">
    <div class="shell">
        <?php if($_REQUEST['page'] == 'usercp' && $isLogged) { ?>
            <div class="account-layout">
                <aside class="account-nav"><div class="account-nav-head"><small>MU PANIC</small><strong>Mi cuenta</strong></div><nav aria-label="Opciones de mi cuenta"><?php templateBuildUsercp(); ?></nav></aside>
                <div class="module-surface"><?php
                    if($_REQUEST['subpage'] === '') { panicAccountHome(); }
                    else {
                        $accountTool=panicAccountTools()[$_REQUEST['subpage']] ?? null;
                        if($accountTool && !in_array($_REQUEST['subpage'],['recharge','shopadmin','vip'],true)) echo '<header class="account-module-head"><span class="account-module-icon">'.panicAccountIcon($accountTool['icon']).'</span><div><h2>'.panicAccountEscape($accountTool['title']).'</h2><p>'.panicAccountEscape($accountTool['copy']).'</p></div></header>';
                        echo '<div class="account-module" data-account-module="'.panicAccountEscape($_REQUEST['subpage']).'">';
                        if($_REQUEST['subpage'] === 'recharge') { include __DIR__.'/inc/recharge.php'; }
                        elseif($_REQUEST['subpage'] === 'vip') { include __DIR__.'/inc/vip.php'; }
                        elseif($_REQUEST['subpage'] === 'shopadmin') { include __DIR__.'/inc/recharge-admin.php'; }
                        else {
                            ob_start(); $handler->loadModule($_REQUEST['page'], $_REQUEST['subpage']);
                            echo panicAccountFormMarkup(ob_get_clean(), $_REQUEST['subpage']);
                        }
                        echo '</div>';
                    }
                ?></div>
            </div>
        <?php } else { ?>
            <div class="module-surface"><?php
                if($_REQUEST['page'] === 'fractura') {
                    include(__DIR__.'/inc/fractura.php');
                } elseif($_REQUEST['page'] === 'market') {
                    include(__DIR__.'/inc/market.php');
                } elseif($_REQUEST['page'] === 'information') {
                    include(__ROOT_DIR__.'modules/information.php');
                } elseif($_REQUEST['page'] === 'info') {
                    include('inc/guide.php');
                } elseif($_REQUEST['page'] === 'rankings') {
                    include(__DIR__.'/inc/rankings.php');
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
            <a class="brand brand-crystal" href="<?php echo __BASE_URL__; ?>"><img class="brand-logo" src="<?php echo __PATH_TEMPLATE__; ?>img/brand/mu-panic-crystal-bronze-matte-v2.webp" alt="MU PANIC" width="960" height="320" decoding="async"></a>
            <p><?php echo $isHome ? 'Sistemas propios. Una experiencia distinta.' : 'Tu historia en el continente de MU.'; ?></p>
        </div>
        <div class="footer-links">
            <div><small>COMUNIDAD</small><a href="<?php echo htmlspecialchars($discordInvite, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Discord ↗</a></div>
            <div><small>JUGAR</small><a href="<?php echo __BASE_URL__; ?>downloads/">Descargas</a><a href="<?php echo __BASE_URL__; ?>info/">Atlas</a></div>
            <div><small>CUENTA</small><?php if($isLogged) { ?><a href="<?php echo __BASE_URL__; ?>usercp/">Mi cuenta</a><?php } else { ?><a href="<?php echo __BASE_URL__; ?>register/">Crear cuenta</a><?php } ?><a href="<?php echo __BASE_URL__; ?>rankings/">Rankings</a></div>
            <div><small>LEGAL</small><a href="<?php echo __BASE_URL__; ?>tos/">Términos y condiciones</a><a href="<?php echo __BASE_URL__; ?>privacy/">Privacidad</a><a href="<?php echo __BASE_URL__; ?>refunds/">Compras y reembolsos</a></div>
        </div>
    </div>
    <div class="shell footer-bottom"><span>© <?php echo date('Y'); ?> MU PANIC</span><span>MU Online y sus marcas pertenecen a sus respectivos titulares.</span></div>
</footer>

<?php if($_REQUEST['page']==='fractura') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>fractura.js?v=<?php echo substr(hash_file('sha256',__DIR__.'/js/fractura.js'),0,12); ?>" defer></script><?php } ?>
<?php if($_REQUEST['page']==='market') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>market.js?v=<?php echo substr(hash_file('sha256',__DIR__.'/js/market.js'),0,12); ?>" defer></script><?php } ?>
<?php if($_REQUEST['page']==='market') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>market-listings.js?v=<?php echo substr(hash_file('sha256',__DIR__.'/js/market-listings.js'),0,12); ?>" defer></script><?php } ?>
<?php if(!$isHome) { ?><script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js"></script><?php } ?>
<script src="<?php echo __PATH_TEMPLATE_JS__; ?>atlas-search.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/js/atlas-search.js'), 0, 12); ?>"></script>
<?php if($_REQUEST['page'] === 'info') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>atlas-drops.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/js/atlas-drops.js'), 0, 12); ?>"></script><?php } ?>
<script src="<?php echo __PATH_TEMPLATE_JS__; ?>community.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/js/community.js'), 0, 12); ?>"></script>
<?php if($_REQUEST['page'] === 'rankings') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>rankings.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/js/rankings.js'), 0, 12); ?>"></script><?php } ?>
<?php if($_REQUEST['page'] === 'profile') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>profiles.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/js/profiles.js'), 0, 12); ?>"></script><?php } ?>
<?php if($_REQUEST['page'] === 'usercp' && $_REQUEST['subpage'] === 'recharge') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>recharge.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/js/recharge.js'), 0, 12); ?>"></script><?php } ?>
<?php if($_REQUEST['page'] === 'usercp') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>account.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/js/account.js'), 0, 12); ?>"></script><?php } ?>
<?php if($isHome) { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>home-launch.js?v=<?php echo substr(hash_file('sha256',__DIR__.'/js/home-launch.js'),0,12); ?>" defer></script><?php } ?>
<script src="<?php echo __PATH_TEMPLATE_JS__; ?>main.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/js/main.js'), 0, 12); ?>"></script>
<?php if($_REQUEST['page'] === 'info') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>atlas-ui.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/js/atlas-ui.js'), 0, 12); ?>"></script><?php } ?>
<?php if($isHome) { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>mu-panic-brasas.js?v=<?php echo substr(hash_file('sha256',__DIR__.'/js/mu-panic-brasas.js'),0,12); ?>" defer></script><?php } ?>
</body>
</html>


