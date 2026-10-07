<?php
if(!defined('access') or !access) die();
require_once __DIR__.'/atlas-runtime.php';
$launchFree = panicAtlasBalance()['accounts'][0];
$launchCommunity = require __DIR__.'/community-config.php';
$e = static function($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$t = rtrim(__PATH_TEMPLATE__, '/').'/';
$base = rtrim(__BASE_URL__, '/').'/';
$preview = in_array(strtolower((string)parse_url(__BASE_URL__, PHP_URL_HOST)), $launchConfig['previewHosts'], true);
$chapters = [
 ['classics','CLÁSICOS','LA PARTY<br>NO SE ABANDONA.','Blood Castle · Devil Square · Chaos Castle','También Illusion Temple y Doppelganger. Entrá con tu equipo, enfrentá el desafío y buscá tu próxima recompensa.','img/atlas/editorial/blood-castle.webp'],
 ['invasions','INVASIONES','EL MUNDO<br>ESTÁ BAJO ATAQUE.','Monstruos. Bosses. Recompensas.','Las invasiones llevan la batalla al continente. Prepará tu personaje, encontrá al enemigo y peleá por el botín.','img/atlas/events/invasion-0-aa3bf042f5e6.webp'],
 ['arenas','ARENAS & SURVIVOR','TU CLASE.<br>TU RIVAL. TU GLORIA.','Duelos por clase · Survivor','Elf contra Elf. Blade Knight contra Blade Knight. Y el resto de las clases cara a cara. Los combates manuales y Survivor tendrán fecha a anunciar.','img/atlas/events/arena-7-39c578be7f53.webp'],
];
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>MU PANIC · Una nueva era · Season 6</title>
<meta name="description" content="MU PANIC Season 6. El Zen vale. Hub F11, eventos, invasiones y duelos por clase. Prepará tu party y sumate al Discord.">
<?php if($preview): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
<link rel="canonical" href="<?= $e($base) ?>"><meta property="og:url" content="<?= $e($base) ?>">
<meta property="og:title" content="MU PANIC · Una nueva era está por empezar">
<meta property="og:description" content="Season 6. El Zen vale. Tu party te espera. Sumate a la apertura en Discord.">
<meta property="og:image" content="<?= $e($t.$launchConfig['hero']) ?>"><meta property="og:type" content="website"><meta name="twitter:card" content="summary_large_image">
<link rel="stylesheet" href="<?= $e($t) ?>css/launch.css?v=4"><script defer src="<?= $e($t) ?>js/launch.js?v=4"></script>
<noscript><style>.launch *{animation:none!important}.campaign-event[hidden]{display:block!important}.campaign-tabs{display:none}</style></noscript>
</head><body class="launch">
<a class="launch-skip" href="#campaign-start">Saltar al contenido</a>
<header class="campaign-header"><a class="campaign-mark" href="#campaign-start">MU<span>PANIC</span></a><span class="campaign-edition">SEASON 6 / ARGENTINA</span><a class="campaign-discord" href="<?= $e($launchCommunity['invite']) ?>" target="_blank" rel="noopener">DISCORD <span>↗</span></a></header>
<nav class="campaign-rail" aria-label="Escenas de lanzamiento"><a href="#campaign-start" aria-label="Lanzamiento" class="is-current">01</a><a href="#zen" aria-label="El Zen vale">02</a><a href="#hub" aria-label="Hub F11">03</a><a href="#eventos" aria-label="Eventos">04</a></nav>
<main>
<section class="campaign-hero campaign-scene" id="campaign-start" aria-labelledby="campaign-title">
<picture class="campaign-art campaign-hero-art"><source media="(max-width:700px)" srcset="<?= $e($t.$launchConfig['heroSmall']) ?>"><img src="<?= $e($t.$launchConfig['hero']) ?>" width="1672" height="941" alt="" fetchpriority="high"></picture>
<div class="campaign-fire" aria-hidden="true"></div><div class="campaign-magic" aria-hidden="true"></div><div class="campaign-slash" aria-hidden="true"></div><canvas class="campaign-fx" aria-hidden="true"></canvas>
<div class="campaign-hero-copy"><p class="campaign-kicker"><span></span> UNA NUEVA ERA ESTÁ POR EMPEZAR</p><h1 id="campaign-title"><span>MU</span><strong>PANIC</strong></h1><p class="campaign-warcry">NO VUELVAS SOLO.<br><em>VOLVÉ CON TU PARTY.</em></p>
<div class="campaign-countdown" data-launch-at="<?= $e($launchConfig['launchAt']) ?>" role="timer" aria-label="Tiempo hasta la apertura"><p>LA BATALLA COMIENZA <span><?= $e($launchConfig['launchDateLabel']) ?></span></p><div class="campaign-clock"><div><strong data-clock="days">--</strong><small>DÍAS</small></div><b>:</b><div><strong data-clock="hours">--</strong><small>HORAS</small></div><b>:</b><div><strong data-clock="minutes">--</strong><small>MINUTOS</small></div><b>:</b><div><strong data-clock="seconds">--</strong><small>SEGUNDOS</small></div></div><noscript><small><?= $e($launchConfig['launchDateLabel']) ?></small></noscript></div>
<a class="campaign-button" href="<?= $e($launchCommunity['invite']) ?>" target="_blank" rel="noopener">ESTOY PARA LA APERTURA <span>↗</span></a><p class="campaign-button-note">Sumate al Discord. Encontrá tu próxima party.</p></div>
<a class="campaign-scroll" href="#zen"><span>DESCUBRÍ QUÉ NOS HACE PANIC</span><b>↓</b></a><button class="campaign-motion" type="button" aria-pressed="false" hidden>Pausar efectos</button>
</section>
<div class="campaign-ticker" aria-label="Rates de cuenta Free"><div class="campaign-ticker-track"><?php for($i=0;$i<2;$i++): ?><span <?= $i ? 'aria-hidden="true"' : '' ?>>EXP <b><?= $e($launchFree['experience']) ?>X</b> <i>✦</i> MASTER <b><?= $e($launchFree['master']) ?>X</b> <i>✦</i> DROP <b><?= $e($launchFree['drop']) ?>%</b> <i>✦</i> SEASON <b>6</b> <i>✦</i> ARGENTINA <i>✦</i></span><?php endfor; ?></div><small>RATES FREE</small></div>
<section class="campaign-zen campaign-scene" id="zen" aria-labelledby="zen-title"><div class="campaign-art"><img src="<?= $e($t) ?>img/atlas/events/invasion-1-117673c35473.webp" width="1672" height="941" loading="lazy" alt=""></div><span class="campaign-giant" aria-hidden="true">ZEN</span><div class="campaign-copy"><p class="campaign-kicker">01 / CADA BATALLA CUENTA</p><h2 id="zen-title">EL ZEN<br><em>VALE.</em></h2><p class="campaign-statement">Lo que ganás jugando<br>forma parte de tu progreso.</p><p class="campaign-body">Farmear, juntar y elegir en qué gastar. El Zen tiene su lugar en las compras del mercado y en tu camino por el servidor.</p><div class="campaign-punchline"><span>FARMEÁ.</span><span>COMERCIÁ.</span><span>PROGRESÁ.</span></div></div></section>
<section class="campaign-hub campaign-scene" id="hub" aria-labelledby="hub-title"><div class="campaign-art"><img src="<?= $e($t) ?>img/server/systems/hub-f11-03b5aefe024d.webp" width="1672" height="941" loading="lazy" alt=""></div><div class="campaign-key" aria-hidden="true">F11<span>ACTIVÁ TU CENTRO DE MANDO</span></div><div class="campaign-copy"><p class="campaign-kicker">02 / IDENTIDAD PANIC</p><h2 id="hub-title">TU JUEGO.<br><em>BAJO CONTROL.</em></h2><p class="campaign-statement">El Hub que reúne<br>tu mundo dentro del cliente.</p><p class="campaign-body">Estadísticas, accesos y funciones del servidor en un mismo lugar. Abrilo con F11 y seguí jugando.</p><div class="campaign-punchline"><span>HUB F11</span><span>LOGROS</span><span>BATTLE PASS</span></div><p class="campaign-footnote">También recompensa diaria y objetivos para seguir avanzando.</p></div></section>
<section class="campaign-events campaign-scene" id="eventos" aria-labelledby="events-title"><div class="campaign-events-heading"><p class="campaign-kicker">03 / EL CONTINENTE NO DUERME</p><h2 id="events-title">ELEGÍ TU <em>BATALLA.</em></h2><div class="campaign-tabs" role="tablist" aria-label="Tipos de eventos"><?php foreach($chapters as $i=>$c): ?><button type="button" id="tab-<?= $e($c[0]) ?>" role="tab" aria-controls="event-<?= $e($c[0]) ?>" aria-selected="<?= $i===0?'true':'false' ?>" tabindex="<?= $i===0?'0':'-1' ?>" data-event-tab="<?= $e($c[0]) ?>"><?= $e($c[1]) ?><span>↗</span></button><?php endforeach; ?></div></div>
<?php foreach($chapters as $i=>$c): ?><div class="campaign-event" id="event-<?= $e($c[0]) ?>" role="tabpanel" aria-labelledby="tab-<?= $e($c[0]) ?>" tabindex="0" <?= $i ? 'hidden' : '' ?>><div class="campaign-event-art"><img src="<?= $e($t.$c[5]) ?>" width="1672" height="941" loading="lazy" alt=""></div><div class="campaign-event-copy"><small><?= $e($c[1]) ?></small><h3><?= $c[2] ?></h3><strong><?= $e($c[3]) ?></strong><p><?= $e($c[4]) ?></p><span class="campaign-event-index" aria-hidden="true">0<?= $i+1 ?></span></div></div><?php endforeach; ?></section>
<section class="campaign-community campaign-scene" aria-labelledby="community-title"><div class="campaign-art"><img src="<?= $e($t.$launchConfig['hero']) ?>" width="1672" height="941" loading="lazy" alt=""></div><p class="campaign-kicker">LA PRIMERA PARTY SE ARMA AHORA</p><h2 id="community-title">NOS VEMOS<br><em>EN PANIC.</em></h2><a class="campaign-button" href="<?= $e($launchCommunity['invite']) ?>" target="_blank" rel="noopener">ENTRÁ AL DISCORD <span>↗</span></a><p>Novedades. Comunidad. Apertura.</p></section>
</main><footer class="campaign-footer"><span>MU PANIC © <?= date('Y') ?></span><span>SEASON 6 · ARGENTINA</span><a href="#campaign-start">VOLVER AL INICIO ↑</a></footer></body></html>
