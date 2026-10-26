<?php if(!defined('access') || !access) die();
/**
 * MU PANIC · Descargas v2.
 * Arriba la puerta de Lorencia y un solo botón; después los mirrors, la instalación jugable
 * (cofre → carpeta → launcher → Jugar), referidos, ayuda por síntoma y un cierre.
 * Links, versión y tamaño en inc/downloads.json. Ninguna imagen nombra la temporada ni la beta.
 */
$dl = json_decode((string)@file_get_contents(__DIR__.'/downloads.json'), true) ?: [];
$dlMirrors = array_values(array_filter($dl['links'] ?? [], function($l) {
    return !empty($l['nombre']) && !empty($l['url']) && preg_match('#^https://#i', $l['url']);
}));
$dlImg = __PATH_TEMPLATE__.'img/downloads/';
$dlEsc = function($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
$dlCommunity = @include(__DIR__.'/community-config.php');
$dlDiscord = is_array($dlCommunity) && !empty($dlCommunity['invite']) ? $dlCommunity['invite'] : 'https://discord.gg/fP4Mxcsee';
$dlSize = $dl['tamano'] ?? '';
$dlFile = 'MU-PANIC-Cliente.zip';
$dlHelp = [
    ['antivirus', 'El antivirus borró o bloqueó archivos', ['Agregá la carpeta del juego como excepción en tu antivirus.', 'Abrí el launcher: vuelve a bajar lo que falte.']],
    ['actualizando', 'El launcher se queda actualizando', ['Revisá tu conexión y que el firewall no bloquee al launcher.', 'Cerralo y abrilo de nuevo: retoma donde quedó.']],
    ['modificados', 'Dice que hay archivos modificados', ['Es el control del juego, no hiciste nada mal.', 'Dejá que los repare: después abre solo.']],
    ['bloqueado', 'No me deja jugar y nombra un programa', ['Ese programa está prohibido (trampas o edición de memoria).', 'Cerralo y apretá Reintentar.']],
];
?>
<div id="dl-page">

    <section class="dl-hero" aria-labelledby="dl-title">
        <img class="dl-hero-art" src="<?php echo $dlImg; ?>descarga-hero-v1.webp" alt="" fetchpriority="high">
        <div class="dl-hero-copy">
            <h1 id="dl-title">Las puertas de Lorencia están abiertas.</h1>
            <p class="dl-lead">MU Online Season 6, hecho en Argentina. Bajá el cliente, abrí el launcher y en minutos estás adentro.</p>
            <a class="dl-btn dl-btn-big" href="#dl-mirrors">Descargar MU PANIC</a>
            <p class="dl-meta">Gratis<?php echo $dlSize ? ' · '.$dlEsc($dlSize) : ''; ?> · Windows 10 y 11</p>
        </div>
    </section>

    <section class="dl-mirrors" id="dl-mirrors" aria-labelledby="dl-mirrors-title">
        <div class="dl-wrap">
            <h2 id="dl-mirrors-title">Elegí desde dónde bajarlo</h2>
            <p class="dl-sub">Los tres tienen el mismo archivo, <code><?php echo $dlFile; ?></code><?php echo $dlSize ? ' ('.$dlEsc($dlSize).')' : ''; ?>. Si uno anda lento, probá con otro.</p>
            <?php if($dlMirrors) { ?>
            <ul class="dl-mirror-list">
                <?php foreach($dlMirrors as $m) { ?>
                <li><a class="dl-mirror" href="<?php echo $dlEsc($m['url']); ?>" target="_blank" rel="noopener">
                    <span class="dl-mirror-name"><?php echo $dlEsc($m['nombre']); ?></span>
                    <span class="dl-mirror-go">Descargar</span>
                </a></li>
                <?php } ?>
            </ul>
            <?php } else { ?>
            <p class="dl-empty">Los links se publican en estos días. <a href="<?php echo $dlEsc($dlDiscord); ?>" target="_blank" rel="noopener">Sumate al Discord</a> y te avisamos.</p>
            <?php } ?>
        </div>
    </section>

    <section class="dl-install" aria-labelledby="dl-install-title" data-dl-sim>
        <div class="dl-wrap">
            <h2 id="dl-install-title">Instalalo en cuatro clics</h2>
            <p class="dl-sub">Probalo acá mismo: hacé los pasos con el mouse y vas a saber exactamente qué hacer en tu PC.</p>
            <div class="dl-sim">
                <ol class="dl-sim-steps">
                    <li data-step="0"><b>Abrí el cofre</b><span>Clic derecho sobre <code><?php echo $dlFile; ?></code> → <em>Extraer todo</em>. Elegí una carpeta tuya, como <code>C:\MU PANIC</code>. No en <em>Archivos de programa</em>.</span></li>
                    <li data-step="1"><b>Abrí el launcher</b><span>En la carpeta, doble clic en <em>MU PANIC Launcher</em>. Siempre por el launcher, nunca por Main.exe.</span></li>
                    <li data-step="2"><b>Esperá la actualización</b><span>Revisa y baja lo que falte. La primera vez puede pedir permiso de administrador: es el anti-trampas, aceptalo.</span></li>
                    <li data-step="3"><b>Apretá Jugar</b><span>Entrás con tu cuenta. Las próximas veces solo baja las novedades.</span></li>
                </ol>
                <div class="dl-stage" style="background-image:url('<?php echo $dlImg; ?>escena-fondo-v1.webp')">
                    <p class="dl-stage-hint" aria-live="polite" data-sim-hint>Hacé clic en el cofre para descomprimirlo.</p>

                    <button type="button" class="dl-chest" data-sim-chest aria-label="Descomprimir <?php echo $dlFile; ?>">
                        <img class="is-closed" src="<?php echo $dlImg; ?>cofre-cerrado-v1.webp" alt="">
                        <img class="is-open" src="<?php echo $dlImg; ?>cofre-abierto-v1.webp" alt="">
                        <span><?php echo $dlFile; ?></span>
                    </button>

                    <div class="dl-folder" data-sim-folder hidden>
                        <p class="dl-folder-path">C:\MU PANIC</p>
                        <ul>
                            <li>Data</li><li>Main.exe</li><li>main.premium</li>
                            <li><button type="button" class="dl-launcher-file" data-sim-launcher><img src="<?php echo $dlImg; ?>launcher-icono-v1.webp" alt="">MU PANIC Launcher</button></li>
                        </ul>
                    </div>

                    <div class="dl-mini" data-sim-mini hidden>
                        <div class="dl-mini-top"><img src="<?php echo $dlImg; ?>launcher-icono-v1.webp" alt=""> MU PANIC Launcher</div>
                        <p class="dl-mini-state" data-sim-state>Actualizando el juego</p>
                        <div class="dl-mini-bar"><i data-sim-bar></i></div>
                        <button type="button" class="dl-btn" data-sim-play disabled>Jugar</button>
                    </div>

                    <div class="dl-done" data-sim-done hidden>
                        <p class="dl-done-title">¡Listo, ya estás adentro!</p>
                        <p>Ahora solo te falta la cuenta. ¿Te invitó un amigo? Usá su link al crearla y arrancás con 3 días de VIP.</p>
                        <a class="dl-btn" href="<?php echo __BASE_URL__; ?>register/">Crear mi cuenta</a>
                        <button type="button" class="dl-link" data-sim-reset>Ver de nuevo</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="dl-ref" aria-labelledby="dl-ref-title">
        <div class="dl-wrap dl-ref-grid">
            <picture>
                <source media="(max-width:700px)" srcset="<?php echo __PATH_TEMPLATE__; ?>img/referidos/referidos-hero-v2-1200.webp">
                <img src="<?php echo __PATH_TEMPLATE__; ?>img/referidos/referidos-hero-v2.webp" alt="Un veterano de MU PANIC con armadura VIP junto a un jugador nuevo" loading="lazy" decoding="async">
            </picture>
            <div>
                <h2 id="dl-ref-title">Traé a tu grupo y jueguen con VIP</h2>
                <dl class="dl-ref-deal">
                    <div><dt>Tu amigo</dt><dd><strong>3 días de VIP</strong> apenas crea la cuenta con tu link.</dd></div>
                    <div><dt>Vos</dt><dd><strong>7 días de VIP</strong> cuando tu amigo llega al reset 2 y juega 3 días distintos.</dd></div>
                </dl>
                <a class="dl-btn" href="<?php echo __BASE_URL__; ?>usercp/referidos/">Conseguir mi link</a>
                <p class="dl-fine">Tu link está en Mi cuenta → Referidos. Si todavía no entraste, primero te pide ingresar.</p>
            </div>
        </div>
    </section>

    <section class="dl-help" aria-labelledby="dl-help-title">
        <div class="dl-wrap">
            <h2 id="dl-help-title">¿Algo no anda?</h2>
            <p class="dl-sub">Buscá tu caso. Cada tarjeta tiene la solución en dos pasos.</p>
            <ul class="dl-help-grid">
                <?php foreach($dlHelp as $h) { ?>
                <li class="dl-help-card">
                    <img src="<?php echo $dlImg; ?>ayuda-<?php echo $h[0]; ?>-v1.webp" alt="" loading="lazy" decoding="async" width="96" height="96">
                    <h3><?php echo $dlEsc($h[1]); ?></h3>
                    <ol><?php foreach($h[2] as $s) { ?><li><?php echo $dlEsc($s); ?></li><?php } ?></ol>
                </li>
                <?php } ?>
            </ul>
            <p class="dl-help-more">¿No es ninguno de estos? Escribinos en el <a href="<?php echo $dlEsc($dlDiscord); ?>" target="_blank" rel="noopener">Discord de MU PANIC</a> con una captura del error.</p>
        </div>
    </section>

    <section class="dl-end" aria-labelledby="dl-end-title">
        <img class="dl-end-art" src="<?php echo $dlImg; ?>cierre-fondo-v1.webp" alt="" loading="lazy" decoding="async">
        <div class="dl-end-copy">
            <h2 id="dl-end-title">Tu lugar en el ranking te espera.</h2>
            <ul class="dl-reasons">
                <li><a href="<?php echo __BASE_URL__; ?>info/#furia">Desatá la Furia</a></li>
                <li><a href="<?php echo __BASE_URL__; ?>fractura/">Elegí tu bando en la Fractura</a></li>
                <li><a href="<?php echo __BASE_URL__; ?>information/">Jugá los minijuegos del Hub</a></li>
            </ul>
            <a class="dl-btn dl-btn-big" href="#dl-mirrors">Descargar MU PANIC</a>
        </div>
    </section>
</div>
<script src="<?php echo __PATH_TEMPLATE__; ?>js/downloads.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/../js/downloads.js'), 0, 12); ?>" defer></script>
