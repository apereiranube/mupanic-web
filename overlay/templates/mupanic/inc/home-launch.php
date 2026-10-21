<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/home-data.php';
$homeEscape = function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$homeArt = __PATH_TEMPLATE__.'img/home/';
$homeEvents = panicHomeEvents();
$homeOnlineValue = $onlinePlayers === null ? '—' : number_format($onlinePlayers, 0, ',', '.');
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
<div class="panic-home" data-panic-home data-launcher-url="<?php echo $homeEscape(__BASE_URL__.'api/launcher.php'); ?>" data-home-version="connected-v10">
    <section class="ph-scene ph-hero" aria-labelledby="home-title">
        <div class="ph-art ph-combat-art">
            <img src="<?php echo $homeArt; ?>hero-rf-mg-v10.webp" width="1280" height="720" alt="Rage Fighter con guanteletes y capa frente a Magic Gladiator con Hurricane y alas, ambos en vuelo" fetchpriority="high">
            <video class="ph-combat-video" data-combat-video data-src="<?php echo $homeArt; ?>hero-rf-mg-v10.mp4" muted loop playsinline preload="none" disablepictureinpicture tabindex="-1" aria-hidden="true"></video>
        </div>
        <div class="ph-battle-sky" aria-hidden="true"><i></i><i></i></div>
        <div class="ph-scene-content ph-wrap"><div class="ph-copy">
            <p class="ph-eyebrow">SEASON 6 · DESARROLLO PROPIO</p>
            <h1 id="home-title">Esto es<br>MU PANIC.</h1>
            <p class="ph-lead">Sistemas propios.<br>Una experiencia distinta.</p>
            <div class="ph-actions"><a class="ph-button" href="<?php echo __BASE_URL__.($isLogged ? 'usercp/' : 'register/'); ?>"><?php echo $isLogged ? 'MI CUENTA' : 'CREAR CUENTA'; ?> <span aria-hidden="true">↗</span></a><a class="ph-button ph-button-outline" href="<?php echo __BASE_URL__; ?>downloads/"><span aria-hidden="true">↓</span> DESCARGAR</a></div>
        </div></div>
    </section>

    <section class="ph-status ph-status-core" aria-label="Datos del servidor" data-server-state="unknown">
        <div class="ph-core-world" aria-hidden="true"><img src="<?php echo $homeArt; ?>stats-world-v15.webp" alt="" width="2172" height="724" loading="lazy" decoding="async"></div>
        <div class="ph-core-atmosphere" aria-hidden="true"><span class="ph-core-mist"></span><?php for($mote=0;$mote<12;$mote++) { ?><i style="--mote-x:<?php echo ($mote*37+11)%100; ?>%;--mote-delay:-<?php echo $mote*.71; ?>s;--mote-time:<?php echo 4+($mote%5)*.8; ?>s"></i><?php } ?></div>
        <dl class="ph-facts">
            <div class="ph-state"><span class="ph-stat-illustration" aria-hidden="true"></span><dt>ESTADO DEL SERVIDOR</dt><dd data-home-state>Sin verificar</dd></div>
            <div class="ph-online-metric"><img class="ph-stat-illustration ph-core-frame" src="<?php echo $homeArt; ?>stats-core-v15.webp" alt="" width="1254" height="1254" loading="lazy" decoding="async"><dt>JUGADORES ONLINE</dt><dd style="--core-count-size:<?php echo number_format(min(23,74/max(1,strlen($homeOnlineValue))),2,'.',''); ?>cqw" data-home-online><?php echo $homeOnlineValue; ?></dd></div>
            <div class="ph-rate"><span class="ph-stat-illustration" aria-hidden="true"></span><dt>EXPERIENCIA</dt><dd><?php echo $homeEscape($serverExp); ?></dd></div>
            <div class="ph-rate"><span class="ph-stat-illustration" aria-hidden="true"></span><dt>MASTER EXP</dt><dd><?php echo $homeEscape($serverMasterExp); ?></dd></div>
            <div class="ph-rate"><span class="ph-stat-illustration" aria-hidden="true"></span><dt>DROP GENERAL</dt><dd><?php echo $homeEscape($serverDrop); ?></dd></div>
        </dl>
        <div class="ph-status-note"><a href="<?php echo __BASE_URL__; ?>info/#rates">RATES Y CONDICIONES <span aria-hidden="true">↗</span></a></div>
    </section>

    <section class="ph-furia" id="furia" aria-labelledby="home-furia-title" data-furia-showcase>
        <div class="ph-scene ph-furia-scene">
            <?php $homePicture('furia-world', 'La energía roja de Furia quiebra el suelo de un escenario de MU PANIC'); ?>
            <div class="ph-scene-content ph-wrap"><div class="ph-copy">
                <p class="ph-eyebrow">SISTEMA EXCLUSIVO</p>
                <h2 id="home-furia-title">Desatá<br> la Furia.</h2>
                <p>Peleá, cargá tu barra y apretá Shift. Más velocidad, más daño y un poder propio de tu clase. Cada enemigo que cae te da más tiempo.</p>
            </div></div>
            <div class="ph-furia-demo" aria-label="La interfaz de Furia del juego: barra lista, combo x20 y Masacre">
                <p class="ph-furia-ready">FURIA LISTA <span aria-hidden="true">—</span> <kbd>SHIFT</kbd></p>
                <div class="ph-furia-bar" aria-hidden="true">
                    <img class="ph-furia-energy-still" src="<?php echo $homeArt; ?>furia-ui-energy-v3.webp" alt="" width="1024" height="64" loading="lazy" decoding="async">
                    <video class="ph-furia-energy-video" data-furia-video data-src="<?php echo $homeArt; ?>furia-energy-v3.webm" data-fallback="<?php echo $homeArt; ?>furia-energy-v3.mp4" muted loop playsinline preload="none" disablepictureinpicture tabindex="-1"></video>
                    <img class="ph-furia-frame" src="<?php echo $homeArt; ?>furia-ui-frame-v3.webp" alt="" width="1024" height="160" loading="lazy" decoding="async">
                    <video data-furia-alpha class="ph-furia-aura-video" data-furia-video data-src="<?php echo $homeArt; ?>furia-aura-v3.webm" data-fallback="<?php echo $homeArt; ?>furia-aura-v3.mp4" muted loop playsinline preload="none" disablepictureinpicture tabindex="-1"></video>
                    <img class="ph-furia-skull" src="<?php echo $homeArt; ?>furia-ui-skull-v3.webp" alt="" width="256" height="256" loading="lazy" decoding="async">
                    <img class="ph-furia-eyes" src="<?php echo $homeArt; ?>furia-ui-eyes-v3.webp" alt="" width="256" height="256" loading="lazy" decoding="async">
                </div>
                <div class="ph-furia-combo" aria-hidden="true"><span class="ph-combo-number"><i class="ph-combo-x"></i><i class="ph-combo-two"></i><i class="ph-combo-zero"></i></span><img src="<?php echo $homeArt; ?>furia-ui-masacre-v3.webp" alt="" width="1024" height="256" loading="lazy" decoding="async"></div>
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
            <a class="ph-link" href="<?php echo __BASE_URL__; ?>fractura/">Conocé la Fractura <span aria-hidden="true">↗</span></a>
        </div></div>
    </section>

    <?php $homeJoin("forest"); ?>
    <section class="ph-scene ph-companions" aria-labelledby="home-companions-title">
        <?php $homePicture('companions-connected', 'Elyndra, Aurion, Vaerion y Grum, los cuatro compañeros de MU PANIC'); ?>
        <div class="ph-scene-content ph-wrap"><div class="ph-copy">
            <p class="ph-eyebrow">TRES MASCOTAS EVOLUTIVAS Y UN COMPAÑERO ÚNICO</p>
            <h2 id="home-companions-title">No avanzás solo.</h2>
            <p class="ph-companion-names">Elyndra · Aurion · Vaerion · Grum</p>
            <a class="ph-link" href="<?php echo __BASE_URL__; ?>info/#vinculos">Conocé tus compañeros <span aria-hidden="true">↗</span></a>
        </div></div>
    </section>

    <?php $homeJoin('canopy'); ?>
    <?php include __DIR__.'/home-world.php'; ?>
</div>
