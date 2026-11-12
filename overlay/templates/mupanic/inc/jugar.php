<?php if(!defined('access') || !access) die();
/**
 * MU PANIC · /jugar/ — página de entrada para la publicidad.
 * Es Crear cuenta con otra envoltura: una sola pantalla, sin menú, con el título, cuatro motivos
 * y el formulario de WebEngine ya visible. El formulario NO se toca (mismos campos, nombres y validación):
 * index.php trata /jugar/ como 'register' y solo cambia lo que lo rodea.
 *
 * Arte con nombre por contenido en img/jugar/<nombre>-<hash12>.webp. Se detecta solo; si falta un archivo,
 * ese lugar queda vacío (nunca un dibujo hecho con código).
 *   jugar-portada (Summoner recortada) · jugar-fondo · jugar-icono-furia|mascota|mercado|nuevo
 */
if(!function_exists('panicJugarArt')) {
    function panicJugarArt($name) {
        static $files = null;
        if($files === null) { $dir = __DIR__.'/../img/jugar/'; $files = is_dir($dir) ? (scandir($dir) ?: []) : []; }
        foreach($files as $f) {
            if(preg_match('/^'.preg_quote($name, '/').'-[0-9a-f]{12}\.webp$/D', $f)) {
                $size = @getimagesize(__DIR__.'/../img/jugar/'.$f);
                return ['src' => (defined('__PATH_TEMPLATE__') ? __PATH_TEMPLATE__ : '').'img/jugar/'.$f, 'w' => $size ? (int)$size[0] : 0, 'h' => $size ? (int)$size[1] : 0];
            }
        }
        return null;
    }
    function panicJugarEsc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

    function panicJugarMotivos() {
        return [
            ['furia', 'Furia', 'Cargás la barra peleando y por unos segundos sos otro personaje.'],
            ['mascota', 'Mascotas que hacés vos', 'Las creás, las evolucionás y juntan el Zen solas.'],
            ['mercado', 'Mercado de Vessara', 'Vendés mientras jugás y cobrás en el momento.'],
            ['nuevo', 'Server nuevo, EXP x50', 'Abrió el 6 de noviembre: todos arrancan parejo.'],
        ];
    }

    /** Abre la pantalla: texto a la izquierda, personaje en el medio y la tarjeta del formulario a la derecha. */
    function panicJugarOpen() {
        $fondo = panicJugarArt('jugar-fondo');
        $fig = panicJugarArt('jugar-portada');
        $ref = function_exists('panicAuthRefCode') ? panicAuthRefCode('register') : null;
        echo '<div id="auth-page" class="auth pui auth-p-register jugar'.($ref ? ' auth--ref' : '').'">';
        echo '<section class="jugar-pantalla"'.($fondo ? ' style="background-image:url(\''.panicJugarEsc($fondo['src']).'\')"' : '').'>';
        echo '<div class="jugar-grilla">';
        echo '<div class="jugar-texto">';
        echo '<h1 class="jugar-titulo">MU, como <em>nunca</em> lo jugaste.</h1>';
        echo '<p class="jugar-bajada">Season 6 con cosas que ningún otro server tiene. Creá tu cuenta y en diez minutos estás adentro.</p>';
        echo '<ul class="jugar-motivos">';
        foreach(panicJugarMotivos() as $m) {
            $ic = panicJugarArt('jugar-icono-'.$m[0]);
            echo '<li class="jugar-m-'.panicJugarEsc($m[0]).'">';
            echo '<span class="jugar-ico" aria-hidden="true">'.($ic ? '<img src="'.panicJugarEsc($ic['src']).'" alt="" width="'.$ic['w'].'" height="'.$ic['h'].'" decoding="async">' : '').'</span>';
            echo '<span class="jugar-m-txt"><b>'.panicJugarEsc($m[1]).'</b><span>'.panicJugarEsc($m[2]).'</span></span>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';
        echo '<div class="jugar-fig" aria-hidden="true">'.($fig ? '<img src="'.panicJugarEsc($fig['src']).'" alt="" width="'.$fig['w'].'" height="'.$fig['h'].'" decoding="async" fetchpriority="high">' : '').'</div>';
        echo '<div class="jugar-form"><div class="auth-card">';
    }

    /** Cierra la tarjeta y suma la franja de abajo: descarga y salida al resto de la web. */
    function panicJugarClose() {
        $b = defined('__BASE_URL__') ? __BASE_URL__ : '/';
        echo '<p class="jugar-pc">Solo para PC. ¿Ya tenés cuenta? <a href="'.panicJugarEsc($b.'login/').'">Ingresá</a></p>';
        echo '</div></div>';
        echo '</div>';
        echo '</section>';
        echo '<div class="jugar-abajo"><div class="jugar-abajo-in">';
        echo '<p>¿Ya creaste tu cuenta? Bajá el juego y entrá.</p>';
        echo '<span class="jugar-acc"><a class="jugar-btn" href="'.panicJugarEsc($b.'downloads/').'">Descargar el juego</a>';
        echo '<a class="jugar-link" href="'.panicJugarEsc($b).'">Conocé todo el server</a></span>';
        echo '</div></div>';
        echo '</div>';
        $js = __DIR__.'/../js/auth-v2.js';
        echo '<script src="'.(defined('__PATH_TEMPLATE__') ? __PATH_TEMPLATE__ : '').'js/auth-v2.js?v='.substr(hash_file('sha256', $js), 0, 12).'" defer></script>';
    }
}
