<?php
if(!defined('access') or !access) die();

function mupanicPageTitle($page, $subpage = '') {
    $map = array(
        'information' => 'Guías y sistemas',
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
