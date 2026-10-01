<?php if(!defined('access') or !access) die();
$art = $atlasAssets['maps'][(string)$map['id']] ?? null;
$spotPanels = $map['spots'];
$spotPanels[] = ['x'=>null, 'y'=>null, 'monsters'=>$map['monsters']];
?>
<div class="atlas-explorer" data-atlas-explorer="<?php echo $map['id']; ?>">
    <div class="atlas-terrain-column">
        <div class="atlas-map-heading"><span>EXPLORÁ EL MAPA</span><strong><?php echo count($map['spots']); ?> spots</strong><button type="button" class="atlas-zoom" data-atlas-zoom aria-pressed="false">Acercar +</button></div>
        <div class="atlas-map-window"><div class="atlas-terrain <?php echo $art ? '' : 'atlas-terrain-empty'; ?>">
            <?php if($art) { ?><img loading="lazy" width="<?php echo $art['width']; ?>" height="<?php echo $art['height']; ?>" src="<?php echo __BASE_URL__; ?>templates/mupanic/<?php echo panicWikiEscape($art['file']); ?>" alt="Terreno de <?php echo panicWikiEscape($map['name']); ?>"><?php } ?>
            <?php foreach($map['spots'] as $i=>$spot) { ?><a class="atlas-pin" href="#spot-<?php echo $map['id'].'-'.$i; ?>" data-atlas-select="<?php echo $i; ?>" style="left:<?php echo round(100*$spot['x']/256,4); ?>%;top:<?php echo round(100*(255-$spot['y'])/256,4); ?>%" aria-label="Spot <?php echo $i+1; ?>: X <?php echo $spot['x']; ?>, Y <?php echo $spot['y']; ?>" title="Spot <?php echo $i+1; ?> · <?php echo $spot['x'].' / '.$spot['y']; ?>"><?php echo $i+1; ?></a><?php } ?>
        </div></div>
        <p class="atlas-map-caption"><?php echo $art ? 'Puntos proyectados desde las coordenadas del servidor. Alineación con el terreno pendiente de comprobar en juego.' : 'Plano de coordenadas. La imagen de este mapa todavía no está disponible.'; ?> Los puntos representan zonas de aparición, no mobs en vivo.</p>
        <details class="atlas-travel"><summary>Cómo llegar a <?php echo panicWikiEscape($map['name']); ?></summary><?php if(count($map['moves'])) { foreach($map['moves'] as $move) { ?><p><strong><?php echo panicWikiEscape($move['name']); ?></strong> · nivel <?php echo $move['level']; ?> · <?php echo number_format($move['zen'],0,',','.'); ?> Zen</p><?php } ?><small>Requisitos del menú de traslado; pueden existir condiciones adicionales.</small><?php } else { ?><p>Sin traslado directo identificado. Revisá el acceso dentro del juego.</p><?php } ?></details>
    </div>
    <div class="atlas-inspector">
        <div class="atlas-map-heading"><span>ELEGÍ UN SPOT</span><a href="#drops" data-map-drops="<?php echo $map['id']; ?>">Drops del mapa ↗</a></div>
        <div class="atlas-spot-picker" aria-label="Spots de <?php echo panicWikiEscape($map['name']); ?>">
            <?php foreach($map['spots'] as $i=>$spot) { ?><a href="#spot-<?php echo $map['id'].'-'.$i; ?>" data-atlas-select="<?php echo $i; ?>"><b><?php echo str_pad($i+1,2,'0',STR_PAD_LEFT); ?></b><span><?php echo implode(' + ',array_map(function($mob) { return panicWikiEscape($mob['name']); },$spot['monsters'])); ?><small>X <?php echo $spot['x']; ?> · Y <?php echo $spot['y']; ?> · <?php echo array_sum(array_column($spot['monsters'],'quantity')); ?> mobs</small></span></a><?php } ?>
            <a href="#mob-list-<?php echo $map['id']; ?>" data-atlas-select="all"><b>↗</b><span>Todos los mobs del mapa<small>Incluye monstruos fuera de los spots</small></span></a>
        </div>
        <label class="atlas-account">Ver tasas para <select data-atlas-account><?php foreach($wiki['accounts'] as $a=>$account) { ?><option value="<?php echo $a; ?>"><?php echo panicWikiEscape($account['name']); ?></option><?php } ?></select></label>
        <?php foreach($spotPanels as $i=>$spot) { $all = $i === count($map['spots']); ?>
        <section class="atlas-spot-panel" id="<?php echo $all ? 'mob-list-'.$map['id'] : 'spot-'.$map['id'].'-'.$i; ?>" data-atlas-panel="<?php echo $all ? 'all' : $i; ?>">
            <header><span class="eyebrow"><?php echo $all ? 'POBLACIÓN DEL MAPA' : 'SPOT '.str_pad($i+1,2,'0',STR_PAD_LEFT); ?></span><h3><?php echo $all ? 'Todos los monstruos' : 'X '.$spot['x'].' · Y '.$spot['y']; ?></h3><p><?php echo $all ? 'Consultá cada monstruo para ver sus drops.' : 'Estos son los mobs que aparecen en esta zona.'; ?></p></header>
            <?php if(!$spot['monsters']) { ?><p>Sin población fija identificada. Los eventos pueden generar monstruos por separado.</p><?php } ?>
            <?php foreach($spot['monsters'] as $monster) {
                $rules = array_filter($wiki['drops'],function($drop) use($map,$monster) {
                    $mode = $map['equipmentDrop']['dropMode'] ?? 'unknown';
                    return ($drop['map'] === -1 || $drop['map'] === $map['id']) && ($mode === '*' || $mode === 'unknown' || ((int)$mode & 4)) && $monster['level'] >= $drop['min'] && $monster['level'] <= $drop['max'] && ($drop['monster'] === -1 || $drop['monster'] === $monster['id']);
                });
                $bags = array_filter($wiki['eventBags'] ?? [],function($bag) use($monster) { return $bag['monster'] === $monster['id']; });
            ?>
            <article class="atlas-mob" <?php if($all) { ?>id="mob-<?php echo $map['id'].'-'.$monster['id']; ?>"<?php } ?>>
                <div class="atlas-mob-heading"><div><h4><?php echo panicWikiEscape($monster['name']); ?></h4><span>Nivel <?php echo $monster['level']; ?><?php if(isset($monster['quantity'])) { echo ' · '.$monster['quantity'].' en el spot'; } ?></span></div><span class="atlas-mob-level">Lv. <?php echo $monster['level']; ?></span></div>
                <div class="atlas-mob-facts"><span>Vida <strong><?php echo number_format($monster['life'],0,',','.'); ?></strong></span><span>Respawn base <strong><?php echo $monster['respawnSeconds']; ?> s</strong></span></div>
                <details class="atlas-loot" open><summary>Drops de objetos <span><?php echo count($rules); ?></span></summary>
                    <?php if(!$rules) { ?><p>Sin reglas específicas identificadas. Puede tener drops comunes u otras recompensas.</p><?php } else { ?><ul><?php foreach($rules as $drop) { ?><li><span><?php echo panicWikiEscape($drop['name']); ?><small><?php echo $drop['map'] === -1 ? 'Regla general' : 'Regla de '.panicWikiEscape($map['name']); ?></small></span><strong data-atlas-rates="<?php echo panicWikiEscape(json_encode($drop['rates'])); ?>"><?php echo panicWikiRate($drop['rates'][0]); ?></strong></li><?php } ?></ul><?php } ?>
                    <?php foreach($bags as $bag) { ?><a class="atlas-special-loot" href="#recompensa-<?php echo $bag['id']; ?>">Recompensas especiales · <?php echo panicWikiEscape($bag['name']); ?> ↗</a><?php } ?>
                </details>
                <details class="atlas-combat"><summary>Estadísticas de combate</summary><dl><?php foreach(['damageMin'=>'Daño mínimo','damageMax'=>'Daño máximo','defense'=>'Defensa','attackRate'=>'Attack Rate','defenseRate'=>'Defense Rate'] as $key=>$label) { if(isset($monster[$key])) { ?><dt><?php echo $label; ?></dt><dd><?php echo number_format($monster[$key],0,',','.'); ?></dd><?php } } ?></dl></details>
            </article>
            <?php } ?>
            <p class="atlas-loot-note">Las tasas corresponden a reglas configuradas, no a una probabilidad final por muerte. El drop común puede incluir otros objetos.</p>
        </section>
        <?php } ?>
    </div>
</div>
