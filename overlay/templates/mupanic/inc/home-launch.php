<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/home-data.php';
$homeEscape = function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$homeArt = __PATH_TEMPLATE__.'img/home/';
$homeEvents = panicHomeEvents();
// Never display the old cron count while the current connection count is loading.
$homeOnlineValue = '—';
// Keep intrinsic image dimensions in markup; no fixed cover/crop viewport.
$homePicture = function($name, $alt, $priority = false, $version = 'v3') use ($homeArt, $homeEscape) {
    $dimensions = getimagesize(__DIR__.'/../img/home/'.$name.'-'.$version.'.webp');
    $mobile = in_array($name, ['hero-connected','fractura-connected','companions-connected','furia-world','market-world','games-hall'],true);
    $mobileDimensions = getimagesize(__DIR__.'/../img/home/'.$name.'-'.$version.($mobile ? '-mobile' : '-960').'.webp');
    ?><picture class="ph-art"><source media="(max-width:760px)" srcset="<?php echo $homeArt.$name; ?>-<?php echo $version; ?><?php echo $mobile ? '-mobile' : '-960'; ?>.webp" width="<?php echo (int)$mobileDimensions[0]; ?>" height="<?php echo (int)$mobileDimensions[1]; ?>"><img src="<?php echo $homeArt.$name; ?>-<?php echo $version; ?>.webp" alt="<?php echo $homeEscape($alt); ?>" width="<?php echo (int)$dimensions[0]; ?>" height="<?php echo (int)$dimensions[1]; ?>" <?php echo $priority ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"'; ?>></picture><?php
};
// Each transition has its own environmental artwork; never a repeated frame.
$homeJoin = function($kind) use ($homeArt) {
    $joinVersion = $kind === 'canopy' ? 'v13' : (in_array($kind, ['market','arcade'], true) ? 'v10' : 'v6');
    $joinSize = getimagesize(__DIR__.'/../img/home/join-'.$kind.'-'.$joinVersion.'.webp'); ?>
<div class="ph-flow-join ph-flow-join-<?php echo $kind; ?>" aria-hidden="true"><img src="<?php echo $homeArt; ?>join-<?php echo $kind.'-'.$joinVersion; ?>.webp" alt="" width="<?php echo (int)$joinSize[0]; ?>" height="<?php echo (int)$joinSize[1]; ?>" loading="lazy" decoding="async"></div>
<?php };
?>
<div class="panic-home" data-panic-home data-launcher-url="<?php echo $homeEscape(__BASE_URL__.'api/launcher.php?home=1'); ?>" data-home-version="connected-v10">
    <section class="ph-scene ph-hero" aria-labelledby="home-title">
        <div class="ph-art ph-combat-art">
            <picture>
            <source media="(max-width:760px)" srcset="<?php echo $homeArt; ?>hero-knight-v18-mobile.webp" width="1080" height="608">
            <img src="<?php echo $homeArt; ?>hero-knight-v18.webp" width="1672" height="941" alt="Magic Gladiator pelirrojo sin casco, con el set azul y violeta, alas de energía fucsia y espada violeta de MU PANIC, ante una fortaleza sobre terreno volcánico" fetchpriority="high">
            </picture>
            <svg class="ph-sword-energy" viewBox="0 0 1672 941" aria-hidden="true" focusable="false">
                <defs>
                    <filter id="ph-sword-soft" filterUnits="userSpaceOnUse" x="500" y="420" width="520" height="370" color-interpolation-filters="sRGB"><feGaussianBlur stdDeviation="7"/></filter>
                    <filter id="ph-sword-edge" filterUnits="userSpaceOnUse" x="490" y="400" width="550" height="410" color-interpolation-filters="sRGB"><feGaussianBlur stdDeviation="3.5"/></filter>
                    <filter id="ph-sword-spark-soft" x="-120%" y="-120%" width="340%" height="340%" color-interpolation-filters="sRGB"><feGaussianBlur stdDeviation=".65"/></filter>
                    <path id="ph-sword-aura-upper" d="M 953 458 C 923 468 917 491 883 496 L 869 520 L 842 522 L 822 545 L 792 550 L 765 578 L 736 584 L 706 612 L 678 621 L 651 651 L 620 665 L 596 694 L 550 747 L 587 723 L 621 690 L 654 673 L 687 644 L 719 628 L 748 606 L 779 590 L 809 567 L 840 551 L 869 529 L 899 513 L 930 487 Z"/>
                    <path id="ph-sword-aura-lower" d="M 962 489 L 935 507 L 908 528 L 876 543 L 851 568 L 817 579 L 792 605 L 757 616 L 732 643 L 697 656 L 670 684 L 635 697 L 607 725 L 550 747 L 605 738 L 643 717 L 674 704 L 705 677 L 742 664 L 765 639 L 800 629 L 826 601 L 860 591 L 887 565 L 921 550 L 947 522 Z"/>
                </defs>
                <g class="ph-sword-mist" filter="url(#ph-sword-soft)">
                    <use href="#ph-sword-aura-upper" class="ph-sword-aura ph-sword-aura-upper"/>
                    <use href="#ph-sword-aura-lower" class="ph-sword-aura ph-sword-aura-lower"/>
                </g>
                <g class="ph-sword-ribbons" filter="url(#ph-sword-edge)">
                    <use href="#ph-sword-aura-upper" class="ph-sword-aura ph-sword-aura-upper"/>
                    <use href="#ph-sword-aura-lower" class="ph-sword-aura ph-sword-aura-lower"/>
                </g>
                <g class="ph-sword-sparks">
                    <path d="M 912.6 477.7 Q 915.3 474.3 919.4 472.3 Q 916.7 475.7 912.6 477.7 Z" style="--dx:-34px;--dy:-34px;--delay:-0.000s"/>
                    <path d="M 935.9 508.3 Q 939.1 504.1 944.1 501.7 Q 940.9 505.9 935.9 508.3 Z" style="--dx:22px;--dy:-41px;--delay:-0.137s"/>
                    <path d="M 880.8 499.2 Q 884.5 494.3 890.4 491.6 Q 886.7 496.5 880.8 499.2 Z" style="--dx:-50px;--dy:-48px;--delay:-0.274s"/>
                    <path d="M 904.1 529.8 Q 908.9 524.7 915.1 521.0 Q 910.3 526.1 904.1 529.8 Z" style="--dx:38px;--dy:-55px;--delay:-0.411s"/>
                    <path d="M 851.9 518.4 Q 854.4 514.8 858.7 513.0 Q 856.2 516.6 851.9 518.4 Z" style="--dx:-34px;--dy:-62px;--delay:-0.548s"/>
                    <path d="M 875.2 549.0 Q 878.2 544.6 883.4 542.4 Q 880.4 546.8 875.2 549.0 Z" style="--dx:22px;--dy:-34px;--delay:-0.685s"/>
                    <path d="M 820.1 539.9 Q 824.2 535.4 829.7 532.3 Q 825.6 536.8 820.1 539.9 Z" style="--dx:-50px;--dy:-41px;--delay:-0.822s"/>
                    <path d="M 843.4 570.5 Q 848.0 565.2 854.4 561.7 Q 849.8 567.0 843.4 570.5 Z" style="--dx:38px;--dy:-48px;--delay:-0.959s"/>
                    <path d="M 791.1 559.2 Q 793.4 555.4 797.9 553.8 Q 795.6 557.6 791.1 559.2 Z" style="--dx:-34px;--dy:-55px;--delay:-1.096s"/>
                    <path d="M 814.4 589.8 Q 817.8 585.8 822.6 583.2 Q 819.2 587.2 814.4 589.8 Z" style="--dx:22px;--dy:-62px;--delay:-1.233s"/>
                    <path d="M 759.4 580.6 Q 763.3 575.9 769.0 573.0 Q 765.1 577.7 759.4 580.6 Z" style="--dx:-50px;--dy:-34px;--delay:-1.370s"/>
                    <path d="M 782.7 611.2 Q 787.1 605.7 793.7 602.4 Q 789.3 607.9 782.7 611.2 Z" style="--dx:38px;--dy:-41px;--delay:-1.507s"/>
                    <path d="M 730.4 599.9 Q 733.1 596.5 737.2 594.5 Q 734.5 597.9 730.4 599.9 Z" style="--dx:-34px;--dy:-48px;--delay:-1.644s"/>
                    <path d="M 753.7 630.5 Q 756.9 626.3 761.9 623.9 Q 758.7 628.1 753.7 630.5 Z" style="--dx:22px;--dy:-55px;--delay:-1.781s"/>
                    <path d="M 698.7 621.3 Q 702.4 616.4 708.3 613.7 Q 704.6 618.6 698.7 621.3 Z" style="--dx:-50px;--dy:-62px;--delay:-1.918s"/>
                    <path d="M 722.0 651.9 Q 726.8 646.8 733.0 643.1 Q 728.2 648.2 722.0 651.9 Z" style="--dx:38px;--dy:-34px;--delay:-2.055s"/>
                    <path d="M 669.7 640.6 Q 672.2 637.0 676.5 635.2 Q 674.0 638.8 669.7 640.6 Z" style="--dx:-34px;--dy:-41px;--delay:-2.192s"/>
                    <path d="M 693.0 671.2 Q 696.0 666.8 701.2 664.6 Q 698.2 669.0 693.0 671.2 Z" style="--dx:22px;--dy:-48px;--delay:-2.329s"/>
                    <path d="M 637.9 662.1 Q 642.0 657.6 647.5 654.5 Q 643.4 659.0 637.9 662.1 Z" style="--dx:-50px;--dy:-55px;--delay:-2.466s"/>
                    <path d="M 661.2 692.7 Q 665.8 687.4 672.2 683.9 Q 667.6 689.2 661.2 692.7 Z" style="--dx:38px;--dy:-62px;--delay:-2.603s"/>
                    <path d="M 609.0 681.3 Q 611.3 677.5 615.8 675.9 Q 613.5 679.7 609.0 681.3 Z" style="--dx:-34px;--dy:-34px;--delay:-2.740s"/>
                    <path d="M 632.3 711.9 Q 635.7 707.9 640.5 705.3 Q 637.1 709.3 632.3 711.9 Z" style="--dx:22px;--dy:-41px;--delay:-2.877s"/>
                    <path d="M 577.2 702.8 Q 581.1 698.1 586.8 695.2 Q 582.9 699.9 577.2 702.8 Z" style="--dx:-50px;--dy:-48px;--delay:-3.014s"/>
                    <path d="M 600.5 733.4 Q 604.9 727.9 611.5 724.6 Q 607.1 730.1 600.5 733.4 Z" style="--dx:38px;--dy:-55px;--delay:-3.151s"/>
                </g>
            </svg>
        </div>
        <div class="ph-battle-sky" aria-hidden="true"><i></i><i></i></div>
        <?php
        // Aviso corrido sobre el video. Se apaga solo al pasar la fecha "hasta" de inc/aviso-home.json.
        $homeAviso = json_decode((string)@file_get_contents(__DIR__.'/aviso-home.json'), true);
        $homeAvisoMsgs = is_array($homeAviso['mensajes'] ?? null) ? array_values(array_filter(array_map('strval', $homeAviso['mensajes']), 'strlen')) : [];
        $homeAvisoHasta = !empty($homeAviso['hasta']) ? strtotime($homeAviso['hasta']) : false;
        if(!empty($homeAviso['activo']) && $homeAvisoMsgs && ($homeAvisoHasta === false || time() < $homeAvisoHasta)) {
            $homeAvisoRun = function($hidden) use ($homeAvisoMsgs, $homeEscape) {
                echo '<span class="ph-aviso-run"'.($hidden ? ' aria-hidden="true"' : '').'>';
                for($rep = 0; $rep < 2; $rep++) foreach($homeAvisoMsgs as $m) echo '<span class="ph-aviso-item">'.$homeEscape($m).'</span>';
                echo '</span>';
            }; ?>
        <a class="ph-aviso" href="<?php echo $homeEscape(__BASE_URL__.ltrim((string)($homeAviso['link'] ?? 'downloads/'), '/')); ?>">
            <span class="ph-aviso-badge"><i aria-hidden="true"></i>AHORA</span>
            <span class="ph-aviso-track"><?php $homeAvisoRun(false); $homeAvisoRun(true); ?></span>
        </a>
        <?php } ?>
        <div class="ph-scene-content ph-wrap"><div class="ph-copy">
            <p class="ph-eyebrow">SEASON 6 · DESARROLLO PROPIO</p>
            <h1 id="home-title">Esto es<br>MU PANIC.</h1>
            <p class="ph-lead">Sistemas propios.<br>Una experiencia distinta.</p>
            <div class="ph-actions"><a class="ph-button ph-button-artwork ph-play-primary" href="<?php echo __BASE_URL__.($isLogged ? 'usercp/' : 'register/'); ?>"><img class="ph-play-button-art" src="<?php echo $homeArt; ?>play-button-account-v16.webp" alt="" aria-hidden="true" width="2172" height="724" decoding="async"><span class="ph-play-action-label"><?php echo $isLogged ? 'MI CUENTA' : 'CREAR CUENTA'; ?></span><span class="ph-play-action-icon" aria-hidden="true">↗︎</span></a><a class="ph-button ph-button-outline ph-button-artwork ph-play-download" href="<?php echo __BASE_URL__; ?>downloads/"><img class="ph-play-button-art" src="<?php echo $homeArt; ?>play-button-download-v16.webp" alt="" aria-hidden="true" width="2172" height="724" decoding="async"><span class="ph-play-action-icon" aria-hidden="true">↓</span><span class="ph-play-action-label">DESCARGAR</span></a></div>
        </div></div>
    </section>

    <section class="ph-status ph-status-core" aria-label="Datos del servidor" data-server-state="unknown">
        <div class="ph-core-world" aria-hidden="true"><img src="<?php echo $homeArt; ?>stats-world-v15.webp" alt="" width="2172" height="724" loading="lazy" decoding="async"></div>
        <div class="ph-core-atmosphere" aria-hidden="true"><span class="ph-core-mist"></span><?php for($mote=0;$mote<12;$mote++) { ?><i style="--mote-x:<?php echo ($mote*37+11)%100; ?>%;--mote-delay:-<?php echo $mote*.71; ?>s;--mote-time:<?php echo 4+($mote%5)*.8; ?>s"></i><?php } ?></div>
        <dl class="ph-facts">
            <div class="ph-state"><span class="ph-stat-illustration" aria-hidden="true"></span><dt>ESTADO DEL SERVIDOR</dt><dd data-home-state>Sin verificar</dd></div>
            <div class="ph-online-metric"><img class="ph-stat-illustration ph-core-frame" src="<?php echo $homeArt; ?>stats-core-v15.webp" alt="" width="1254" height="1254" loading="lazy" decoding="async"><dt><span>JUGADORES</span> <span>ONLINE</span></dt><dd style="--core-count-size:<?php echo number_format(min(23,74/max(1,strlen($homeOnlineValue))),2,'.',''); ?>cqw" data-home-online><?php echo $homeOnlineValue; ?></dd></div>
            <div class="ph-rate"><span class="ph-stat-illustration" aria-hidden="true"></span><dt>EXPERIENCIA</dt><dd data-home-rate="experience"><?php echo $homeEscape($serverExp); ?></dd></div>
            <div class="ph-rate"><span class="ph-stat-illustration" aria-hidden="true"></span><dt>MASTER EXP</dt><dd data-home-rate="master"><?php echo $homeEscape($serverMasterExp); ?></dd></div>
            <div class="ph-rate"><span class="ph-stat-illustration" aria-hidden="true"></span><dt>DROP GENERAL</dt><dd data-home-rate="drop"><?php echo $homeEscape($serverDrop); ?></dd></div>
        </dl>
        <div class="ph-status-note"><a href="<?php echo __BASE_URL__; ?>info/#rates">RATES Y CONDICIONES <span aria-hidden="true">↗︎</span></a></div>
    </section>

    <section class="ph-furia" id="furia" aria-labelledby="home-furia-title" data-furia-showcase>
        <div class="ph-scene ph-furia-scene">
            <?php $homePicture('furia-world', 'La energía roja de Furia quiebra el suelo de un escenario de MU PANIC'); ?>
            <div class="ph-scene-content ph-wrap"><div class="ph-copy">
                <p class="ph-eyebrow">SISTEMA EXCLUSIVO</p>
                <h2 id="home-furia-title">Desatá<br> la Furia.</h2>
                <p>Peleá, cargá tu barra y apretá Shift. Más velocidad, más daño y un poder propio de tu clase. Cada enemigo que cae te da más tiempo.</p>
            </div></div>
            <div class="ph-furia-demo" aria-label="Demostración de la interfaz de Furia: COMBO, IMPARABLE y MASACRE">
                <p class="ph-furia-ready">FURIA LISTA <span aria-hidden="true">—</span> <kbd>SHIFT</kbd></p>
                <div class="ph-furia-bar" aria-hidden="true">
                    <img class="ph-furia-energy-still" src="<?php echo $homeArt; ?>furia-ui-energy-v3.webp" alt="" width="1024" height="64" loading="lazy" decoding="async">
                    <video class="ph-furia-energy-video" data-furia-video data-src="<?php echo $homeArt; ?>furia-energy-v3.webm" data-fallback="<?php echo $homeArt; ?>furia-energy-v3.mp4" muted loop playsinline preload="none" disablepictureinpicture tabindex="-1"></video>
                    <img class="ph-furia-frame" src="<?php echo $homeArt; ?>furia-ui-frame-v3.webp" alt="" width="1024" height="160" loading="lazy" decoding="async">
                    <video data-furia-alpha class="ph-furia-aura-video" data-furia-video data-src="<?php echo $homeArt; ?>furia-aura-v3.webm" data-fallback="<?php echo $homeArt; ?>furia-aura-v3.mp4" muted loop playsinline preload="none" disablepictureinpicture tabindex="-1"></video>
                    <img class="ph-furia-skull" src="<?php echo $homeArt; ?>furia-ui-skull-v3.webp" alt="" width="256" height="256" loading="lazy" decoding="async">
                    <img class="ph-furia-eyes" src="<?php echo $homeArt; ?>furia-ui-eyes-v3.webp" alt="" width="256" height="256" loading="lazy" decoding="async">
                </div>
                <div class="ph-furia-combo" data-furia-combo data-combo-state="masacre" data-combo-value="20" aria-hidden="true"><span class="ph-combo-number"><i class="ph-combo-x"></i><i class="ph-combo-two" data-combo-tens></i><i class="ph-combo-zero" data-combo-units></i></span><?php foreach(['combo','imparable','masacre'] as $comboStage) { ?><img data-combo-stage="<?php echo $comboStage; ?>" src="<?php echo $homeArt.'furia-ui-'.$comboStage; ?>-v3.webp" alt="" width="1024" height="256" loading="eager" decoding="async"<?php echo $comboStage==='masacre' ? '' : ' hidden'; ?>><?php } ?></div>
            </div>
        <div class="ph-furia-bottom ph-wrap">
            <ol class="ph-furia-steps">
                <li><svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.3" aria-hidden="true"><path d="M3 10h26v13H3zM7 13v7m4-7v7m4-7v7m4-7v7m4-7v7M15 3l-3 5h5l-2 5"/></svg><div><h3>Cargala.</h3><p>Contra monstruos y en PvP.<br> La carga no se pierde.</p></div></li>
                <li><svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.3" aria-hidden="true"><path d="M4 5h24v23H4zM11 18l5-6 5 6h-3v6h-4v-6zM8 2l2 3m14-3-2 3"/></svg><div><h3>Desatala.</h3><p>Apretá <kbd>SHIFT</kbd>.<br> Más velocidad, más daño, tu poder de clase.</p></div></li>
                <li><svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.3" aria-hidden="true"><path d="M7 14a9 9 0 1 1 18 0c0 4-3 6-4 7v5H11v-5c-1-1-4-3-4-7zM12 26v3m4-3v3m4-3v3M2 5l4 4m20-4-4 4M1 16h4m22 0h4"/><path d="M11 13h3v3h-3zm7 0h3v3h-3zm-3 5h2"/></svg><div><h3>No pares.</h3><p>Cada muerte suma tiempo.<br> Encadená combos hasta la MASACRE.</p></div></li>
            </ol>
        </div>
        </div>
    </section>

    <?php $homeJoin("mist"); ?>
    <section data-scene-fx="fractura" class="ph-scene ph-fractura" aria-labelledby="home-fractura-title">
        <?php $homePicture('fractura-connected', 'Aldren y Varkor ante el Señor de la Fractura, los protagonistas de Nexo y Caos'); ?>
        <div class="ph-scene-content ph-wrap"><div class="ph-copy">
            <p class="ph-eyebrow">LA FRACTURA</p>
            <h2 id="home-fractura-title">Dos bandos.<br>Una guerra propia.</h2>
            <p>Nexo o Caos. Tu bando, tus batallas,<br>tu lugar en la historia.</p>
            <a class="ph-link" href="<?php echo __BASE_URL__; ?>fractura/">Conocé la Fractura <span aria-hidden="true">↗︎</span></a>
        </div></div>
    </section>

    <?php $homeJoin("forest"); ?>
    <section class="ph-scene ph-companions" aria-labelledby="home-companions-title">
        <?php $homePicture('companions-connected', 'Elyndra, Aurion, Vaerion y Grum, los cuatro compañeros de MU PANIC'); ?>
        <div class="ph-scene-content ph-wrap"><div class="ph-copy">
            <p class="ph-eyebrow">TRES MASCOTAS EVOLUTIVAS Y UN COMPAÑERO ÚNICO</p>
            <h2 id="home-companions-title">No avanzás solo.</h2>
            <p class="ph-companion-names">Elyndra · Aurion · Vaerion · Grum</p>
            <a class="ph-link" href="<?php echo __BASE_URL__; ?>info/#vinculos">Conocé tus compañeros <span aria-hidden="true">↗︎</span></a>
        </div></div>
    </section>

    <?php $homeJoin('canopy'); ?>
    <?php include __DIR__.'/home-world.php'; ?>
</div>
