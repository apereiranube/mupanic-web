<?php if(!defined('access') || !access) die(); ?>
<section class="atlas-hero" aria-labelledby="atlas-title">
    <div class="shell atlas-hero-layout">
        <div><span class="eyebrow">EL CONTINENTE / MU PANIC</span><h1 id="atlas-title">Conocé el mundo.<br><em>Elegí tu conquista.</em></h1><p>Mapas, criaturas y recompensas.<br>Todo lo que necesitás para tu próximo paso.</p></div>
        <a href="#inicio" class="atlas-hero-link">Explorar el Atlas <span aria-hidden="true">↓</span></a>
    </div>
    <div class="shell atlas-world-facts" aria-label="Contenido del Atlas"><span><strong><?php echo count($publicBalance['maps']); ?></strong> mapas</span><span><strong><?php echo array_sum(array_map(function($map) { return count($map['spots']); },$publicBalance['maps'])); ?></strong> spots</span><span><strong><?php echo count($publicBalance['eventBags'] ?? []); ?></strong> listas de recompensas</span><span class="atlas-world-source">Información del servidor <i aria-hidden="true"></i></span></div>
</section>
