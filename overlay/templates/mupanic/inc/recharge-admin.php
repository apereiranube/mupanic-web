<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/recharge-orders.php';
if(!panicRechargeAdminAllowed()) { http_response_code(403); echo 'Acceso no autorizado.'; return; }
if(!is_string($_SESSION['shop_admin_csrf'] ?? null)) $_SESSION['shop_admin_csrf']=bin2hex(random_bytes(32));
$adminUrl=__BASE_URL__.'usercp/shopadmin/';
$adminSection=is_string($_GET['vista'] ?? null)?$_GET['vista']:'inicio';
// Keep older links, open tabs and submitted forms working.
if(isset($_GET['eco']))$adminSection='economia';
elseif(isset($_GET['ref']))$adminSection='referidos';
elseif(($_GET['transferencias'] ?? '')==='pendientes')$adminSection='transferencias';
elseif(!isset($_GET['vista']) && isset($_GET['bancos']))$adminSection='transferencias';
elseif(!isset($_GET['vista']) && (isset($_GET['buscar']) || isset($_GET['estado']) || isset($_GET['pagina'])))$adminSection='compras';
if(($_SERVER['REQUEST_METHOD'] ?? '')==='POST' && !isset($_GET['eco']) && !isset($_GET['ref'])) {
    $action=$_POST['admin_action'] ?? '';
    if(in_array($action,['catalogue','storefront','bank_config','votar_sitios'],true))$adminSection='configuracion';
    elseif(in_array($action,['bank_approve','bank_reject'],true))$adminSection='transferencias';
    elseif($action==='check')$adminSection='compras';
}
$adminViews=['inicio'=>'Inicio','transferencias'=>'Transferencias por aprobar','compras'=>'Compras','economia'=>'Economía','referidos'=>'Referidos','votos'=>'Votos','configuracion'=>'Configuración'];
if(!isset($adminViews[$adminSection]))$adminSection='inicio';
?>
<?php if(!isset($_GET['vista']) && !isset($_GET['eco']) && !isset($_GET['ref']) && ($_SERVER['REQUEST_METHOD'] ?? 'GET')==='GET') { ?><script>
if(location.hash==='#shop-orders'){const target=new URL(location.href);target.searchParams.set('vista','compras');target.hash='';location.replace(target.href);}
if(/^#shop-(?:bank|catalogue|mounts|vip|delivery|vip-diagnostic)$/.test(location.hash)) location.replace(<?= json_encode($adminUrl.'?vista=configuracion') ?>+location.hash);
</script><?php } ?>
<nav class="admin-console-menu" aria-label="Panel administrador">
<?php foreach($adminViews as $key=>$label) { ?><a href="<?= panicAccountEscape($adminUrl.($key==='economia'?'?eco=1':($key==='referidos'?'?ref=1':'?vista='.$key))) ?>" <?= $key===$adminSection?'aria-current="page"':'' ?>><?= $label ?></a><?php } ?>
</nav>
<?php
if($adminSection==='economia') { require __DIR__.'/admin-economy/entry.php'; return; }
if($adminSection==='referidos') { require __DIR__.'/referidos-admin.php'; return; }
if($adminSection==='votos') { require __DIR__.'/votar-admin.php'; return; }
if($adminSection==='transferencias') { require __DIR__.'/recharge-bank-review.php'; return; }
if($adminSection==='configuracion') { require __DIR__.'/recharge-admin-settings.php'; return; }
require __DIR__.'/recharge-admin-overview.php';
