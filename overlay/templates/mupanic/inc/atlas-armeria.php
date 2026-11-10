<?php
if(!defined('access') or !access) die();
/*
 * MU PANIC · Atlas · Armería: sets y armas de cada clase, de peor a mejor, con sus versiones Ancient.
 * Datos: inc/atlas-armeria.json (sale de Item.txt, SetItemType.txt y SetItemOption.txt del servidor; ver docs/atlas-armeria.md).
 * Imágenes de ítems: img/atlas/armeria/<sección>-<índice>.webp. Arte de clases: img/servidor/clase-*.webp.
 */
$armRaw = @file_get_contents(__DIR__.'/atlas-armeria.json');
$armData = $armRaw !== false ? json_decode($armRaw, true) : null;
if(is_array($armData)) {
$armArt = [
    'bk'=>'clase-bk-c45b8f3ccc32.webp', 'sm'=>'clase-sm-af9b0534e970.webp', 'elfa'=>'clase-elfa-69f0d3f6df9b.webp',
    'mg'=>'clase-mg-43bf82fd11fc.webp', 'dl'=>'clase-dl-d94afd89361e.webp', 'su'=>'clase-su-16919ddd9d2a.webp', 'rf'=>'clase-rf-0aa5b77fc1b8.webp',
];
foreach($armArt as $k=>$f) $armArt[$k] = __PATH_TEMPLATE__.'img/servidor/'.$f;
$armVer = function($rel) { $p = __DIR__.'/../'.$rel; return is_file($p) ? substr(hash_file('sha256', $p), 0, 12) : '0'; };
?>
<link href="<?php echo __PATH_TEMPLATE_CSS__; ?>atlas-armeria.css?v=<?php echo $armVer('css/atlas-armeria.css'); ?>" rel="stylesheet">
<section id="armeria" class="wiki-section" data-wiki-search data-art="<?php echo htmlspecialchars(json_encode($armArt, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>" data-items="<?php echo htmlspecialchars(__PATH_TEMPLATE__.'img/atlas/armeria/', ENT_QUOTES, 'UTF-8'); ?>">
    <?php foreach(['dk','dw','elfa','mg','dl','su','rf'] as $armId) { ?><span class="arm-anchor" id="armeria-<?php echo $armId; ?>" aria-hidden="true"></span><?php } ?>
<div class="arm-hero">
  <div class="arm-wrap">
    <div class="arm-hero-copy">
      <p class="arm-kicker">Atlas · Armería</p>
      <h2 class="arm-title"><span id="arm-hWhat">Sets</span> <span id="arm-clsArt">del</span> <em id="arm-clsWord">Dark Knight</em></h2>
      <p class="arm-lead">Así escala el equipo de cada personaje en MU PANIC, del primero al mejor del juego. Elegí tu clase y recorré la escalera.</p>
      <div class="arm-picker" role="tablist" aria-label="Personaje" id="arm-picker"></div>
      <div class="arm-modes" role="group" aria-label="Qué ver"><button type="button" id="arm-mSets" data-on="true">Sets</button><button type="button" id="arm-mWeap" data-on="false">Armas</button></div>
    </div>
    <div class="arm-hero-art" aria-hidden="true"><span class="arm-hero-word" id="arm-heroWord">BK</span><img id="arm-heroImg" alt="" src=""></div>
  </div>
</div>

<div class="arm-wrap arm-body">
  <section class="arm-stairs-sec" aria-labelledby="stairsTitle">
    <div class="arm-sec-head">
      <div><h3 id="arm-stairsTitle">La escalera</h3><p id="arm-stairsSub"></p></div>
      <label class="arm-opt" for="arm-show380" id="arm-opt380"><input type="checkbox" id="arm-show380" checked><span aria-hidden="true"></span>Incluir sets de jefes (nivel 380)</label>
    </div>
    <div class="arm-fams" id="arm-fams" hidden></div>
    <div class="arm-stairs" id="arm-stairs"></div>
    <div class="arm-tiers" id="arm-tiers"></div>
    <label class="arm-hint" for="arm-scrub">Deslizá para recorrer la escalera</label>
    <input type="range" class="arm-scrub" id="arm-scrub" min="0" max="0" value="0">
    <p class="arm-mgnote" id="arm-mgNote" hidden>El Magic Gladiator también puede usar los sets de Dark Knight y Dark Wizard que no piden segunda evolución. Acá van solo los suyos.</p>

    <article class="arm-card" id="arm-card" aria-live="polite">
      <div class="arm-card-main">
        <span class="arm-num" id="arm-cNum" aria-hidden="true">1</span>
        <div class="arm-vitrina" id="arm-cImgBox"><img id="arm-cImg" alt=""></div>
        <span class="arm-tier-badge" id="arm-cTier">Inicial</span>
        <h3 id="arm-cName">Leather</h3>
        <div class="arm-chips" id="arm-cChips"></div>
        <div class="arm-stats" id="arm-cStats"></div>
        <div class="arm-reqs" id="arm-cReqs"></div>
        <p class="arm-how" id="arm-cHow"></p>
        <div class="arm-nav"><button type="button" id="arm-prev">← Anterior</button><button type="button" id="arm-next">Siguiente →</button></div>
      </div>
      <div class="arm-pieces" id="arm-cPieces"></div>
    </article>
    <div id="arm-ancBox"></div>
  </section>
  <p class="arm-note" id="arm-foot"></p>
</div>
    <script type="application/json" id="arm-data"><?php echo json_encode($armData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?></script>
</section>
<script src="<?php echo __PATH_TEMPLATE_JS__; ?>atlas-armeria.js?v=<?php echo $armVer('js/atlas-armeria.js'); ?>" defer></script>
<?php } ?>
