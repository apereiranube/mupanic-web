<?php if(!defined('access') or !access) die();
// Map bosses have no event activation record. Explain their audited access here,
// without manufacturing an enabled flag or a recurring agenda for them.
$atlasBossEditorial=json_decode(file_get_contents(__DIR__.'/atlas-event-guides.json'),true)['guides'];
$atlasBossGuideIds=[32=>'kundun',33=>'erohim'];
?>
    <?php foreach($rewardBags as $bag) { $portrait = $bag['monster'] >= 0 ? ($atlasAssets['monsters'][(string)$bag['monster']] ?? null) : ($atlasAssets['rewards'][(string)$bag['id']] ?? null); $portrait = $portrait ?? ['file'=>'img/atlas/editorial/atlas-chamber.webp','kind'=>'illustration']; $artKind = $portrait['kind'] ?? 'monster'; ?>
        <details class="wiki-map" id="recompensa-<?php echo $bag['id']; ?>" data-reward-list <?php if($bag['monster'] < 0 && $bag['item'] < 0) { ?>data-event-reward-list hidden<?php } ?> data-reward-type="<?php echo $bag['monster'] >= 0 ? 'boss' : ($bag['item'] >= 0 ? 'box' : 'event'); ?>" data-wiki-search>
            <summary class="atlas-reward-summary"><span class="atlas-reward-art atlas-reward-art--<?php echo panicWikiEscape($artKind); ?>" aria-hidden="true"><img loading="eager" decoding="async" fetchpriority="low" width="<?php echo $portrait['width'] ?? 90; ?>" height="<?php echo $portrait['height'] ?? 112; ?>" src="<?php echo __PATH_TEMPLATE__.panicWikiEscape($portrait['file']); ?>" alt=""></span><span class="atlas-reward-name"><strong><?php echo panicWikiEscape($bag['name']); ?></strong><small><?php echo $bag['monster'] >= 0 ? panicWikiEscape($bag['monsterName'] ?: 'Monstruo '.$bag['monster']) : ($bag['item'] >= 0 ? 'Caja u objeto' : 'Evento o sistema'); ?></small><span class="atlas-reward-count"><?php echo in_array($bag['format'],['standard','advanced'],true) ? count($bag['items']).' entradas posibles' : 'Verificación pendiente'; ?></span></span><b aria-hidden="true">+</b></summary>
            <div class="wiki-map-body">
                <?php if($artKind === 'illustration') { ?><p class="atlas-art-caption">Arte ilustrativo del evento o sistema.</p><?php } ?>
                <a href="#recompensa-<?php echo $bag['id']; ?>">Enlace directo ↗</a>
                <?php if(isset($atlasBossGuideIds[$bag['id']])) { $bossGuide=$atlasBossEditorial[$atlasBossGuideIds[$bag['id']]]; ?>
                <div class="atlas-boss-event-guide"><span class="eyebrow">Guía del encuentro · sin agenda fija</span><p><?php echo panicWikiEscape($bossGuide['intro']); ?></p><dl class="atlas-event-facts"><div><dt>Cómo acceder</dt><dd><?php echo panicWikiEscape($bossGuide['entry']); ?></dd></div><div><dt>Premio configurado</dt><dd><?php echo panicWikiEscape($bossGuide['rewardNote']); ?> · Auditoría 06/10/2026.</dd></div></dl><p class="atlas-event-validation"><strong>Estado de validación</strong><?php echo panicWikiEscape($bossGuide['validation']); ?></p></div>
                <?php } ?>
                <?php if(!in_array($bag['format'], array('standard','advanced'), true)) { ?>
                    <p class="wiki-note">Esta lista todavía requiere verificación. Sus recompensas no se muestran hasta confirmar la información.</p>
                <?php } else { ?>
                    <?php if($bag['format'] === 'advanced') { ?>
                    <p>Hasta <?php echo count(array_filter($bag['selection']['attempts'], function($attempt) { return $attempt['dropRate'] > 0; })); ?> selecciones de recompensa configuradas. Cada selección tiene sus propias condiciones; los objetos de la lista no se entregan todos juntos.</p>
                    <?php } else { ?>
                    <p>Cantidad de objetos configurada: <?php echo $bag['settings']['itemDropCount']; ?>. La cantidad recibida depende de las condiciones y la selección de la recompensa.</p>
                    <?php } ?>
                    <?php if($bag['topHit'] > 0) { ?><p class="wiki-note">Esta recompensa tiene condiciones adicionales de participación y daño. Consultá las reglas del boss dentro del juego.</p><?php } ?>
                    <label class="wiki-control">Buscar en esta recompensa<input type="search" data-reward-item-filter placeholder="Objeto o set…" aria-label="<?php echo panicWikiEscape('Buscar objetos en '.$bag['name']); ?>"></label>
                    <p data-reward-item-status role="status" aria-live="polite"></p>
                    <div class="wiki-table-scroll"><table><thead><tr><th>Objeto posible</th><th>Nivel del objeto</th><th>Opciones posibles</th></tr></thead><tbody>
                    <?php foreach($bag['items'] as $item) { $options = array(); if($item['skill'] > 0) $options[] = 'Skill'; if($item['luck'] > 0) $options[] = 'Luck'; if($item['excellent'] > 0) $options[] = 'Excellent'; if($item['setOption'] > 0) $options[] = 'Ancient'; if($item['socketOption'] > 0) $options[] = 'Socket'; if(isset($item['duration']) && $item['duration'] > 0) $options[] = 'Temporal'; ?>
                        <tr data-reward-item><td><?php echo panicWikiEscape($item['name']); ?><?php if(!empty($item['setName'])) { ?><small> · <?php echo panicWikiEscape($item['setName']); ?></small><?php } ?></td><td><?php echo '+'.$item['min'].($item['min'] !== $item['max'] ? ' a +'.$item['max'] : ''); ?></td><td><?php echo $options ? implode(' · ', $options) : 'Sin opciones especiales indicadas'; ?></td></tr>
                    <?php } ?>
                    </tbody></table></div>
                <?php } ?>
            </div>
        </details>
    <?php } ?>
