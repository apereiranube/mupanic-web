<?php
if(!defined('access') || !access) die();

function panicRechargeCheckAllowed() {
    if(!isLoggedIn() || !function_exists('config')) return false;
    $admins=config('admins',true);
    return is_array($admins) && array_key_exists((string)($_SESSION['username'] ?? ''),$admins);
}

function panicRechargeCheckRequest(array $post, array &$session, $now, callable $authenticate) {
    if(!panicRechargeCheckAllowed()) return null;
    if(!is_string($post['uala_check_csrf'] ?? null) || !is_string($session['uala_check_csrf'] ?? null) ||
       !hash_equals($session['uala_check_csrf'],$post['uala_check_csrf'])) return 'La sesión de la comprobación venció. Recargá la página e intentá nuevamente.';
    if($now-(int)($session['uala_check_time'] ?? 0)<60) return 'Esperá un minuto antes de volver a comprobar.';
    $session['uala_check_time']=$now;
    try { return $authenticate(); }
    catch(Throwable $exception) { return 'No se pudo conectar con Ualá. Revisá que las tres credenciales correspondan al ambiente de prueba. Las claves no se muestran en esta página.'; }
}

function panicRechargeCheckAuthentication() {
    require_once __DIR__.'/recharge-uala.php';
    try { $settings=panicUalaPrivateSettings(); }
    catch(Throwable $exception) { return 'No se pudo leer la configuración. Revisá payments-private/settings.json, sus permisos y que sales_enabled esté en false.'; }
    if($settings['environment']!=='test') return 'El archivo debe tener environment en test y las tres credenciales de prueba para esta comprobación.';
    if(!function_exists('curl_init')) return 'El hosting necesita habilitar la extensión cURL de PHP. Podés solicitarlo al soporte.';
    $api=new PanicUalaBisApi($settings['uala_bis'],'test');
    $api->authenticate();
    return 'Conexión correcta con Ualá Bis en modo de prueba. No se crearon cobros ni se modificaron monedas.';
}

function panicRechargeCheckRender() {
if(!panicRechargeCheckAllowed()) return;
if(!is_string($_SESSION['uala_check_csrf'] ?? null)) $_SESSION['uala_check_csrf']=bin2hex(random_bytes(32));
$ualaCheckResult=null;
if(($_SERVER['REQUEST_METHOD'] ?? '')==='POST' && isset($_POST['uala_check'])) {
    $ualaCheckResult=panicRechargeCheckRequest($_POST,$_SESSION,time(),'panicRechargeCheckAuthentication');
}
?>
<section class="recharge-notice" aria-labelledby="uala-check-title">
    <strong id="uala-check-title">Administración · Comprobar Ualá Bis</strong>
    <p>Verificá los datos de prueba guardados en el hosting. Esta comprobación no realiza cobros ni acredita monedas.</p>
    <form method="post" action="<?php echo panicAccountEscape(__BASE_URL__.'usercp/recharge/'); ?>">
        <input type="hidden" name="uala_check_csrf" value="<?php echo panicAccountEscape($_SESSION['uala_check_csrf']); ?>">
        <button class="btn btn-primary" type="submit" name="uala_check" value="1">Comprobar conexión con Ualá</button>
    </form>
    <?php if($ualaCheckResult!==null) { ?><p role="status"><?php echo panicAccountEscape($ualaCheckResult); ?></p><?php } ?>
</section>
<?php
}
panicRechargeCheckRender();
