<?php if(!defined('access') or !access) die(); ?>
<section id="inicio" class="wiki-section atlas-home" aria-labelledby="atlas-home-title">
    <span class="eyebrow">ATLAS PANIC / TU GUÍA PARA JUGAR</span>
    <h2 id="atlas-home-title">¿Qué querés hacer hoy?</h2>
    <p>Elegí tu objetivo. Te ayudamos a encontrar un lugar para entrenar, buscar un objeto o dar tus primeros pasos.</p>
    <div class="atlas-home-grid">
        <a class="atlas-home-card" href="#progresion">
            <div class="atlas-home-art"><img src="<?php echo __BASE_URL__; ?>templates/mupanic/img/atlas/maps/0.webp" alt="" width="320" height="170"></div>
            <div><span class="atlas-purpose">MAPAS Y SPOTS</span><h3>Quiero subir de nivel</h3><p>Explorá un mapa y elegí una zona con monstruos para entrenar.</p><strong>Elegir un mapa →</strong></div>
        </a>
        <a class="atlas-home-card" href="#buscar">
            <div class="atlas-home-art atlas-home-creatures"><?php foreach([2,3,19] as $homeMob) { if(isset($atlasAssets['monsters'][(string)$homeMob])) { ?><img src="<?php echo __BASE_URL__; ?>templates/mupanic/<?php echo panicWikiEscape($atlasAssets['monsters'][(string)$homeMob]['file']); ?>" alt="" width="110" height="150"><?php } } ?></div>
            <div><span class="atlas-purpose">OBJETOS Y MONSTRUOS</span><h3>Quiero encontrar algo</h3><p>Buscá un objeto o un monstruo y descubrí dónde encontrarlo.</p><strong>Abrir el buscador →</strong></div>
        </a>
        <a class="atlas-home-card" href="#primeros-pasos">
            <div class="atlas-home-art"><img src="<?php echo __BASE_URL__; ?>templates/mupanic/img/atlas/maps/3.webp" alt="" width="320" height="170"></div>
            <div><span class="atlas-purpose">PARA NUEVOS JUGADORES</span><h3>Estoy empezando</h3><p>Conocé lo básico antes de salir a combatir y mejorar tu personaje.</p><strong>Ver los primeros pasos →</strong></div>
        </a>
    </div>
    <div class="atlas-basics"><h3>Tres palabras que vas a ver</h3><dl><div><dt>Monstruo o mob</dt><dd>Un enemigo del juego. Al derrotarlo ganás experiencia y puede dejar objetos.</dd></div><div><dt>Spot</dt><dd>Una zona donde reaparecen varios monstruos. En los mapas los marcamos con números.</dd></div><div><dt>Drop</dt><dd>Un objeto que puede dejar un monstruo cuando lo derrotás. No siempre sale.</dd></div></dl></div>
    <a class="atlas-home-secondary" href="#recompensas">Consultar bosses, cajas y recompensas →</a>
</section>
