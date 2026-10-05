<?php
if(!defined('access') || !access) die();

function panicAccountTools() {
    return [
        'shopadmin'=>['title'=>'Administrar tienda','copy'=>'Paquetes, promociones, compras y entrega automática.','group'=>'Administración','icon'=>'crown'],
        'recharge'=>['title'=>'Recargar WCoin C','copy'=>'Paquetes, medios de pago y estado de tus recargas.','group'=>'Créditos y Zen','icon'=>'gem'],
        'myaccount'=>['title'=>'Mi cuenta','copy'=>'Tus datos, personajes y estado de conexión.','group'=>'Cuenta','icon'=>'shield'],
        'myemail'=>['title'=>'Cambiar correo','copy'=>'Actualizá el correo de tu cuenta.','group'=>'Cuenta','icon'=>'mail'],
        'mypassword'=>['title'=>'Cambiar contraseña','copy'=>'Administrá la contraseña de acceso.','group'=>'Cuenta','icon'=>'shield'],
        'reset'=>['title'=>'Reset de personaje','copy'=>'Consultá los requisitos y elegí el personaje.','group'=>'Personajes','icon'=>'cycle'],
        'unstick'=>['title'=>'Destrabar personaje','copy'=>'Recuperá un personaje que quedó atascado.','group'=>'Personajes','icon'=>'compass'],
        'clearpk'=>['title'=>'Limpiar PK','copy'=>'Consultá el costo para limpiar el estado PK.','group'=>'Personajes','icon'=>'sword'],
        'resetstats'=>['title'=>'Reiniciar atributos','copy'=>'Consultá los requisitos para redistribuir los puntos.','group'=>'Personajes','icon'=>'cycle'],
        'addstats'=>['title'=>'Asignar puntos','copy'=>'Distribuí los puntos disponibles de tu personaje.','group'=>'Personajes','icon'=>'gem'],
        'clearskilltree'=>['title'=>'Reiniciar habilidades','copy'=>'Consultá los requisitos para reiniciar el árbol Master.','group'=>'Personajes','icon'=>'magic'],
        'vote'=>['title'=>'Votar por créditos','copy'=>'Revisá los sitios de votación y sus recompensas.','group'=>'Créditos y Zen','icon'=>'crown'],
        'donation'=>['title'=>'Comprar créditos','copy'=>'Consultá las opciones disponibles para tu cuenta.','group'=>'Créditos y Zen','icon'=>'gem'],
        'buycredits'=>['title'=>'Comprar créditos','copy'=>'Consultá las opciones disponibles para tu cuenta.','group'=>'Créditos y Zen','icon'=>'gem'],
        'buyzen'=>['title'=>'Comprar Zen','copy'=>'Consultá el cambio de créditos por Zen.','group'=>'Créditos y Zen','icon'=>'coin'],
        'vip'=>['title'=>'Suscripción VIP','copy'=>'Tu VIP, sus beneficios y el estado de tu cuenta.','group'=>'Créditos y Zen','icon'=>'crown'],
    ];
}
function panicAccountEscape($value) { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function panicAccountIcon($type) {
    $paths = [
        'shield'=>'M6 7l14-4 14 4v17L20 37 6 24zM13 19l5 5 10-11',
        'mail'=>'M5 10h30v23H5zM5 11l15 12 15-12',
        'cycle'=>'M31 12A14 14 0 1 0 34 26M31 5v9H22',
        'compass'=>'M20 4a16 16 0 1 0 0 32 16 16 0 0 0 0-32zM26 14l-4 10-10 4 4-10z',
        'sword'=>'M26 4l3 9-15 15-6-6zM6 24l11 11M4 37l7-7',
        'gem'=>'M11 7h18l7 12-16 19L4 19zM4 19h32M11 7l9 31L29 7',
        'magic'=>'M20 3l5 12 12 5-12 5-5 12-5-12-12-5 12-5z',
        'crown'=>'M6 11l7 8 7-14 7 14 7-8-3 20H9zM9 36h22',
        'coin'=>'M20 4a16 16 0 1 0 0 32 16 16 0 0 0 0-32zM25 13H15v7h10v7H15M20 9v22',
    ];
    return '<svg viewBox="0 0 40 42" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="'.($paths[$type] ?? $paths['shield']).'"/></svg>';
}
function panicAccountMenuItems() {
    $config = loadConfig('usercp'); $items=[]; $tools=panicAccountTools();
    if(!is_array($config)) $config=[];
    foreach($config as $element) {
        if(!is_array($element) || empty($element['active'])) continue;
        if(($element['visibility'] ?? '') === 'guest' && isLoggedIn()) continue;
        if(($element['visibility'] ?? '') === 'user' && !isLoggedIn()) continue;
        $link = ($element['type'] === 'internal' ? __BASE_URL__.$element['link'] : $element['link']);
        if(preg_match('~^(?:javascript|data):~i', trim($link))) continue;
        $path = parse_url($link, PHP_URL_PATH) ?? '';
        $key = basename(rtrim($path, '/'));
        if(strpos($path, '/donation/') !== false) $key='donation';
        // MU PANIC uses its own WCoin C recharge flow. Hide legacy WebEngine purchase modules.
        if(in_array($key, ['donation','buycredits','buyzen'], true)) continue;
        $tool=$tools[$key] ?? ['title'=>strip_tags(lang($element['phrase'], true)), 'copy'=>'Abrí esta opción para consultar los detalles.', 'group'=>'Más opciones','icon'=>'shield'];
        $items[] = array_merge($tool, ['key'=>$key,'href'=>$link,'newtab'=>!empty($element['newtab'])]);
    }
    // Storefront checkout availability is controlled by private payment settings.
    if(isLoggedIn() && !in_array('vip',array_column($items,'key'),true)) $items[] = array_merge($tools['vip'], ['key'=>'vip','href'=>__BASE_URL__.'usercp/vip/','newtab'=>false]);
    if(isLoggedIn()) $items[] = array_merge($tools['recharge'], ['key'=>'recharge','href'=>__BASE_URL__.'usercp/recharge/','newtab'=>false]);
    require_once __DIR__.'/recharge-management.php';
    if(panicRechargeAdminAllowed()) $items[]=array_merge($tools['shopadmin'],['key'=>'shopadmin','href'=>__BASE_URL__.'usercp/shopadmin/','newtab'=>false]);
    return $items;
}
function panicAccountNavigation() {
    $items=panicAccountMenuItems(); $current=(string)($_REQUEST['subpage'] ?? '');
    echo '<a class="account-overview-link" href="'.panicAccountEscape(__BASE_URL__.'usercp/').'"'.($current==='' ? ' aria-current="page"' : '').'>'.panicAccountIcon('compass').'Inicio del panel</a>';
    foreach(['Cuenta','Personajes','Créditos y Zen','Más opciones','Administración'] as $group) {
        $groupItems=array_filter($items,function($item) use ($group) { return $item['group']===$group; });
        if(!$groupItems) continue;
        echo '<div class="account-nav-group"><span class="account-nav-group-title">'.panicAccountEscape($group).'</span><ul class="account-menu">';
        foreach($groupItems as $item) {
            echo '<li><a href="'.panicAccountEscape($item['href']).'"'.($item['newtab'] ? ' target="_blank" rel="noopener"' : '').($item['key']===$current ? ' aria-current="page"' : '').'>'.panicAccountIcon($item['icon']).'<span>'.panicAccountEscape($item['title']).'</span><span class="menu-arrow" aria-hidden="true">→</span></a></li>';
        }
        echo '</ul></div>';
    }
}
function panicAccountHome() {
    $name=isset($_SESSION['username']) ? (string)$_SESSION['username'] : '';
    echo '<section class="account-welcome"><span class="eyebrow">TU AVENTURA CONTINÚA</span><h2>'.($name!=='' ? 'Hola, '.panicAccountEscape($name) : 'Bienvenido a MU PANIC').'</h2><p>Administrá tu cuenta y tus personajes.<br>Elegí qué querés hacer.</p></section>';
    $items=panicAccountMenuItems();
    if(!$items) { echo '<p class="alert alert-info">No hay opciones disponibles para esta cuenta en este momento.</p>'; return; }
    foreach(['Cuenta','Personajes','Créditos y Zen','Más opciones','Administración'] as $group) {
        $groupItems=array_filter($items,function($item) use ($group) { return $item['group']===$group; });
        if(!$groupItems) continue;
        echo '<section class="account-tool-section"><h2>'.panicAccountEscape($group).'</h2><div class="account-tools-grid">';
        foreach($groupItems as $item) {
            echo '<a class="account-tool" href="'.panicAccountEscape($item['href']).'"'.($item['newtab'] ? ' target="_blank" rel="noopener"' : '').'><span class="account-tool-icon">'.panicAccountIcon($item['icon']).'</span><span><strong>'.panicAccountEscape($item['title']).'</strong><small>'.panicAccountEscape($item['copy']).'</small></span><b aria-hidden="true">↗</b></a>';
        }
        echo '</div></section>';
    }
}
function panicAccountFormMarkup($markup, $module) {
    if(!in_array($module, ['reset','resetstats','clearpk','clearskilltree','unstick','vote'], true)) return $markup;
    // Legacy modules put forms around table rows, which browsers discard.
    // Emit the same forms outside the table and associate their controls by ID.
    // Keep action, method, hidden fields, submit names and values unchanged.
    $forms=[];
    $markup=preg_replace_callback('~<form\b([^>]*)>\s*((?:<input\b[^>]*>\s*)+)(<tr\b.*?</tr>)\s*</form>~is', function($match) use (&$forms) {
        if(preg_match('~\bid\s*=~i', $match[1])) return $match[0];
        $id='panic-character-form-'.count($forms);
        $forms[]='<form'.$match[1].' id="'.$id.'">'.$match[2].'</form>';
        return preg_replace('~<(button|input|select|textarea)\b~i', '<$1 form="'.$id.'"', $match[3]);
    }, $markup);
    return implode('', $forms).$markup;
}
