<?php if(!defined('access') || !access) die(); ?>
<?php if($bankHistory['orders']) { ?>
<section id="bank-transfers" class="recharge-section bank-transfer-history" aria-labelledby="bank-history-title">
<div class="recharge-section-head"><div><span class="eyebrow">SEGUÍ TU RECARGA</span><h3 id="bank-history-title">Mis transferencias</h3></div></div>
<div class="recharge-history-list">
<?php foreach($bankHistory['orders'] as $o) { [$tone,$label,$copy]=panicBankStatus($o); ?>
<article id="bank-order-<?= panicAccountEscape($o['id']) ?>" tabindex="-1" class="recharge-order bank-payment" data-order-id="<?= panicAccountEscape($o['id']) ?>" data-order-state="<?= $tone ?>">
<div class="recharge-order-top"><span><?= panicAccountEscape(panicRechargeArgDate($o['created_at'])) ?> ARG</span><span class="recharge-status" data-order-label><?= panicAccountEscape($label) ?></span></div>
<div class="recharge-order-value"><strong><?= number_format($o['coins'],0,',','.') ?> <small>Eryns</small></strong><b>$ <?= number_format($o['price_cents']/100,0,',','.') ?> ARS</b></div>
<p class="bank-destination">Para tu cuenta <b><?= panicAccountEscape($o['account']) ?></b></p>
<?php if($o['payment_state']==='pending') { ?>
<div class="bank-payment-steps">
<section class="bank-payment-step bank-payment-step--transfer"><h4><span>1</span> Hacé la transferencia</h4><p>Desde tu banco o billetera, enviá este importe a la cuenta de abajo.</p>
<div class="bank-amount"><span>Importe exacto</span><strong>$ <?= number_format($o['price_cents']/100,0,',','.') ?> <small>ARS</small></strong><button type="button" class="bank-copy" data-bank-copy="<?= panicAccountEscape(number_format($o['price_cents']/100,2,',','')) ?>" data-copy-label="importe">Copiar importe</button></div>
<div class="bank-details"><dl>
<?php foreach(['alias'=>'Alias','cbu'=>'CBU / CVU','holder'=>'Titular','bank'=>'Banco / billetera','recipient_cuit'=>'CUIT del titular'] as $k=>$title) { if($o['bank'][$k]==='')continue; ?><div><dt><?= $title ?></dt><dd><span><?= panicAccountEscape($o['bank'][$k]) ?></span><?php if(in_array($k,['alias','cbu'],true)) { ?> <button type="button" class="bank-copy" data-bank-copy="<?= panicAccountEscape($o['bank'][$k]) ?>" data-copy-label="<?= $k==='alias'?'alias':'CBU / CVU' ?>">Copiar <?= $k==='alias'?'alias':'CBU / CVU' ?></button><?php } ?></dd></div><?php } ?>
</dl></div><p class="bank-step-note">Guardá el comprobante: lo vas a enviar en el siguiente paso.</p></section>
<section class="bank-payment-step bank-payment-step--receipt"><h4><span>2</span> Enviá el comprobante</h4>
<?php if($o['ticket_channel']) { ?>
<p><?= !empty($o['receipt_at'])?'Ya recibimos tu comprobante. Podés consultar el seguimiento en tu ticket privado.':'Tu ticket privado está listo. Abrilo y adjuntá el comprobante de la transferencia.' ?></p>
<a class="recharge-button" href="<?= panicAccountEscape('https://discord.com/channels/'.$o['discord']['guild_id'].'/'.$o['ticket_channel']) ?>" target="_blank" rel="noopener noreferrer">Abrir mi ticket en Discord ↗︎</a>
<?php } else { $claim=$_SESSION['bank_claim_code'] ?? null; $hasClaim=is_array($claim) && $claim['id']===$o['id'] && $claim['expires']>=time(); ?>
<?php if(!$hasClaim) { ?><p>¿Ya hiciste la transferencia? Continuá para enviarnos el comprobante por Discord.</p><?php } ?>
<?php if($hasClaim) { ?><details class="bank-operation-details"><summary>Necesito otro código</summary><?php } ?><form method="post" action="<?= panicAccountEscape(__BASE_URL__.'usercp/recharge/?transferencias='.$bankPage.'#bank-transfers') ?>"><input type="hidden" name="recharge_csrf" value="<?= $_SESSION['recharge_csrf'] ?>"><input type="hidden" name="order_id" value="<?= panicAccountEscape($o['id']) ?>"><button class="recharge-button" name="recharge_action" value="bank_ready"><?= $hasClaim?'Generar otro código':'Ya transferí · Enviar comprobante' ?></button></form><?php if($hasClaim) { ?></details><?php } ?>
<?php if($hasClaim) { ?>
<div id="bank-ticket-<?= panicAccountEscape($o['id']) ?>" tabindex="-1" class="bank-ticket-code" role="status"><strong>Continuá en Discord</strong><ol><li>Copiá este código de tu recarga:<code><?= panicAccountEscape($claim['code']) ?></code><button type="button" class="bank-copy" data-bank-copy="<?= panicAccountEscape($claim['code']) ?>" data-copy-label="código">Copiar código</button></li><li>Abrí Discord y pulsá <b>Recarga por transferencia</b> en el canal de soporte.</li><li>Pegá el código cuando el bot lo pida. En el ticket privado que se abre, <b>adjuntá el comprobante</b>.</li></ol>
<a class="recharge-button" href="<?= panicAccountEscape('https://discord.com/channels/'.$o['discord']['guild_id'].'/'.$o['discord']['support_channel_id']) ?>" target="_blank" rel="noopener noreferrer">Ir al soporte de Discord ↗︎</a><p class="bank-step-note">El código es privado y vence en 30 minutos. Si no ves el botón de recarga en Discord, escribí <b>/recarga</b>.</p></div>
<?php } } ?></section>
<section class="bank-payment-step bank-payment-step--wait"><h4><span>3</span> Recibí tus Eryns</h4><p>Revisamos el ingreso en el banco. El proceso puede demorar <b>hasta 48 horas</b>. Con el pago aprobado, <b>desconectate del juego</b> para recibir la recarga. El bot te avisa en tu ticket cuando esté acreditada.</p></section>
</div>
<?php } else { ?><p data-order-copy><?= panicAccountEscape($copy) ?></p><?php } ?>
<?php if($o['payment_state']==='pending') { ?><p data-order-copy hidden><?= panicAccountEscape($copy) ?></p><?php } ?>
<p class="bank-copy-feedback" data-bank-copy-feedback role="status" aria-live="polite"></p>
<details class="bank-operation-details"><summary>Ver datos de la operación</summary><p>Número: <?= panicAccountEscape($o['id']) ?><br>CUIT informado: <?= panicAccountEscape(substr($o['cuit'],0,2).'••••••••'.substr($o['cuit'],-1)) ?></p></details>
</article><?php } ?></div>
<?php if($bankHistory['total']>10) { ?><nav class="recharge-pagination" aria-label="Páginas de transferencias"><?php if($bankPage>1) { ?><a href="?transferencias=<?= $bankPage-1 ?>#bank-transfers">← Anteriores</a><?php } ?><span>Página <?= $bankPage ?></span><?php if($bankPage*10<$bankHistory['total']) { ?><a href="?transferencias=<?= $bankPage+1 ?>#bank-transfers">Siguientes →</a><?php } ?></nav><?php } ?>
</section><?php } ?>
