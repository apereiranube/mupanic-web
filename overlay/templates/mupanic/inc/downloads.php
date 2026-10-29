<?php if(!defined('access') || !access) die();
/**
 * MU PANIC · Descargas (estilo nuevo, color propio: naranja; protagonista: Rage Fighter).
 * Portada con un solo botón; después los mirrors, la instalación jugable (caja → carpeta → launcher → Jugar),
 * referidos, ayuda por síntoma y un cierre. Links, versión y tamaño en inc/downloads.json.
 * Arte en inc/descargas-art.php: sin archivo, cada lugar queda limpio. Ninguna imagen nombra la temporada ni la beta.
 */
require_once __DIR__.'/descargas-art.php';
$dl = json_decode((string)@file_get_contents(__DIR__.'/downloads.json'), true) ?: [];
$dlMirrors = array_values(array_filter($dl['links'] ?? [], function($l) {
    return !empty($l['nombre']) && !empty($l['url']) && preg_match('#^https://#i', $l['url']);
}));
$dlEsc = function($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
$dlBg = function($src) use ($dlEsc) { return $src ? ' style="background-image:url(\''.$dlEsc($src).'\')"' : ''; };
$dlCommunity = @include(__DIR__.'/community-config.php');
$dlDiscord = is_array($dlCommunity) && !empty($dlCommunity['invite']) ? $dlCommunity['invite'] : 'https://discord.gg/fP4Mxcsee';
$dlSize = $dl['tamano'] ?? '';
$dlFile = 'MU-PANIC-Cliente.zip';
$dlLauncherIcon = __PATH_TEMPLATE__.'img/downloads/launcher-icono-v1.webp';
$dlArt = [
    'rf' => panicDescargasArt('descargas-ragefighter'),
    'fondo' => panicDescargasArt('descargas-fondo'),
    'banda' => panicDescargasArt('descargas-fondo-banda'),
    'cerrada' => panicDescargasArt('descargas-caja-cerrada'),
    'abierta' => panicDescargasArt('descargas-caja-abierta'),
    'escena' => panicDescargasArt('descargas-escena'),
    'duo' => panicDescargasArt('descargas-duo'),
    'cierre' => panicDescargasArt('descargas-cierre'),
];
$dlHelp = [
    ['antivirus', 'El antivirus borró o bloqueó archivos', ['Agregá la carpeta del juego como excepción en tu antivirus.', 'Abrí el launcher: vuelve a bajar lo que falte.']],
    ['actualizando', 'El launcher se queda actualizando', ['Revisá tu conexión y que el firewall no bloquee al launcher.', 'Cerralo y abrilo de nuevo: retoma donde quedó.']],
    ['modificados', 'Dice que hay archivos modificados', ['Es el control del juego, no hiciste nada mal.', 'Dejá que los repare: después abre solo.']],
    ['bloqueado', 'No me deja jugar y nombra un programa', ['Ese programa está prohibido (trampas o edición de memoria).', 'Cerralo y apretá Reintentar.']],
];
?>
<div id="dl-page" class="dl pui">

    <section class="dl-hero" aria-labelledby="dl-title">
        <div class="pui-wrap dl-hero-copy">
            <p class="pui-kicker">Cliente para PC</p>
            <h1 class="pui-h1 dl-hero-title" id="dl-title">Las puertas de Lorencia están abiertas.</h1>
            <p class="dl-lead">MU Online Season 6, hecho en Argentina. Bajá el cliente, abrí el launcher y en minutos estás adentro.</p>
            <a class="pui-btn pui-btn--lg dl-btn dl-punch" href="#dl-mirrors">Descargar MU PANIC <span aria-hidden="true">↓</span></a>
            <p class="dl-meta">Gratis<?php echo $dlSize ? ' · '.$dlEsc($dlSize) : ''; ?> · Windows 10 y 11</p>
        </div>
        <div class="dl-hero-art">
            <div class="dl-hero-block" aria-hidden="true"><?php if($dlArt['fondo']) { ?><i class="dl-hero-fondo" data-dl-parallax<?php echo $dlBg($dlArt['fondo']); ?>></i><?php } ?></div>
            <?php if($dlArt['rf']) { ?><div class="dl-hero-fig" data-dl-fig><img src="<?php echo $dlEsc($dlArt['rf']); ?>" alt="Rage Fighter" width="1376" height="1072" decoding="async" fetchpriority="high"></div><?php } ?>
        </div>
    </section>

    <section class="dl-band dl-band--bone dl-mirrors" id="dl-mirrors" aria-labelledby="dl-mirrors-title">
        <div class="pui-wrap">
            <div class="dl-head">
                <div>
                    <p class="pui-kicker">Paso uno</p>
                    <h2 class="pui-h2" id="dl-mirrors-title"><span class="pui-reveal">Elegí desde dónde bajarlo</span></h2>
                </div>
                <p class="dl-file"><code><?php echo $dlFile; ?></code><?php echo $dlSize ? ' <b>'.$dlEsc($dlSize).'</b>' : ''; ?><br>Los tres tienen el mismo archivo. Si uno anda lento, probá con otro.</p>
            </div>
            <?php if($dlMirrors) { ?>
            <ul class="dl-mirror-list" data-dl-reveal>
                <?php foreach($dlMirrors as $i => $m) { ?>
                <li style="--i:<?php echo $i; ?>"><a class="dl-mirror dl-punch" href="<?php echo $dlEsc($m['url']); ?>" target="_blank" rel="noopener">
                    <span class="dl-mirror-n" aria-hidden="true"><?php echo sprintf('%02d', $i + 1); ?></span>
                    <span class="dl-mirror-name"><?php echo $dlEsc($m['nombre']); ?></span>
                    <span class="dl-mirror-go">Descargar <span aria-hidden="true">↓</span></span>
                </a></li>
                <?php } ?>
            </ul>
            <?php } else { ?>
            <p class="dl-empty">Los links se publican en estos días. <a href="<?php echo $dlEsc($dlDiscord); ?>" target="_blank" rel="noopener">Sumate al Discord</a> y te avisamos.</p>
            <?php } ?>
        </div>
    </section>

    <section class="dl-band dl-install<?php echo $dlArt['banda'] ? ' has-bg' : ''; ?>" aria-labelledby="dl-install-title" data-dl-sim<?php echo $dlBg($dlArt['banda']); ?>>
        <div class="pui-wrap">
            <div class="dl-head">
                <div>
                    <p class="pui-kicker">Paso dos</p>
                    <h2 class="pui-h2" id="dl-install-title"><span class="pui-reveal">Instalalo en cuatro clics</span></h2>
                </div>
                <p class="dl-sub">Probalo acá mismo: hacé los pasos con el mouse y vas a saber exactamente qué hacer en tu PC.</p>
            </div>
            <div class="dl-sim">
                <div class="dl-sim-list"><i class="dl-sim-marker" aria-hidden="true"></i><ol class="dl-sim-steps">
                    <li data-step="0"><b>Abrí la caja</b><span>Clic derecho sobre <code><?php echo $dlFile; ?></code> → <em>Extraer todo</em>. Elegí una carpeta tuya, como <code>C:\MU PANIC</code>. No en <em>Archivos de programa</em>.</span><small class="dl-ok">Listo</small></li>
                    <li data-step="1"><b>Abrí el launcher</b><span>En la carpeta, doble clic en <em>MU PANIC Launcher</em>. Siempre por el launcher, nunca por Main.exe.</span><small class="dl-ok">Listo</small></li>
                    <li data-step="2"><b>Esperá la actualización</b><span>Revisa y baja lo que falte. La primera vez puede pedir permiso de administrador: es el anti-trampas, aceptalo.</span><small class="dl-ok">Listo</small></li>
                    <li data-step="3"><b>Apretá Jugar</b><span>Entrás con tu cuenta. Las próximas veces solo baja las novedades.</span><small class="dl-ok">Listo</small></li>
                </ol></div>
                <div class="dl-stage<?php echo $dlArt['escena'] ? ' has-bg' : ''; ?>"<?php echo $dlBg($dlArt['escena']); ?>>
                    <p class="dl-stage-hint" aria-live="polite" data-sim-hint>Hacé clic en la caja para descomprimirla.</p>

                    <button type="button" class="dl-crate<?php echo $dlArt['cerrada'] ? ' has-art' : ''; ?><?php echo $dlArt['cerrada'] && $dlArt['abierta'] ? ' has-open' : ''; ?>" data-sim-chest aria-label="Descomprimir <?php echo $dlFile; ?>">
                        <span class="dl-crate-box">
                        <?php if($dlArt['cerrada']) { ?>
                            <img class="is-closed" src="<?php echo $dlEsc($dlArt['cerrada']); ?>" alt="" loading="lazy" decoding="async">
                            <?php if($dlArt['abierta']) { ?><img class="is-open" src="<?php echo $dlEsc($dlArt['abierta']); ?>" alt="" loading="lazy" decoding="async"><?php } ?>
                        <?php } else { ?>
                            <span class="dl-crate-plain"><b>ZIP</b><em>Clic para extraer</em></span>
                        <?php } ?>
                        </span>
                        <span class="dl-crate-name"><?php echo $dlFile; ?></span>
                    </button>

                    <div class="dl-folder" data-sim-folder hidden>
                        <p class="dl-folder-path">C:\MU PANIC</p>
                        <ul>
                            <li>Data</li><li>Main.exe</li><li>main.premium</li>
                            <li><button type="button" class="dl-launcher-file" data-sim-launcher><img src="<?php echo $dlLauncherIcon; ?>" alt="">MU PANIC Launcher</button></li>
                        </ul>
                    </div>

                    <div class="dl-mini" data-sim-mini hidden>
                        <div class="dl-mini-top"><img src="<?php echo $dlLauncherIcon; ?>" alt=""> MU PANIC Launcher</div>
                        <p class="dl-mini-state" data-sim-state>Actualizando el juego</p>
                        <div class="dl-mini-bar"><i data-sim-bar></i></div>
                        <button type="button" class="pui-btn dl-btn" data-sim-play disabled>Jugar</button>
                    </div>

                    <div class="dl-done" data-sim-done hidden>
                        <p class="dl-done-title">¡Listo, ya estás adentro!</p>
                        <p>Ahora solo te falta la cuenta. ¿Te invitó un amigo? Usá su link al crearla y arrancás con 3 días de VIP.</p>
                        <div class="dl-done-actions">
                            <a class="pui-btn pui-btn--dark" href="<?php echo __BASE_URL__; ?>register/">Crear mi cuenta</a>
                            <button type="button" class="dl-link" data-sim-reset>Ver de nuevo</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="dl-band dl-band--bone dl-ref<?php echo $dlArt['duo'] ? ' has-art' : ''; ?>" aria-labelledby="dl-ref-title">
        <div class="pui-wrap dl-ref-grid">
            <?php if($dlArt['duo']) { ?><div class="dl-ref-art"><img src="<?php echo $dlEsc($dlArt['duo']); ?>" alt="Un Blade Knight veterano le da la bienvenida a un Dark Knight recién creado" loading="lazy" decoding="async"></div><?php } ?>
            <div class="dl-ref-copy">
                <p class="pui-kicker">Referidos</p>
                <h2 class="pui-h2" id="dl-ref-title"><span class="pui-reveal">Traé a tu grupo y jueguen con VIP</span></h2>
                <dl class="dl-ref-deal">
                    <div><dt>Tu amigo</dt><dd><strong>3 días de VIP</strong> apenas crea la cuenta con tu link.</dd></div>
                    <div><dt>Vos</dt><dd><strong>7 días de VIP</strong> cuando tu amigo llega al reset 2 y juega 3 días distintos.</dd></div>
                </dl>
                <a class="pui-btn dl-btn dl-punch" href="<?php echo __BASE_URL__; ?>usercp/referidos/">Conseguir mi link</a>
                <p class="dl-fine">Tu link está en Mi cuenta → Referidos. Si todavía no entraste, primero te pide ingresar.</p>
            </div>
        </div>
    </section>

    <section class="dl-band dl-help<?php echo $dlArt['banda'] ? ' has-bg' : ''; ?>" aria-labelledby="dl-help-title"<?php echo $dlBg($dlArt['banda']); ?>>
        <div class="pui-wrap">
            <div class="dl-head">
                <div>
                    <p class="pui-kicker">Ayuda</p>
                    <h2 class="pui-h2" id="dl-help-title"><span class="pui-reveal">¿Algo no anda?</span></h2>
                </div>
                <p class="dl-sub">Buscá tu caso. Cada tarjeta tiene la solución en dos pasos.</p>
            </div>
            <ul class="dl-help-grid" data-dl-reveal>
                <?php foreach($dlHelp as $i => $h) { $ico = panicDescargasArt('descargas-ayuda-'.$h[0]); ?>
                <li class="dl-help-card" style="--i:<?php echo $i; ?>">
                    <span class="dl-help-ico<?php echo $ico ? ' has-art' : ''; ?>" aria-hidden="true"><?php if($ico) { ?><img src="<?php echo $dlEsc($ico); ?>" alt="" loading="lazy" decoding="async" width="96" height="96"><?php } else { echo sprintf('%02d', $i + 1); } ?></span>
                    <div>
                        <h3><?php echo $dlEsc($h[1]); ?></h3>
                        <ol><?php foreach($h[2] as $s) { ?><li><?php echo $dlEsc($s); ?></li><?php } ?></ol>
                    </div>
                </li>
                <?php } ?>
            </ul>
            <p class="dl-help-more">¿No es ninguno de estos? Escribinos en el <a href="<?php echo $dlEsc($dlDiscord); ?>" target="_blank" rel="noopener">Discord de MU PANIC</a> con una captura del error.</p>
        </div>
    </section>

    <section class="dl-end<?php echo $dlArt['cierre'] ? ' has-art' : ''; ?>" aria-labelledby="dl-end-title">
        <div class="dl-end-bg" aria-hidden="true"><?php if($dlArt['cierre']) { ?><i<?php echo $dlBg($dlArt['cierre']); ?>></i><?php } ?></div>
        <div class="pui-wrap dl-end-copy">
            <p class="pui-kicker">Último paso</p>
            <h2 class="pui-h2" id="dl-end-title"><span class="pui-reveal">Tu lugar en el ranking te espera.</span></h2>
            <ul class="dl-reasons">
                <li><a href="<?php echo __BASE_URL__; ?>info/#furia">Desatá la Furia</a></li>
                <li><a href="<?php echo __BASE_URL__; ?>fractura/">Elegí tu bando en la Fractura</a></li>
                <li><a href="<?php echo __BASE_URL__; ?>information/">Jugá los minijuegos del Hub</a></li>
            </ul>
            <a class="pui-btn pui-btn--lg dl-btn dl-punch" href="#dl-mirrors">Descargar MU PANIC <span aria-hidden="true">↓</span></a>
        </div>
    </section>
</div>
<script src="<?php echo __PATH_TEMPLATE__; ?>js/downloads.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/../js/downloads.js'), 0, 12); ?>" defer></script>
