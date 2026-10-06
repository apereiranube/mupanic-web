<?php if(!defined('access') or !access) die(); ?>
<section id="recompensas" class="wiki-section">
    <span class="eyebrow">BOSSES, CAJAS Y EVENTOS</span><h2>Recompensas posibles.</h2>
    <p>Buscá un boss, una caja o un objeto para consultar su lista de recompensas. La lista incluye posibilidades, no objetos garantizados. Los grupos de selección y las condiciones de cada evento afectan el resultado.</p>
    <label class="wiki-control">Buscar recompensa<input type="search" data-reward-filter placeholder="Selupan, Kundun, Harmony…"></label>
    <div class="wiki-quick" aria-label="Tipo de recompensa"><button type="button" data-reward-kind="" aria-pressed="true">Todo</button><button type="button" data-reward-kind="boss" aria-pressed="false">Bosses y criaturas</button><button type="button" data-reward-kind="box" aria-pressed="false">Cajas y objetos</button><button type="button" data-reward-kind="event" aria-pressed="false">Eventos y sistemas</button></div>
    <p data-reward-status role="status" aria-live="polite"></p>
    <div class="wiki-map-list">
    <?php foreach($wiki['eventBags'] as $bag) { $portrait = $bag['monster'] >= 0 ? ($atlasAssets['monsters'][(string)$bag['monster']] ?? null) : ($atlasAssets['rewards'][(string)$bag['id']] ?? null); $portrait = $portrait ?? ['file'=>'img/atlas/editorial/atlas-chamber.webp','kind'=>'illustration']; $artKind = $portrait['kind'] ?? 'monster'; ?>
        <details class="wiki-map" id="recompensa-<?php echo $bag['id']; ?>" data-reward-list data-reward-type="<?php echo $bag['monster'] >= 0 ? 'boss' : ($bag['item'] >= 0 ? 'box' : 'event'); ?>" data-wiki-search>
            <summary class="atlas-reward-summary"><span class="atlas-reward-art atlas-reward-art--<?php echo panicWikiEscape($artKind); ?>" aria-hidden="true"><img loading="eager" decoding="async" fetchpriority="low" width="<?php echo $portrait['width'] ?? 90; ?>" height="<?php echo $portrait['height'] ?? 112; ?>" src="<?php echo __PATH_TEMPLATE__.panicWikiEscape($portrait['file']); ?>" alt=""></span><span class="atlas-reward-name"><strong><?php echo panicWikiEscape($bag['name']); ?></strong><small><?php echo $bag['monster'] >= 0 ? panicWikiEscape($bag['monsterName'] ?: 'Monstruo '.$bag['monster']) : ($bag['item'] >= 0 ? 'Caja u objeto' : 'Evento o sistema'); ?></small><span class="atlas-reward-count"><?php echo in_array($bag['format'],['standard','advanced'],true) ? count($bag['items']).' entradas posibles' : 'Verificación pendiente'; ?></span></span><b aria-hidden="true">+</b></summary>
            <div class="wiki-map-body">
                <?php if($artKind === 'illustration') { ?><p class="atlas-art-caption">Arte ilustrativo del evento o sistema.</p><?php } ?>
                <a href="#recompensa-<?php echo $bag['id']; ?>">Enlace directo ↗</a>
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
    </div>
    <p class="atlas-portrait-credit">Imágenes del juego: <a href="https://www.muonline.net/guides/" target="_blank" rel="noopener noreferrer">MuOnline.Net</a> y <a href="https://blackrock.games/index.php?id=guide" target="_blank" rel="noopener noreferrer">Blackrock</a>, <a href="https://bless.gs/en/index.php?page=dropboxes" target="_blank" rel="noopener noreferrer">Bless</a>, <a href="https://wiki.munext.online/db/" target="_blank" rel="noopener noreferrer">MuNext</a>, <a href="https://mureforge.com/about-server/event-drops" target="_blank" rel="noopener noreferrer">MU Reforge</a> y <a href="https://mu.lv/guides/" target="_blank" rel="noopener noreferrer">MU.LV</a>. Assets de MU Online: Webzen. Arte ilustrativo: MU PANIC.</p>
</section>
