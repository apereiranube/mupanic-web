<?php
/**
 * MU PANIC · Socios: arte con nombre por contenido en img/socios-v2/<nombre>-<hash12>.(webp|png|svg).
 * Se detecta solo; si el archivo no está, cada lugar queda limpio (color plano o tipografía), nunca un dibujo.
 *   socios-elfa (portada, ~2000x1600, transparente) · socios-fondo (bloque verde de la portada, SVG 2400x1600)
 *   socios-paso-link / -amigo / -cobro (íconos de los 3 pasos)
 *   socios-emblema-socio / -creador / -mes (niveles y puesto 1 del ranking)
 *   socios-cierre (fondo del cierre, SVG 2400x900) · socios-fondo-banda (bandas oscuras, SVG 2400x1600)
 *   socios-creadores-escena (portada de Creadores, ~2400x1200)
 * La gema de Eryns es la misma del Mercado (img/votar/votar-gema-64-*.webp).
 */
if(!defined('access') || !access) die();
if(!function_exists('panicSociosArt')) {
    function panicSociosArtFiles() {
        static $files = null;
        if($files === null) { $dir = __DIR__.'/../img/socios-v2/'; $files = is_dir($dir) ? (scandir($dir) ?: []) : []; }
        return $files;
    }
    function panicSociosArt($name) {
        foreach(panicSociosArtFiles() as $f) {
            if(preg_match('/^'.preg_quote($name, '/').'-[0-9a-f]{12}\.(webp|png|svg)$/D', $f)) return (defined('__PATH_TEMPLATE__') ? __PATH_TEMPLATE__ : '').'img/socios-v2/'.$f;
        }
        return null;
    }
    // Ancho y alto reales del archivo (webp/png); para SVG o si falla, el tamaño de referencia.
    function panicSociosArtSize($name, $w, $h) {
        foreach(panicSociosArtFiles() as $f) {
            if(preg_match('/^'.preg_quote($name, '/').'-[0-9a-f]{12}\.(webp|png)$/D', $f)) { $i = @getimagesize(__DIR__.'/../img/socios-v2/'.$f); if($i && $i[0] > 0 && $i[1] > 0) return [(int)$i[0], (int)$i[1]]; }
        }
        return [$w, $h];
    }
    function panicSociosGema() {
        $dir = __DIR__.'/../img/votar/';
        foreach((is_dir($dir) ? (scandir($dir) ?: []) : []) as $f) {
            if(preg_match('/^votar-gema-64-[0-9a-f]{12}\.webp$/D', $f)) return (defined('__PATH_TEMPLATE__') ? __PATH_TEMPLATE__ : '').'img/votar/'.$f;
        }
        return null;
    }
}
