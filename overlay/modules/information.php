<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/../templates/mupanic/inc/atlas-runtime.php';
$serverEscape = function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$serverBase = __PATH_TEMPLATE__.'img/home/';
$serverBalance = panicAtlasBalance();
$serverFree = $serverBalance['accounts'][0] ?? [];
$companionsData = json_decode(file_get_contents(__DIR__.'/../templates/mupanic/inc/atlas-vinculos.json'), true);
$companionsBySlug = [];
foreach(($companionsData['entries'] ?? []) as $companion) $companionsBySlug[$companion['slug']] = $companion;
$companionRoles = ['elyndra'=>['Defensa','Aelira → Sylthara → Elyndra'], 'aurion'=>['Experiencia y Zen','Aurik → Aurion'], 'vaerion'=>['Daño','Vaeryn → Vaerath → Vaerion'], 'grum'=>['Recolección','Grum · El Feo']];
$games = [
    ['Ruleta','Elegí tu apuesta y seguí el giro.'],
    ['Tragamonedas','Hacé girar los rodillos.'],
    ['Mayor o Menor','Decidí qué viene después.'],
    ['Vuelo del Dragón','Decidí cuándo cobrar.'],
    ['Lluvia de Joyas','Seguí la caída de las joyas.'],
    ['Tesoro de Kalima','Elegí hasta dónde arriesgar.'],
    ['Torre del Caos','Avanzá por la torre.'],
    ['Carrera','Elegí tu monstruo y seguí la carrera.'],
];
?>
<div class="panic-experience panic-server">
    <header class="px-server-intro px-wrap">
        <p class="px-kicker">EL SERVIDOR · SEASON 6</p>
        <h1>El MU que conocés.<br><em>Lo que creamos para vos.</em></h1>
        <p class="px-lead">Bandos en guerra, compañeros que evolucionan, un mercado entre jugadores y herramientas propias dentro del cliente.</p>
        <nav class="px-section-nav" aria-label="Secciones de El servidor"><a href="#progresion">PROGRESIÓN</a><a href="#fractura">FRACTURA</a><a href="#mascotas">MASCOTAS</a><a href="#cliente">CLIENTE</a><a href="#mercado">MERCADO</a><a href="#eventos">EVENTOS</a><a href="#minijuegos">MINIJUEGOS</a></nav>
    </header>

    <section class="px-server-section px-wrap" id="progresion" aria-labelledby="server-progression-title">
        <div class="px-section-heading"><p class="px-kicker">01 / PROGRESIÓN</p><h2 id="server-progression-title">Tu equipo.<br><em>Tu próxima mejora.</em></h2></div>
        <div class="px-prose"><p>El avance combina experiencia, equipo, Zen y joyas. Preparar una combinación, conseguir materiales y elegir dónde farmear forman parte del recorrido.</p><p>Los rates base son el punto de partida: los tramos de reset, las condiciones de cada cuenta y los bonos pueden modificar lo que recibís.</p><dl class="px-inline-facts"><div><dt>EXP BASE FREE</dt><dd><?php echo $serverEscape($serverFree['experience'] ?? '—'); ?>x</dd></div><div><dt>MASTER EXP FREE</dt><dd><?php echo $serverEscape($serverFree['master'] ?? '—'); ?>x</dd></div><div><dt>DROP GENERAL FREE</dt><dd><?php echo $serverEscape($serverFree['drop'] ?? '—'); ?>%</dd></div></dl><p class="px-note">El drop general no es la probabilidad individual de cada joya u objeto.</p><a class="px-link" href="<?php echo __BASE_URL__; ?>info/#rates">VER RATES, RESETS Y CONDICIONES ↗</a></div>
    </section>

    <section class="px-feature px-server-fractura" id="fractura" aria-labelledby="server-fractura-title">
        <picture class="px-scene"><source media="(max-width:700px)" srcset="<?php echo $serverBase; ?>fractura-nexo-caos-v1-960.webp"><img src="<?php echo $serverBase; ?>fractura-nexo-caos-v1.webp" alt="Aldren, Varkor y el Señor de la Fractura" width="1672" height="941" loading="lazy" decoding="async"></picture>
        <div class="px-wrap px-feature-content"><div class="px-copy"><p class="px-kicker">02 / NEXO CONTRA CAOS</p><h2 id="server-fractura-title">Un bando.<br><em>Una batalla común.</em></h2><p>Unite a Nexo o Caos y disputá Vulcanus. El PvP y el Señor de la Fractura forman parte de una competencia entre bandos con cierre semanal y Salón de la Fama.</p><a class="px-link" href="<?php echo __BASE_URL__; ?>fractura/">VER BANDOS, REGLAS Y RESULTADOS ↗</a></div></div>
    </section>

    <section class="px-pet-detail" id="mascotas" aria-labelledby="server-pets-title">
        <div class="px-feature px-companions"><picture class="px-scene"><source media="(max-width:700px)" srcset="<?php echo $serverBase; ?>companions-four-v1-960.webp"><img src="<?php echo $serverBase; ?>companions-four-v1.webp" alt="Elyndra, Aurion, Vaerion y Grum" width="1672" height="941" loading="lazy" decoding="async"></picture><div class="px-wrap px-feature-content px-copy-right"><div class="px-copy"><p class="px-kicker">03 / VÍNCULOS DE PANIC</p><h2 id="server-pets-title">Elegí quién<br><em>te acompaña.</em></h2><p>Tres líneas con funciones distintas: defensa, experiencia y Zen, o daño. Grum completa el grupo como recolector sin bonos adicionales.</p><a class="px-link" href="<?php echo __BASE_URL__; ?>info/#vinculos">ABRIR EL BESTIARIO ↗</a></div></div></div>
        <div class="px-wrap px-companion-descriptions">
            <?php foreach($companionRoles as $slug=>$role) { $pet=$companionsBySlug[$slug] ?? null; if(!$pet) continue; ?>
            <details class="px-detail"><summary><span><small><?php echo $serverEscape($role[0]); ?></small><strong><?php echo $serverEscape($pet['name']); ?></strong></span><b aria-hidden="true">+</b></summary><div class="px-detail-content"><p class="px-pet-lineage"><?php echo $serverEscape($role[1]); ?></p><ul><?php foreach($pet['stats'] as $stat) { ?><li><?php echo $serverEscape($stat); ?></li><?php } ?></ul><p><?php echo $serverEscape($pet['quote']); ?></p><a class="px-link" href="<?php echo __BASE_URL__.'info/#vinculo-'.$slug; ?>">HISTORIA, OBTENCIÓN Y EVOLUCIONES ↗</a></div></details>
            <?php } ?>
            <p class="px-note">Los bonos mostrados corresponden a la criatura nombrada. Las etapas anteriores tienen sus propios valores. Consultá materiales, costos y probabilidades en el Atlas.</p>
        </div>
    </section>

    <section class="px-client px-wrap" id="cliente" aria-labelledby="server-client-title">
        <div class="px-client-copy"><p class="px-kicker">04 / CLIENTE RENOVADO</p><h2 id="server-client-title">MU PANIC,<br><em>desde adentro.</em></h2><p>El Hub F11 reúne Personaje, Fractura, Eventos, Mercado, Tienda, Juegos y Ayuda. Consultás los sistemas y entrás a sus funciones desde un mismo lugar.</p><p>El árbol Master, MU Helper, los diálogos de NPC y otras ventanas también recibieron mejoras visuales.</p><a class="px-link" href="<?php echo __BASE_URL__; ?>downloads/">DESCARGAR EL CLIENTE ↗</a></div>
        <figure class="px-client-figure"><div class="px-client-shot"><img src="<?php echo $serverBase; ?>hub-client-20261020.webp" alt="Hub F11 actual de MU PANIC, capturado dentro del juego" width="1920" height="1080" loading="lazy" decoding="async"></div><figcaption>CAPTURA REAL DEL HUB · OCTUBRE 2026</figcaption><a class="px-capture-link" href="<?php echo $serverBase; ?>hub-client-20261020.webp" target="_blank" rel="noopener noreferrer">AMPLIAR CAPTURA ↗</a></figure>
    </section>

    <section class="px-server-section px-wrap" id="mercado" aria-labelledby="server-market-title"><div class="px-section-heading"><p class="px-kicker">05 / MERCADO VESSARA</p><h2 id="server-market-title">Conseguí.<br>Comerciá.<br><em>Progresá.</em></h2></div><div class="px-prose"><p>Publicá objetos y comprá lo que otros jugadores consiguieron. Vessara conecta la actividad del juego con un mercado que también podés consultar desde la web.</p><p>Julia, en Lorencia, es el acceso al mercado dentro del juego. Revisá las monedas admitidas, las comisiones y las condiciones de publicación antes de vender.</p><p>Las mascotas y monturas exclusivas tienen restricciones de comercio. Las joyas se publican en lotes de cinco unidades.</p><div class="px-actions"><a class="px-link" href="<?php echo __BASE_URL__; ?>market/">ABRIR MERCADO ↗</a><a class="px-link" href="<?php echo __BASE_URL__; ?>info/#vessara">LEER LA GUÍA ↗</a></div></div></section>

    <section class="px-server-section px-wrap" id="eventos" aria-labelledby="server-events-title"><div class="px-section-heading"><p class="px-kicker">06 / EVENTOS Y COMBATE</p><h2 id="server-events-title">Siempre hay<br><em>otro desafío.</em></h2></div><div class="px-prose"><p>Blood Castle, Devil Square, Chaos Castle, invasiones y otros eventos clásicos conviven con Survivor y arenas por clase.</p><p>Survivor y las arenas se organizan de forma manual: se anuncian cuando el staff abre la convocatoria. Los eventos con horario tienen su próxima apertura en el calendario.</p><p>El Atlas reúne requisitos, entradas, mapas y recompensas para que puedas prepararte.</p><a class="px-link" href="<?php echo __BASE_URL__; ?>info/#eventos">VER EVENTOS Y CALENDARIO ↗</a></div></section>

    <section class="px-games px-wrap" id="minijuegos" aria-labelledby="server-games-title"><div class="px-copy"><p class="px-kicker">07 / SALA DE JUEGOS</p><h2 id="server-games-title">Ocho maneras<br><em>de cambiar el ritmo.</em></h2><p>Entrá a Juegos desde F11. Las partidas usan Eryns; cada juego muestra sus opciones y condiciones dentro del cliente.</p><p>Los minijuegos son una actividad adicional a la progresión, los eventos y el combate.</p></div><figure class="px-games-figure"><div class="px-games-shot"><img src="<?php echo $serverBase; ?>games-client-20261020.webp" alt="Sala actual con Ruleta, Tragamonedas, Mayor o Menor, Vuelo del Dragón, Lluvia de Joyas, Tesoro de Kalima, Torre del Caos y Carrera" width="1920" height="1080" loading="lazy" decoding="async"></div><figcaption>LOS OCHO JUEGOS ACTUALES · CAPTURA REAL</figcaption><a class="px-capture-link" href="<?php echo $serverBase; ?>games-client-20261020.webp" target="_blank" rel="noopener noreferrer">AMPLIAR CAPTURA ↗</a></figure><ol class="px-games-index"><?php foreach($games as $game) { ?><li><strong><?php echo $serverEscape($game[0]); ?></strong><span><?php echo $serverEscape($game[1]); ?></span></li><?php } ?></ol></section>

    <section class="px-server-section px-wrap" id="cuenta" aria-labelledby="server-account-title"><div class="px-section-heading"><p class="px-kicker">08 / TU CUENTA</p><h2 id="server-account-title">Eryns y<br><em>VIP MU PANIC.</em></h2></div><div class="px-prose"><p>Eryns es la moneda propia de MU PANIC. Desde tu cuenta podés consultar las recargas y las opciones disponibles.</p><p>VIP MU PANIC reúne la membresía del servidor. Su precio, duración y beneficios vigentes se muestran en el panel antes de comprar.</p><a class="px-link" href="<?php echo __BASE_URL__; ?>usercp/vip/">CONSULTAR VIP ↗</a></div></section>
    <section class="px-final px-wrap"><p class="px-kicker">CONOCÉ EL MUNDO. ENTRÁ AL JUEGO.</p><h2>Esto es <em>MU PANIC.</em></h2><div class="px-actions"><a class="px-button" href="<?php echo __BASE_URL__; ?>register/">CREAR CUENTA ↗</a><a class="px-link" href="<?php echo __BASE_URL__; ?>downloads/">DESCARGAR ↓</a><a class="px-link" href="<?php echo __BASE_URL__; ?>info/">EXPLORAR EL ATLAS ↗</a></div></section>
</div>
