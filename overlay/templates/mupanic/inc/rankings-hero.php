<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/rankings-art.php';
$rkHeroEsc = function($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$rkFondo = panicRankingArt('rankings-fondo');
$rkSoul = panicRankingArt('rankings-soulmaster');
?>
<section class="rankings-hero rk-hero pui" aria-labelledby="rankings-title">
    <div class="rk-hero-block" aria-hidden="true"><?php if($rkFondo) { ?><i class="rk-hero-fondo" data-rk-parallax style="background-image:url('<?php echo $rkHeroEsc($rkFondo); ?>')"></i><?php } ?></div>
    <div class="rk-hero-fig<?php echo $rkSoul ? ' has-art' : ''; ?>" data-art="rankings-soulmaster 1204x944"><?php if($rkSoul) { ?><img src="<?php echo $rkHeroEsc($rkSoul); ?>" alt="Soul Master" width="1204" height="944" decoding="async" fetchpriority="high"><?php } ?></div>
    <div class="pui-wrap rk-hero-copy">
        <p class="pui-kicker">Los mejores del continente</p>
        <h1 class="pui-h1 rk-hero-title" id="rankings-title">Rankings</h1>
        <p class="rk-hero-lead">Nivel, resets, guilds y eventos. Mirá quién va adelante en cada categoría.</p>
        <a class="pui-btn rk-hero-btn rankings-hero-link" href="#rankings-results">Ver posiciones <span aria-hidden="true">↓</span></a>
    </div>
</section>
