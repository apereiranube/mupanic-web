<?php
if(!defined('access') or !access) die();
require_once __DIR__.'/atlas-runtime.php';
$launchBalance = panicAtlasBalance();
$launchFree = $launchBalance['accounts'][0];
$launchCommunity = require __DIR__.'/community-config.php';
$launchEscape = static function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$launchBase = rtrim(__BASE_URL__, '/').'/';
$launchTemplate = rtrim(__PATH_TEMPLATE__, '/').'/';
$launchPreview = in_array(strtolower((string)parse_url(__BASE_URL__, PHP_URL_HOST)), $launchConfig['previewHosts'], true);
$launchFeatures = [
    ['01 / HUB F11', 'Todo tu poder. Un solo lugar.', 'Estadísticas, accesos y funciones dentro del juego. Abrí el Hub con F11 y tomá el control de tu aventura.', 'img/server/systems/hub-f11-03b5aefe024d.webp'],
    ['02 / ECONOMÍA', 'Acá el Zen vale.', 'El Zen forma parte del progreso y de las compras del mercado. Cada batalla también construye tu economía.', 'img/atlas/events/invasion-1-117673c35473.webp'],
    ['03 / EVENTOS', 'Entrá con tu party. Salí con gloria.', 'Eventos clásicos, invasiones y combates por clase. Del desafío cooperativo al duelo cara a cara.', 'img/atlas/events/arena-0-518f387c7872.webp'],
];
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>MU PANIC · Próximamente · Season 6</title>
<meta name="description" content="MU PANIC, Season 6 Argentina. Explorá el servidor, sus rates, sistemas y eventos. Próximamente. Seguí las novedades de apertura en Discord.">
<?php if($launchPreview): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
<link rel="canonical" href="<?= $launchEscape($launchBase) ?>">
<meta property="og:title" content="MU PANIC · La próxima batalla empieza acá">
<meta property="og:description" content="Season 6 · Hub F11, eventos y una economía donde el Zen vale. Sumate al Discord y prepará tu party.">
<meta property="og:image" content="<?= $launchEscape($launchTemplate.$launchConfig['hero']) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= $launchEscape($launchBase) ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="stylesheet" href="<?= $launchEscape($launchTemplate) ?>css/launch.css?v=3">
<noscript><style>.launch *{animation:none!important}</style></noscript>
<script defer src="<?= $launchEscape($launchTemplate) ?>js/launch.js?v=3"></script>
</head>
<body class="launch">
<a class="launch-skip" href="#launch-content">Saltar al contenido</a>
<header class="launch-header">
<a class="launch-brand" href="#launch-content" aria-label="MU PANIC, inicio"><svg viewBox="0 0 40 48" aria-hidden="true"><path d="M3 38V9l17 15L37 9v29L20 47z M3 9V2l17 15L37 2v7 M20 24v23"/></svg><span>MU PANIC<small>PREPARATE PARA LA APERTURA</small></span></a>
<nav aria-label="Navegación de lanzamiento"><a href="#servidor">El servidor</a><a href="#desafios">Los desafíos</a><a class="launch-nav-discord" href="<?= $launchEscape($launchCommunity['invite']) ?>" target="_blank" rel="noopener">Discord ↗</a></nav>
</header>
<main id="launch-content">
<section class="launch-hero" aria-labelledby="launch-title">
<picture class="launch-scene"><source media="(max-width: 600px)" srcset="<?= $launchEscape($launchTemplate.$launchConfig['heroSmall']) ?>"><img src="<?= $launchEscape($launchTemplate.$launchConfig['hero']) ?>" alt="" width="1672" height="941" fetchpriority="high"></picture>
<div class="launch-battle" aria-hidden="true"></div><div class="launch-glow" aria-hidden="true"></div><div class="launch-rift" aria-hidden="true"></div><canvas class="launch-particles" aria-hidden="true"></canvas><div class="launch-embers" aria-hidden="true"><?php for($i=0;$i<12;$i++): ?><i style="--n:<?= $i ?>"></i><?php endfor; ?></div>
<div class="launch-hero-inner">
<p class="launch-eyebrow">MU ONLINE <span>/</span> SEASON 6 <span>/</span> ARGENTINA</p>
<p class="launch-status"><span></span> PRÓXIMAMENTE</p>
<h1 id="launch-title"><span>MU</span><em>PANIC</em></h1>
<p class="launch-warcry">LA PRÓXIMA BATALLA EMPIEZA ACÁ.</p>
<p class="launch-lead">Season 6. Siete clases. Una nueva conquista.<br>Armá tu party. El continente no se conquista solo.</p>
<div class="launch-actions"><a class="launch-button" href="<?= $launchEscape($launchCommunity['invite']) ?>" target="_blank" rel="noopener">SUMATE AL DISCORD <span>↗</span></a><a class="launch-link" href="#servidor">Conocé el servidor <span>↓</span></a></div>
<div class="launch-countdown" data-launch-at="<?= $launchEscape($launchConfig['launchAt']) ?>" role="timer" aria-label="Tiempo hasta la apertura">
<p>CUENTA REGRESIVA <span><?= $launchEscape($launchConfig['launchDateLabel']) ?></span></p>
<div class="launch-clock"><div><strong data-clock="days">--</strong><small>DÍAS</small></div><b>:</b><div><strong data-clock="hours">--</strong><small>HORAS</small></div><b>:</b><div><strong data-clock="minutes">--</strong><small>MINUTOS</small></div><b>:</b><div><strong data-clock="seconds">--</strong><small>SEGUNDOS</small></div></div>
<noscript><small><?= $launchEscape($launchConfig['launchDateLabel']) ?></small></noscript>
</div>
</div>
<button class="launch-motion" type="button" aria-pressed="false" hidden>Pausar efectos</button>
<span class="launch-scene-label" aria-hidden="true">SEASON 6 / NUEVA ERA</span>
</section>
<section class="launch-rates" aria-label="Rates del servidor, cuenta Free">
<div><small>EL CONTINENTE</small><strong>SEASON <b>6</b></strong><span>MU PANIC · Argentina</span></div>
<div><small>EXPERIENCIA</small><strong><?= $launchEscape($launchFree['experience']) ?><b>X</b></strong><span>Tu primer camino</span></div>
<div><small>MASTER EXP</small><strong><?= $launchEscape($launchFree['master']) ?><b>X</b></strong><span>La siguiente etapa</span></div>
<div><small>DROP</small><strong><?= $launchEscape($launchFree['drop']) ?><b>%</b></strong><span>Tu próxima mejora</span></div>
<p>Rates de cuenta Free · Tu aventura comienza en Season 6.</p>
</section>
<section class="launch-server" id="servidor" aria-labelledby="launch-server-title">
<div class="launch-section-heading"><p class="launch-eyebrow">02 / LA ESENCIA PANIC</p><h2 id="launch-server-title">NO VENÍS SÓLO A LEVELEAR.<br><em>VENÍS A DEJAR TU MARCA.</em></h2><p>Un centro de mando propio. Una economía que importa. Batallas para compartir.</p></div>
<div class="launch-features" id="desafios"><?php foreach($launchFeatures as $feature): ?>
<article class="launch-feature"><img src="<?= $launchEscape($launchTemplate.$feature[3]) ?>" alt="" width="960" height="640" loading="lazy"><div><small><?= $launchEscape($feature[0]) ?></small><h3><?= $launchEscape($feature[1]) ?></h3><p><?= $launchEscape($feature[2]) ?></p></div></article>
<?php endforeach; ?></div>
<div class="launch-notes"><p><strong>PROGRESO CON OBJETIVOS.</strong>Logros, Battle Pass y recompensa diaria acompañan tu recorrido. Siempre hay un próximo desafío.</p><p><strong>CLÁSICOS. INVASIONES. ARENAS.</strong>Blood Castle, Devil Square, Chaos Castle, Illusion Temple y Doppelganger. Survivor y arenas por clase tendrán fecha a anunciar.</p></div>
</section>
<section class="launch-community" aria-labelledby="launch-community-title"><p class="launch-eyebrow">03 / EL PRIMER ENCUENTRO</p><h2 id="launch-community-title">LA PRIMERA BATALLA:<br><em>ARMAR TU PARTY.</em></h2><p>Sumate a la comunidad. Conocé las novedades del servidor<br> y enterate de la apertura desde el primer anuncio.</p><a class="launch-button" href="<?= $launchEscape($launchCommunity['invite']) ?>" target="_blank" rel="noopener">ENTRÁ AL DISCORD <span>↗</span></a><small>Survivor y arenas por clase: fecha a anunciar.</small></section>
</main>
<footer class="launch-footer"><p>© <?= date('Y') ?> MU PANIC <span>SEASON 6 · ARGENTINA</span></p><span>EL PRÓXIMO CAPÍTULO ESTÁ POR COMENZAR.</span></footer>
</body></html>
