<?php
/**
 * MU PANIC · Rankings: arte con nombre por contenido en img/rankings/<nombre>-<hash12>.(webp|png|svg).
 * Se detecta solo; si el archivo no está, cada lugar queda limpio (sin dibujos hechos con código).
 *   rankings-soulmaster (portada) · rankings-fondo (fondo de la portada, SVG 2400x1600)
 *   rankings-podio (2400x900: 2.º izquierda, 1.º centro, 3.º derecha)
 *   rankings-insignia-1/-2/-3 (tarjetas del podio y puestos 1 a 3 de la tabla)
 *   rankings-cat-<clave> (level, master, resets, grandresets, guilds, killers, bloodcastle, devilsquare, duels, gens, online, votes)
 *   rankings-clase-<id> (sm, bk, elfa, mg, dl, su, rf)
 *   rankings-fondo-claro (SVG 2400x1400, banda del podio) · rankings-fondo-tabla (SVG 2400x2400, banda de la tabla)
 */
if(!defined('access') || !access) die();
if(!function_exists('panicRankingArt')) {
    function panicRankingArtFiles() {
        static $files = null;
        if($files === null) { $dir = __DIR__.'/../img/rankings/'; $files = is_dir($dir) ? (scandir($dir) ?: []) : []; }
        return $files;
    }
    function panicRankingArt($name) {
        foreach(panicRankingArtFiles() as $f) {
            if(preg_match('/^'.preg_quote($name, '/').'-[0-9a-f]{12}\.(webp|png|svg)$/D', $f)) return (defined('__PATH_TEMPLATE__') ? __PATH_TEMPLATE__ : '').'img/rankings/'.$f;
        }
        return null;
    }
    // Solo lo que existe: js/rankings.js arma el podio y las filas con esto.
    function panicRankingArtMap() {
        $map = ['podio'=>panicRankingArt('rankings-podio'), 'insignia'=>[], 'cat'=>[], 'clase'=>[]];
        foreach([1, 2, 3] as $n) if($src = panicRankingArt('rankings-insignia-'.$n)) $map['insignia'][$n] = $src;
        foreach(['level','master','resets','grandresets','guilds','killers','bloodcastle','devilsquare','duels','gens','online','votes'] as $k) if($src = panicRankingArt('rankings-cat-'.$k)) $map['cat'][$k] = $src;
        foreach(['sm','bk','elfa','mg','dl','su','rf'] as $k) if($src = panicRankingArt('rankings-clase-'.$k)) $map['clase'][$k] = $src;
        $map['bg'] = [];
        foreach(['stage'=>'rankings-fondo-claro','board'=>'rankings-fondo-tabla'] as $k=>$n) if($src = panicRankingArt($n)) $map['bg'][$k] = $src;
        if(!$map['bg']) $map['bg'] = new stdClass();
        if(!$map['podio']) unset($map['podio']);
        foreach(['insignia','cat','clase'] as $k) if(!$map[$k]) $map[$k] = new stdClass();
        return $map;
    }
    function panicRankingArtScript() {
        return '<script>window.RK_ART='.json_encode(panicRankingArtMap(), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT).';</script>';
    }
}
