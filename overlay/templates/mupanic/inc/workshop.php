<?php if(!defined('access') or !access) die(); ?>
<?php
// Taller: recipes in crafting-recipes.json; every success rate comes from the synced ChaosMix.dat (mixRates).
if(!function_exists('panicTallerRate')) {
    function panicTallerRate($wiki, $recipe, $level, $account) {
        $key = $level ? ($level['rateKey'] ?? null) : ($recipe['rateKey'] ?? null);
        $mode = $recipe['rateMode'] ?? 'fixed';
        if(!$key) return array('text' => ['always' => 'No falla', 'stack' => 'Se arma sola', 'none' => 'Sin mezcla'][$mode] ?? '—', 'pct' => in_array($mode, ['always','stack'], true) ? 100 : 0, 'variable' => false);
        $rate = $wiki['crafting']['mixRates'][$key][$account] ?? null;
        if($rate === null) return array('text' => '—', 'pct' => 0, 'variable' => false);
        if($rate === -1) return array('text' => 'Variable', 'pct' => (int)($recipe['rateCap'] ?? 0), 'variable' => true);
        return array('text' => $rate.'%', 'pct' => max(0, min(100, (int)$rate)), 'variable' => false);
    }
    function panicTallerRangeText($wiki, $recipe, $account) {
        if(empty($recipe['levels'])) return panicWikiEscape(panicTallerRate($wiki, $recipe, null, $account)['text']);
        $groups = array();
        foreach($recipe['levels'] as $level) {
            $t = panicTallerRate($wiki, $recipe, $level, $account)['text'];
            $n = count($groups);
            if($n && $groups[$n-1]['t'] === $t) $groups[$n-1]['to'] = $level['label'];
            else $groups[] = array('t' => $t, 'from' => $level['label'], 'to' => $level['label']);
        }
        if(count($groups) === 1) return '<b>'.panicWikiEscape($groups[0]['t']).'</b>';
        $out = array();
        foreach($groups as $g) $out[] = '<span class="tl-rate-range"><small>'.panicWikiEscape(($recipe['levelLabel'] ?? '').' '.($g['from'] === $g['to'] ? $g['from'] : $g['from'].'–'.$g['to'])).'</small> <b>'.panicWikiEscape($g['t']).'</b></span>';
        return implode(' ', $out);
    }
    function panicTallerCardRate($wiki, $recipe, $account) {
        if(empty($recipe['levels'])) return panicTallerRate($wiki, $recipe, null, $account)['text'];
        $values = array();
        foreach($recipe['levels'] as $level) { $r = panicTallerRate($wiki, $recipe, $level, $account); $values[] = $r['text']; }
        $numbers = array_map('intval', array_filter($values, function($v) { return substr($v, -1) === '%'; }));
        if(count($numbers) === count($values) && $numbers) { $min = min($numbers); $max = max($numbers); return $min === $max ? $min.'%' : $min.'–'.$max.'%'; }
        return $values[0];
    }
    function panicTallerZen($recipe, $level) {
        $zen = $level ? ($level['zen'] ?? null) : ($recipe['zen'] ?? null);
        if($zen !== null) return number_format($zen, 0, ',', '.');
        if(!empty($recipe['zenText'])) return $recipe['zenText'];
        return 'Sin costo';
    }
    function panicTallerChar($level) {
        $parts = explode(' · ', (string)($level['charLevel'] ?? ''), 2);
        return array($parts[0], $parts[1] ?? '');
    }
}
$tallerGroups = array('Entradas' => 'Entradas de eventos', 'Alas' => 'Alas', 'Equipo' => 'Equipo', 'Fenrir' => 'Fenrir', 'Mascotas' => 'Mascotas', 'Socket' => 'Socket', 'Otros' => 'Otros');
?>
<section id="taller" class="wiki-section tl" data-tl>
    <header class="tl-head"><span class="eyebrow">06 / TALLER PANIC</span><h2>Recetas de la Chaos Machine.</h2><p>Elegí qué querés crear. Te mostramos qué poner, cuánto cuesta y qué chance tenés.</p></header>
    <div class="tl-bar"><label class="wiki-control tl-search">Buscar receta<input type="search" data-recipe-search placeholder="Devil Square, alas, Fenrir…" autocomplete="off"></label><label class="wiki-control tl-account">Tu cuenta<select data-recipe-account><?php foreach($wiki['accounts'] as $i => $account) { ?><option value="<?php echo $i; ?>"><?php echo panicWikiEscape($account['name']); ?></option><?php } ?></select></label></div>
    <div class="tl-chips wiki-quick" role="group" aria-label="Filtrar recetas"><button type="button" data-recipe-group="Todas" aria-pressed="true">Todas</button><?php foreach($tallerGroups as $group => $label) { ?><button type="button" data-recipe-group="<?php echo $group; ?>" aria-pressed="false"><?php echo panicWikiEscape($label); ?></button><?php } ?></div>
    <p class="tl-status" data-recipe-status role="status" aria-live="polite"></p>
    <div class="tl-browse" data-tl-browse>
        <?php foreach($tallerGroups as $group => $label) { $inGroup = array_values(array_filter($recipes, function($r) use ($group) { return $r['group'] === $group; })); if(!$inGroup) continue; ?>
        <div class="tl-group" data-tl-group="<?php echo $group; ?>"><h3><?php echo panicWikiEscape($label); ?> <small><?php echo count($inGroup); ?></small></h3><div class="tl-cards">
            <?php foreach($inGroup as $recipe) { $thumbs = array(); $jewels = array(); foreach($recipe['materials'] as $material) { if(empty($material['image'])) continue; if(preg_match('~/jewel-(chaos|creation|bless|soul|life)-~', $material['image'])) { if(!in_array($material['image'], $jewels, true)) $jewels[] = $material['image']; } elseif(!in_array($material['image'], $thumbs, true)) $thumbs[] = $material['image']; } $thumbs = array_slice($thumbs ?: $jewels, 0, 2); ?>
            <a class="tl-card<?php echo !empty($recipe['art']) ? ' has-art' : ''; ?>" href="#crear-<?php echo $recipe['id']; ?>" data-recipe-nav="<?php echo $recipe['id']; ?>">
                <span class="tl-card-media" aria-hidden="true"><?php if(!empty($recipe['art'])) { ?><img src="<?php echo __PATH_TEMPLATE__.panicWikiEscape($recipe['art']); ?>" alt="" loading="lazy" decoding="async"><?php } elseif($thumbs) { foreach($thumbs as $thumb) { ?><img src="<?php echo __PATH_TEMPLATE__.panicWikiEscape($thumb); ?>" alt="" loading="lazy" decoding="async"><?php } } else { echo panicAtlasIcon('drops', '+'); } ?></span>
                <span class="tl-card-body"><small><?php echo panicWikiEscape($label); ?></small><strong><?php echo panicWikiEscape($recipe['name']); ?></strong><span><?php echo panicWikiEscape($recipe['summary']); ?></span></span>
                <span class="tl-card-rate" data-card-rate="<?php echo $recipe['id']; ?>"><?php echo panicWikiEscape(panicTallerCardRate($wiki, $recipe, 0)); ?></span>
            </a>
            <?php } ?>
        </div></div>
        <?php } ?>
        <details class="recipe-reference tl-reference"><summary>Ver todas las tasas juntas</summary><div class="wiki-table-scroll"><table class="tl-rates"><thead><tr><th>Receta</th><?php foreach($wiki['accounts'] as $account) { ?><th><?php echo panicWikiEscape($account['name']); ?></th><?php } ?></tr></thead><tbody><?php foreach($recipes as $recipe) { ?><tr><td><a href="#crear-<?php echo $recipe['id']; ?>"><?php echo panicWikiEscape($recipe['name']); ?></a></td><?php foreach($wiki['accounts'] as $accountLevel => $account) { ?><td><?php echo panicTallerRangeText($wiki, $recipe, $accountLevel); ?></td><?php } ?></tr><?php } ?></tbody></table></div></details>
    </div>
    <div class="recipe-articles tl-articles"><?php foreach($recipes as $recipe) { $first = $recipe['levels'][0] ?? null; $rate = panicTallerRate($wiki, $recipe, $first, 0); $hasChar = false; foreach($recipe['levels'] as $level) if(!empty($level['charLevel'])) $hasChar = true; $label = $tallerGroups[$recipe['group']] ?? $recipe['group']; ?>
        <article class="recipe-article tl-recipe" id="crear-<?php echo $recipe['id']; ?>" data-recipe-id="<?php echo $recipe['id']; ?>" tabindex="-1">
            <a class="tl-back" href="#taller">← Todas las recetas</a>
            <header class="tl-recipe-head"><div><span class="eyebrow"><?php echo panicWikiEscape($label); ?></span><h3><?php echo panicWikiEscape($recipe['name']); ?></h3><p><?php echo panicWikiEscape($recipe['summary']); ?></p></div><p class="tl-place"><small>Dónde se hace</small><strong><?php echo panicWikiEscape($recipe['place']); ?></strong></p></header>
            <?php if($recipe['levels']) { ?><div class="tl-levels" role="group" aria-label="<?php echo panicWikiEscape($recipe['levelLabel']); ?>"><span><?php echo panicWikiEscape($recipe['levelLabel']); ?></span><?php foreach($recipe['levels'] as $i => $level) { ?><button type="button" data-tl-level="<?php echo $i; ?>" aria-pressed="<?php echo $i === 0 ? 'true' : 'false'; ?>"><?php echo panicWikiEscape($level['label']); ?></button><?php } ?></div><?php } ?>
            <?php if($recipe['materials']) { ?><div class="tl-formula">
                <div class="tl-put"><small>Poné en la máquina</small><ul class="tl-ings"><?php foreach($recipe['materials'] as $i => $material) { $name = $first ? $first['materials'][$i] : $material['name']; $qty = $first && isset($first['quantities']) ? $first['quantities'][$i] : $material['quantity']; $img = $first && isset($first['images']) ? $first['images'][$i] : $material['image']; if($i > 0) { ?><li class="tl-plus" aria-hidden="true">+</li><?php } ?><li class="tl-ing"><span class="tl-ing-img" data-tl-img="<?php echo $i; ?>"><?php if($img) { ?><img src="<?php echo __PATH_TEMPLATE__.panicWikiEscape($img); ?>" alt="" loading="lazy" decoding="async"><?php } else { ?><b aria-hidden="true"><?php echo panicWikiEscape(mb_substr($name, 0, 1)); ?></b><?php } ?></span><b class="tl-qty" data-tl-qty="<?php echo $i; ?>">×<?php echo (int)$qty; ?></b><span class="tl-ing-name" data-tl-name="<?php echo $i; ?>"><?php echo panicWikiEscape($name); ?></span></li><?php } ?></ul></div>
                <div class="tl-out"><small>Sale</small><strong data-tl-result><?php echo panicWikiEscape($first ? $first['result'] : $recipe['result']['name']); ?></strong></div>
            </div><?php } else { ?><div class="tl-formula is-none"><div class="tl-put"><small>Sin receta</small><p><?php echo panicWikiEscape($recipe['summary']); ?></p></div><div class="tl-out"><small>Entrada</small><strong><?php echo panicWikiEscape($recipe['result']['name']); ?></strong></div></div><?php } ?>
            <div class="tl-stats">
                <div class="tl-stat tl-stat-rate"><small>Probabilidad</small><strong data-recipe-rate><?php echo panicWikiEscape($rate['text']); ?></strong><span class="tl-meter" aria-hidden="true"><i data-tl-bar style="width:<?php echo $rate['pct']; ?>%"></i></span><span class="tl-stat-note" data-tl-rate-note><?php echo $rate['variable'] ? 'Depende del valor de lo que pongas. Tope: '.(int)$recipe['rateCap'].'%.' : ($recipe['rateKey'] || ($first['rateKey'] ?? null) ? 'Tasa de tu cuenta. La máquina muestra la final.' : ''); ?></span></div>
                <div class="tl-stat"><small>Costo en Zen</small><strong class="<?php echo ($first ? ($first['zen'] ?? null) : $recipe['zen']) === null && !empty($recipe['zenText']) ? 'is-text' : ''; ?>" data-tl-zen><?php echo panicWikiEscape(panicTallerZen($recipe, $first)); ?></strong><span class="tl-stat-note"><?php echo panicWikiEscape(!empty($recipe['zenNote']) ? $recipe['zenNote'] : 'Por intento.'); ?></span></div>
                <?php if($hasChar) { $char = panicTallerChar($first); ?><div class="tl-stat"><small>Nivel del personaje</small><strong class="is-text" data-tl-char><?php echo panicWikiEscape($char[0]); ?></strong><span class="tl-stat-note" data-tl-char-alt><?php echo panicWikiEscape($char[1]); ?></span></div><?php } else { ?><div class="tl-stat tl-stat-fail"><small>Si falla</small><p><?php echo panicWikiEscape(preg_replace('/^Si falla,\s*/u', '', $recipe['note'])); ?></p></div><?php } ?>
            </div>
            <?php $links = array(); foreach($recipe['materials'] as $i => $material) { if($material['query']) $links[] = array('drop', $material['query'], $material['query']); elseif($material['recipe']) $links[] = array('recipe', $material['recipe'], $material['name']); } ?>
            <?php if($links || $recipe['event']) { ?><div class="tl-where"><h4>Dónde conseguir</h4><div class="tl-links"><?php foreach($links as $link) { if($link[0] === 'drop') { ?><a href="#drops" data-material-drop="<?php echo panicWikiEscape($link[1]); ?>"><?php echo panicWikiEscape($link[2]); ?> <span aria-hidden="true">↗︎</span></a><?php } else { ?><a href="#crear-<?php echo panicWikiEscape($link[1]); ?>" class="is-recipe">Crear: <?php echo panicWikiEscape($link[2]); ?> <span aria-hidden="true">↗︎</span></a><?php } } if($recipe['event']) { ?><a href="#eventos" class="is-event">Ver el evento <span aria-hidden="true">↗︎</span></a><?php } ?></div></div><?php } ?>
            <div class="tl-cols">
                <?php if($recipe['materials']) { ?><div class="tl-check"><h4>Tu lista</h4><p class="recipe-inventory-note">Marcá lo que ya tenés. Se guarda en este navegador.</p><ul class="recipe-materials"><?php foreach($recipe['materials'] as $i => $material) { $name = $first ? $first['materials'][$i] : $material['name']; $qty = $first && isset($first['quantities']) ? $first['quantities'][$i] : $material['quantity']; ?><li><label><input type="checkbox" data-material="<?php echo $recipe['id'].'-'.$i; ?>"><span class="material-count" data-tl-qty="<?php echo $i; ?>">×<?php echo (int)$qty; ?></span><span><strong data-tl-name="<?php echo $i; ?>"><?php echo panicWikiEscape($name); ?></strong><?php if($material['note']) { ?><small><?php echo panicWikiEscape($material['note']); ?></small><?php } ?></span></label></li><?php } ?></ul><p class="recipe-progress" data-recipe-progress role="status" aria-live="polite"></p></div><?php } ?>
                <div class="tl-how"><h4>Cómo se hace</h4><ol class="recipe-steps"><?php foreach($recipe['steps'] as $step) { ?><li><?php echo panicWikiEscape($step); ?></li><?php } ?></ol><?php if($hasChar) { ?><p class="tl-fail"><b>Si falla</b> <?php echo panicWikiEscape(preg_replace('/^Si falla,\s*/u', '', $recipe['note'])); ?></p><?php } ?><?php if(($recipe['extra'] ?? '') === 'luck') { ?><p class="tl-fail"><b>Con Luck</b> la tasa sube <span data-tl-luck><?php echo (int)$wiki['crafting']['jewels']['AddLuckSuccessRate2'][0]; ?></span> puntos, hasta 100%.</p><?php } ?></div>
            </div>
            <?php if(count($recipe['levels']) > 1) { ?><details class="tl-all"><summary>Ver todos los niveles</summary><div class="wiki-table-scroll"><table><thead><tr><th><?php echo panicWikiEscape($recipe['levelLabel']); ?></th><?php if($hasChar) { ?><th>Personaje</th><?php } ?><th>Probabilidad</th><th>Zen</th></tr></thead><tbody><?php foreach($recipe['levels'] as $i => $level) { ?><tr><td><?php echo panicWikiEscape($level['label']); ?></td><?php if($hasChar) { ?><td><?php echo panicWikiEscape(str_replace(' · ', ' / ', $level['charLevel'])); ?></td><?php } ?><td data-tl-row-rate="<?php echo $i; ?>"><?php echo panicWikiEscape(panicTallerRate($wiki, $recipe, $level, 0)['text']); ?></td><td><?php echo panicWikiEscape(panicTallerZen($recipe, $level)); ?></td></tr><?php } ?></tbody></table></div></details><?php } ?>
        </article><?php } ?></div>
</section>
<?php
// Mejorar mi equipo (#mejoras) y Tipos de equipo (#equipo): every rate comes from the synced $wiki['crafting'] (mixRates / jewels).
$mjJewels = $wiki['crafting']['jewels']; $mjRates = $wiki['crafting']['mixRates'];
$mjImg = array('bless' => 'img/atlas/drops/jewel-bless-2c80b97e4d75.webp', 'soul' => 'img/atlas/drops/jewel-soul-05f3890104b2.webp', 'chaos' => 'img/atlas/drops/jewel-chaos-809384516512.webp', 'life' => 'img/atlas/drops/jewel-life-6e1a802e1074.webp', 'creation' => 'img/atlas/drops/jewel-creation-9b7e2a322074.webp', 'gemstone' => 'img/atlas/drops/jewel-gemstone-5edf360037d7.webp', 'sphere' => 'img/atlas/drops/6214-0-4292d8890a9d.webp', 'armor' => 'img/market/emblems/armor.svg');
$mjPic = function($key, $cls = '') use ($mjImg) { return '<img class="'.$cls.'" src="'.__PATH_TEMPLATE__.$mjImg[$key].'" alt="" decoding="async">'; };
$mjNames = array('bless' => 'Bless', 'soul' => 'Soul', 'chaos' => 'Chaos');
$mjStages = array(
    array('key' => 'bless', 'from' => 1, 'to' => 6, 'name' => 'Bless', 'where' => 'Inventario'),
    array('key' => 'soul', 'from' => 7, 'to' => 9, 'name' => 'Soul', 'where' => 'Inventario'),
    array('key' => 'chaos', 'from' => 10, 'to' => 15, 'name' => 'Chaos Machine', 'where' => 'Chaos Goblin'),
);
$mjStepRate = function($level) use ($mjJewels, $mjRates) { if($level <= 6) return 100; if($level <= 9) return min(100, $mjJewels['SoulSuccessRate'][0]); return min(100, $mjRates['PlusItemLevelMixRate'.($level - 9)][0]); };
?>
<section id="mejoras" class="wiki-section mj">
    <header class="mj-head"><span class="eyebrow">07 / CONSTRUÍ TU EQUIPO</span><h2>De +0 a +15.</h2><p>Hay tres mejoras distintas. Elegí cuál querés antes de gastar joyas.</p>
        <ul class="mj-kinds"><li><b>Nivel +</b><span>Bless, Soul y Chaos</span></li><li><b>Opción adicional</b><span>Jewel of Life</span></li><li><b>Opciones Excellent</b><span>Vienen de drops o recompensas</span></li></ul></header>
    <div class="upgrade-calculator mj-calc" data-upgrade-calculator>
        <h3 class="mj-calc-title">Calculá tu mejora</h3>
        <div class="upgrade-controls mj-controls"><label class="wiki-control">Equipo<select data-upgrade-type><option value="PlusItemLevelMixRate">Normal</option><option value="PlusItemExcLevelMixRate">Excellent</option><option value="PlusItemSetLevelMixRate">Ancient</option><option value="PlusItemSocketLevelMixRate">Socket</option><option value="PlusItemWingLevelMixRate">Alas / capa</option></select></label><label class="wiki-control">Cuenta<select data-upgrade-account><?php foreach($wiki['accounts'] as $i => $account) { ?><option value="<?php echo $i; ?>"><?php echo $account['name']; ?></option><?php } ?></select></label><label class="wiki-control">Nivel actual<select data-upgrade-from><?php for($level=0;$level<15;$level++) { ?><option value="<?php echo $level; ?>" <?php echo $level === 9 ? 'selected' : ''; ?>>+<?php echo $level; ?></option><?php } ?></select></label><label class="wiki-control">Objetivo<select data-upgrade-to><?php for($level=1;$level<=15;$level++) { ?><option value="<?php echo $level; ?>" <?php echo $level === 10 ? 'selected' : ''; ?>>+<?php echo $level; ?></option><?php } ?></select></label><label class="upgrade-luck mj-luck"><input type="checkbox" data-upgrade-luck> <span>Mi ítem tiene <b>Luck</b></span></label></div>
        <div class="mj-hero">
            <div class="mj-odds is-safe" data-mj-odds role="status" aria-live="polite"><small>Chance de llegar sin fallar</small><strong data-chain-rate><?php echo $mjStepRate(10); ?>%</strong><span class="mj-meter" aria-hidden="true"><i data-mj-meter style="width:<?php echo $mjStepRate(10); ?>%"></i></span><span class="mj-risk" data-mj-risk>Chance alta</span><p data-upgrade-summary>Probabilidad de completar de +9 a +10 sin fallar ningún paso. Cada intento tiene su propia tasa.</p></div>
            <div class="mj-need"><small>Vas a necesitar</small><ul class="mj-need-list"><?php foreach($mjNames as $key => $name) { $start = array('bless' => 1, 'soul' => 1, 'chaos' => 1)[$key]; ?><li class="mj-need-item" data-mj-need-item="<?php echo $key; ?>"><span class="mj-need-img"><?php echo $mjPic($key); ?></span><b data-mj-need="<?php echo $key; ?>">×<?php echo $start; ?></b><span>Jewel of <?php echo $name; ?></span></li><?php } ?></ul><p>Para una cadena sin fallos. No incluye equipo ni Zen.</p><p class="mj-sr" data-upgrade-resources></p></div>
        </div>
        <div class="mj-ladder" data-upgrade-steps aria-label="Pasos de la mejora">
            <?php foreach($mjStages as $stage) { ?><div class="mj-band is-<?php echo $stage['key']; ?>"><h4 class="mj-band-head"><span class="mj-band-img"><?php echo $mjPic($stage['key']); ?></span><span><b><?php echo $stage['name']; ?></b><small><?php echo $stage['where']; ?></small></span></h4><ol class="mj-steps"><?php for($level=$stage['from'];$level<=$stage['to'];$level++) { $amount = $level - 9; $on = $level === 10; ?><li class="mj-step<?php echo $on ? ' is-on' : ''; ?>" data-mj-step="<?php echo $level; ?>"><span class="mj-step-lv"><small>+<?php echo $level - 1; ?> →</small>+<?php echo $level; ?></span><?php if($amount > 0) { ?><span class="mj-step-ings" aria-label="1 Chaos, <?php echo $amount; ?> Bless y <?php echo $amount; ?> Soul"><span><?php echo $mjPic('chaos'); ?>×1</span><span><?php echo $mjPic('bless'); ?>×<?php echo $amount; ?></span><span><?php echo $mjPic('soul'); ?>×<?php echo $amount; ?></span></span><?php } else { ?><span class="mj-sr">1 <?php echo $stage['name']; ?></span><?php } ?><b class="mj-step-rate" data-mj-step-rate><?php echo $mjStepRate($level); ?>%</b><span class="mj-sr" data-mj-step-state><?php echo $on ? 'En tu mejora.' : 'Fuera de tu mejora.'; ?></span></li><?php } ?></ol></div><?php } ?>
        </div>
        <ul class="mj-alerts">
            <li class="is-fail"><b>Si falla en Chaos</b><span>De +10 a +15, sin protección, podés perder el ítem y las joyas.</span></li>
            <li><b>Qué cuenta</b><span>Éxitos seguidos con las tasas y el Luck del servidor. Sin talismanes, protección ni bonos externos.</span></li>
            <li><b>Qué no cuenta</b><span>No es un costo promedio de reintentos. Comprobá la tasa final y el costo en el juego.</span></li>
        </ul>
    </div>
    <h3 class="mj-h3">Las tres etapas de mejora</h3>
    <ol class="mj-stages">
        <li class="mj-stage is-bless"><span class="mj-stage-num">01</span><?php echo $mjPic('bless', 'mj-stage-img'); ?><span class="mj-stage-range">+1 a +6</span><strong>Bless</strong><span class="mj-stage-key"><b>100%</b> no falla</span><p>Una Bless sube un nivel en equipo compatible.</p></li>
        <li class="mj-stage is-soul"><span class="mj-stage-num">02</span><?php echo $mjPic('soul', 'mj-stage-img'); ?><span class="mj-stage-range">+7 a +9</span><strong>Soul</strong><span class="mj-stage-key"><b><?php echo $mjJewels['SoulSuccessRate'][0]; ?>%</b> base Free</span><p>Una Soul por nivel. Usá la tasa de MU PANIC, no la de otros servidores.</p></li>
        <li class="mj-stage is-chaos"><span class="mj-stage-num">03</span><?php echo $mjPic('chaos', 'mj-stage-img'); ?><span class="mj-stage-range">+10 a +15</span><strong>Chaos Machine</strong><span class="mj-stage-key"><b>+1</b> Bless y Soul por nivel</span><p>Para +10: ítem +9, 1 Chaos, 1 Bless y 1 Soul. Para +15: 1 Chaos, 6 Bless y 6 Soul. Todo junto, en combinación regular.</p></li>
    </ol>
    <details class="recipe-reference mj-rates"><summary>Tasas base por tipo de equipo, sin Luck</summary><div class="wiki-table-scroll"><table><thead><tr><th>Tipo / Free</th><?php for($level=10;$level<=15;$level++) { ?><th>+<?php echo $level; ?></th><?php } ?></tr></thead><tbody><?php foreach(['PlusItemLevelMixRate'=>'Normal','PlusItemExcLevelMixRate'=>'Excellent','PlusItemSetLevelMixRate'=>'Ancient','PlusItemSocketLevelMixRate'=>'Socket','PlusItemWingLevelMixRate'=>'Alas / capa'] as $prefix=>$name) { ?><tr><td><?php echo $name; ?></td><?php for($level=1;$level<=6;$level++) { ?><td><?php echo $mjRates[$prefix.$level][0]; ?>%</td><?php } ?></tr><?php } ?></tbody></table></div><p>Luck suma <b><?php echo $mjJewels['AddLuckSuccessRate2'][0]; ?> puntos</b> en Chaos para Free, hasta 100%. Elegí tu cuenta en la calculadora para ver sus tasas.</p></details>
    <div class="mj-extra">
        <article class="mj-card is-life"><header><span class="mj-card-img"><?php echo $mjPic('life'); ?></span><div><span class="eyebrow">Jewel of Life</span><h3>Opción adicional</h3></div><p class="mj-card-rate"><b><?php echo $mjJewels['LifeSuccessRate'][0]; ?>%</b><small>base Free</small></p></header>
            <p class="mj-card-lead">Mejora la opción adicional compatible del objeto: daño o defensa adicional, por ejemplo.</p>
            <ul class="mj-chips"><li class="is-no">No sube el nivel +</li><li class="is-no">No agrega Excellent</li></ul>
            <p class="mj-fail"><b>Si falla</b> la opción puede bajar o perderse, según el objeto. Revisá la opción actual antes de usarla.</p></article>
        <article class="mj-card is-luck"><header><div><span class="eyebrow">Propiedades del objeto</span><h3>Luck y Skill</h3></div></header>
            <dl class="mj-props"><div><dt>Luck</dt><dd>Mejora ciertas probabilidades y da efecto de crítico. En Chaos suma <b><?php echo $mjJewels['AddLuckSuccessRate2'][0]; ?> puntos</b> (Free).</dd></div><div><dt>Skill</dt><dd>Habilita la habilidad propia de un arma compatible.</dd></div></dl>
            <p class="mj-fail is-note"><b>Ojo</b> Ni Bless ni Life vuelven Excellent un ítem normal. Tampoco le agregan Luck o Skill.</p></article>
    </div>
</section>
<section id="equipo" class="wiki-section eq" data-wiki-search><span class="eyebrow">08 / LEÉ TU BOTÍN</span><h2>Excellent, Ancient y Socket.</h2>
    <div class="equipment-tabs eq-tabs" aria-label="Consultar tipo de equipo"><a href="#equipo-excellent">Excellent</a><a href="#equipo-ancient">Ancient</a><a href="#equipo-socket">Socket</a><a href="#equipo-harmony">Harmony / 380</a></div>
    <article id="equipo-excellent" class="equipment-article eq-card is-exc"><header class="equipment-heading eq-head"><span class="eq-mark"><?php echo panicAtlasIcon('drops', 'X'); ?></span><div><span class="eyebrow">Opciones adicionales</span><h3>Excellent</h3><p class="eq-big">Importa la opción, no solo el nombre.</p></div></header>
        <div class="eq-tiles"><div class="eq-tile"><small>Qué es</small><p>Un ítem con opciones extra propias de su tipo. Puede ser +0 y seguir siendo Excellent.</p></div><div class="eq-tile"><small>Cómo se consigue</small><p>En drops o recompensas que den esa calidad. No hay receta estándar y las joyas normales no la agregan.</p></div><div class="eq-tile"><small>Nivel y opciones</small><p>Subirlo a +9 o +15 mejora su nivel. Las opciones Excellent van aparte.</p></div></div>
        <div class="eq-opts"><div><h4>Armas y staffs</h4><ul class="eq-tags"><li>Recuperar HP o MP al matar</li><li>Velocidad de ataque</li><li>Daño físico o mágico %</li><li>Daño según el nivel</li><li>Chance de daño Excellent</li></ul></div><div><h4>Armaduras y escudos</h4><ul class="eq-tags"><li>HP o MP máximo</li><li>Reducción de daño</li><li>Reflejo de daño</li><li>Éxito de defensa</li><li>Zen adicional<small>si el objeto lo permite</small></li></ul></div></div>
        <p class="eq-tip"><b>Tip</b> No todos los objetos admiten todas las opciones. Más líneas no siempre rinden más: compará para tu clase y tu objetivo.</p></article>
    <article id="equipo-ancient" class="equipment-article eq-card is-anc"><header class="equipment-heading eq-head"><span class="eq-mark is-item"><?php echo $mjPic('armor'); ?></span><div><span class="eyebrow">Bonos de conjunto</span><h3>Ancient</h3><p class="eq-big">Armá el conjunto.</p></div></header>
        <div class="eq-tiles"><div class="eq-tile"><small>Qué da</small><p>Bonos de set que se activan al reunir las piezas indicadas.</p></div><div class="eq-tile"><small>Revisá</small><p>El nombre del set y su lista de efectos. Piezas de sets distintos no completan el mismo conjunto.</p></div><div class="eq-tile"><small>También importa</small><p>Además del nivel y la opción, la variante de estadísticas del Ancient.</p></div></div>
        <p class="eq-warn"><b>Se consume</b> Algunas creaciones, como la Pluma de Cóndor o un Seed, usan un Ancient como ingrediente.</p>
        <div class="eq-links"><a href="#crear-pluma-condor">Receta de Pluma de Cóndor <span aria-hidden="true">↗︎</span></a></div></article>
    <article id="equipo-socket" class="equipment-article eq-card is-sock"><header class="equipment-heading eq-head"><span class="eq-mark is-item"><?php echo $mjPic('sphere'); ?></span><div><span class="eyebrow">Seeds y slots</span><h3>Socket</h3><p class="eq-big">Planificá antes de montar.</p></div></header>
        <ol class="eq-steps"><li><b>Equipo con slots</b><p>Conseguí equipo con slots libres. Un ítem normal no gana slots con una Seed.</p></li><li><span class="eq-step-art"><?php echo $mjPic('chaos'); ?><?php echo $mjPic('creation'); ?></span><b>Creá un Seed</b><p>Con equipo Excellent, Ancient y las joyas indicadas.</p></li><li><span class="eq-step-art"><?php echo $mjPic('sphere'); ?></span><b>Seed + Sphere</b><p>Combinalos para obtener una Seed Sphere.</p></li><li><b>Montala</b><p>En un slot vacío, con el NPC del sistema Socket. Verificá tipo compatible y posición antes de confirmar.</p></li></ol>
        <div class="eq-tiles is-two"><div class="eq-tile"><small>Qué define el efecto</small><p>La opción del Seed y el nivel de la Sphere.</p></div></div>
        <p class="eq-warn"><b>Ojo</b> Extraer o destruir una opción es otra operación y puede consumir la Seed Sphere. Verificá qué muestra el NPC.</p>
        <div class="eq-links"><a href="#crear-seed">Crear un Seed <span aria-hidden="true">↗︎</span></a><a href="#crear-seed-sphere">Crear una Seed Sphere <span aria-hidden="true">↗︎</span></a></div></article>
    <article id="equipo-harmony" class="equipment-article eq-card is-harm"><header class="equipment-heading eq-head"><span class="eq-mark is-item"><?php echo $mjPic('gemstone'); ?></span><div><span class="eyebrow">Refinación y compatibilidad</span><h3>Harmony y 380</h3><p class="eq-big">Otra capa de equipo.</p></div></header>
        <div class="eq-rates"><div class="eq-rate"><small>Gemstone → Harmony</small><b><?php echo $mjRates['JewelOfHarmonyItemPurityMixRate'][0]; ?>%</b><span>base Free</span><p>Refinación en el NPC correspondiente. Buscá dónde cae Gemstone, incluidos los élites de mapas avanzados.</p></div><div class="eq-rate"><small>Jewel of Harmony</small><b><?php echo $mjJewels['HarmonySuccessRate'][0]; ?>%</b><span>base Free</span><p>Agrega una opción Harmony a equipo compatible. No es Excellent ni un slot Socket.</p></div><div class="eq-rate"><small>Refining Stones</small><b><?php echo $mjJewels['SmeltStoneSuccessRate1'][0]; ?>% <i>/</i> <?php echo $mjJewels['SmeltStoneSuccessRate2'][0]; ?>%</b><span>Lower / Higher · base Free</span><p>Intentan mejorar una opción Harmony que ya tenés.</p></div><div class="eq-rate is-text"><small>Opción 380</small><b>Nivel 380</b><span>del equipo, no del personaje</span><p>Equipo habilitado de nivel 380. Se procesa aparte en Chaos. Confirmá la compatibilidad y la receta.</p></div></div>
        <p class="eq-warn"><b>Si falla</b> una Refining Stone, la opción Harmony puede volver a su nivel inicial.</p>
        <div class="eq-links"><a href="#drops" data-material-drop="Gemstone">Buscar Gemstone <span aria-hidden="true">↗︎</span></a></div></article>
    <h3 class="eq-h3">Dónde están habilitados estos drops</h3>
    <ul class="eq-facts"><li>Calidad de equipo configurada por mapa</li><li>No garantiza un ítem concreto</li><li>No es el % general de drop</li><li>Cada monstruo y objeto debe ser elegible</li><li>Eventos y recompensas tienen sus reglas</li></ul>
    <?php $eqExc=array(); $eqAnc=array(); $eqSock=array(); foreach($wiki['maps'] as $map) { $d=$map['equipmentDrop']; $eqExc[(string)$d['excellent']][]=$map; if($d['ancient']>0) $eqAnc[]=$map; if($d['socket']) $eqSock[]=$map; } ?>
    <div class="eq-where">
        <article class="eq-where-card"><small>Excellent</small><?php if(count($eqExc)===1) { ?><strong><?php echo panicWikiRate((float)array_key_first($eqExc)); ?></strong><p>En todos los mapas.</p><?php } else { ?><ul class="eq-where-list"><?php foreach($eqExc as $rate=>$maps) { ?><li><b><?php echo panicWikiRate((float)$rate); ?></b> <?php echo panicWikiEscape(implode(', ', array_column($maps,'name'))); ?></li><?php } ?></ul><?php } ?></article>
        <article class="eq-where-card"><small>Ancient</small><?php if($eqAnc) { ?><ul class="eq-where-list"><?php foreach($eqAnc as $map) { ?><li><a href="#mapa-<?php echo $map['id']; ?>"><?php echo panicWikiEscape($map['name']); ?></a> <b><?php echo panicWikiRate($map['equipmentDrop']['ancient']); ?></b></li><?php } ?></ul><p>Solo en estos mapas.</p><?php } else { ?><strong>—</strong><p>Ningún mapa lo tira por ahora.</p><?php } ?></article>
        <article class="eq-where-card"><small>Socket</small><?php if($eqSock) { ?><ul class="eq-where-list"><?php foreach($eqSock as $map) { ?><li><a href="#mapa-<?php echo $map['id']; ?>"><?php echo panicWikiEscape($map['name']); ?></a></li><?php } ?></ul><p>Solo en estos mapas.</p><?php } else { ?><strong>—</strong><p>Ningún mapa lo tira por ahora.</p><?php } ?></article>
    </div>
    <details class="recipe-reference eq-maps-detail"><summary>Ver mapa por mapa</summary><div class="wiki-table-scroll"><table class="eq-maps"><thead><tr><th>Mapa</th><th>Excellent</th><th>Ancient</th><th>Socket</th></tr></thead><tbody><?php foreach($wiki['maps'] as $map) { $d=$map['equipmentDrop']; ?><tr><td><a href="#mapa-<?php echo $map['id']; ?>"><?php echo panicWikiEscape($map['name']); ?></a></td><td><?php echo panicWikiRate($d['excellent']); ?></td><td><?php echo $d['ancient']>0 ? panicWikiRate($d['ancient']) : '—'; ?></td><td><?php echo $d['socket'] ? 'Sí' : '—'; ?></td></tr><?php } ?></tbody></table></div></details>
</section>
