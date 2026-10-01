<?php if(!defined('access') or !access) die(); ?>
<section id="buscar" class="wiki-section atlas-finder" aria-labelledby="atlas-finder-title">
    <span class="eyebrow">ENCONTRÁ TU OBJETIVO</span><h2 id="atlas-finder-title">¿Qué estás buscando?</h2>
    <p>Buscá un objeto para ver cómo conseguirlo, o un mob para encontrar sus mapas y spots.</p>
    <div class="atlas-finder-controls">
        <label class="wiki-control">Nombre del objeto, mob o mapa<input type="search" data-atlas-query placeholder="Por ejemplo: Chaos, Spider o LaCleon" autocomplete="off"></label>
        <label class="wiki-control">Mostrar<select data-atlas-type><option value="">Todo</option><option value="object">Objetos</option><option value="mob">Mobs</option><option value="boss">Mobs con recompensas especiales</option><option value="map">Mapas</option></select></label>
        <label class="wiki-control">Mapa<select data-atlas-map><option value="">Todos los mapas</option><?php foreach($wiki['maps'] as $finderMap) { ?><option value="<?php echo $finderMap['id']; ?>"><?php echo panicWikiEscape($finderMap['name']); ?></option><?php } ?></select></label>
        <label class="wiki-control" data-atlas-level-control hidden>Nivel máximo del mob<input type="number" data-atlas-level min="0" max="10000" placeholder="Sin límite"></label>
    </div>
    <div class="wiki-quick"><button type="button" data-atlas-example="Chaos">Chaos</button><button type="button" data-atlas-example="Bless">Bless</button><button type="button" data-atlas-example="Feather">Plumas</button><button type="button" data-atlas-example="Selupan">Selupan</button><button type="button" data-atlas-clear>Limpiar búsqueda</button></div>
    <p data-atlas-find-status role="status" aria-live="polite"></p>
    <div class="atlas-find-results" data-atlas-find-results></div>
    <button class="atlas-find-more" type="button" data-atlas-find-more hidden>Mostrar más resultados</button>
    <p class="atlas-find-help" data-atlas-map-note hidden>El filtro de mapa muestra población fija y drops compatibles. Para ver cajas y eventos sin ubicación confirmada, elegí «Todos los mapas».</p>
    <p class="atlas-find-help">Que un objeto figure como recompensa significa que puede salir, no que esté garantizado. Abrí el resultado para ver sus condiciones.</p>
    <noscript><p>El buscador necesita JavaScript. Podés consultar los mapas, drops y recompensas en las secciones de la guía.</p></noscript>
</section>
