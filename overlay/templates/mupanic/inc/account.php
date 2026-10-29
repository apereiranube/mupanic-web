<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/tienda-art.php';

function panicAccountTools() {
    return [
        'shopadmin'=>['title'=>'Panel administrador','copy'=>'Transferencias, compras, economía, referidos y configuración.','group'=>'Administración','icon'=>'crown'],
        'recharge'=>['title'=>'Recargar Eryns','copy'=>'Elegí tus Eryns, pagá con Ualá y seguí la entrega a tu cuenta.','group'=>'Eryns y VIP','icon'=>'gem'],
        'referidos'=>['title'=>'Referidos','copy'=>'Invitá amigos y ganá días de VIP.','group'=>'Eryns y VIP','icon'=>'friends'],
        'myaccount'=>['title'=>'Datos de mi cuenta','copy'=>'Tus datos, personajes y estado de conexión.','group'=>'Seguridad','icon'=>'shield'],
        'myemail'=>['title'=>'Cambiar correo','copy'=>'Actualizá el correo de tu cuenta.','group'=>'Seguridad','icon'=>'mail'],
        'mypassword'=>['title'=>'Cambiar contraseña','copy'=>'Administrá la contraseña de acceso.','group'=>'Seguridad','icon'=>'lock'],
        'reset'=>['title'=>'Reset de personaje','copy'=>'Consultá los requisitos y elegí el personaje.','group'=>'Personajes','icon'=>'cycle'],
        'unstick'=>['title'=>'Destrabar personaje','copy'=>'Recuperá un personaje que quedó atascado.','group'=>'Personajes','icon'=>'compass'],
        'clearpk'=>['title'=>'Limpiar PK','copy'=>'Consultá el costo para limpiar el estado PK.','group'=>'Personajes','icon'=>'sword'],
        'resetstats'=>['title'=>'Reiniciar atributos','copy'=>'Consultá los requisitos para redistribuir los puntos.','group'=>'Personajes','icon'=>'stats'],
        'addstats'=>['title'=>'Asignar puntos','copy'=>'Distribuí los puntos disponibles de tu personaje.','group'=>'Personajes','icon'=>'stats'],
        'clearskilltree'=>['title'=>'Reiniciar habilidades','copy'=>'Consultá los requisitos para reiniciar el árbol Master.','group'=>'Personajes','icon'=>'skill'],
        'vote'=>['title'=>'Votar por créditos','copy'=>'Revisá los sitios de votación y sus recompensas.','group'=>'Eryns y VIP','icon'=>'vote'],
        'donation'=>['title'=>'Comprar créditos','copy'=>'Consultá las opciones disponibles para tu cuenta.','group'=>'Eryns y VIP','icon'=>'gem'],
        'buycredits'=>['title'=>'Comprar créditos','copy'=>'Consultá las opciones disponibles para tu cuenta.','group'=>'Eryns y VIP','icon'=>'gem'],
        'buyzen'=>['title'=>'Comprar Zen','copy'=>'Consultá el cambio de créditos por Zen.','group'=>'Eryns y VIP','icon'=>'coin'],
        'vip'=>['title'=>'VIP PANIC','copy'=>'Tu VIP, sus beneficios y el estado de tu cuenta.','group'=>'Eryns y VIP','icon'=>'crown'],
    ];
}
function panicAccountEscape($value) { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
// Presentation only: historical package names keep their original stored values.
function panicAccountCurrencyLabel($value) { return preg_replace('/\bWCoin(?:\s+C)?\b/i', 'Eryns', (string)$value); }
// Íconos de herramientas: arte hueso de img/tienda (tienda-icono-<clave>). Sin archivo: la inicial en Anton o nada.
function panicAccountIcon($type, $label = '') { return panicTiendaIcon((string)$type, (string)$label); }
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
        if(in_array($key, ['donation','buycredits','buyzen','vote'], true)) continue; // vote: reemplazado por /votar/
        $tool=$tools[$key] ?? ['title'=>strip_tags(lang($element['phrase'], true)), 'copy'=>'Abrí esta opción para consultar los detalles.', 'group'=>'Más opciones','icon'=>'shield'];
        $items[] = array_merge($tool, ['key'=>$key,'href'=>$link,'newtab'=>!empty($element['newtab'])]);
    }
    // Storefront checkout availability is controlled by private payment settings.
    if(isLoggedIn() && !in_array('vip',array_column($items,'key'),true)) $items[] = array_merge($tools['vip'], ['key'=>'vip','href'=>__BASE_URL__.'usercp/vip/','newtab'=>false]);
    if(isLoggedIn() && !in_array('recharge',array_column($items,'key'),true)) $items[] = array_merge($tools['recharge'], ['key'=>'recharge','href'=>__BASE_URL__.'usercp/recharge/','newtab'=>false]);
    if(isLoggedIn() && !in_array('referidos',array_column($items,'key'),true)) $items[] = array_merge($tools['referidos'], ['key'=>'referidos','href'=>__BASE_URL__.'usercp/referidos/','newtab'=>false]);
    require_once __DIR__.'/recharge-management.php';
    if(panicRechargeAdminAllowed()) $items[]=array_merge($tools['shopadmin'],['key'=>'shopadmin','href'=>__BASE_URL__.'usercp/shopadmin/','newtab'=>false]);
    return $items;
}
function panicAccountNavigation() {
    $items=panicAccountMenuItems(); $current=(string)($_REQUEST['subpage'] ?? '');
    echo '<a class="cta-nav-row cta-nav-home" href="'.panicAccountEscape(__BASE_URL__.'usercp/').'"'.($current==='' ? ' aria-current="page"' : '').'>'.panicAccountIcon('compass','Inicio').'<span>Inicio del panel</span></a>';
    foreach(['Eryns y VIP','Personajes','Seguridad','Más opciones','Administración'] as $group) {
        $groupItems=array_filter($items,function($item) use ($group) { return $item['group']===$group; });
        if(!$groupItems) continue;
        echo '<div class="cta-nav-group"><span class="cta-nav-group-title">'.panicAccountEscape($group).'</span><ul class="cta-nav-list">';
        foreach($groupItems as $item) {
            echo '<li><a class="cta-nav-row" href="'.panicAccountEscape($item['href']).'"'.($item['newtab'] ? ' target="_blank" rel="noopener"' : '').($item['key']===$current ? ' aria-current="page"' : '').'>'.panicAccountIcon($item['icon'],$item['title']).'<span>'.panicAccountEscape($item['title']).'</span></a></li>';
        }
        echo '</ul></div>';
    }
}
function panicAccountHome() {
    require_once __DIR__.'/account-dashboard.php';
    panicAccountDashboard();
}
function panicAccountFormMarkup($markup, $module) {
    // Solo presentación: las etiquetas van sin los dos puntos finales (como en Ingresar).
    $markup=preg_replace('#(<label\b[^>]*class="[^"]*control-label[^"]*"[^>]*>[^<]*?)\s*:\s*</label>#u', '$1</label>', $markup);
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
