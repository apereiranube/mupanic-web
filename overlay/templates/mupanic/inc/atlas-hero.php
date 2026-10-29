<?php if(!defined('access') || !access) die();
require_once __DIR__.'/atlas-art.php';
$atlHeroEsc = function($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$atlMapa = panicAtlasArt('atlas-mapa');
$atlFondo = panicAtlasArt('atlas-fondo');
$atlSpots = array_sum(array_map(function($map) { return count($map['spots']); }, $publicBalance['maps']));
?>
<section class="atlas-hero atl-hero pui" aria-labelledby="atlas-title" data-atl-hero>
    <div class="atl-hero-block" aria-hidden="true"><?php if($atlFondo) { ?><i class="atl-hero-fondo" style="background-image:url('<?php echo $atlHeroEsc($atlFondo); ?>')"></i><?php } ?></div>
    <div class="atl-hero-map<?php echo $atlMapa ? ' has-art' : ''; ?>" aria-hidden="true" data-art="atlas-mapa 1469x939"><?php if($atlMapa) { ?><img src="<?php echo $atlHeroEsc($atlMapa); ?>" alt="" width="1469" height="939" decoding="async" fetchpriority="high" data-atl-parallax><?php } else { ?><span class="atl-hero-word" data-atl-parallax>ATLAS</span><?php } ?></div>
    <div class="pui-wrap atl-hero-copy">
        <p class="pui-kicker">El continente / MU PANIC</p>
        <h1 class="pui-h1 atl-hero-title" id="atlas-title">Atlas</h1>
        <p class="atl-hero-lead">Mapas, criaturas, eventos y recompensas. Todo lo que necesitás para tu próximo paso.</p>
        <a class="pui-btn atl-btn atlas-hero-link" href="#inicio">Explorar el Atlas <span aria-hidden="true">↓</span></a>
    </div>
    <div class="pui-wrap atl-hero-facts atlas-world-facts" aria-label="Contenido del Atlas">
        <span><strong class="pui-num" data-atl-count="<?php echo count($publicBalance['maps']); ?>"><?php echo count($publicBalance['maps']); ?></strong> mapas</span>
        <span><strong class="pui-num" data-atl-count="<?php echo $atlSpots; ?>"><?php echo $atlSpots; ?></strong> spots</span>
        <span><strong class="pui-num" data-atl-count="<?php echo count($publicBalance['eventBags'] ?? []); ?>"><?php echo count($publicBalance['eventBags'] ?? []); ?></strong> listas de recompensas</span>
    </div>
</section>
