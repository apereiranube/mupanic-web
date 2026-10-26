<?php
if(!defined('access') or !access) die();

function mupanicPageTitle($page, $subpage = '') {
    if($page === 'usercp' && $subpage !== '' && function_exists('panicAccountTools')) {
        $tools=panicAccountTools();
        if(isset($tools[$subpage])) return $tools[$subpage]['title'];
    }
    $map = array(
        'information' => 'El servidor',
        'fractura' => 'La Fractura',
        'socios' => 'Socios',
        'market' => 'El Sello de Vessara',
        'info' => 'Atlas',
        'downloads' => 'Descargas',
        'rankings' => 'Rankings',
        'profile' => $subpage === 'guild' ? 'Perfil de guild' : 'Perfil de personaje',
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

// SEO por página: título, descripción y si Google debe indexarla.
function mupanicPageSeo($page, $subpage = '', $isHome = false) {
    $base = 'MU PANIC: MU Online Season 6 argentino con reset dinámico, Furia, mascotas evolutivas, Mercado de Vessara y minijuegos.';
    if($isHome) return array('MU PANIC · MU Online Season 6 Argentina', 'Servidor argentino de MU Online Season 6: EXP 15x, reset dinámico, siete clases, guerra Nexo contra Caos, mascotas evolutivas, Mercado de Vessara y ocho minijuegos. Creá tu cuenta gratis.', 'index,follow');
    $map = array(
        'information' => array('El servidor · MU Online Season 6 con reset dinámico', 'Cómo es MU PANIC: reset dinámico de 200 a 350 puntos, siete clases con su rol, PvP con barra de vida, Furia, eventos en vivo y VIP. Todo lo que hace distinto a este MU Online argentino.'),
        'info' => array('Atlas · mapas, drops y eventos de MU Online', 'Atlas de MU PANIC: mapas, monstruos, drops, eventos con horarios de Argentina y recompensas de MU Online Season 6.'),
        'downloads' => array('Descargar MU Online · cliente para Windows', 'Descargá el cliente de MU PANIC, MU Online Season 6 para Windows, con launcher propio y actualizaciones automáticas. Gratis.'),
        'register' => array('Crear cuenta gratis · MU Online Season 6', 'Creá tu cuenta de MU PANIC gratis y empezá a jugar MU Online Season 6 en un servidor argentino.'),
        'market' => array('Mercado de Vessara · objetos de jugadores', 'El Mercado de Vessara de MU PANIC: los objetos que los jugadores venden dentro del juego, también desde la web.'),
        'fractura' => array('La Fractura · guerra semanal Nexo contra Caos', 'La Fractura de MU PANIC: la guerra semanal entre Nexo y Caos, sus rankings y el Salón de la Fama.'),
        'rankings' => array('Rankings de MU Online', 'Rankings de MU PANIC: los mejores personajes y guilds del servidor de MU Online Season 6.'),
        'castlesiege' => array('Castle Siege', 'Castle Siege en MU PANIC: la guerra por el castillo de los guilds de MU Online Season 6.'),
        'profile' => array($subpage === 'guild' ? 'Perfil de guild' : 'Perfil de personaje', $base),
        'tos' => array('Términos y condiciones', 'Términos y condiciones de MU PANIC.'),
        'privacy' => array('Privacidad', 'Política de privacidad de MU PANIC.'),
        'refunds' => array('Reembolsos', 'Política de reembolsos de MU PANIC.'),
    );
    $privadas = array('usercp', 'login', 'forgotpassword', 'verifyemail', 'logout');
    $robots = in_array($page, $privadas, true) ? 'noindex,follow' : 'index,follow';
    if(isset($map[$page])) return array($map[$page][0].' · MU PANIC', $map[$page][1], $robots);
    return array(mupanicPageTitle($page, $subpage).' · MU PANIC', $base, $robots);
}

// Datos estructurados para Google (solo en el inicio).
function mupanicStructuredData() {
    $img = 'https://mupanic.com.ar/templates/mupanic/img/share/mupanic-share-v1.jpg';
    $data = array('@context' => 'https://schema.org', '@graph' => array(
        array('@type' => 'Organization', '@id' => 'https://mupanic.com.ar/#org', 'name' => 'MU PANIC', 'url' => 'https://mupanic.com.ar/',
            'logo' => 'https://mupanic.com.ar/templates/mupanic/img/brand/mu-panic-crystal-bronze-matte-v2.webp', 'sameAs' => array('https://discord.gg/fP4Mxcsee')),
        array('@type' => 'WebSite', '@id' => 'https://mupanic.com.ar/#web', 'name' => 'MU PANIC', 'url' => 'https://mupanic.com.ar/', 'inLanguage' => 'es-AR', 'publisher' => array('@id' => 'https://mupanic.com.ar/#org')),
        array('@type' => 'VideoGame', 'name' => 'MU PANIC', 'alternateName' => 'MU Online Season 6 Argentina', 'url' => 'https://mupanic.com.ar/',
            'description' => 'Servidor argentino de MU Online Season 6 con reset dinámico, Furia, mascotas evolutivas, Mercado de Vessara y minijuegos.',
            'image' => $img, 'genre' => array('MMORPG', 'Rol', 'Acción'), 'gamePlatform' => 'PC', 'operatingSystem' => 'Windows',
            'applicationCategory' => 'Game', 'inLanguage' => 'es', 'isAccessibleForFree' => true, 'publisher' => array('@id' => 'https://mupanic.com.ar/#org')),
    ));
    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
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
    if(function_exists('panicAccountNavigation')) { panicAccountNavigation(); return; }
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

