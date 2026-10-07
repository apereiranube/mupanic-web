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
<meta name="description" content="MU PANIC Season 6. No pay to win. Economía en Zen y joyas. Cliente personalizado, Hub F11 y eventos. Prepará tu party y sumate al Discord.">
<?php if($preview): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
<link rel="canonical" href="<?= $e($base) ?>"><meta property="og:url" content="<?= $e($base) ?>">
<meta property="og:title" content="MU PANIC · Una nueva era está por empezar">
<meta property="og:description" content="Season 6. No pay to win. Zen y joyas. Un cliente con identidad propia. Sumate a la apertura en Discord.">
<meta property="og:image" content="<?= $e($t.$launchConfig['hero']) ?>"><meta property="og:type" content="website"><meta name="twitter:card" content="summary_large_image">
<link rel="stylesheet" href="<?= $e($t) ?>css/launch.css?v=8"><script defer src="<?= $e($t) ?>js/launch.js?v=8"></script>
<noscript><style>.launch *{animation:none!important}.campaign-event[hidden],.campaign-client-panel[hidden]{display:block!important}.campaign-tabs{display:none}</style></noscript>
</head><body class="launch">
<a class="launch-skip" href="#campaign-start">Saltar al contenido</a>
<header class="campaign-header"><a class="campaign-mark" href="#campaign-start">MU<span>PANIC</span></a><span class="campaign-edition">SEASON 6 / ARGENTINA</span><a class="campaign-discord" href="<?= $e($launchCommunity['invite']) ?>" target="_blank" rel="noopener">DISCORD <span>↗</span></a></header>
<nav class="campaign-rail" aria-label="Escenas de lanzamiento"><a href="#campaign-start" aria-label="Lanzamiento" class="is-current">01</a><a href="#filosofia" aria-label="No pay to win">02</a><a href="#zen" aria-label="Zen y joyas">03</a><a href="#hub" aria-label="Cliente personalizado y Hub F11">04</a><a href="#eventos" aria-label="Eventos">05</a><a href="#medusa" aria-label="Boss épico Medusa">06</a></nav>
<main>
<section class="campaign-hero campaign-scene" id="campaign-start" aria-labelledby="campaign-title">
<picture class="campaign-art campaign-hero-art"><source media="(max-width:700px)" srcset="<?= $e($t.$launchConfig['heroSmall']) ?>"><img src="<?= $e($t.$launchConfig['hero']) ?>" width="1672" height="941" alt="" fetchpriority="high"></picture>
<div class="campaign-fire" aria-hidden="true"></div><div class="campaign-magic" aria-hidden="true"></div><div class="campaign-slash" aria-hidden="true"></div><canvas class="campaign-fx" aria-hidden="true"></canvas>
<div class="campaign-hero-copy"><p class="campaign-kicker"><span></span> UNA NUEVA ERA ESTÁ POR EMPEZAR</p><h1 id="campaign-title"><span>MU</span><strong>PANIC</strong></h1><p class="campaign-warcry">NO VUELVAS SOLO.<br><em>VOLVÉ CON TU PARTY.</em></p>
<?php if(!empty($launchConfig['launchAt'])): ?>
<div class="campaign-countdown" data-launch-at="<?= $e($launchConfig['launchAt']) ?>" role="timer" aria-label="Tiempo hasta la apertura"><p>LA BATALLA COMIENZA <span><?= $e($launchConfig['launchDateLabel']) ?></span></p><div class="campaign-clock"><div><strong data-clock="days">--</strong><small>DÍAS</small></div><b>:</b><div><strong data-clock="hours">--</strong><small>HORAS</small></div><b>:</b><div><strong data-clock="minutes">--</strong><small>MINUTOS</small></div><b>:</b><div><strong data-clock="seconds">--</strong><small>SEGUNDOS</small></div></div><noscript><small><?= $e($launchConfig['launchDateLabel']) ?></small></noscript></div>
<?php else: ?>
<div class="campaign-opening"><p>APERTURA MU PANIC <span>LA NUEVA ERA</span></p><strong data-text="PRÓXIMAMENTE">PRÓXIMAMENTE</strong><small>La fecha se anuncia en Discord.</small><i aria-hidden="true"></i></div>
<?php endif; ?>
<a class="campaign-button" href="<?= $e($launchCommunity['invite']) ?>" target="_blank" rel="noopener">ESTOY PARA LA APERTURA <span>↗</span></a><p class="campaign-button-note">Sumate al Discord. Encontrá tu próxima party.</p></div>
<a class="campaign-scroll" href="#zen"><span>DESCUBRÍ QUÉ NOS HACE PANIC</span><b>↓</b></a><button class="campaign-motion" type="button" aria-pressed="false" hidden>Pausar efectos</button>
</section>
<div class="campaign-ticker" aria-label="Rates de cuenta Free"><div class="campaign-ticker-track"><?php for($i=0;$i<2;$i++): ?><span <?= $i ? 'aria-hidden="true"' : '' ?>>EXP <b><?= $e($launchFree['experience']) ?>X</b> <i>✦</i> MASTER <b><?= $e($launchFree['master']) ?>X</b> <i>✦</i> DROP <b><?= $e($launchFree['drop']) ?>%</b> <i>✦</i> SEASON <b>6</b> <i>✦</i> ARGENTINA <i>✦</i></span><?php endfor; ?></div><small>RATES FREE</small></div>
<section class="campaign-principle campaign-scene" id="filosofia" aria-labelledby="principle-title">
<div class="campaign-principle-grid" aria-hidden="true"></div><div class="campaign-principle-orbit" aria-hidden="true"></div>
<p class="campaign-kicker">ESTO DEFINE CÓMO QUEREMOS JUGAR</p>
<h2 id="principle-title"><span>NO</span><strong>PAY TO WIN.</strong></h2>
<p class="campaign-principle-claim">TU PODER <em>SE GANA JUGANDO.</em></p>
<p class="campaign-principle-copy">El protagonista es tu progreso.<br>Tu personaje, tus decisiones, tu tiempo en el continente.</p>
<div class="campaign-principle-seal"><span>MU PANIC</span><b>JUGÁ POR TU LUGAR.</b><span>SEASON 6</span></div>
</section>
<div class="campaign-cut"><i aria-hidden="true"></i><p>EL VALOR ESTÁ <strong>EN LO QUE CONSEGUÍS.</strong></p><i aria-hidden="true"></i></div>
<section class="campaign-zen campaign-scene" id="zen" aria-labelledby="zen-title"><div class="campaign-art"><img src="<?= $e($t) ?>img/atlas/events/invasion-1-117673c35473.webp" width="1672" height="941" loading="lazy" alt=""></div><span class="campaign-giant" aria-hidden="true">ZEN</span><div class="campaign-copy"><p class="campaign-kicker">LA ECONOMÍA SE JUEGA</p><h2 id="zen-title">ZEN<br><em>+ JOYAS.</em></h2><p class="campaign-statement">El botín no es un adorno.<br>Es parte de tu próxima decisión.</p><p class="campaign-body">Juntá Zen y joyas. Elegí qué comprar, qué mejorar y qué comerciar. Las compras del mercado forman parte de una economía que se mueve jugando.</p><div class="campaign-punchline"><span>FARMEÁ.</span><span>COMERCIÁ.</span><span>PROGRESÁ.</span></div></div>
<div class="campaign-economy-visual" aria-hidden="true"><span>LO QUE FARMEÁS TIENE VALOR</span><div><img src="<?= $e($t) ?>img/atlas/drops/jewel-bless-2c80b97e4d75.webp" alt="" width="512" height="512" loading="lazy"><b>+</b><img src="<?= $e($t) ?>img/atlas/drops/jewel-soul-05f3890104b2.webp" alt="" width="512" height="512" loading="lazy"></div><strong>ZEN + JOYAS</strong></div></section>
<div class="campaign-cut campaign-cut-violet"><i aria-hidden="true"></i><p>EL MU QUE CONOCÉS. <strong>NUESTRA FORMA DE VIVIRLO.</strong></p><i aria-hidden="true"></i></div>
<section class="campaign-client campaign-scene" id="hub" aria-labelledby="hub-title">
<div class="campaign-client-brand" aria-hidden="true">CUSTOM</div>
<div class="campaign-client-top"><p class="campaign-kicker">CLIENTE PERSONALIZADO POR NOSOTROS</p><span class="campaign-client-signature">DISEÑADO PARA MU PANIC <b>↗</b></span></div>
<div class="campaign-client-layout"><div class="campaign-copy"><h2 id="hub-title">TU CLIENTE.<br>NUESTRO <em>ADN.</em></h2><p class="campaign-statement">Personalizado por nosotros.<br>Desde que lo abrís.</p><p class="campaign-body">Interfaz, Hub y accesos trabajados para MU PANIC. Una experiencia con nuestro diseño y nuestras funciones dentro del juego.</p><ol class="campaign-hub-functions"><li><b>01</b><div><strong>TU PERSONAJE</strong><span>Estadísticas y progreso a la vista.</span></div></li><li><b>02</b><div><strong>TUS ACCESOS</strong><span>Funciones del servidor en un mismo lugar.</span></div></li><li><b>03</b><div><strong>TUS OBJETIVOS</strong><span>Eventos, logros y Battle Pass.</span></div></li></ol></div>
<div class="campaign-client-gallery"><div class="campaign-gallery-heading"><span>ESTO ES MU PANIC</span><strong>DESDE EL PRIMER INGRESO.</strong></div><div class="campaign-tabs campaign-client-tabs" role="tablist" aria-label="Vistas del cliente"><button type="button" id="client-tab-entry" role="tab" aria-controls="client-entry" aria-selected="true" tabindex="0" data-client-tab="entry">INGRESO <span>↗</span></button><button type="button" id="client-tab-hub" role="tab" aria-controls="client-hub" aria-selected="false" tabindex="-1" data-client-tab="hub">HUB F11 <span>↗</span></button></div><figure class="campaign-hub-preview campaign-client-panel" id="client-entry" role="tabpanel" aria-labelledby="client-tab-entry" tabindex="0"><div class="campaign-hub-preview-frame"><img src="<?= $e($t) ?>img/launch/client-entry-enhanced-v6.webp" width="1672" height="941" loading="lazy" alt="Ingreso al servidor MU PANIC: guerrero, escenario volcánico y selección de servidor"></div><figcaption>INGRESO AL SERVIDOR <span>CLIENTE MU PANIC</span></figcaption></figure><figure class="campaign-hub-preview campaign-client-panel" id="client-hub" role="tabpanel" aria-labelledby="client-tab-hub" tabindex="0" hidden><div class="campaign-hub-preview-label"><kbd>F11</kbd><span>ABRÍ TU<br><strong>CENTRO DE MANDO.</strong></span><b aria-hidden="true">↙</b></div><div class="campaign-hub-preview-frame"><img src="<?= $e($t) ?>img/server/systems/hub-f11-03b5aefe024d.webp" width="1522" height="1033" loading="lazy" alt="Vista completa del Hub MU PANIC, con progreso, estadísticas y accesos del servidor"></div><figcaption>HUB MU PANIC <span>EN TU CLIENTE · CON F11</span></figcaption></figure></div></div>
</section>
<div class="campaign-cut"><i aria-hidden="true"></i><p>YA TENÉS TU PARTY. <strong>AHORA ELEGÍ EL DESAFÍO.</strong></p><i aria-hidden="true"></i></div>
<section class="campaign-events campaign-scene" id="eventos" aria-labelledby="events-title"><div class="campaign-events-heading"><p class="campaign-kicker">03 / EL CONTINENTE NO DUERME</p><h2 id="events-title">ELEGÍ TU <em>BATALLA.</em></h2><div class="campaign-tabs" role="tablist" aria-label="Tipos de eventos"><?php foreach($chapters as $i=>$c): ?><button type="button" id="tab-<?= $e($c[0]) ?>" role="tab" aria-controls="event-<?= $e($c[0]) ?>" aria-selected="<?= $i===0?'true':'false' ?>" tabindex="<?= $i===0?'0':'-1' ?>" data-event-tab="<?= $e($c[0]) ?>"><?= $e($c[1]) ?><span>↗</span></button><?php endforeach; ?></div></div>
<?php foreach($chapters as $i=>$c): ?><div class="campaign-event" id="event-<?= $e($c[0]) ?>" role="tabpanel" aria-labelledby="tab-<?= $e($c[0]) ?>" tabindex="0" <?= $i ? 'hidden' : '' ?>><div class="campaign-event-art"><img src="<?= $e($t.$c[5]) ?>" width="1672" height="941" loading="lazy" alt=""></div><div class="campaign-event-copy"><small><?= $e($c[1]) ?></small><h3><?= $c[2] ?></h3><strong><?= $e($c[3]) ?></strong><p><?= $e($c[4]) ?></p><span class="campaign-event-index" aria-hidden="true">0<?= $i+1 ?></span></div></div><?php endforeach; ?></section>
<div class="campaign-cut campaign-cut-venom"><i aria-hidden="true"></i><p>REUNÍ A TU PARTY. <strong>LA REINA LOS ESPERA.</strong></p><i aria-hidden="true"></i></div>
<section class="campaign-boss campaign-scene" id="medusa" aria-labelledby="medusa-title">
<div class="campaign-art campaign-boss-art"><img src="<?= $e($t) ?>img/launch/medusa-party-v7-1672.webp" width="1672" height="941" loading="lazy" alt="Medusa, reina serpiente de escala colosal, ataca con magia verde a una party de guerrero, elfa y mago en una fortaleza en ruinas"><canvas class="campaign-boss-fx" aria-hidden="true"></canvas></div>
<div class="campaign-boss-venom" aria-hidden="true"></div><div class="campaign-boss-sparks" aria-hidden="true"></div>
<div class="campaign-copy"><p class="campaign-kicker">BOSS ÉPICO / MU PANIC</p><h2 id="medusa-title">MEDUSA<span>LA REINA<br> DEL CAOS.</span></h2><p class="campaign-statement">Una amenaza colosal.<br>Una party para enfrentarla.</p><p class="campaign-body">Reuní a tu equipo. Prepará tu personaje.<br>El próximo desafío tiene nombre.</p><div class="campaign-boss-seal"><span>✦</span> ¿TU PARTY ESTÁ LISTA?</div></div>
<div class="campaign-boss-caption" aria-hidden="true"><span>MU PANIC · SEASON 6</span><strong>ENFRENTÁ LO ÉPICO.</strong></div>
</section>
<div class="campaign-cut campaign-cut-violet"><i aria-hidden="true"></i><p>LA PRÓXIMA HISTORIA <strong>LA ESCRIBÍS VOS.</strong></p><i aria-hidden="true"></i></div>
<section class="campaign-community campaign-scene" aria-labelledby="community-title"><div class="campaign-art"><img src="<?= $e($t.$launchConfig['hero']) ?>" width="1672" height="941" loading="lazy" alt=""></div><p class="campaign-kicker">LA PRIMERA PARTY SE ARMA AHORA</p><h2 id="community-title">NOS VEMOS<br><em>EN PANIC.</em></h2><a class="campaign-button" href="<?= $e($launchCommunity['invite']) ?>" target="_blank" rel="noopener">ENTRÁ AL DISCORD <span>↗</span></a><p>Novedades. Comunidad. Apertura.</p></section>
</main><footer class="campaign-footer"><span>MU PANIC © <?= date('Y') ?></span><span>SEASON 6 · ARGENTINA</span><a href="#campaign-start">VOLVER AL INICIO ↑</a></footer></body></html>
