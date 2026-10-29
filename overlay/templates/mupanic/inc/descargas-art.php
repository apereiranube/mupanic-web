<?php
/**
 * MU PANIC · Descargas: arte con nombre por contenido en img/descargas/<nombre>-<hash12>.(webp|png|svg).
 * Se detecta solo; si el archivo no está, cada lugar queda limpio (color plano o tipografía), nunca un dibujo.
 *   descargas-ragefighter (portada, 1376x1072) · descargas-fondo (bloque naranja de la portada, SVG 2400x1600)
 *   descargas-caja-cerrada / descargas-caja-abierta (instalación, mismo tamaño y posición)
 *   descargas-escena (escenario de la instalación, SVG 1300x800)
 *   descargas-duo (referidos, ~2400x1200)
 *   descargas-ayuda-antivirus / -actualizando / -modificados / -bloqueado (íconos de ayuda)
 *   descargas-cierre (fondo del cierre, SVG 2400x900) · descargas-fondo-banda (bandas oscuras, SVG 2400x1600)
 */
if(!defined('access') || !access) die();
if(!function_exists('panicDescargasArt')) {
    function panicDescargasArtFiles() {
        static $files = null;
        if($files === null) { $dir = __DIR__.'/../img/descargas/'; $files = is_dir($dir) ? (scandir($dir) ?: []) : []; }
        return $files;
    }
    function panicDescargasArt($name) {
        foreach(panicDescargasArtFiles() as $f) {
            if(preg_match('/^'.preg_quote($name, '/').'-[0-9a-f]{12}\.(webp|png|svg)$/D', $f)) return (defined('__PATH_TEMPLATE__') ? __PATH_TEMPLATE__ : '').'img/descargas/'.$f;
        }
        return null;
    }
}
