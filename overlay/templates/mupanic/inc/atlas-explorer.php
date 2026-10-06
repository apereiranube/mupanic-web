<?php if(!defined('access') or !access) die();
$art = $atlasAssets['maps'][(string)$map['id']] ?? null;
$dropLabel = function($name) { return preg_replace('/^(?:(?:REGIONAL|GLOBAL RARO) V[0-9.]+ INTEGRAL|PILAR1 RAKLION MIX) (?=Jewel of )/', '', $name); };
$spotPanels = $map['spots'];
$spotPanels[] = ['x'=>null, 'y'=>null, 'monsters'=>$map['monsters']];
?>
<div class="atlas-explorer" data-atlas-explorer="<?php echo $map['id']; ?>">
    <div class="atlas-terrain-column">
        <div class="atlas-map-heading"><span>PLANO DEL CLIENTE</span><strong><?php echo count($map['spots']); ?> spots</strong><button type="button" class="atlas-zoom" data-atlas-zoom aria-pressed="false">Acercar +</button></div>
        <div class="atlas-map-window"><div class="atlas-terrain <?php echo $art ? '' : 'atlas-terrain-empty'; ?>">
            <?php if(!$art) { ?><span class="atlas-coordinates-label">Plano de coordenadas</span><?php } ?>
            <?php if($art) { ?><img loading="lazy" width="<?php echo $art['width']; ?>" height="<?php echo $art['height']; ?>" src="<?php echo __BASE_URL__; ?>templates/mupanic/<?php echo panicWikiEscape($art['file']); ?>" alt="Terreno de <?php echo panicWikiEscape($map['name']); ?>"><?php } ?>
            <?php foreach($map['spots'] as $i=>$spot) { ?><a class="atlas-pin" href="#spot-<?php echo $map['id'].'-'.$i; ?>" data-atlas-select="<?php echo $i; ?>" style="left:<?php echo round(100*$spot['x']/256,4); ?>%;top:<?php echo round(100*(255-$spot['y'])/256,4); ?>%" aria-label="Spot <?php echo $i+1; ?>: X <?php echo $spot['x']; ?>, Y <?php echo $spot['y']; ?>" title="Spot <?php echo $i+1; ?> · <?php echo $spot['x'].' / '.$spot['y']; ?>"><?php echo $i+1; ?></a><?php } ?>
        </div></div>
        <div class="atlas-map-legend"><i aria-hidden="true"></i><span><?php echo count($map['spots']) ? 'Spots fijos · elegí un número para explorar' : 'Sin spots fijos registrados'; ?></span></div>
        <p class="atlas-map-caption"><?php echo $art ? 'Coordenadas del servidor. Alineación sobre el terreno pendiente de verificar en juego.' : 'Plano de coordenadas. La imagen de este mapa todavía no está disponible.'; ?> No muestra mobs en vivo.</p>
        <details class="atlas-travel"><summary>Cómo llegar a <?php echo panicWikiEscape($map['name']); ?></summary><?php if(count($map['moves'])) { foreach($map['moves'] as $move) { ?><p><strong><?php echo panicWikiEscape($move['name']); ?></strong> · nivel <?php echo $move['level']; ?> · <?php echo number_format($move['zen'],0,',','.'); ?> Zen</p><?php } ?><small>Requisitos del menú de traslado; pueden existir condiciones adicionales.</small><?php } else { ?><p>Sin traslado directo identificado. Revisá el acceso dentro del juego.</p><?php } ?></details>
    </div>
    <div class="atlas-inspector">
        <div class="atlas-map-heading"><span>TU ZONA DE EXPLORACIÓN</span><a href="#drops" data-map-drops="<?php echo $map['id']; ?>">Drops del mapa ↗</a></div>
        <div class="atlas-zone-heading"><strong>Elegí un spot</strong><span><?php echo count($map['spots']) ? 'El número coincide con el plano.' : 'Explorá la población del mapa.'; ?></span></div>
        <nav class="atlas-spot-picker" aria-label="Spots de <?php echo panicWikiEscape($map['name']); ?>">
            <?php foreach($map['spots'] as $i=>$spot) { $spotNames=implode(' + ',array_column($spot['monsters'],'name')); ?>
            <a href="#spot-<?php echo $map['id'].'-'.$i; ?>" data-atlas-select="<?php echo $i; ?>" aria-label="<?php echo panicWikiEscape('Spot '.($i+1).' · '.$spotNames.' · X '.$spot['x'].' Y '.$spot['y']); ?>" title="<?php echo panicWikiEscape($spotNames); ?>"><b><?php echo str_pad($i+1,2,'0',STR_PAD_LEFT); ?></b><small><?php echo $spot['x'].' / '.$spot['y']; ?></small></a>
            <?php } ?>
            <a class="atlas-all-mobs" href="#mob-list-<?php echo $map['id']; ?>" data-atlas-select="all"><b>Todos los mobs</b><small>Dentro y fuera de los spots ↗</small></a>
        </nav>
        <label class="atlas-account">Ver tasas para <select data-atlas-account><?php foreach($wiki['accounts'] as $a=>$account) { ?><option value="<?php echo $a; ?>"><?php echo panicWikiEscape($account['name']); ?></option><?php } ?></select></label>
        <?php foreach($spotPanels as $i=>$spot) { $all = $i === count($map['spots']); $zoneLevels=array_column($spot['monsters'],'level'); ?>
        <section class="atlas-spot-panel" id="<?php echo $all ? 'mob-list-'.$map['id'] : 'spot-'.$map['id'].'-'.$i; ?>" data-atlas-panel="<?php echo $all ? 'all' : $i; ?>">
            <header class="atlas-zone-profile"><div><span class="eyebrow"><?php echo $all ? 'POBLACIÓN DEL MAPA' : 'SPOT '.str_pad($i+1,2,'0',STR_PAD_LEFT).' / '.panicWikiEscape($map['name']); ?></span><h3><?php echo $all ? 'Todos los monstruos' : 'X '.$spot['x'].' <span>·</span> Y '.$spot['y']; ?></h3></div><dl><div><dt>Nivel de mobs</dt><dd><?php echo $zoneLevels ? min($zoneLevels).'–'.max($zoneLevels) : 'Sin registro'; ?></dd></div><div><dt><?php echo $all ? 'Tipos de mobs' : 'Mobs en el spot'; ?></dt><dd><?php echo $all ? count($spot['monsters']) : array_sum(array_column($spot['monsters'],'quantity')); ?></dd></div></dl></header>
            <?php if(!$spot['monsters']) { ?><p>Sin población fija identificada. Los eventos pueden generar monstruos por separado.</p><?php } ?>
            <?php if(count($spot['monsters'])>1) { if($all) { ?><label class="atlas-mob-choice">Elegí un monstruo<select data-atlas-mob-picker><?php foreach($spot['monsters'] as $mob) { ?><option value="<?php echo $mob['id']; ?>"><?php echo panicWikiEscape($mob['name']); ?> · Lv. <?php echo $mob['level']; ?></option><?php } ?></select></label><?php } else { ?>
            <nav class="atlas-mob-picker" aria-label="Monstruos de este spot"><?php foreach($spot['monsters'] as $mob) { $thumb=$atlasAssets['monsters'][(string)$mob['id']] ?? null; ?><button type="button" data-atlas-mob-select="<?php echo $mob['id']; ?>" aria-pressed="false" aria-controls="spot-mob-<?php echo $map['id'].'-'.$i.'-'.$mob['id']; ?>"><?php if($thumb) { ?><img loading="lazy" decoding="async" width="44" height="48" src="<?php echo __PATH_TEMPLATE__.panicWikiEscape($thumb['file']); ?>" alt=""><?php } ?><span><?php echo panicWikiEscape($mob['name']); ?><small>Lv. <?php echo $mob['level']; ?></small></span></button><?php } ?></nav>
            <?php } } ?>
            <?php foreach($spot['monsters'] as $monster) {
                $rules = array_filter($wiki['drops'],function($drop) use($map,$monster) {
                    $mode = $map['equipmentDrop']['dropMode'] ?? 'unknown';
                    return ($drop['map'] === -1 || $drop['map'] === $map['id']) && ($mode === '*' || $mode === 'unknown' || ((int)$mode & 4)) && $monster['level'] >= $drop['min'] && $monster['level'] <= $drop['max'] && ($drop['monster'] === -1 || $drop['monster'] === $monster['id']);
                });
                $ruleGroups = ['Drops exclusivos de este mob'=>array_filter($rules,function($d) { return $d['monster'] !== -1; }), 'Drops del mapa'=>array_filter($rules,function($d) { return $d['monster'] === -1 && $d['map'] !== -1; }), 'Drops generales'=>array_filter($rules,function($d) { return $d['monster'] === -1 && $d['map'] === -1; })];
                $priorityRules=[]; foreach($ruleGroups as $groupRules) { $priorityRules=array_merge($priorityRules,$groupRules); } $previewDrops=[]; $previewSeen=[]; foreach($priorityRules as $drop) { $key=$drop['id'].':'.($drop['variant'] ?? 0); if(isset($previewSeen[$key])) continue; $previewSeen[$key]=true; $previewDrops[]=$drop; if(count($previewDrops)===3) break; }
                $portrait = $atlasAssets['monsters'][(string)$monster['id']] ?? null;
                $bags = array_filter($wiki['eventBags'] ?? [],function($bag) use($monster) { return $bag['monster'] === $monster['id']; });
            ?>
            <article class="atlas-mob" data-atlas-mob="<?php echo $monster['id']; ?>" id="<?php echo $all ? 'mob-'.$map['id'].'-'.$monster['id'] : 'spot-mob-'.$map['id'].'-'.$i.'-'.$monster['id']; ?>">
                <div class="atlas-mob-heading"><?php if($portrait) { ?><img class="atlas-mob-portrait" loading="lazy" decoding="async" width="96" height="96" src="<?php echo __BASE_URL__; ?>templates/mupanic/<?php echo panicWikiEscape($portrait['file']); ?>" alt="<?php echo panicWikiEscape($monster['name']); ?>"><?php } ?><div class="atlas-mob-name"><h4><?php echo panicWikiEscape($monster['name']); ?></h4><span class="atlas-mob-tier">NIVEL <?php echo $monster['level']; ?></span><?php if(isset($monster['quantity'])) { ?><span class="atlas-mob-population"><?php echo $monster['quantity']; ?> en esta zona</span><?php } ?></div></div>
                <div class="atlas-mob-facts"><span>Vida <strong><?php echo number_format($monster['life'],0,',','.'); ?></strong></span><span>Reaparece cada <strong><?php echo $monster['respawnSeconds']; ?> s</strong></span></div>
                <div class="atlas-drop-preview"><span class="atlas-drop-preview-label">DROPS CONFIGURADOS</span><?php if($previewDrops) { ?><ul><?php foreach($previewDrops as $drop) { ?><li title="<?php echo panicWikiEscape($drop['name']); ?>"><?php echo panicWikiEscape($dropLabel($drop['name'])); ?></li><?php } ?></ul><?php } else { ?><p>Sin reglas específicas identificadas. Puede dejar drops comunes.</p><?php } ?></div>
                <?php foreach($bags as $bag) { ?><a class="atlas-special-loot" href="#recompensa-<?php echo $bag['id']; ?>"><span>RECOMPENSA ESPECIAL</span><?php echo panicWikiEscape($bag['name']); ?> ↗</a><?php } ?>
                <details class="atlas-loot"><summary>Ver lista de drops <span><?php echo count($rules); ?></span></summary>
                    <?php if(!$rules) { ?><p>Sin reglas específicas identificadas. Puede tener drops comunes u otras recompensas.</p><?php } else { foreach($ruleGroups as $groupName=>$groupRules) { if(!$groupRules) continue; ?><h5 class="atlas-drop-group"><?php echo panicWikiEscape($groupName); ?></h5><ul><?php foreach($groupRules as $drop) { ?><li><span><?php echo panicWikiEscape($drop['name']); ?><small><?php echo $drop['map'] === -1 ? 'Regla general' : 'Regla de '.panicWikiEscape($map['name']); ?></small></span><strong data-atlas-rates="<?php echo panicWikiEscape(json_encode($drop['rates'])); ?>"><?php echo panicWikiRate($drop['rates'][0]); ?></strong></li><?php } ?></ul><?php } } ?>
                </details>
                <details class="atlas-combat"><summary>Estadísticas de combate</summary><dl><?php foreach(['damageMin'=>'Daño mínimo','damageMax'=>'Daño máximo','defense'=>'Defensa','attackRate'=>'Attack Rate','defenseRate'=>'Defense Rate'] as $key=>$label) { if(isset($monster[$key])) { ?><dt><?php echo $label; ?></dt><dd><?php echo number_format($monster[$key],0,',','.'); ?></dd><?php } } ?></dl></details>
            </article>
            <?php } ?>
            <p class="atlas-loot-note">Las tasas corresponden a reglas configuradas, no a una probabilidad final por muerte. El drop común puede incluir otros objetos.</p>
        </section>
        <?php } ?>
    </div>
</div>
