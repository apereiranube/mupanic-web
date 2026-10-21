<?php if(!defined('access') || !access) die(); ?>
<?php if($bankHistory['orders']) { ?>
<section id="bank-transfers" class="recharge-section bank-transfer-history" aria-labelledby="bank-history-title">
<div class="recharge-section-head"><div><span class="eyebrow">TRANSFERENCIAS BANCARIAS</span><h3 id="bank-history-title">Mis transferencias</h3></div></div>
<div class="recharge-history-list">
<?php foreach($bankHistory['orders'] as $o) { [$tone,$label,$copy]=panicBankStatus($o); ?>
<article class="recharge-order" data-order-id="<?= panicAccountEscape($o['id']) ?>" data-order-state="<?= $tone ?>">
<div class="recharge-order-top"><span><?= panicAccountEscape(panicRechargeArgDate($o['created_at'])) ?> ARG</span><span class="recharge-status" data-order-label><?= panicAccountEscape($label) ?></span></div>
<div class="recharge-order-value"><strong><?= number_format($o['coins'],0,',','.') ?> <small>Eryns</small></strong><b>$ <?= number_format($o['price_cents']/100,0,',','.') ?> ARS</b></div>
<p data-order-copy><?= panicAccountEscape($copy) ?></p><p>Cuenta: <b><?= panicAccountEscape($o['account']) ?></b> · CUIT: <?= panicAccountEscape(substr($o['cuit'],0,2).'••••••••'.substr($o['cuit'],-1)) ?></p>
<p class="recharge-order-reference"><?= panicAccountEscape($o['id']) ?></p>
<?php if($o['payment_state']==='pending') { ?>
<details class="bank-details" open><summary>Datos para transferir</summary><dl>
<?php foreach(['holder'=>'Titular','bank'=>'Banco / billetera','recipient_cuit'=>'CUIT del titular','alias'=>'Alias','cbu'=>'CBU / CVU'] as $k=>$title) { if($o['bank'][$k]==='')continue; ?><div><dt><?= $title ?></dt><dd><?= panicAccountEscape($o['bank'][$k]) ?></dd></div><?php } ?>
</dl><p>Transferí <b>$ <?= number_format($o['price_cents']/100,0,',','.') ?> ARS</b>. Estos datos pertenecen a esta compra. Guardá el comprobante.</p></details>
<?php if($o['ticket_channel']) { ?><a class="recharge-button" href="<?= panicAccountEscape('https://discord.com/channels/'.$o['discord']['guild_id'].'/'.$o['ticket_channel']) ?>" target="_blank" rel="noopener noreferrer">Abrir mi ticket de Discord ↗</a>
<?php } else { ?>
<form method="post" action="<?= panicAccountEscape(__BASE_URL__.'usercp/recharge/#bank-transfers') ?>"><input type="hidden" name="recharge_csrf" value="<?= $_SESSION['recharge_csrf'] ?>"><input type="hidden" name="order_id" value="<?= panicAccountEscape($o['id']) ?>"><button class="recharge-button" name="recharge_action" value="bank_ready">Ya transferí · Preparar ticket</button></form>
<?php $claim=$_SESSION['bank_claim_code'] ?? null;if(is_array($claim) && $claim['id']===$o['id'] && $claim['expires']>=time()) { ?>
<div class="bank-ticket-code" role="status"><strong>Tu código para Discord</strong><code><?= panicAccountEscape($claim['code']) ?></code><p>Copiá este código. En soporte, pulsá <b>Recarga por transferencia</b> o escribí <b>/recarga</b>. Pegalo en el formulario y adjuntá el comprobante en el ticket privado. El código vence en 30 minutos; no lo publiques en un canal.</p>
<a class="recharge-button" href="<?= panicAccountEscape('https://discord.com/channels/'.$o['discord']['guild_id'].'/'.$o['discord']['support_channel_id']) ?>" target="_blank" rel="noopener noreferrer">Abrir soporte en Discord ↗</a></div>
<?php } } ?>
<?php if(!empty($o['receipt_at'])) { ?><p class="recharge-subtle">Comprobante recibido. Falta que el equipo verifique la transferencia bancaria.</p><?php } ?>
<?php } ?>
</article><?php } ?></div>
<?php if($bankHistory['total']>10) { ?><nav class="recharge-pagination" aria-label="Páginas de transferencias"><?php if($bankPage>1) { ?><a href="?transferencias=<?= $bankPage-1 ?>#bank-transfers">← Anteriores</a><?php } ?><span>Página <?= $bankPage ?></span><?php if($bankPage*10<$bankHistory['total']) { ?><a href="?transferencias=<?= $bankPage+1 ?>#bank-transfers">Siguientes →</a><?php } ?></nav><?php } ?>
<p>Un comprobante adjunto no confirma el ingreso. La recarga se acredita después de la revisión del equipo y con la cuenta desconectada del juego.</p>
</section><?php } ?>
