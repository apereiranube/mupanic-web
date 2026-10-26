<?php
if(!defined('access') || !access || !panicRechargeAdminAllowed()) return;
require_once __DIR__.'/recharge-bank.php';
$bankAdmin=null;$bankAdminError=null;$bankAdminOk=null;$bankRows=['orders'=>[],'total'=>0];$bankAdminConfig=PanicBankTransfers::defaults();
$bankReviewOnly=$bankReviewOnly ?? false;
$bankAdminPage=max(1,min(500,(int)($_GET['bancos'] ?? 1)));
$bankReviewUrl=$adminUrl.'?vista=transferencias';
$bankPostUrl=$bankReviewOnly?$bankReviewUrl.'#shop-bank':$adminUrl.'?vista=configuracion#shop-bank';
$bankBotInfo=[];
try {
    $bankAdmin=new PanicBankTransfers();
    $bankAdminConfig=$bankAdmin->config();
} catch(Throwable $e){$bankAdminError='No se pudo leer la configuración bancaria. Recargá el panel antes de guardar.';}
try {
    if(!$bankAdmin || $bankAdminError)throw new RuntimeException('Configuración no disponible.');
    if(($_SERVER['REQUEST_METHOD'] ?? '')==='POST' && strpos((string)($_POST['admin_action'] ?? ''),'bank_')===0) {
        if(!is_string($_POST['admin_csrf'] ?? null) || !hash_equals($_SESSION['shop_admin_csrf'],$_POST['admin_csrf']))throw new InvalidArgumentException('La sesión venció.');
        $action=$_POST['admin_action'];
        if($action==='bank_config') {
            if(!is_string($_POST['bank_revision'] ?? null) || !preg_match('/^\d{1,10}$/D',$_POST['bank_revision']) || !is_array($_POST['bank'] ?? null))throw new InvalidArgumentException('Revisá la configuración.');
            $bankAdmin->saveConfig($_POST['bank'],(int)$_POST['bank_revision']);$bankAdminOk='Cuenta receptora guardada. Las compras anteriores conservan sus datos originales.';
        } elseif($action==='bank_approve') {
            $bankAdmin->approve(is_string($_POST['bank_order'] ?? null)?$_POST['bank_order']:'',$_POST['bank_reference'] ?? '',isset($_POST['bank_confirmed']));
            $bankAdminOk='Transferencia confirmada. El servicio del VPS entregará los Eryns cuando la cuenta esté desconectada.';
        } elseif($action==='bank_reject') {
            $bankAdmin->reject(is_string($_POST['bank_order'] ?? null)?$_POST['bank_order']:'');$bankAdminOk='Transferencia marcada como no aprobada. No se acreditarán Eryns.';
        } else throw new InvalidArgumentException('Acción inválida.');
    }
} catch(Throwable $e){$bankAdminError=$e instanceof InvalidArgumentException?$e->getMessage():'No se pudo completar la operación. Recargá el panel y revisá el estado antes de repetirla.';}
// Refresh independently from POST validation: an error must never blank saved settings or hide orders.
try {
    if(!$bankAdmin)throw new RuntimeException('Registro no disponible.');
    $bankAdminConfig=$bankAdmin->config();$bankBotInfo=$bankAdmin->botInfo();
    if($bankAdminConfig['support_channel_id']==='' && ($bankBotInfo['guild_id'] ?? '')===$bankAdminConfig['guild_id']) $bankAdminConfig['support_channel_id']=$bankBotInfo['support_channel_id'];
    $bankRows=$bankAdmin->history(null,$bankAdminPage,true,true);
} catch(Throwable $e){$bankAdminError=$e instanceof InvalidArgumentException?$e->getMessage():'No se pudo completar la operación. Recargá el panel y revisá el estado antes de repetirla.';}
?>
<section id="shop-bank" class="recharge-section bank-admin"><div class="recharge-section-head"><div><span class="eyebrow">RECARGAS / TRANSFERENCIAS</span><h3><?= $bankReviewOnly?'Transferencias por aprobar':'Cuenta bancaria receptora' ?></h3></div></div>
<?php if($bankAdminError) { ?><p class="shop-error" role="alert"><?= panicAccountEscape($bankAdminError) ?></p><?php } ?>
<?php if($bankAdminOk) { ?><p role="status"><?= panicAccountEscape($bankAdminOk) ?></p><?php } ?>
<?php if(!$bankReviewOnly) { ?><p>Los compradores verán estos datos al preparar una transferencia. La revisión de pagos está en una pantalla aparte.</p><a class="recharge-text-button" href="<?= panicAccountEscape($bankReviewUrl) ?>">Abrir transferencias por aprobar →</a>
<p>El bot actual de MU PANIC abre los tickets y registra los adjuntos. Revisá el comprobante y el ingreso real en tu banco antes de confirmar una recarga.</p>
<form method="post" action="<?= panicAccountEscape($bankPostUrl) ?>"><input type="hidden" name="admin_csrf" value="<?= $_SESSION['shop_admin_csrf'] ?>"><input type="hidden" name="bank_revision" value="<?= (int)$bankAdminConfig['revision'] ?>">
<div class="shop-editor-grid">
<?php foreach(['holder'=>'Titular de la cuenta','bank'=>'Banco o billetera','recipient_cuit'=>'CUIT del titular','alias'=>'Alias','cbu'=>'CBU / CVU','guild_id'=>'ID del servidor de Discord','support_channel_id'=>'ID del canal soporte de Discord']as $key=>$label) { ?><label><?= $label ?><input name="bank[<?= $key ?>]" value="<?= panicAccountEscape($bankAdminConfig[$key]) ?>" maxlength="<?= $key==='holder'?120:($key==='bank'?80:64) ?>" autocomplete="off"></label><?php } ?>
</div><label class="bank-enable"><input type="checkbox" name="bank[enabled]" value="1" <?= $bankAdminConfig['enabled']?'checked':'' ?>> Ofrecer transferencias bancarias</label>
<p class="recharge-subtle">Se ofrecerán cuando el bot y el servicio de entrega versión 3 estén conectados. No hace falta guardar el token de Discord en la web.</p>
<button class="recharge-button" name="admin_action" value="bank_config">Guardar cuenta receptora</button></form>
<?php } else { ?><p>Abrí el comprobante, verificá el ingreso en tu banco y confirmá el pago. Acá sólo aparecen las transferencias pendientes de aprobación.</p>
<?php if(!$bankRows['orders']) { ?><p>No hay transferencias pendientes de aprobación.</p><?php } ?>
<div class="recharge-history-list"><?php foreach($bankRows['orders']as $o){[$tone,$label,$copy]=panicBankStatus($o); ?>
<article class="recharge-order"><div class="recharge-order-top"><span><?= panicAccountEscape(panicRechargeArgDate($o['created_at'])) ?> · <?= $o['live']?'Producción':'Prueba' ?></span><span class="recharge-status"><?= panicAccountEscape($label) ?></span></div>
<div class="recharge-order-value"><strong><?= panicAccountEscape($o['account']) ?><small> <?= number_format($o['coins'],0,',','.') ?> Eryns</small></strong><b>$ <?= number_format($o['price_cents']/100,0,',','.') ?> ARS</b></div>
<p>CUIT de quien realiza el aporte: <b><?= panicAccountEscape($o['cuit']) ?></b><br>Orden: <?= panicAccountEscape($o['id']) ?></p>
<?php if($o['ticket_channel']) { ?><a class="recharge-text-button" href="<?= panicAccountEscape('https://discord.com/channels/'.$o['discord']['guild_id'].'/'.$o['ticket_channel']) ?>" target="_blank" rel="noopener noreferrer">Abrir ticket ↗︎</a><?php } ?>
<?php if(!empty($o['receipt_url'])) { ?><a class="recharge-text-button" href="<?= panicAccountEscape($o['receipt_url']) ?>" target="_blank" rel="noopener noreferrer">Ver comprobante en Discord ↗︎</a><?php } else { ?><p>El bot todavía no registró un comprobante del comprador.</p><?php } ?>
<details><summary>Datos de esta transferencia</summary><?php if(!empty($o['terms_acceptance'])) { ?><p>Condiciones aceptadas: versión <?= panicAccountEscape($o['terms_acceptance']['version']) ?> · <?= panicAccountEscape(panicRechargeArgDate($o['terms_acceptance']['accepted_at'])) ?> · cuenta <?= panicAccountEscape($o['terms_acceptance']['account']) ?>.</p><?php } else { ?><p>Operación anterior al registro de aceptación de condiciones.</p><?php } ?><p>Titular receptor: <?= panicAccountEscape($o['bank']['holder']) ?><br>Banco: <?= panicAccountEscape($o['bank']['bank']) ?><br>Alias / CBU: <?= panicAccountEscape($o['bank']['alias'].' / '.$o['bank']['cbu']) ?><br>Usuario Discord: <?= panicAccountEscape($o['discord_id'] ?? 'Sin vincular') ?></p>
<?php if(!empty($o['approved_at'])) { ?><p>Confirmada por <?= panicAccountEscape($o['approved_by']) ?> · <?= panicAccountEscape(panicRechargeArgDate($o['approved_at'])) ?><br>Referencia: <?= panicAccountEscape($o['payment_id']) ?></p><?php } ?>
<?php if(isset($o['before_coin'],$o['after_coin'])) { ?><p>Saldo: <?= $o['before_coin'] ?> → <?= $o['after_coin'] ?> Eryns</p><?php } ?></details>
<?php if($o['payment_state']==='pending') { ?>
<form method="post" action="<?= panicAccountEscape($bankPostUrl) ?>"><input type="hidden" name="admin_csrf" value="<?= $_SESSION['shop_admin_csrf'] ?>"><input type="hidden" name="bank_order" value="<?= panicAccountEscape($o['id']) ?>">
<?php if(!empty($o['receipt_url'])) { ?><label>Número de operación del banco<input name="bank_reference" required pattern="[A-Za-z0-9_-]{1,80}" maxlength="80" value="<?= panicAccountEscape(($_POST['bank_order'] ?? '')===$o['id'] && is_string($_POST['bank_reference'] ?? null)?substr($_POST['bank_reference'],0,80):'') ?>" placeholder="Copiá el número de la transferencia recibida" aria-describedby="bank-reference-<?= panicAccountEscape($o['id']) ?>"></label><p class="recharge-subtle" id="bank-reference-<?= panicAccountEscape($o['id']) ?>">Está en el detalle del movimiento de tu banco o en el comprobante. <b>No es el código de Discord.</b> Sirve para evitar acreditar dos veces la misma operación.</p><label class="bank-enable"><input type="checkbox" name="bank_confirmed" required> Revisé el comprobante y confirmé el ingreso de $ <?= number_format($o['price_cents']/100,0,',','.') ?> ARS en la cuenta indicada.</label><button class="recharge-button" name="admin_action" value="bank_approve">Confirmar pago y habilitar Eryns</button><?php } ?>
<button class="recharge-text-button" name="admin_action" value="bank_reject" formnovalidate>Marcar como no aprobada</button></form><?php } ?>
</article><?php } ?></div>
<?php if($bankRows['total']>10) { ?><nav class="recharge-pagination" aria-label="Páginas de revisión bancaria"><?php if($bankAdminPage>1) { ?><a href="<?= panicAccountEscape($bankReviewUrl.'&bancos='.($bankAdminPage-1).'#shop-bank') ?>">← Anteriores</a><?php } ?><span>Página <?= $bankAdminPage ?></span><?php if($bankAdminPage*10<$bankRows['total']) { ?><a href="<?= panicAccountEscape($bankReviewUrl.'&bancos='.($bankAdminPage+1).'#shop-bank') ?>">Siguientes →</a><?php } ?></nav><?php } ?>
<?php } ?>
</section>
