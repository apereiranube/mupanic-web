<?php
if(!defined('access') || !access || !function_exists('panicRechargeAdminAllowed') || !panicRechargeAdminAllowed() || ($_REQUEST['page']??'')!=='usercp' || ($_REQUEST['subpage']??'')!=='shopadmin') { http_response_code(403); exit('Acceso no autorizado.'); }
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET') { http_response_code(405); echo 'Este panel solo admite consultas.'; return; }
define('PANIC_ECO_ADMIN',true);
require_once __DIR__.'/bootstrap.php'; require_once __DIR__.'/filters.php'; require_once __DIR__.'/repository.php';
$assetBase=__PATH_TEMPLATE__;
?><link rel="stylesheet" href="<?php echo panicEcoEscape($assetBase); ?>css/admin-economy.css?v=<?php echo substr(hash_file('sha256',__DIR__.'/../../css/admin-economy.css'),0,12); ?>">
<?php
try { $ecoFilters=panicEcoFilters($_GET); }
catch(InvalidArgumentException $e) { echo '<div class="eco eco-alert eco-alert--error" role="alert">'.panicEcoEscape($e->getMessage()).' <a href="'.panicEcoEscape(__BASE_URL__.'usercp/shopadmin/?eco=1').'">Restablecer filtros</a></div>'; return; }
try { $ecoRepository=new PanicEconomyRepository($ecoFilters); $ecoState=$ecoRepository->state(); }
catch(Throwable $e) { echo '<div class="eco eco-alert eco-alert--error" role="alert"><strong>No se pudo leer el estado de Economía.</strong> Revisá la conexión y las vistas SQL. <a href="'.panicEcoEscape(__BASE_URL__.'usercp/shopadmin/').'">Volver a tienda</a></div>'; return; }
require __DIR__.'/layout.php';
?>
<script src="<?php echo panicEcoEscape($assetBase); ?>js/vendor/chart.umd.min.js" defer></script>
<script src="<?php echo panicEcoEscape($assetBase); ?>js/vendor/dataTables.min.js" defer></script>
<script src="<?php echo panicEcoEscape($assetBase); ?>js/admin-economy.js?v=<?php echo substr(hash_file('sha256',__DIR__.'/../../js/admin-economy.js'),0,12); ?>" defer></script>
