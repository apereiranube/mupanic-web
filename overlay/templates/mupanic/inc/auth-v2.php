<?php if(!defined('access') || !access) die();
/**
 * MU PANIC · Cuenta (estilo nuevo): Crear cuenta, Ingresar, Olvidé mi contraseña y Verificar email.
 * Pantalla partida: a la izquierda (arriba en el celular) un bloque naranja con el título; a la derecha
 * el formulario de WebEngine dentro de una tarjeta. El formulario NO se toca: mismos campos, nombres,
 * acción y validación. Acá solo se envuelve y se le suman clases de estilo.
 *
 * Arte con nombre por contenido en img/auth/<nombre>-<hash12>.(webp|png|svg). Se detecta solo;
 * si el archivo no está, el lugar queda en naranja plano con el título (nunca un dibujo hecho con código).
 *   auth-fondo  (fondo del bloque de Crear cuenta, SVG vertical 1600x2000)
 *   auth-puerta (Ingresar / Olvidé / Verificar: la puerta de Lorencia, transparente, vertical ~1072x1259;
 *                va abajo sobre el naranja, entera, nunca sobre fondo oscuro)
 * Crear cuenta usa además el Rage Fighter de Descargas (inc/descargas-art.php), el mismo a propósito.
 */
require_once __DIR__.'/descargas-art.php';
if(!function_exists('panicAuthArt')) {
    function panicAuthPages() { return ['register','login','forgotpassword','verifyemail']; }
    function panicAuthArtFiles() {
        static $files = null;
        if($files === null) { $dir = __DIR__.'/../img/auth/'; $files = is_dir($dir) ? (scandir($dir) ?: []) : []; }
        return $files;
    }
    function panicAuthArt($name) {
        foreach(panicAuthArtFiles() as $f) {
            if(preg_match('/^'.preg_quote($name, '/').'-[0-9a-f]{12}\.(webp|png|svg)$/D', $f)) return (defined('__PATH_TEMPLATE__') ? __PATH_TEMPLATE__ : '').'img/auth/'.$f;
        }
        return null;
    }
    function panicAuthEsc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

    /** Código de invitación ya guardado (link ?ref=). Solo se usa en Crear cuenta. */
    function panicAuthRefCode($page) {
        if($page !== 'register' || !function_exists('panicReferidosCodigoPendiente')) return null;
        $code = panicReferidosCodigoPendiente();
        return is_string($code) && preg_match('/^[A-Z0-9]{8}$/D', $code) ? $code : null;
    }

    function panicAuthCopy($page) {
        $c = [
            'register' => ['kicker' => 'Cuenta gratis', 'title' => 'Sumate a la pelea.', 'lead' => 'Creá tu cuenta, bajá el cliente y entrá a jugar.'],
            'login' => ['kicker' => 'Tu cuenta', 'title' => 'Volvé a la pelea.', 'lead' => 'Entrá a Mi cuenta para resetear, recargar Eryns y conseguir tu link de referidos.'],
            'forgotpassword' => ['kicker' => 'Contraseña', 'title' => 'Recuperá el acceso.', 'lead' => 'Poné el correo de tu cuenta y te mandamos un link para recuperarla.'],
            'verifyemail' => ['kicker' => 'Correo', 'title' => 'Confirmá tu correo.', 'lead' => 'Abriste un link desde tu correo: el resultado lo ves acá.'],
        ];
        return $c[$page] ?? $c['login'];
    }

    /** Abre la pantalla partida y la tarjeta del formulario. */
    function panicAuthOpen($page) {
        $copy = panicAuthCopy($page);
        $isRegister = $page === 'register';
        $ref = panicAuthRefCode($page);
        $art = $isRegister
            ? ['fondo' => panicAuthArt('auth-fondo'), 'fig' => panicDescargasArt('descargas-ragefighter'), 'puerta' => null]
            : ['fondo' => null, 'fig' => null, 'puerta' => panicAuthArt('auth-puerta')];
        $cls = 'auth pui auth--'.($isRegister ? 'register' : 'door').' auth-p-'.$page.($ref ? ' auth--ref' : '')
            .($art['fig'] ? ' has-fig' : '').($art['puerta'] ? ' has-door' : '').($art['fondo'] ? ' has-fondo' : '');
        echo '<div id="auth-page" class="'.panicAuthEsc($cls).'">';
        echo '<section class="auth-split">';
        echo '<div class="auth-art" data-auth-art><div class="auth-stage">';
        echo '<div class="auth-bg" aria-hidden="true">';
        if($art['fondo']) echo '<i class="auth-fondo" data-auth-parallax style="background-image:url(\''.panicAuthEsc($art['fondo']).'\')"></i>';
        echo '</div>';
        if($art['puerta']) {
            $size = @getimagesize(__DIR__.'/../img/auth/'.basename($art['puerta']));
            $wh = $size ? ' width="'.(int)$size[0].'" height="'.(int)$size[1].'"' : '';
            echo '<div class="auth-door" data-auth-door aria-hidden="true"><img src="'.panicAuthEsc($art['puerta']).'" alt=""'.$wh.' decoding="async" fetchpriority="high"></div>';
        }
        if($art['fig']) echo '<div class="auth-fig" data-auth-fig aria-hidden="true"><img src="'.panicAuthEsc($art['fig']).'" alt="" width="1376" height="1072" decoding="async" fetchpriority="high"></div>';
        echo '<div class="auth-copy">';
        echo '<p class="auth-kicker">'.panicAuthEsc($copy['kicker']).'</p>';
        echo '<h1 class="auth-title">'.panicAuthEsc($copy['title']).'</h1>';
        echo '<p class="auth-lead">'.panicAuthEsc($copy['lead']).'</p>';
        if($isRegister) {
            echo '<ul class="auth-perks">';
            echo '<li><b>01</b>Gratis</li>';
            echo '<li><b>02</b>Season 6, hecho en Argentina</li>';
            echo '<li><b>03</b>'.($ref ? 'Tu código de invitación ya está cargado' : 'Si te invitaron, arrancás con 3 días de VIP').'</li>';
            echo '</ul>';
            if($ref) echo '<p class="auth-badge"><span>Te invitó un amigo</span><strong>3 días de VIP</strong></p>';
        }
        echo '</div>';
        echo '</div></div>';
        echo '<div class="auth-main"><div class="auth-card">';
    }

    /**
     * Retoques visuales del HTML del módulo (se usa como callback de ob_start):
     * saca los dos puntos de las etiquetas, el margen en línea del contenedor y suma las clases del botón.
     * No toca inputs, nombres, acción ni el campo de referidos.
     */
    function panicAuthMarkup($html) {
        $html = preg_replace('#(<label\b[^>]*class="[^"]*control-label[^"]*"[^>]*>[^<]*?)\s*:\s*</label>#u', '$1</label>', $html);
        $html = str_replace('<div class="col-xs-8 col-xs-offset-2" style="margin-top:30px;">', '<div class="col-xs-8 col-xs-offset-2 auth-form">', $html);
        $html = preg_replace('#<button type="submit"([^>]*) class="btn btn-primary">#', '<button type="submit"$1 class="btn btn-primary pui-btn pui-btn--lg auth-btn auth-punch">', $html);
        return $html;
    }

    /** Cierra la tarjeta: enlaces entre las pantallas de cuenta y, en Crear cuenta, la franja a Descargas. */
    function panicAuthClose($page) {
        $b = defined('__BASE_URL__') ? __BASE_URL__ : '/';
        $links = [
            'register' => [['¿Ya tenés cuenta?', 'Ingresá', 'login/']],
            'login' => [['¿No tenés cuenta?', 'Creala', 'register/']],
            'forgotpassword' => [['¿Te acordaste?', 'Ingresá', 'login/'], ['¿No tenés cuenta?', 'Creala', 'register/']],
            'verifyemail' => [['¿Ya está todo?', 'Ingresá', 'login/'], ['¿Se venció el link?', 'Olvidé mi contraseña', 'forgotpassword/']],
        ];
        echo '<nav class="auth-links" aria-label="Otras opciones de cuenta">';
        foreach($links[$page] ?? [] as $l) echo '<p>'.panicAuthEsc($l[0]).' <a href="'.panicAuthEsc($b.$l[2]).'">'.panicAuthEsc($l[1]).'</a></p>';
        echo '</nav>';
        echo '</div></div>';
        echo '</section>';
        if($page === 'register') {
            echo '<a class="auth-strip" href="'.panicAuthEsc($b.'downloads/').'"><span class="auth-strip-q">¿Todavía no bajaste el juego?</span><span class="auth-strip-go">Ir a Descargas <span aria-hidden="true">→</span></span></a>';
        }
        echo '</div>';
        $js = __DIR__.'/../js/auth-v2.js';
        echo '<script src="'.(defined('__PATH_TEMPLATE__') ? __PATH_TEMPLATE__ : '').'js/auth-v2.js?v='.substr(hash_file('sha256', $js), 0, 12).'" defer></script>';
    }
}
