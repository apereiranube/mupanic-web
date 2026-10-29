<?php
/**
 * MU PANIC · Mi cuenta y Tienda de Eryns: arte con nombre por contenido en img/tienda/<nombre>-<hash12>.(webp|png|svg).
 * Se detecta solo; si el archivo no está, cada lugar queda limpio (color plano, gema o letra), nunca un dibujo hecho con código.
 *   tienda-summoner (portada de la Tienda, transparente ~2000x1600)
 *   tienda-fondo (bloque azul de la portada de la Tienda y de Mi cuenta, SVG 2400x1600)
 *   tienda-fondo-banda (bandas oscuras del área de cuenta, SVG)
 *   tienda-pack-1 / -2 / -3 (packs de Eryns de chico a grande: bolsa, caja, cofre; transparentes)
 *   tienda-theryon / -nerathys / -vaeraxes (bestias, transparentes)
 *   tienda-icono-<clave> (íconos hueso en SVG, se tiñen con máscara): gem friends shield mail lock cycle compass
 *     sword stats skill crown coin vote star
 * La gema de Eryns es la del Mercado (img/votar/votar-gema-64-*.webp) y el emblema VIP el de El servidor.
 */
if(!defined('access') || !access) die();
if(!function_exists('panicTiendaArt')) {
    function panicTiendaArtFiles() {
        static $files = null;
        if($files === null) { $dir = __DIR__.'/../img/tienda/'; $files = is_dir($dir) ? (scandir($dir) ?: []) : []; }
        return $files;
    }
    function panicTiendaArt($name) {
        foreach(panicTiendaArtFiles() as $f) {
            if(preg_match('/^'.preg_quote($name, '/').'-[0-9a-f]{12}\.(webp|png|svg)$/D', $f)) return (defined('__PATH_TEMPLATE__') ? __PATH_TEMPLATE__ : '').'img/tienda/'.$f;
        }
        return null;
    }
    // Ancho y alto reales (webp/png); para SVG o si falla, el tamaño de referencia.
    function panicTiendaArtSize($name, $w, $h) {
        foreach(panicTiendaArtFiles() as $f) {
            if(preg_match('/^'.preg_quote($name, '/').'-[0-9a-f]{12}\.(webp|png)$/D', $f)) { $i = @getimagesize(__DIR__.'/../img/tienda/'.$f); if($i && $i[0] > 0 && $i[1] > 0) return [(int)$i[0], (int)$i[1]]; }
        }
        return [$w, $h];
    }
    function panicTiendaGema() {
        static $gema = false;
        if($gema === false) {
            $gema = null; $dir = __DIR__.'/../img/votar/';
            foreach((is_dir($dir) ? (scandir($dir) ?: []) : []) as $f) {
                if(preg_match('/^votar-gema-64-[0-9a-f]{12}\.webp$/D', $f)) { $gema = (defined('__PATH_TEMPLATE__') ? __PATH_TEMPLATE__ : '').'img/votar/'.$f; break; }
            }
        }
        return $gema;
    }
    function panicTiendaEmblemaVip() {
        static $vip = false;
        if($vip === false) {
            $vip = null; $dir = __DIR__.'/../img/servidor/';
            foreach((is_dir($dir) ? (scandir($dir) ?: []) : []) as $f) {
                if(preg_match('/^vip-emblema-[0-9a-f]{12}\.webp$/D', $f)) { $vip = (defined('__PATH_TEMPLATE__') ? __PATH_TEMPLATE__ : '').'img/servidor/'.$f; break; }
            }
        }
        return $vip;
    }
    /** La gema de Eryns como <img> (o nada si el archivo no está). */
    function panicTiendaGemaImg($size = 24, $class = 'tnd-gem') {
        $g = panicTiendaGema();
        return $g ? '<img class="'.htmlspecialchars($class, ENT_QUOTES, 'UTF-8').'" src="'.htmlspecialchars($g, ENT_QUOTES, 'UTF-8').'" alt="" width="'.(int)$size.'" height="'.(int)$size.'" decoding="async">' : '';
    }
    /**
     * Ícono de herramienta: el SVG hueso teñido con máscara. Sin archivo: la inicial en Anton (o nada si no hay etiqueta).
     */
    function panicTiendaIcon($key, $label = '') {
        $file = preg_match('/^[a-z]{2,12}$/D', (string)$key) ? panicTiendaArt('tienda-icono-'.$key) : null;
        if($file) return '<i class="cta-ic" aria-hidden="true" style="--ic:url(\''.htmlspecialchars($file, ENT_QUOTES, 'UTF-8').'\')"></i>';
        $label = trim((string)$label);
        if($label === '') return '';
        $first = function_exists('mb_substr') ? mb_strtoupper(mb_substr($label, 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($label, 0, 1));
        return '<i class="cta-ic cta-ic--txt" aria-hidden="true">'.htmlspecialchars($first, ENT_QUOTES, 'UTF-8').'</i>';
    }
}
