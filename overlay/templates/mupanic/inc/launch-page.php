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
    ['01 / EL MUNDO', 'Un continente por conquistar.', 'Mapas, spots, monstruos y drops. Descubrí dónde empieza tu camino y prepará tu próxima conquista.', 'img/server/server-world.webp', 'info/#mapas', 'Explorá los mapas'],
    ['02 / TU PROGRESO', 'Más que subir de nivel.', 'Logros, Battle Pass y recompensa diaria. Objetivos que acompañan tu recorrido dentro del juego.', 'img/server/systems/chronicles-d6291c69cf6b.webp', 'information/#sistemas', 'Conocé los sistemas'],
    ['03 / EL DESAFÍO', 'Hay batallas que se comparten.', 'Eventos clásicos, invasiones y desafíos. Conocé cómo se juegan y consultá la agenda del servidor.', 'img/server/server-events.webp', 'info/#eventos', 'Descubrí los eventos'],
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
<meta property="og:title" content="MU PANIC · El continente está por despertar">
<meta property="og:description" content="Season 6 · Argentina. Prepará tu próxima conquista. Fecha de apertura a anunciar.">
<meta property="og:image" content="<?= $launchEscape($launchTemplate.$launchConfig['hero']) ?>">
<meta property="og:type" content="website">
<link rel="stylesheet" href="<?= $launchEscape($launchTemplate) ?>css/launch.css?v=1">
<noscript><style>.launch-scene,.launch-glow,.launch-embers i{animation:none!important}</style></noscript>
<script defer src="<?= $launchEscape($launchTemplate) ?>js/launch.js?v=1"></script>
</head>
<body class="launch">
<a class="launch-skip" href="#launch-content">Saltar al contenido</a>
<header class="launch-header">
<a class="launch-brand" href="<?= $launchEscape($launchBase) ?>" aria-label="MU PANIC, inicio"><svg viewBox="0 0 40 48" aria-hidden="true"><path d="M3 38V9l17 15L37 9v29L20 47z M3 9V2l17 15L37 2v7 M20 24v23"/></svg><span>MU PANIC<small>EL CONTINENTE TE ESPERA</small></span></a>
<nav aria-label="Navegación de lanzamiento"><a href="#servidor">El servidor</a><a href="<?= $launchEscape($launchBase) ?>info/">Atlas</a><a class="launch-nav-discord" href="<?= $launchEscape($launchCommunity['invite']) ?>" target="_blank" rel="noopener">Discord ↗</a></nav>
</header>
<main id="launch-content">
<section class="launch-hero" aria-labelledby="launch-title">
<picture class="launch-scene"><source media="(max-width: 600px)" srcset="<?= $launchEscape($launchTemplate.$launchConfig['heroSmall']) ?>"><img src="<?= $launchEscape($launchTemplate.$launchConfig['hero']) ?>" alt="" width="1672" height="941" fetchpriority="high"></picture>
<div class="launch-glow" aria-hidden="true"></div><div class="launch-embers" aria-hidden="true"><?php for($i=0;$i<12;$i++): ?><i style="--n:<?= $i ?>"></i><?php endfor; ?></div>
<div class="launch-hero-inner">
<p class="launch-eyebrow">MU ONLINE <span>/</span> SEASON 6 <span>/</span> ARGENTINA</p>
<p class="launch-status"><span></span> PRÓXIMAMENTE</p>
<h1 id="launch-title">EL CONTINENTE<br>ESTÁ POR<br><em>DESPERTAR.</em></h1>
<p class="launch-lead">El MU de siempre. Una historia con identidad propia.<br> Prepará tu próxima conquista.</p>
<div class="launch-actions"><a class="launch-button" href="<?= $launchEscape($launchCommunity['invite']) ?>" target="_blank" rel="noopener">SUMATE AL DISCORD <span>↗</span></a><a class="launch-link" href="#servidor">Conocé el servidor <span>↓</span></a></div>
<p class="launch-opening">Fecha de apertura a anunciar.<br><span>Las novedades empiezan en Discord.</span></p>
</div>
<button class="launch-motion" type="button" aria-pressed="false" hidden>Pausar efectos</button>
<span class="launch-scene-label" aria-hidden="true">01 / EL UMBRAL</span>
</section>
<section class="launch-rates" aria-label="Rates del servidor, cuenta Free">
<div><small>EL CONTINENTE</small><strong>SEASON <b>6</b></strong><span>MU PANIC · Argentina</span></div>
<div><small>EXPERIENCIA</small><strong><?= $launchEscape($launchFree['experience']) ?><b>X</b></strong><span>Tu primer camino</span></div>
<div><small>MASTER EXP</small><strong><?= $launchEscape($launchFree['master']) ?><b>X</b></strong><span>La siguiente etapa</span></div>
<div><small>DROP</small><strong><?= $launchEscape($launchFree['drop']) ?><b>%</b></strong><span>Tu próxima mejora</span></div>
<p>Rates de cuenta Free · <a href="<?= $launchEscape($launchBase) ?>information/">Ver información del servidor →</a></p>
</section>
<section class="launch-server" id="servidor" aria-labelledby="launch-server-title">
<div class="launch-section-heading"><p class="launch-eyebrow">02 / LA ESENCIA PANIC</p><h2 id="launch-server-title">UN MUNDO CONOCIDO.<br><em>Una nueva conquista.</em></h2><p>Elegí tu clase. Encontrá tu party. Hacé que cada etapa cuente.</p></div>
<div class="launch-features"><?php foreach($launchFeatures as $feature): ?>
<a class="launch-feature" href="<?= $launchEscape($launchBase.$feature[4]) ?>"><img src="<?= $launchEscape($launchTemplate.$feature[3]) ?>" alt="" width="960" height="640" loading="lazy"><div><small><?= $launchEscape($feature[0]) ?></small><h3><?= $launchEscape($feature[1]) ?></h3><p><?= $launchEscape($feature[2]) ?></p><span><?= $launchEscape($feature[5]) ?> →</span></div></a>
<?php endforeach; ?></div>
<div class="launch-notes"><p><strong>Tu cliente. Tu centro de mando.</strong>Nexo PANIC reúne accesos, estadísticas y funciones dentro del juego. Abrilo con F11.</p><p><strong>El continente, a tu alcance.</strong>El Atlas reúne mapas, drops, bosses y eventos para que puedas preparar tu recorrido antes de entrar.</p></div>
</section>
<section class="launch-community" aria-labelledby="launch-community-title"><p class="launch-eyebrow">03 / EL PRIMER ENCUENTRO</p><h2 id="launch-community-title">TU PARTY EMPIEZA<br><em>ANTES DE ENTRAR.</em></h2><p>Sumate a la comunidad. Conocé las novedades del servidor<br> y enterate de la apertura desde el primer anuncio.</p><a class="launch-button" href="<?= $launchEscape($launchCommunity['invite']) ?>" target="_blank" rel="noopener">ENTRÁ AL DISCORD <span>↗</span></a><small>Survivor y arenas por clase: fecha a anunciar.</small></section>
</main>
<footer class="launch-footer"><p>© <?= date('Y') ?> MU PANIC <span>SEASON 6 · ARGENTINA</span></p><nav aria-label="Información legal"><a href="<?= $launchEscape($launchBase) ?>tos/">Términos</a><a href="<?= $launchEscape($launchBase) ?>privacy/">Privacidad</a><a href="<?= $launchEscape($launchBase) ?>refunds/">Compras y reembolsos</a></nav></footer>
</body></html>
