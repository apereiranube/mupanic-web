<?php
if(!defined('access') or !access) die();

function mupanicPageTitle($page, $subpage = '') {
    $map = array(
        'information' => 'Guías y sistemas',
        'info' => 'Atlas PANIC',
        'downloads' => 'Descargas',
        'rankings' => 'Rankings',
        'register' => 'Crear cuenta',
        'login' => 'Ingresar',
        'usercp' => 'Mi cuenta',
        'forgotpassword' => 'Recuperar contraseña',
        'castlesiege' => 'Castle Siege',
    );
    if(isset($map[$page])) return $map[$page];
    if(check_value($page)) return ucfirst(str_replace(array('-', '_'), ' ', $page));
    return 'MU PANIC';
}

function mupanicServerValue($key, $fallback = '—') {
    $value = config($key, true);
    return check_value($value) ? $value : $fallback;
}

function templateBuildNavbar() {
    $cfg = loadConfig('navbar');
    if(!is_array($cfg)) return;
    echo '<ul>';
    foreach($cfg as $element) {
        if(!is_array($element) || !$element['active']) continue;
        $link = ($element['type'] == 'internal' ? __BASE_URL__ . $element['link'] : $element['link']);
        $title = (check_value(lang($element['phrase'], true)) ? lang($element['phrase'], true) : 'Unk_phrase');
        if($element['visibility'] == 'guest' && isLoggedIn()) continue;
        if($element['visibility'] == 'user' && !isLoggedIn()) continue;
        echo '<li><a href="'.$link.'"'.($element['newtab'] ? ' target="_blank"' : '').'>'.$title.'</a></li>';
    }
    echo '</ul>';
}

function templateBuildUsercp() {
    $cfg = loadConfig('usercp');
    if(!is_array($cfg)) return;
    echo '<ul class="account-menu">';
    foreach($cfg as $element) {
        if(!is_array($element) || !$element['active']) continue;
        $link = ($element['type'] == 'internal' ? __BASE_URL__ . $element['link'] : $element['link']);
        $title = (check_value(lang($element['phrase'], true)) ? lang($element['phrase'], true) : 'Unk_phrase');
        if($element['visibility'] == 'guest' && isLoggedIn()) continue;
        if($element['visibility'] == 'user' && !isLoggedIn()) continue;
        echo '<li><a href="'.$link.'"'.($element['newtab'] ? ' target="_blank"' : '').'><span class="menu-dot"></span>'.$title.'<span class="menu-arrow">→</span></a></li>';
    }
    echo '</ul>';
}

function templateLanguageSelector() {
    $langList = array(
        'en' => array('English', 'US'),
        'es' => array('Español', 'ES'),
        'ph' => array('Filipino', 'PH'),
        'br' => array('Português', 'BR'),
        'ro' => array('Romanian', 'RO'),
        'cn' => array('Simplified Chinese', 'CN'),
        'ru' => array('Russian', 'RU'),
        'lt' => array('Lithuanian', 'LT'),
    );
    $lang = isset($_SESSION['language_display']) ? $_SESSION['language_display'] : config('language_default', true);
    if(!isset($langList[$lang])) $lang = 'es';
    echo '<div class="language-mini">';
    echo '<a href="'.__BASE_URL__.'language/switch/to/'.strtolower($lang).'">'.strtoupper($lang).'</a>';
    echo '</div>';
}

function templateCastleSiegeWidget() {
    $castleSiege = new CastleSiege();
    if(!$castleSiege->showWidget()) return;
    $siegeData = $castleSiege->siegeData();
    if(!is_array($siegeData) || !is_array($siegeData['castle_data'])) return;
    echo '<div class="mupanic-widget"><strong>Castle Siege</strong><p>'.$siegeData['current_stage']['title'].'</p><a href="'.__BASE_URL__.'castlesiege">Ver estado →</a></div>';
}

// Original line symbols: swords, wings, jewels and the PANIC crest.
function mupanicGlyph($type) {
    $paths = array(
        'sword' => '<path d="M25 4l3 9-15 15-5-5zM6 24l10 10M4 36l6-6M3 39l3-3"/>',
        'wings' => '<path d="M20 31V17M20 23L4 6l2 14 12 11M20 23L36 6l-2 14-12 11M8 14l9 10M32 14l-9 10M10 22l7 7M30 22l-7 7M16 34l4 4 4-4"/>',
        'gem' => '<path d="M11 8h18l7 12-16 18L4 20zM4 20h32M11 8l9 30 9-30M11 8l9 12 9-12"/>',
        'party' => '<path d="M20 7l5 5-5 5-5-5zM8 14l4 4-4 4-4-4zM32 14l4 4-4 4-4-4zM12 32v-6l8-5 8 5v6M3 30v-4l5-2M37 30v-4l-5-2M16 35h8"/>',
        'crest' => '<path d="M6 9l14-5 14 5v17L20 38 6 26zM12 25V13l8 7 8-7v12l-8 7zM20 20v12"/>'
    );
    return '<svg viewBox="0 0 40 42" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">'.($paths[$type] ?? $paths['crest']).'</svg>';
}
