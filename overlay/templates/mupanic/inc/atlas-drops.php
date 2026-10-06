<?php
if(!defined('access') or !access) die();
require_once(__DIR__.'/atlas-drop-model.php');
$dropGroups = panicAtlasDropGroups($wiki['drops']);
$dropImages = json_decode(file_get_contents(__DIR__.'/atlas-drop-items.json'), true);
$dropCategories = ['all'=>'Todos','joyas'=>'Joyas','alas'=>'Alas','sockets'=>'Sockets','entradas'=>'Entradas','materiales'=>'Materiales','cajas'=>'Cajas'];
$dropCategoryOrder = ['joyas'=>0,'alas'=>1,'sockets'=>2,'entradas'=>3,'materiales'=>4,'cajas'=>5];
uasort($dropGroups, function($a,$b) use($dropCategoryOrder) { return $dropCategoryOrder[$a['category']] <=> $dropCategoryOrder[$b['category']]; });
$dropImage = function($group) use($dropImages) {
    $art = $dropImages[$group['key']] ?? null;
    if(!$art) return '<span class="atlas-drop-art" aria-hidden="true">◇</span>';
    return '<span class="atlas-drop-art" aria-hidden="true"><img loading="eager" decoding="async" width="'.$art['width'].'" height="'.$art['height'].'" src="'.__PATH_TEMPLATE__.panicWikiEscape($art['file']).'" alt=""></span>';
};
?>
<section id="drops" class="wiki-section" aria-labelledby="atlas-drops-title">
    <header class="atlas-drops-intro"><div><span class="eyebrow">TU PRÓXIMO OBJETIVO</span><h2 id="atlas-drops-title">Encontrá tu<br><em>recompensa.</em></h2><p>Elegí un objeto. Explorá sus drops, monstruos y spots.</p></div><div class="atlas-drop-count"><strong><?php echo count($dropGroups); ?></strong><span>objetos y familias<br><?php echo count($wiki['drops']); ?> reglas del servidor</span></div></header>
    <div class="atlas-drop-toolbar">
        <label class="wiki-control atlas-drop-search">¿Qué querés conseguir?<input type="search" data-drop-filter placeholder="Chaos, Feather, Blood Bone…" autocomplete="off"></label>
        <label class="wiki-control">Mapa<select data-drop-map><option value="">Todos los mapas</option><?php foreach($wiki['maps'] as $map) { ?><option value="<?php echo $map['id']; ?>"><?php echo panicWikiEscape($map['name']); ?></option><?php } ?></select></label>
        <label class="wiki-control">Monstruo<select data-drop-monster><option value="">Todos los monstruos</option></select></label>
        <label class="wiki-control atlas-drop-account">Tu cuenta<select data-drop-account><?php foreach($wiki['accounts'] as $i=>$account) { ?><option value="<?php echo $i; ?>"><?php echo panicWikiEscape($account['name']); ?></option><?php } ?></select></label>
        <div class="atlas-drop-categories" role="group" aria-label="Categorías de objetos"><?php foreach($dropCategories as $key=>$label) { ?><button type="button" data-drop-category="<?php echo $key; ?>" aria-pressed="<?php echo $key === 'all' ? 'true' : 'false'; ?>"><span aria-hidden="true"><?php echo ['all'=>'◇','joyas'=>'◆','alas'=>'↗','sockets'=>'◉','entradas'=>'✧','materiales'=>'⚒','cajas'=>'▣'][$key]; ?></span><?php echo $label; ?></button><?php } ?></div>
        <div class="atlas-drop-toolbar-foot"><p data-drop-status role="status" aria-live="polite"></p><button type="button" data-drop-reset>Limpiar filtros ↺</button></div>
    </div>
    <details class="atlas-drop-help"><summary>Cómo interpretar los drops y las tasas</summary><p>El nivel indicado es el del <strong>monstruo</strong>, no el de tu personaje. Cada porcentaje pertenece a una regla independiente: no se suman tasas ni se garantiza un objeto cada cierta cantidad de muertes. Deben cumplirse las condiciones de mapa y monstruo.</p><p>Los mobs y spots compatibles se calculan desde la población fija publicada. Los eventos, cajas y combinaciones tienen otras reglas.</p></details>
    <div class="atlas-drop-empty" data-drop-empty hidden><span class="eyebrow">PROBÁ OTRO OBJETIVO</span><h3>No encontramos objetos.</h3><p>Cambiá el nombre, el mapa o el monstruo para ampliar la búsqueda.</p><button type="button" data-drop-reset>Ver todos los objetos →</button></div>
    <div class="atlas-drop-catalogue" data-drop-catalogue>
    <?php foreach($dropGroups as $group) { $rules=$group['rules']; $specific=[]; $global=false; foreach($rules as $rule) { if($rule['map'] === -1) $global=true; else $specific[$rule['map']]=$mapNames[$rule['map']] ?? 'Mapa '.$rule['map']; } ?>
        <a class="atlas-drop-card" href="#drop-item-<?php echo $group['key']; ?>" data-drop-card="<?php echo $group['key']; ?>" data-drop-kind="<?php echo $group['category']; ?>">
            <div class="atlas-drop-card-top"><?php echo $dropImage($group); ?><div><span class="eyebrow"><?php echo $dropCategories[$group['category']]; ?></span><h3><?php echo panicWikiEscape($group['name']); ?></h3></div></div>
            <div class="atlas-drop-tags"><?php foreach(array_slice($specific,0,2) as $name) { ?><span><?php echo panicWikiEscape($name); ?></span><?php } ?><?php if(count($specific)>2) { ?><span>+<?php echo count($specific)-2; ?> mapas</span><?php } ?><?php if($global) { ?><span>Drop general</span><?php } ?></div>
            <div class="atlas-drop-card-foot"><small><?php echo count($rules); ?> <?php echo $group['numbered'] ? 'niveles' : (count($rules) === 1 ? 'regla' : 'reglas'); ?></small><span>Dónde conseguirlo →</span></div>
        </a>
    <?php } ?>
    </div>
    <div class="atlas-drop-details">
    <?php foreach($dropGroups as $group) { $rules=$group['rules']; ?>
        <section class="atlas-drop-detail" id="drop-item-<?php echo $group['key']; ?>" data-drop-item="<?php echo $group['key']; ?>" aria-labelledby="drop-title-<?php echo $group['key']; ?>">
            <div class="atlas-drop-breadcrumb"><a href="#drops" data-drop-back>← Volver a los objetos</a><span>DROP DE MONSTRUOS</span></div>
            <header class="atlas-drop-object"><?php echo $dropImage($group); ?><div><span class="eyebrow"><?php echo $dropCategories[$group['category']]; ?></span><h3 id="drop-title-<?php echo $group['key']; ?>" tabindex="-1"><?php echo panicWikiEscape($group['name']); ?></h3><p>Reglas independientes · tasas para <strong data-account-name>Free</strong></p></div>
            <?php if($group['numbered']) { ?><label>Elegí el nivel<select data-drop-variant><option value="">Todos los niveles</option><?php foreach($rules as $rule) { ?><option value="<?php echo $rule['variant']; ?>">+<?php echo $rule['variant']; ?></option><?php } ?></select></label><?php } ?></header>
            <div class="atlas-drop-rules">
            <?php foreach($rules as $index=>$drop) { $mapArt=$territoryArt[(string)$drop['map']] ?? null; ?>
                <article class="atlas-drop-rule" id="drop-<?php echo $index; ?>" data-drop-row="<?php echo $index; ?>" data-wiki-search>
                    <header><span class="atlas-drop-location-art" aria-hidden="true"><?php if($mapArt) { ?><img loading="lazy" width="80" height="64" src="<?php echo __PATH_TEMPLATE__.panicWikiEscape($mapArt['thumbnail'] ?? $mapArt['file']); ?>" alt=""><?php } else { ?>◇<?php } ?></span><div><span class="eyebrow"><?php echo $drop['map'] === -1 ? 'REGLA GENERAL' : 'REGLA DEL MAPA'; ?><?php if($group['numbered']) { echo ' · +'.$drop['variant']; } ?></span><h4><?php if(isset($mapNames[$drop['map']])) { ?><a href="#mapa-<?php echo $drop['map']; ?>"><?php echo panicWikiEscape($mapNames[$drop['map']]); ?> ↗</a><?php } else { echo $drop['map'] === -1 ? 'Mapas con drop habilitado' : 'Mapa '.$drop['map']; } ?></h4></div><div class="atlas-drop-rate"><small>Tasa <span data-account-name>Free</span></small><strong data-drop-rate><?php echo panicWikiRate($drop['rates'][0]); ?></strong></div></header>
                    <div class="atlas-drop-conditions"><span>Nivel del monstruo <b><?php echo $drop['min'].'–'.$drop['max']; ?></b></span><?php if($drop['monster'] !== -1) { ?><span>Monstruo <b data-drop-monster-name><?php echo $drop['monster']; ?></b></span><?php } ?><span class="atlas-drop-rule-name"><?php echo panicWikiEscape($drop['name']); ?></span></div>
                    <div class="atlas-drop-rule-actions"><p data-drop-compatible-count>Consultá los monstruos compatibles.</p><button type="button" data-drop-routes="<?php echo $index; ?>" aria-expanded="false" aria-controls="drop-routes-<?php echo $index; ?>">Ver mobs y spots →</button></div>
                    <div class="atlas-drop-routes" id="drop-routes-<?php echo $index; ?>" data-drop-route-panel hidden tabindex="-1"></div>
                </article>
            <?php } ?>
            </div><p class="atlas-drop-detail-note">El drop general y las reglas de cada mapa se muestran por separado. Cada tasa conserva sus propias condiciones.</p>
        </section>
    <?php } ?>
    </div>
    <p class="atlas-drop-credit">Imágenes del juego: MuOnline.Net · MU.LV · Bless · recursos © Webzen.</p>
</section>
