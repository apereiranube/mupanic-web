<?php
if(!defined('access') || !access) die();
if(!panicRechargeCheckAllowed()) return;
require_once __DIR__.'/recharge-pilot.php';
$pilot=null; $pilotMessage=null; $pilotConfigured=false;
try {
    $pilotStore=new PanicRechargePilot();
    $pilotStore->token(); $pilotConfigured=true;
    if(($_SERVER['REQUEST_METHOD'] ?? '')==='POST' && isset($_POST['uala_pilot_action'])) {
        if(!is_string($_POST['uala_check_csrf'] ?? null) || !hash_equals($_SESSION['uala_check_csrf'],$_POST['uala_check_csrf'])) {
            $pilotMessage='Recargá la página antes de volver a intentar.';
        } elseif(time()-(int)($_SESSION['uala_pilot_time'] ?? 0)<15) {
            $pilotMessage='Esperá 15 segundos antes de volver a consultar.';
        } else {
            $_SESSION['uala_pilot_time']=time();
            [$pilotApi,$pilotMerchant]=panicRechargePilotGateway();
            if($_POST['uala_pilot_action']==='create') $pilotStore->begin($pilotApi,'https://beta.mupanic.com.ar/');
            elseif($_POST['uala_pilot_action']==='refresh') $pilotStore->refresh($pilotApi,$pilotMerchant);
            elseif($_POST['uala_pilot_action']==='inspect') $pilotStore->inspect($pilotApi,$pilotMerchant);
        }
    }
    $pilot=$pilotStore->current();
} catch(Throwable $exception) {
    $pilotMessage=$pilotConfigured?'No se pudo completar la consulta de prueba. No repitas el pago si ya lo realizaste. El registro de esta prueba se conserva.':'Falta instalar la conexión con el VPS para habilitar esta prueba.';
    $pilotMessage.=' Código: '.PanicRechargePilot::diagnostic($exception).'.';
    if(isset($pilotStore)) { try { $pilot=$pilotStore->current(); } catch(Throwable $ignored) {} }
}
$pilotLabels=['creating'=>'Creación pendiente de revisar','pending'=>'Pago pendiente','approved'=>'Pago simulado aprobado','rejected'=>'Pago rechazado','cancelled'=>'Pago cancelado','review'=>'En revisión'];
?>
<section class="recharge-notice" aria-labelledby="pilot-title">
    <strong id="pilot-title">Administración · Compra de prueba</strong>
    <p>$1.000 simulados en Ualá → 1.000 WCoin C reales, únicamente para <b>pruebacoin</b>. Usá la tarjeta de prueba de Ualá. Esta prueba permite una sola compra.</p>
    <?php if($pilotMessage) { ?><p role="status"><?php echo panicAccountEscape($pilotMessage); ?></p><?php } ?>
    <?php if($pilot) { ?>
        <?php if(isset($pilot['last_error'])) { ?><p>Diagnóstico: <b><?php echo panicAccountEscape($pilot['last_error']); ?></b></p><?php } ?>
        <?php if(($pilot['last_error'] ?? '')==='RECOVERY_MORE_PAGES') { ?><p>Revisamos <?php echo (int)($pilot['recovery_pages'] ?? 0); ?> página(s). Hay más resultados: esperá 15 segundos y continuá la revisión con el botón de abajo. No se crea otro cobro.</p><?php } ?>
        <p><b><?php echo panicAccountEscape($pilotLabels[$pilot['payment_state']] ?? 'En revisión'); ?></b> · Entrega: <?php echo panicAccountEscape(['pending'=>'Pendiente','credited'=>'1.000 WCoin C acreditados','reverted'=>'Monedas de prueba retiradas'][$pilot['delivery_state']] ?? 'En revisión'); ?></p>
        <?php if(isset($pilot['checkout_url']) && $pilot['payment_state']==='pending') { ?><p><a class="btn btn-primary" href="<?php echo panicAccountEscape($pilot['checkout_url']); ?>" target="_blank" rel="noopener noreferrer">Abrir pago simulado en Ualá</a></p><?php } ?>
        <?php if(isset($pilot['payment_id'])) { ?>
        <form method="post" action="<?php echo panicAccountEscape(__BASE_URL__.'usercp/recharge/'); ?>">
            <input type="hidden" name="uala_check_csrf" value="<?php echo panicAccountEscape($_SESSION['uala_check_csrf']); ?>">
            <button class="btn btn-primary" type="submit" name="uala_pilot_action" value="refresh">Actualizar estado de la prueba</button>
        </form>
        <?php } ?>
        <?php if(!isset($pilot['payment_id'])) { ?>
        <form method="post" action="<?php echo panicAccountEscape(__BASE_URL__.'usercp/recharge/'); ?>">
            <input type="hidden" name="uala_check_csrf" value="<?php echo panicAccountEscape($_SESSION['uala_check_csrf']); ?>">
            <button class="btn btn-primary" type="submit" name="uala_pilot_action" value="inspect">Revisar reserva sin crear otro cobro</button>
        </form>
        <?php } ?>
    <?php } elseif($pilotConfigured) { ?>
        <form method="post" action="<?php echo panicAccountEscape(__BASE_URL__.'usercp/recharge/'); ?>">
            <input type="hidden" name="uala_check_csrf" value="<?php echo panicAccountEscape($_SESSION['uala_check_csrf']); ?>">
            <button class="btn btn-primary" type="submit" name="uala_pilot_action" value="create">Crear compra de prueba para pruebacoin</button>
        </form>
    <?php } ?>
    <p>La entrega la ejecuta el sincronizador del VPS. Para esta prueba, dejá <b>pruebacoin desconectada del juego</b>; después entrá para comprobar el saldo.</p>
</section>
