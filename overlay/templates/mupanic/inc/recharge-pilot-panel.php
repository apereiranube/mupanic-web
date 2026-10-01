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
            elseif($_POST['uala_pilot_action']==='replace') $pilotStore->replaceMissingLink($pilotApi,$pilotMerchant,'https://beta.mupanic.com.ar/');
        }
    }
    $pilot=$pilotStore->current();
} catch(Throwable $exception) {
    $pilotMessage=$pilotConfigured?'No se pudo completar la consulta de prueba. No repitas el pago si ya lo realizaste. El registro de esta prueba se conserva.':'Falta instalar la conexión con el VPS para habilitar esta prueba.';
    $pilotMessage.=' Código: '.PanicRechargePilot::diagnostic($exception).'.';
    if(isset($pilotStore)) { try { $pilot=$pilotStore->current(); } catch(Throwable $ignored) {} }
}
$pilotWait=max(0,15-(time()-(int)($_SESSION['uala_pilot_time'] ?? 0)));
$pilotLabels=['creating'=>'Creación pendiente de revisar','pending'=>'Pago pendiente','approved'=>'Pago simulado aprobado','rejected'=>'Pago rechazado','cancelled'=>'Pago cancelado','review'=>'En revisión'];
?>
<section class="recharge-notice" aria-labelledby="pilot-title">
    <strong id="pilot-title">Prueba de pago · Ualá Bis</strong>
    <p><b>$1.000 de prueba → 1.000 WCoin C para pruebacoin.</b> Usá la tarjeta de prueba de Ualá y mantené esa cuenta desconectada del juego.</p>
    <?php if($pilotMessage) { ?><p role="status"><?php echo panicAccountEscape($pilotMessage); ?></p><?php } ?>
    <?php if($pilot) { $legacyAmount=($pilot['flow_version'] ?? 0)<3 && $pilot['delivery_state']==='pending' && isset($pilot['payment_id']); ?>
        <p><b><?php echo panicAccountEscape($pilotLabels[$pilot['payment_state']] ?? 'En revisión'); ?></b> · <?php echo panicAccountEscape(['pending'=>'Monedas todavía no entregadas','credited'=>'1.000 WCoin C acreditados','reverted'=>'Monedas de prueba retiradas'][$pilot['delivery_state']] ?? 'Entrega en revisión'); ?></p>
        <?php if(!$legacyAmount && isset($pilot['checkout_url']) && $pilot['payment_state']==='pending') { ?>
            <p><b>Paso 1:</b> abrí el pago y completalo con la tarjeta de prueba.</p>
            <p><a class="btn btn-primary" href="<?php echo panicAccountEscape($pilot['checkout_url']); ?>" target="_blank" rel="noopener noreferrer">Abrir pago de prueba</a></p>
        <?php } elseif(!$legacyAmount && $pilot['payment_state']==='approved' && $pilot['delivery_state']==='pending') { ?>
            <p><b>Paso 2:</b> ejecutá el script del VPS para entregar las monedas.</p>
        <?php } elseif($pilot['delivery_state']==='credited') { ?>
            <p><b>Paso 3:</b> entrá al juego con pruebacoin y comprobá el saldo.</p>
        <?php } ?>
        <?php $canRestart=$legacyAmount || isset($pilot['payment_id']) && $pilot['payment_state']==='pending' && $pilot['delivery_state']==='pending' && empty($pilot['checkout_url']) && (empty($pilot['replacement_used']) || ($pilot['flow_version'] ?? 0)<2); ?>
        <?php if($canRestart) { ?>
            <p><?php echo $legacyAmount?'El importe del intento anterior debe corregirse.':'El intento anterior quedó sin enlace.'; ?> Este botón prepara una nueva prueba, conserva el historial y mantiene el límite de una sola acreditación.</p>
        <?php } elseif($pilot['payment_state']==='pending' && empty($pilot['checkout_url'])) { ?>
            <p>No pudimos habilitar el enlace. No crees más compras: el diagnóstico de abajo permite revisar la respuesta recibida.</p>
        <?php } ?>
        <?php if($canRestart || isset($pilot['payment_id']) || $pilot['payment_state']==='creating') { ?>
        <form method="post" action="<?php echo panicAccountEscape(__BASE_URL__.'usercp/recharge/'); ?>" data-pilot-form>
            <input type="hidden" name="uala_check_csrf" value="<?php echo panicAccountEscape($_SESSION['uala_check_csrf']); ?>">
            <button class="btn btn-primary" type="submit" name="uala_pilot_action" value="<?php echo $canRestart?'replace':(isset($pilot['payment_id'])?'refresh':'inspect'); ?>" data-pilot-wait="<?php echo $pilotWait; ?>" <?php if($pilotWait>0) echo 'disabled'; ?>><?php echo $canRestart?($legacyAmount?'Preparar prueba de $1.000':'Preparar nueva prueba'):(isset($pilot['payment_id'])?'Comprobar pago y entrega':'Revisar intento anterior'); ?></button>
        </form>
        <?php } ?>
        <details style="margin-top:20px">
            <summary>Diagnóstico de esta prueba</summary>
            <p>Código: <b><?php echo panicAccountEscape($pilot['last_error'] ?? 'Sin errores registrados'); ?></b></p>
            <?php foreach(['checkout_response'=>'Respuesta al crear el pago','get_link_diagnostic'=>'Consulta del pago'] as $field=>$label) {
                $info=$field==='checkout_response'?($pilot[$field]['link_diagnostic'] ?? null):($pilot[$field] ?? null);
                if(!is_array($info)) continue; ?>
                <p><?php echo panicAccountEscape($label); ?>: <b><?php echo panicAccountEscape($info['issue'] ?? 'Sin diagnóstico'); ?></b>
                <?php if(!empty($info['host'])) echo ' · Dominio: '.panicAccountEscape($info['host']); ?>
                <?php if(!empty($info['scheme'])) echo ' · Protocolo: '.panicAccountEscape($info['scheme']); ?></p>
            <?php } ?>
            <?php if(isset($pilot['worker_error'])) { ?><p>Último error del VPS: <?php echo panicAccountEscape($pilot['worker_error']); ?></p><?php } ?>
            <p>El enlace original y los identificadores se conservan de forma privada. Este panel no muestra tokens ni datos de tarjetas.</p>
        </details>
    <?php } elseif($pilotConfigured) { ?>
        <form method="post" action="<?php echo panicAccountEscape(__BASE_URL__.'usercp/recharge/'); ?>" data-pilot-form>
            <input type="hidden" name="uala_check_csrf" value="<?php echo panicAccountEscape($_SESSION['uala_check_csrf']); ?>">
            <button class="btn btn-primary" type="submit" name="uala_pilot_action" value="create" data-pilot-wait="<?php echo $pilotWait; ?>" <?php if($pilotWait>0) echo 'disabled'; ?>>Preparar prueba para pruebacoin</button>
        </form>
    <?php } ?>
    <p data-pilot-countdown aria-live="polite"></p>
</section>
<script>
(function(){
    document.querySelectorAll('[data-pilot-form]').forEach(function(form){
        var button=form.querySelector('button');
        var remaining=Number(button.dataset.pilotWait||0);
        var notice=form.closest('section').querySelector('[data-pilot-countdown]');
        function tick(){
            button.disabled=remaining>0;
            notice.textContent=remaining>0?'Podés continuar en '+remaining+' segundos.':'';
            if(remaining>0){remaining--;window.setTimeout(tick,1000);}
        }
        tick();
        form.addEventListener('submit',function(event){
            if(form.dataset.sending==='yes'){event.preventDefault();return;}
            form.dataset.sending='yes';
            // Keep the clicked submit button enabled until its name/value is serialized.
            window.setTimeout(function(){button.disabled=true;button.textContent='Consultando Ualá…';},0);
        });
    });
})();
</script>
