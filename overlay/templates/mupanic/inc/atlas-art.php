<?php
/**
 * MU PANIC · Atlas: arte con nombre por contenido en img/atlas-v2/<nombre>-<hash12>.(webp|png|svg).
 * Se detecta solo; si el archivo no está, cada lugar queda limpio (color plano o una letra Anton), nunca un dibujo.
 *   atlas-mapa (portada: mapa plano del continente, lima/noche, ~2400x1600, transparente o SVG)
 *   atlas-fondo (bloque lima de la portada, SVG 2400x1600) · atlas-fondo-banda (bandas oscuras, SVG)
 *   atlas-icono-<clave> (íconos hueso para la navegación, se tiñen con máscara):
 *     inicio, mapas, buscar, recompensas, eventos, vinculos, furia, vessara, fractura,
 *     drops, monstruos, territorios, guia
 * Las imágenes de referencia del juego (objetos, monstruos, planos, territorios, vínculos) siguen en img/atlas/.
 */
if(!defined('access') || !access) die();
if(!function_exists('panicAtlasArt')) {
    function panicAtlasArtFiles() {
        static $files = null;
        if($files === null) { $dir = __DIR__.'/../img/atlas-v2/'; $files = is_dir($dir) ? (scandir($dir) ?: []) : []; }
        return $files;
    }
    function panicAtlasArt($name) {
        foreach(panicAtlasArtFiles() as $f) {
            if(preg_match('/^'.preg_quote($name, '/').'-[0-9a-f]{12}\.(webp|png|svg)$/D', $f)) return (defined('__PATH_TEMPLATE__') ? __PATH_TEMPLATE__ : '').'img/atlas-v2/'.$f;
        }
        return null;
    }
    // Arte nuevo que ya existe en otras páginas (img/<carpeta>/<nombre>-<hash12>.ext): ruta o null.
    function panicAtlasSiteArt($dir, $name) {
        static $cache = [];
        if(!isset($cache[$dir])) { $d = __DIR__.'/../img/'.$dir.'/'; $cache[$dir] = is_dir($d) ? (scandir($d) ?: []) : []; }
        foreach($cache[$dir] as $f) {
            if(preg_match('/^'.preg_quote($name, '/').'-[0-9a-f]{12}\.(webp|png|svg)$/D', $f)) return (defined('__PATH_TEMPLATE__') ? __PATH_TEMPLATE__ : '').'img/'.$dir.'/'.$f;
        }
        return null;
    }
    // Arte nuevo o, si falta el archivo, la imagen anterior (ruta relativa a la plantilla).
    function panicAtlasPick($dir, $name, $fallback) {
        $art = panicAtlasSiteArt($dir, $name);
        return $art ? $art : (defined('__PATH_TEMPLATE__') ? __PATH_TEMPLATE__ : '').$fallback;
    }
    function panicAtlasE($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
    // Ícono de navegación: máscara teñible si hay arte; si no, la inicial en Anton.
    function panicAtlasIcon($key, $letter) {
        $art = panicAtlasArt('atlas-icono-'.$key);
        if($art) return '<i class="atl-ico has-art" aria-hidden="true" style="--ico:url(\''.htmlspecialchars($art, ENT_QUOTES, 'UTF-8').'\')"></i>';
        return '<i class="atl-ico" aria-hidden="true">'.htmlspecialchars($letter, ENT_QUOTES, 'UTF-8').'</i>';
    }
}
