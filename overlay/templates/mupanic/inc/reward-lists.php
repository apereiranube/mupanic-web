<?php if(!defined('access') or !access) die(); ?>
<section id="recompensas" class="wiki-section">
    <span class="eyebrow">BOSSES, CAJAS Y EVENTOS</span><h2>Recompensas posibles.</h2>
    <p>Buscá un boss, una caja o un objeto para consultar su lista de recompensas. La lista incluye posibilidades, no objetos garantizados. Los grupos de selección y las condiciones de cada evento afectan el resultado.</p>
    <label class="wiki-control">Buscar recompensa<input type="search" data-reward-filter placeholder="Selupan, Kundun, Harmony…"></label>
    <p data-reward-status role="status" aria-live="polite"></p>
    <div class="wiki-map-list">
    <?php foreach($wiki['eventBags'] as $bag) { ?>
        <details class="wiki-map" id="recompensa-<?php echo $bag['id']; ?>" data-reward-list data-wiki-search>
            <summary><strong><?php echo panicWikiEscape($bag['name']); ?></strong><small><?php echo $bag['monster'] >= 0 ? panicWikiEscape($bag['monsterName'] ?: 'Monstruo '.$bag['monster']) : ($bag['item'] >= 0 ? 'Caja u objeto' : 'Evento o sistema'); ?></small><b aria-hidden="true">+</b></summary>
            <div class="wiki-map-body">
                <a href="#recompensa-<?php echo $bag['id']; ?>">Enlace directo ↗</a>
                <?php if($bag['format'] !== 'standard') { ?>
                    <p class="wiki-note">Esta lista todavía requiere verificación. Sus recompensas no se muestran hasta confirmar la información.</p>
                <?php } else { ?>
                    <p>Cantidad de objetos configurada: <?php echo $bag['settings']['itemDropCount']; ?>. La cantidad recibida depende de las condiciones y la selección de la recompensa.</p>
                    <?php if($bag['topHit'] > 0) { ?><p class="wiki-note">Esta recompensa tiene condiciones adicionales de participación y daño. Consultá las reglas del boss dentro del juego.</p><?php } ?>
                    <div class="wiki-table-scroll"><table><thead><tr><th>Objeto posible</th><th>Nivel del objeto</th><th>Opciones posibles</th></tr></thead><tbody>
                    <?php foreach($bag['items'] as $item) { $options = array(); if($item['excellent'] > 0) $options[] = 'Excellent'; if($item['setOption'] > 0) $options[] = 'Ancient'; if($item['socketOption'] > 0) $options[] = 'Socket'; if($item['skill'] > 0) $options[] = 'Skill'; if($item['luck'] > 0) $options[] = 'Luck'; ?>
                        <tr><td><?php echo panicWikiEscape($item['name']); ?></td><td><?php echo '+'.$item['min'].($item['min'] !== $item['max'] ? ' a +'.$item['max'] : ''); ?></td><td><?php echo $options ? implode(' · ', $options) : 'Sin opciones especiales indicadas'; ?></td></tr>
                    <?php } ?>
                    </tbody></table></div>
                <?php } ?>
            </div>
        </details>
    <?php } ?>
    </div>
</section>
