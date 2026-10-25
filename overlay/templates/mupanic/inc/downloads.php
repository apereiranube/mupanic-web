<?php if(!defined('access') || !access) die();
/**
 * MU PANIC · Descargas. Links, arte de cada mirror, versión y tamaño en inc/downloads.json.
 * Pieza central: el launcher real. Debajo, los 3 mirrors y un tutorial paso a paso con imágenes.
 */
$dl = json_decode((string)@file_get_contents(__DIR__.'/downloads.json'), true) ?: [];
$dlMirrors = array_values(array_filter($dl['links'] ?? [], function($l) { return !empty($l['nombre']); }));
$dlImg = __PATH_TEMPLATE__.'img/downloads/';
$dlEsc = function($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
$dlCommunity = @include(__DIR__.'/community-config.php');
$dlDiscord = is_array($dlCommunity) && !empty($dlCommunity['invite']) ? $dlCommunity['invite'] : 'https://discord.gg/fP4Mxcsee';
$dlReady = 0;
foreach($dlMirrors as $m) { if(!empty($m['url']) && preg_match('#^https://#i', $m['url'])) $dlReady++; }
$dlMeta = array_filter([$dl['version'] ?? '', $dl['tamano'] ?? '', !empty($dl['fecha']) ? 'actualizado el '.$dl['fecha'] : '']);
$dlSteps = [
    ['paso-1-descarga-v1.webp', 'Descargá el cliente', 'Elegí cualquiera de los tres mirrors: todos tienen el mismo archivo, <code>MU_PANIC_Cliente.zip</code>. Si uno anda lento, probá con otro.', 'Archivo MU_PANIC_Cliente.zip descargado'],
    ['paso-2-descomprimir-v1.webp', 'Descomprimilo en una carpeta tuya', 'Clic derecho sobre el archivo y <em>Extraer todo</em>. Elegí una carpeta como <code>C:\MU PANIC</code> o el Escritorio. Evitá <em>Archivos de programa</em>: ahí Windows bloquea las actualizaciones.', 'Menú Extraer todo y la carpeta C:\MU PANIC con MU PANIC Launcher.exe resaltado'],
    ['paso-3-launcher-v1.webp', 'Abrí MU PANIC Launcher', 'Siempre desde el launcher, nunca desde Main.exe. Revisa cada archivo y baja lo que falte o haya cambiado. La primera vez puede pedirte permiso de administrador: es el control anti-trampas, aceptalo.', 'El launcher actualizando el juego, con la barra de progreso al 46%'],
    ['paso-4-jugar-v1.webp', 'Apretá Jugar', 'Cuando diga <em>Todo listo para jugar</em>, apretá Jugar y entrá con tu cuenta. Las próximas veces el launcher baja solo las novedades.', 'El launcher con el mensaje Todo listo para jugar y el botón Jugar'],
];
?>
<div id="dl-page">
    <section class="dl-hero" aria-labelledby="dl-title">
        <div class="dl-hero-copy">
            <h1 id="dl-title">Instalá MU PANIC y entrá a jugar.</h1>
            <p class="dl-lead">Bajás el cliente una sola vez. Después el launcher se encarga del resto: actualiza el juego, repara lo que falte y te avisa cuándo está listo.</p>
            <a class="dl-btn is-main" href="#dl-mirrors"><?php echo $dlReady ? 'Elegir dónde descargar' : 'Ver cómo se instala'; ?></a>
            <?php if($dlMeta) { ?><p class="dl-meta">Para Windows 10 y 11. <?php echo $dlEsc(implode(', ', $dlMeta)); ?>.</p><?php } ?>
        </div>
        <figure class="dl-shot">
            <img src="<?php echo $dlImg; ?>launcher-v1-1600.webp" srcset="<?php echo $dlImg; ?>launcher-v1-900.webp 900w, <?php echo $dlImg; ?>launcher-v1-1600.webp 1600w" sizes="(max-width: 900px) 100vw, 58vw" width="1600" height="985" alt="El launcher de MU PANIC listo para jugar, con las novedades del servidor y el botón Jugar" fetchpriority="high">
        </figure>
    </section>

    <section class="dl-mirrors" id="dl-mirrors" aria-labelledby="dl-mirrors-title">
        <h2 id="dl-mirrors-title">Elegí dónde descargarlo</h2>
        <p class="dl-sub"><?php echo $dlReady ? 'Los tres tienen exactamente el mismo archivo. Usá el que te ande más rápido.' : 'Los links se publican en estos días. Sumate al Discord y te avisamos apenas estén.'; ?></p>
        <ul class="dl-mirror-list">
            <?php foreach($dlMirrors as $m) { $ok = !empty($m['url']) && preg_match('#^https://#i', $m['url']); ?>
            <li class="dl-mirror<?php echo $ok ? '' : ' is-off'; ?>">
                <?php if(!empty($m['arte'])) { ?><img src="<?php echo __PATH_TEMPLATE__.$dlEsc($m['arte']); ?>" alt="" loading="lazy" decoding="async"><?php } ?>
                <div class="dl-mirror-body">
                    <h3><?php echo $dlEsc($m['nombre']); ?></h3>
                    <?php if($ok) { ?>
                    <a class="dl-btn is-main" href="<?php echo $dlEsc($m['url']); ?>" target="_blank" rel="noopener">Descargar desde <?php echo $dlEsc($m['nombre']); ?></a>
                    <?php } else { ?>
                    <span class="dl-btn is-disabled" aria-disabled="true">Muy pronto</span>
                    <?php } ?>
                </div>
            </li>
            <?php } ?>
        </ul>
        <?php if(!$dlReady) { ?><p class="dl-sub"><a href="<?php echo $dlEsc($dlDiscord); ?>" target="_blank" rel="noopener">Entrar al Discord de MU PANIC</a> · <a href="<?php echo __BASE_URL__; ?>register/">Crear tu cuenta ahora</a></p><?php } ?>
    </section>

    <section class="dl-guide" aria-labelledby="dl-guide-title" data-dl-guide>
        <h2 id="dl-guide-title">Cómo instalarlo, paso a paso</h2>
        <div class="dl-guide-tabs" role="tablist" aria-label="Pasos de la instalación">
            <?php foreach($dlSteps as $i => $s) { ?>
            <button type="button" role="tab" id="dl-tab-<?php echo $i; ?>" aria-controls="dl-step-<?php echo $i; ?>" aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>" tabindex="<?php echo $i === 0 ? '0' : '-1'; ?>"><span><?php echo $i + 1; ?></span><?php echo $dlEsc($s[1]); ?></button>
            <?php } ?>
        </div>
        <?php foreach($dlSteps as $i => $s) { ?>
        <div class="dl-step" role="tabpanel" id="dl-step-<?php echo $i; ?>" aria-labelledby="dl-tab-<?php echo $i; ?>"<?php echo $i === 0 ? '' : ' hidden'; ?>>
            <img src="<?php echo $dlImg.$s[0]; ?>" width="1040" height="640" alt="<?php echo $dlEsc($s[3]); ?>" loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>" decoding="async">
            <div class="dl-step-text">
                <p class="dl-step-count">Paso <?php echo $i + 1; ?> de <?php echo count($dlSteps); ?></p>
                <h3><?php echo $dlEsc($s[1]); ?></h3>
                <p><?php echo $s[2]; ?></p>
                <div class="dl-step-nav">
                    <?php if($i > 0) { ?><button type="button" class="dl-btn" data-dl-go="<?php echo $i - 1; ?>">Anterior</button><?php } ?>
                    <?php if($i < count($dlSteps) - 1) { ?><button type="button" class="dl-btn is-main" data-dl-go="<?php echo $i + 1; ?>">Siguiente</button><?php } else { ?><a class="dl-btn is-main" href="<?php echo __BASE_URL__; ?>register/">Crear mi cuenta</a><?php } ?>
                </div>
            </div>
        </div>
        <?php } ?>
    </section>

    <section class="dl-notes">
        <div>
            <h2>Lo que necesitás</h2>
            <dl>
                <div><dt>Sistema</dt><dd>Windows 10 u 11.</dd></div>
                <div><dt>Espacio libre</dt><dd>Unos 5 GB: 1,2 GB del archivo comprimido y unos 3,5 GB del juego instalado. Después de instalar podés borrar el comprimido.</dd></div>
                <div><dt>Internet</dt><dd>El launcher se conecta al abrirse para buscar actualizaciones.</dd></div>
            </dl>
        </div>
        <div>
            <h2>Si Windows te pregunta</h2>
            <p><b>“Windows protegió su PC”.</b> Tocá <em>Más información</em> y después <em>Ejecutar de todas formas</em>.</p>
            <p><b>Permiso de administrador.</b> Lo pide el control anti-trampas que protege a todos los jugadores. Aceptalo.</p>
        </div>
    </section>

    <section class="dl-faq" aria-labelledby="dl-faq-title">
        <h2 id="dl-faq-title">Si algo no anda</h2>
        <details><summary>El antivirus borró o bloqueó archivos</summary><p>Algunos antivirus desconfían de los clientes de MU. Agregá la carpeta del juego como excepción y abrí el launcher: vuelve a bajar lo que falte.</p></details>
        <details><summary>El launcher se queda buscando actualizaciones</summary><p>Revisá tu conexión y que el firewall no lo bloquee. Si se corta, cerralo y abrilo de nuevo: retoma donde quedó.</p></details>
        <details><summary>Dice que hay archivos modificados</summary><p>Es el control del juego. Dejá que el launcher los repare: después vuelve a abrir solo.</p></details>
        <details><summary>No me deja jugar y nombra un programa</summary><p>Ese programa está en la lista de trampas o de edición de memoria. Cerralo y apretá reintentar.</p></details>
        <p class="dl-help">¿Nada de esto? Escribinos en el <a href="<?php echo $dlEsc($dlDiscord); ?>" target="_blank" rel="noopener">Discord de MU PANIC</a> con una captura del error.</p>
    </section>
</div>
<script src="<?php echo __PATH_TEMPLATE__; ?>js/downloads.js?v=<?php echo substr(hash_file('sha256', __DIR__.'/../js/downloads.js'), 0, 12); ?>" defer></script>
