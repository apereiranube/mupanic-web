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

// The legacy WebEngine vote module is replaced by the verified Votar page.
if($_REQUEST['page'] === 'usercp' && $_REQUEST['subpage'] === 'vote') {
    header('Location: '.__BASE_URL__.'votar/', true, 302);
    exit;
}
if($legacyPurchaseRoute) {
    $target = isLoggedIn() ? __BASE_URL__.'usercp/recharge/' : __BASE_URL__.'login/';
    header('Location: '.$target, true, 302);
    exit;
}
// Referidos: el link de invitacion (?ref=CODIGO) se recuerda hasta que el invitado lo confirma en Mi cuenta.
if(in_array($_REQUEST['page'],['register','usercp'],true) && is_string($_GET['ref'] ?? null) && preg_match('/^[A-Za-z0-9]{8}$/D',$_GET['ref'])) {
    require_once __DIR__.'/inc/referidos-cola.php';
    panicReferidosGuardarCodigo($_GET['ref']);
}
if(in_array($_REQUEST['page'],['register','login','forgotpassword','verifyemail'],true)) {
    $GLOBALS['lang'] = array_replace(is_array($GLOBALS['lang'] ?? null) ? $GLOBALS['lang'] : [], require(__DIR__.'/inc/account-locale.php'), require(__DIR__.'/inc/public-locale.php'));
}
if($_REQUEST['page'] === 'register') {
    require_once __DIR__.'/inc/referidos-cola.php';
    panicReferidosRegistroPost();
}
if($_REQUEST['page'] === 'usercp' && isLoggedIn() && isset($_SESSION['ref_reg'])) {
    require_once __DIR__.'/inc/referidos-cola.php';
    panicReferidosAutoVincular((string)($_SESSION['username'] ?? ''));
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
        require_once __DIR__.'/inc/recharge-bank.php';
        $bankHistory=(new PanicBankTransfers())->history((string)$_SESSION['username'],max(1,min(500,(int)($_GET['transferencias'] ?? 1))));
        foreach($bankHistory['orders'] as $o) $status[$o['id']]=panicBankStatus($o);
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
    <?php list($seoTitle, $seoDescription, $seoRobots) = mupanicPageSeo($_REQUEST['page'] ?? '', $_REQUEST['subpage'] ?? '', $isHome); ?>
    <title><?php echo htmlspecialchars($seoTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seoDescription); ?>"/>
    <meta name="robots" content="<?php echo $seoRobots; ?>"/>
    <?php if($isHome) { ?><script type="application/ld+json"><?php echo mupanicStructuredData(); ?></script><?php } ?>
    <meta name="keywords" content="MU PANIC, MU Online, MU Online Argentina, servidor de MU, Season 6, Argentina"/>
    <link rel="canonical" href="<?php echo htmlspecialchars('https://mupanic.com.ar'.(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/')); ?>"/>
    <meta property="og:type" content="website"/>
    <?php $refPreviewCode=($_REQUEST['page']==='register' && is_string($_GET['ref'] ?? null) && preg_match('/^[A-Za-z0-9]{8}$/D',$_GET['ref'])) ? strtoupper($_GET['ref']) : null; ?>
    <?php if($refPreviewCode) { $refPreviewImg=__PATH_TEMPLATE__.'img/referidos/referidos-preview-v2.jpg'; ?>
    <meta property="og:site_name" content="MU PANIC"/>
    <meta property="og:title" content="Te invitaron a MU PANIC"/>
    <meta property="og:description" content="Creá tu cuenta con este link y arrancá con 3 días de VIP. MU Online Season 6 · Argentina."/>
    <meta property="og:url" content="<?php echo htmlspecialchars(__BASE_URL__.'register/?ref='.$refPreviewCode); ?>"/>
    <meta property="og:image" content="<?php echo htmlspecialchars($refPreviewImg); ?>"/>
    <meta property="og:image:secure_url" content="<?php echo htmlspecialchars($refPreviewImg); ?>"/>
    <meta property="og:image:type" content="image/jpeg"/>
    <meta property="og:image:width" content="1200"/>
    <meta property="og:image:height" content="630"/>
    <meta property="og:image:alt" content="MU PANIC: te invitaron a jugar y arrancás con 3 días de VIP"/>
    <meta name="twitter:card" content="summary_large_image"/>
    <meta name="twitter:image" content="<?php echo htmlspecialchars($refPreviewImg); ?>"/>
    <?php } else { ?>
    <?php $sharePreviewImg='https://mupanic.com.ar/templates/mupanic/img/share/mupanic-share-v1.jpg'; ?>
    <meta property="og:site_name" content="MU PANIC"/>
    <meta property="og:locale" content="es_AR"/>
    <meta property="og:title" content="<?php echo htmlspecialchars($isHome ? 'MU PANIC · MU Online Season 6 Argentina' : $seoTitle); ?>"/>
    <meta property="og:description" content="<?php echo htmlspecialchars($seoDescription); ?>"/>
    <meta property="og:url" content="<?php echo htmlspecialchars('https://mupanic.com.ar'.(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/')); ?>"/>
    <meta property="og:image" content="<?php echo $sharePreviewImg; ?>"/>
    <meta property="og:image:secure_url" content="<?php echo $sharePreviewImg; ?>"/>
    <meta property="og:image:type" content="image/jpeg"/>
    <meta property="og:image:width" content="1200"/>
    <meta property="og:image:height" content="630"/>
    <meta property="og:image:alt" content="MU PANIC, MU Online Season 6 argentino"/>
    <meta name="twitter:card" content="summary_large_image"/>
    <meta name="twitter:image" content="<?php echo $sharePreviewImg; ?>"/>
    <?php } ?>
    <?php if($isHome) { ?><link rel="preload" as="image" href="<?php echo __PATH_TEMPLATE__; ?>img/home/hero-rf-mg-v10.webp" fetchpriority="high"><?php } ?>
    <?php $isAuthPage = in_array($_REQUEST['page'],['register','login','forgotpassword','verifyemail'],true); ?>
    <?php if(!$isHome && !$isAuthPage && !in_array($_REQUEST['page'],['information','market','fractura','downloads','socios','votar'],true)) { ?><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css"><?php } ?>
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>style.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/style.css'), 0, 12); ?>" rel="stylesheet">
    <?php if(!$isHome) { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>atlas.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/atlas.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>panic-ui.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/panic-ui.css'),0,12); ?>" rel="stylesheet">
    <?php if($isHome) { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>home-v2.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/home-v2.css'),0,12); ?>" rel="stylesheet"><?php } ?>
    <?php if($_REQUEST['page'] === 'info') { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>atlas-vessara.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/atlas-vessara.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <?php if($_REQUEST['page'] === 'information') { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>server-v2.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/server-v2.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <?php if($_REQUEST['page'] === 'downloads') { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>downloads.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/downloads.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <?php if($isAuthPage) { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>auth-v2.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/auth-v2.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <?php if($_REQUEST['page'] === 'rankings') { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>rankings.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/rankings.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <?php if($_REQUEST['page'] === 'profile') { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>profiles.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/profiles.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <?php if($_REQUEST['page'] === 'usercp') { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>account.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/account.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <?php if($_REQUEST['page'] === 'usercp' && $_REQUEST['subpage']==='vip') { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>vip.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/vip.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <?php if($_REQUEST['page'] === 'usercp' && $_REQUEST['subpage']==='recharge') { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>tienda.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/tienda.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <?php if($_REQUEST['page'] === 'usercp' && $_REQUEST['subpage']==='shopadmin') { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>recharge.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/recharge.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <?php if($_REQUEST['page'] === 'usercp' && $_REQUEST['subpage']==='shopadmin') { ?><link href="<?php echo __PATH_TEMPLATE_CSS__; ?>recharge-admin-friendly.css?v=<?php echo substr(hash_file('sha256', __DIR__.'/css/recharge-admin-friendly.css'), 0, 12); ?>" rel="stylesheet"><?php } ?>
    <!-- Google tag (gtag.js) - Google Ads MU PANIC -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-18541212818"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','AW-18541212818');</script>
    <script>var baseUrl = '<?php echo __BASE_URL__; ?>';</script>
    <?php if($_REQUEST['page']==='market') { ?><link rel="stylesheet" href="<?php echo __PATH_TEMPLATE_CSS__; ?>market-v2.css?v=<?php echo substr(hash_file('sha256',__DIR__.'/css/market-v2.css'),0,12); ?>"><?php } ?>
    <?php if($_REQUEST['page']==='socios') { ?><link rel="stylesheet" href="<?php echo __PATH_TEMPLATE_CSS__; ?>socios.css?v=<?php echo substr(hash_file('sha256',__DIR__.'/css/socios.css'),0,12); ?>"><?php } ?>
    <?php if($_REQUEST['page']==='votar') { ?><link rel="stylesheet" href="<?php echo __PATH_TEMPLATE_CSS__; ?>votar.css?v=<?php echo substr(hash_file('sha256',__DIR__.'/css/votar.css'),0,12); ?>"><?php } ?>
    <?php if($_REQUEST['page']==='fractura') { ?><link rel="stylesheet" href="<?php echo __PATH_TEMPLATE_CSS__; ?>fractura-v2.css?v=<?php echo substr(hash_file('sha256',__DIR__.'/css/fractura-v2.css'),0,12); ?>"><?php } ?>
    <noscript><style>@media(max-width:1100px){.site-header{position:relative;height:auto}.site-header .nav-shell{height:auto;min-height:var(--site-header-height);flex-wrap:wrap;padding-block:12px}.site-header .main-nav{display:flex;position:static;flex-basis:100%;width:100%;order:3;max-height:none;flex-direction:row;flex-wrap:wrap;padding:8px 0 0;border:0;background:none}.site-header .main-nav a{min-height:38px;padding:8px}.site-header .main-nav .mobile-account{display:flex}.site-header .menu-toggle{display:none}.site-header .nav-actions{margin-left:auto}}</style></noscript>
</head>
<body class="<?php echo $isHome ? 'is-home-launch ' : ''; ?><?php echo $_REQUEST['page']==='fractura' ? 'is-fractura ' : ''; ?><?php echo $_REQUEST['page']==='market' ? 'is-market ' : ''; ?><?php echo $_REQUEST['page']==='socios' ? 'is-socios ' : ''; ?><?php echo $_REQUEST['page']==='votar' ? 'is-votar ' : ''; ?><?php echo $isAuthPage ? 'is-auth ' : ''; ?><?php echo $isHome ? 'is-home' : 'is-inner'.($_REQUEST['page'] === 'info' ? ' is-wiki' : ($_REQUEST['page'] === 'rankings' ? ' is-rankings' : ($_REQUEST['page'] === 'profile' ? ' is-profile' : ($_REQUEST['page'] === 'usercp' ? ' is-account' : ($_REQUEST['page'] === 'information' ? ' is-server-info' : ($_REQUEST['page'] === 'downloads' ? ' is-downloads' : '')))))); ?>">

<header class="site-header">
    <div class="shell nav-shell">
        <a class="brand brand-crystal" href="<?php echo __BASE_URL__; ?>">
            <img class="brand-mark" src="<?php echo __PATH_TEMPLATE__; ?>img/brand/mp-gema.svg" alt="" width="56" height="52" decoding="async"><span class="brand-word">MU PANIC</span>
        </a>

        <button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="main-navigation">
            <span></span><span></span>
        </button>

        <nav class="main-nav" id="main-navigation" aria-label="Navegación principal">
            <a class="nav-main-link nav-p-inicio<?php echo $isHome ? ' is-current' : ''; ?>" href="<?php echo __BASE_URL__; ?>"<?php echo $isHome ? ' aria-current="page"' : ''; ?>><span>Inicio</span></a>
            <a class="nav-main-link nav-p-servidor<?php echo $_REQUEST['page'] === 'information' ? ' is-current' : ''; ?>" href="<?php echo __BASE_URL__; ?>information/"<?php echo $_REQUEST['page'] === 'information' ? ' aria-current="page"' : ''; ?>><span>El servidor</span></a>
            <a class="nav-main-link nav-p-mercado<?php echo $_REQUEST['page']==='market' ? ' is-current' : ''; ?>" href="<?php echo __BASE_URL__; ?>market/"<?php echo $_REQUEST['page']==='market' ? ' aria-current="page"' : ''; ?>><span>Mercado</span></a>
            <a class="nav-main-link nav-p-fractura<?php echo $_REQUEST['page']==='fractura' ? ' is-current' : ''; ?>" href="<?php echo __BASE_URL__; ?>fractura/"<?php echo $_REQUEST['page']==='fractura' ? ' aria-current="page"' : ''; ?>><span>Fractura</span></a>
            <a class="nav-main-link nav-p-atlas<?php echo $_REQUEST['page'] === 'info' ? ' is-current' : ''; ?>" href="<?php echo __BASE_URL__; ?>info/"<?php echo $_REQUEST['page'] === 'info' ? ' aria-current="page"' : ''; ?>><span>Atlas</span></a>
            <a class="nav-main-link nav-p-rankings<?php echo $_REQUEST['page'] === 'rankings' ? ' is-current' : ''; ?>" href="<?php echo __BASE_URL__; ?>rankings/"<?php echo $_REQUEST['page'] === 'rankings' ? ' aria-current="page"' : ''; ?>><span>Rankings</span></a>
            <a class="nav-main-link nav-p-descargas<?php echo $_REQUEST['page'] === 'downloads' ? ' is-current' : ''; ?>" href="<?php echo __BASE_URL__; ?>downloads/"<?php echo $_REQUEST['page'] === 'downloads' ? ' aria-current="page"' : ''; ?>><span>Descargas</span></a>
            <a class="nav-discord" href="https://discord.com/channels/<?php echo rawurlencode($community['guildId']); ?>" target="_blank" rel="noopener noreferrer"><span>Discord</span><b aria-hidden="true">↗︎</b></a>
            <a class="nav-main-link nav-p-socios nav-socios<?php echo $_REQUEST['page']==='socios' ? ' is-current' : ''; ?>" href="<?php echo __BASE_URL__; ?>socios/"<?php echo $_REQUEST['page']==='socios' ? ' aria-current="page"' : ''; ?>><i aria-hidden="true"></i><span>Socios</span></a>
            <a class="nav-main-link nav-p-votar nav-votar<?php echo $_REQUEST['page']==='votar' ? ' is-current' : ''; ?>" href="<?php echo __BASE_URL__; ?>votar/"<?php echo $_REQUEST['page']==='votar' ? ' aria-current="page"' : ''; ?>><i aria-hidden="true"></i><span>Votar</span></a>
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

<?php include __DIR__.'/inc/home-v2.php'; ?>

<?php } else { ?>

<?php if($_REQUEST['page'] === 'rankings') { include(__DIR__.'/inc/rankings-hero.php'); } elseif($_REQUEST['page'] === 'info') { include(__DIR__.'/inc/atlas-hero.php'); } elseif(!$isAuthPage && !in_array($_REQUEST['page'],['information','market','fractura','downloads','socios','votar'],true) && !($_REQUEST['page'] === 'usercp' && $isLogged)) { ?>
<section class="inner-hero">
    <div class="shell inner-head">
        <div><span class="eyebrow"><?php echo $_REQUEST['page'] === 'profile' ? ($_REQUEST['subpage'] === 'guild' ? 'GUILD / MU PANIC' : 'PERSONAJE / MU PANIC') : (in_array($_REQUEST['page'],['usercp','register','login','forgotpassword','verifyemail'],true) ? 'TU CUENTA / MU PANIC' : htmlspecialchars(strtoupper($_REQUEST['page']))); ?></span><h1><?php echo htmlspecialchars(mupanicPageTitle($_REQUEST['page'], $_REQUEST['subpage'])); ?></h1></div>
        <?php if($_REQUEST['page'] === 'profile') { ?><a class="profile-back" href="<?php echo __BASE_URL__; ?>rankings/">← Volver al ranking</a><?php } elseif($_REQUEST['page'] === 'usercp' && $_REQUEST['subpage'] !== '') { ?><a href="<?php echo __BASE_URL__; ?>usercp/">← Mi cuenta</a><?php } else { ?><a href="<?php echo __BASE_URL__; ?>">← Inicio</a><?php } ?>
    </div>
</section>
<?php } ?>

<section class="inner-content">
    <div class="shell">
        <?php if($_REQUEST['page'] == 'usercp' && $isLogged) { ?>
            <?php require_once __DIR__.'/inc/tienda-art.php'; $ctaSub=(string)$_REQUEST['subpage']; $ctaBand=panicTiendaArt('tienda-fondo-banda'); ?>
            <div class="account-layout cta<?php echo $ctaSub==='shopadmin' ? ' cta--admin' : ' pui'; ?> cta-p-<?php echo $ctaSub==='' ? 'home' : (preg_match('/^[a-z]{1,20}$/D',$ctaSub) ? $ctaSub : 'tool'); ?><?php echo $ctaBand ? ' has-banda' : ''; ?>"<?php if($ctaBand) { ?> style="--cta-banda:url('<?php echo panicAccountEscape($ctaBand); ?>')"<?php } ?>>
                <aside class="account-nav cta-nav"><div class="account-nav-head cta-nav-head"><small>MU PANIC</small><strong>Mi cuenta</strong><span class="cta-nav-user"><?php echo panicAccountEscape((string)($_SESSION['username'] ?? '')); ?></span></div><nav aria-label="Opciones de mi cuenta"><?php templateBuildUsercp(); ?></nav></aside>
                <div class="module-surface cta-main"><?php
                    if($_REQUEST['subpage'] === '') { panicAccountHome(); }
                    else {
                        $accountTool=panicAccountTools()[$_REQUEST['subpage']] ?? null;
                        $accountPage=in_array($_REQUEST['subpage'],['recharge','shopadmin','vip','referidos'],true);
                        if($accountTool && !$accountPage) echo '<header class="account-module-head cta-module-head"><span class="account-module-icon">'.panicAccountIcon($accountTool['icon'],$accountTool['title']).'</span><div><p class="cta-kicker">'.panicAccountEscape($accountTool['group']).'</p><h2>'.panicAccountEscape($accountTool['title']).'</h2><p>'.panicAccountEscape($accountTool['copy']).'</p></div></header>';
                        echo '<div class="account-module'.($accountPage ? '' : ' cta-card cta-tool').'" data-account-module="'.panicAccountEscape($_REQUEST['subpage']).'">';
                        if($_REQUEST['subpage'] === 'recharge') { include __DIR__.'/inc/recharge.php'; }
                        elseif($_REQUEST['subpage'] === 'vip') { include __DIR__.'/inc/vip.php'; }
                        elseif($_REQUEST['subpage'] === 'referidos') { include __DIR__.'/inc/referidos.php'; }
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
                if($isAuthPage) { require_once __DIR__.'/inc/auth-v2.php'; panicAuthOpen($_REQUEST['page']); ob_start('panicAuthMarkup'); }
                if($_REQUEST['page'] === 'fractura') {
                    include(__DIR__.'/inc/fractura-v2.php');
                } elseif($_REQUEST['page'] === 'socios') {
                    include(__DIR__.'/inc/socios.php');
                } elseif($_REQUEST['page'] === 'votar') {
                    include(__DIR__.'/inc/votar.php');
                } elseif($_REQUEST['page'] === 'market') {
                    include(__DIR__.'/inc/market-v2.php');
                } elseif($_REQUEST['page'] === 'information') {
                    include(__DIR__.'/inc/server-v2.php');
                } elseif($_REQUEST['page'] === 'info') {
                    include('inc/guide.php');
                } elseif($_REQUEST['page'] === 'rankings') {
                    include(__DIR__.'/inc/rankings.php');
                } elseif($_REQUEST['page'] === 'downloads') {
                    include(__DIR__.'/inc/downloads.php');
                } elseif($_REQUEST['page'] === 'register') {
                    ob_start();
                    $handler->loadModule($_REQUEST['page'], $_REQUEST['subpage']);
                    $registerMarkup = ob_get_clean();
                    echo panicReferidosCampoRegistro($registerMarkup);
                    // Google Ads: conversion "Cuenta creada" only after a successful sign-up POST
                    if(isset($_POST['webengineRegister_submit']) && strpos($registerMarkup, 'alert-success') !== false) {
                        echo "<script>gtag('event','conversion',{'send_to':'AW-18541212818/uTqaCOjRjawdEJLpkYlF'});</script>";
                    }
                } else {
                    $handler->loadModule($_REQUEST['page'], $_REQUEST['subpage']);
                }
                if($isAuthPage) { ob_end_flush(); panicAuthClose($_REQUEST['page']); }
                ?></div>
        <?php } ?>
    </div>
</section>

<?php } ?>
</main>

<footer class="footer-modern">
    <div class="shell footer-main">
        <div>
            <a class="brand brand-crystal" href="<?php echo __BASE_URL__; ?>"><img class="brand-mark" src="<?php echo __PATH_TEMPLATE__; ?>img/brand/mp-gema.svg" alt="" width="56" height="52" decoding="async"><span class="brand-word">MU PANIC</span></a>
            <p><?php echo $isHome ? 'Sistemas propios. Una experiencia distinta.' : 'Tu historia en el continente de MU.'; ?></p>
        </div>
        <div class="footer-links">
            <div><small>COMUNIDAD</small><a href="<?php echo htmlspecialchars($discordInvite, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Discord ↗︎</a></div>
            <div><small>JUGAR</small><a href="<?php echo __BASE_URL__; ?>downloads/">Descargas</a><a href="<?php echo __BASE_URL__; ?>info/">Atlas</a></div>
            <div><small>CUENTA</small><?php if($isLogged) { ?><a href="<?php echo __BASE_URL__; ?>usercp/">Mi cuenta</a><?php } else { ?><a href="<?php echo __BASE_URL__; ?>register/">Crear cuenta</a><?php } ?><a href="<?php echo __BASE_URL__; ?>rankings/">Rankings</a></div>
            <div><small>LEGAL</small><a href="<?php echo __BASE_URL__; ?>tos/">Términos y condiciones</a><a href="<?php echo __BASE_URL__; ?>privacy/">Privacidad</a><a href="<?php echo __BASE_URL__; ?>refunds/">Compras y reembolsos</a></div>
        </div>
    </div>
    <div class="shell footer-bottom"><span>© <?php echo date('Y'); ?> MU PANIC</span><span>MU Online y sus marcas pertenecen a sus respectivos titulares.</span></div>
</footer>

<?php if($_REQUEST['page']==='fractura') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>fractura-v2.js?v=<?php echo substr(hash_file('sha256',__DIR__.'/js/fractura-v2.js'),0,12); ?>" defer></script><?php } ?>
<?php if($_REQUEST['page']==='socios') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>socios.js?v=<?php echo substr(hash_file('sha256',__DIR__.'/js/socios.js'),0,12); ?>" defer></script><?php } ?>
<?php if($_REQUEST['page']==='information') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>server-v2.js?v=<?php echo substr(hash_file('sha256',__DIR__.'/js/server-v2.js'),0,12); ?>" defer></script><?php } ?>
<?php if($_REQUEST['page']==='market') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>market-v2.js?v=<?php echo substr(hash_file('sha256',__DIR__.'/js/market-v2.js'),0,12); ?>" defer></script><?php } ?>
<?php if(!$isHome) { ?><script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js"></script><?php } ?>
<script src="<?php echo __PATH_TEMPLATE_JS__; ?>atlas-search.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/js/atlas-search.js'), 0, 12); ?>"></script>
<?php if($_REQUEST['page'] === 'info') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>atlas-drops.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/js/atlas-drops.js'), 0, 12); ?>"></script><?php } ?>
<script src="<?php echo __PATH_TEMPLATE_JS__; ?>community.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/js/community.js'), 0, 12); ?>"></script>
<?php if($_REQUEST['page'] === 'rankings') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>rankings.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/js/rankings.js'), 0, 12); ?>"></script><?php } ?>
<?php if($_REQUEST['page'] === 'profile') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>profiles.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/js/profiles.js'), 0, 12); ?>"></script><?php } ?>
<?php if($_REQUEST['page'] === 'usercp' && $_REQUEST['subpage'] === 'recharge') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>recharge.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/js/recharge.js'), 0, 12); ?>"></script><?php } ?>
<?php if($_REQUEST['page'] === 'usercp') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>account.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/js/account.js'), 0, 12); ?>"></script><?php } ?>
<script src="<?php echo __PATH_TEMPLATE_JS__; ?>panic-ui.js?v=<?php echo substr(hash_file('sha256',__DIR__.'/js/panic-ui.js'),0,12); ?>" defer></script>
<?php if($isHome) { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>home-v2.js?v=<?php echo substr(hash_file('sha256',__DIR__.'/js/home-v2.js'),0,12); ?>" defer></script><?php } ?>
<script src="<?php echo __PATH_TEMPLATE_JS__; ?>main.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/js/main.js'), 0, 12); ?>"></script>
<?php if($_REQUEST['page'] === 'info') { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>atlas-ui.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/js/atlas-ui.js'), 0, 12); ?>"></script><?php } ?>
<?php if($isHome) { ?><script src="<?php echo __PATH_TEMPLATE_JS__; ?>mu-panic-brasas.js?v=<?php echo substr(hash_file('sha256',__DIR__.'/js/mu-panic-brasas.js'),0,12); ?>" defer></script><?php } ?>
</body>
</html>


