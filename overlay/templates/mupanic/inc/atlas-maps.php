<?php
if(!defined('access') or !access) die();
$territoryArt = json_decode(file_get_contents(__DIR__.'/atlas-territories.json'), true);
$territorySpotCount = array_sum(array_map(function($m) { return count($m['spots']); }, $wiki['maps']));
?>
<section id="progresion" class="wiki-section" aria-labelledby="atlas-maps-title">
    <header class="atlas-territories-intro">
        <div><span class="eyebrow">LOS TERRITORIOS DE PANIC</span><h2 id="atlas-maps-title">Elegí tu próxima<br><em>frontera.</em></h2><p>Encontrá tu zona. Conocé sus monstruos y drops.</p></div>
        <div class="atlas-territories-facts"><span><strong><?php echo count($wiki['maps']); ?></strong> mapas para explorar</span><span><strong><?php echo $territorySpotCount; ?></strong> spots del servidor</span></div>
    </header>
    <div class="atlas-map-toolbar">
        <label class="wiki-control">Encontrá tu destino<input type="search" data-map-filter placeholder="Buscá un mapa o monstruo…" autocomplete="off"></label>
        <label class="wiki-control">Ordenar por<select data-map-sort><option value="atlas">Orden del Atlas</option><option value="level">Nivel de los mobs</option><option value="name">Nombre del mapa</option></select></label>
        <div class="atlas-map-filters" role="group" aria-label="Filtrar territorios"><button type="button" data-map-scope="all" aria-pressed="true">Todos los mapas</button><button type="button" data-map-scope="spots" aria-pressed="false">Con spots</button><button type="button" data-map-scope="other" aria-pressed="false">Sin spots fijos</button><button type="button" data-map-scope="saved" aria-pressed="false">Guardados</button></div>
        <div class="atlas-map-results"><p data-map-status role="status" aria-live="polite"></p><span>Arte conceptual de cada territorio · planos del cliente en el detalle</span></div>
    </div>
    <div class="atlas-map-empty" data-map-empty hidden><span class="eyebrow">BUSCÁ OTRA FRONTERA</span><h3>No encontramos mapas.</h3><p>Probá otro nombre o guardá tus mapas favoritos desde su detalle.</p><button type="button" data-map-reset>Ver todos los mapas →</button></div>
    <div class="wiki-map-list">
    <?php foreach($wiki['maps'] as $mapIndex=>$map) {
        $mapPreview = $territoryArt[(string)$map['id']] ?? ['file'=>'img/atlas/editorial/atlas-territories.webp','width'=>1536,'height'=>1024];
        $mapLevels = array_column($map['monsters'], 'level');
        $mapSearch = implode(' ', array_merge([$map['name']], $map['aliases'] ?? [], array_column($map['monsters'], 'name')));
    ?>
        <details class="wiki-map atlas-territory" id="mapa-<?php echo $map['id']; ?>" data-map-index="<?php echo $mapIndex; ?>" data-map-name="<?php echo panicWikiEscape($map['name']); ?>" data-map-level="<?php echo $mapLevels ? min($mapLevels) : ''; ?>" data-map-spots="<?php echo count($map['spots']); ?>" data-map-search="<?php echo panicWikiEscape($mapSearch); ?>" data-map-aliases="<?php echo panicWikiEscape(implode(' ', $map['aliases'] ?? [])); ?>" data-wiki-search>
            <summary>
                <span class="atlas-territory-cover"><img class="atlas-map-preview" loading="eager" fetchpriority="<?php echo $mapIndex < 2 ? 'high' : 'low'; ?>" decoding="async" width="<?php echo $mapPreview['width']; ?>" height="<?php echo $mapPreview['height']; ?>" src="<?php echo __PATH_TEMPLATE__.panicWikiEscape($mapPreview['thumbnail'] ?? $mapPreview['file']); ?>"<?php if(isset($mapPreview['thumbnail'])) { ?> srcset="<?php echo __PATH_TEMPLATE__.panicWikiEscape($mapPreview['thumbnail']); ?> 768w, <?php echo __PATH_TEMPLATE__.panicWikiEscape($mapPreview['file']); ?> 1536w" sizes="(max-width: 540px) 92vw, (max-width: 750px) 44vw, (max-width: 1400px) 34vw, 560px"<?php } ?> alt=""><span class="atlas-territory-index" aria-hidden="true">TERRITORIO / <?php echo str_pad($mapIndex+1,2,'0',STR_PAD_LEFT); ?></span><span class="atlas-map-title"><strong><?php echo panicWikiEscape($map['name']); ?></strong></span></span>
                <span class="atlas-territory-stats"><span><small>Spots</small><b><?php echo count($map['spots']); ?></b></span><span><small>Tipos de mobs</small><b><?php echo count($map['monsters']); ?></b></span><span><small>Nivel de mobs</small><b><?php echo $mapLevels ? min($mapLevels).'–'.max($mapLevels) : 'Sin registro'; ?></b></span></span>
                <span class="atlas-map-open-label"><span>Explorar mapa</span><i aria-hidden="true">↗</i></span>
            </summary>
            <div class="wiki-map-body"><div class="wiki-map-actions"><span class="eyebrow">TU EXPEDICIÓN / <?php echo panicWikiEscape($map['name']); ?></span><button type="button" data-save-map="<?php echo $map['id']; ?>" aria-pressed="false">Guardar mapa</button><a href="#mapa-<?php echo $map['id']; ?>">Enlace directo ↗</a><button type="button" data-map-close>Volver a mapas ↑</button></div>
                <?php include(__DIR__.'/atlas-explorer.php'); ?>
            </div>
        </details>
    <?php } ?>
    </div>
    <p class="atlas-territories-note">El nivel indicado corresponde a los monstruos, no al requisito de entrada. Tu equipo y tu party determinan qué zona podés sostener.</p>
</section>
