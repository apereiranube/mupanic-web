<?php
if(!defined('access') || !access || !panicRechargeAdminAllowed()) return;
require_once __DIR__.'/recharge-admin-data.php';
$overviewErrors=[];$overviewMessage=null;$overviewUala=[];$overviewBank=[];
$overviewFilters=['query'=>is_string($_GET['buscar'] ?? null)?substr($_GET['buscar'],0,120):'',
    'state'=>in_array($_GET['estado'] ?? '',['pending','approved','credited','review','creating','rejected'],true)?$_GET['estado']:'',
    'provider'=>in_array($_GET['medio'] ?? '',['uala_bis','bank_transfer'],true)?$_GET['medio']:'',
    'environment'=>in_array($_GET['ambiente'] ?? 'all',['all','test','production'],true)?($_GET['ambiente'] ?? 'all'):'all'];
if($adminSection==='inicio') { $overviewFilters['query']='';$overviewFilters['state']='';$overviewFilters['provider']=''; }
$overviewStore=null;
try { $overviewStore=new PanicRechargeOrders(); } catch(Throwable $ignored) { $overviewErrors[]='No se pudo leer el registro de Ualá.'; }
if(($_SERVER['REQUEST_METHOD'] ?? '')==='POST') {
    try {
        if(!is_string($_POST['admin_csrf'] ?? null) || !hash_equals($_SESSION['shop_admin_csrf'],$_POST['admin_csrf']))throw new InvalidArgumentException('La sesión venció. Recargá la página.');
        if(($_POST['admin_action'] ?? '')!=='check' || !$overviewStore)throw new InvalidArgumentException('Acción no disponible.');
        if(time()-(int)($_SESSION['shop_admin_check'] ?? 0)<15)throw new InvalidArgumentException('Esperá 15 segundos entre consultas.');
        $_SESSION['shop_admin_check']=time();
        [$gateway,$merchant,$settings]=panicRechargeShopGateway();
        $order=$overviewStore->get(is_string($_POST['order_id'] ?? null)?$_POST['order_id']:'');
        if(!$order || $order['live']!==($settings['environment']==='production'))throw new InvalidArgumentException('La compra pertenece a otro ambiente o no está disponible.');
        $overviewStore->refresh($order['id'],$gateway,$merchant);$overviewMessage='Estado de Ualá consultado. La entrega la procesa automáticamente el VPS.';
    }catch(Throwable $e){$overviewErrors[]=$e instanceof InvalidArgumentException?$e->getMessage():'No se pudo consultar el pago. La compra se conserva; revisá su estado antes de repetir.';}
}
if($overviewStore)try{$overviewUala=$overviewStore->administrationRows();}catch(Throwable $ignored){$overviewErrors[]='No se pudo leer el registro de Ualá.';}
try{$overviewBank=(new PanicBankTransfers())->administrationRows();}catch(Throwable $ignored){$overviewErrors[]='No se pudo leer el registro de transferencias.';}
$overview=PanicRechargeAdminOverview::build($overviewUala,$overviewBank,$overviewFilters,max(1,min(500,(int)($_GET['pagina'] ?? 1))));
$isOverviewHome=$adminSection==='inicio';$summary=$overview['summary'];
?>
<section class="recharge-shop shop-management shop-management--v6 admin-console-content" aria-labelledby="admin-overview-title">
<header class="recharge-hero"><span class="eyebrow">MU PANIC / ADMINISTRACIÓN</span><h2 id="admin-overview-title"><?= $isOverviewHome?'Tu panel, de un vistazo.':'Todas las compras.' ?></h2><p><?= $isOverviewHome?'Pagos recibidos, transferencias por revisar y entregas de Eryns.':'Historial unificado de Ualá y transferencias bancarias, incluidas las aprobadas, acreditadas y rechazadas.' ?></p></header>
<?php foreach($overviewErrors as $error){ ?><p class="recharge-notice shop-error" role="alert"><?= panicAccountEscape($error) ?> Los totales pueden estar incompletos.</p><?php } ?>
<?php if($overviewMessage){ ?><p class="recharge-notice" role="status"><?= panicAccountEscape($overviewMessage) ?></p><?php } ?>
<form class="shop-admin-filters admin-overview-filters" method="get" action="<?= panicAccountEscape($adminUrl) ?>"><input type="hidden" name="vista" value="<?= $adminSection ?>">
<?php if(!$isOverviewHome){ ?><label>Cuenta, compra o número de operación<input name="buscar" maxlength="120" value="<?= panicAccountEscape($overviewFilters['query']) ?>" placeholder="Nombre de cuenta o PANIC-…"></label><label>Medio de pago<select name="medio"><?php foreach([''=>'Todos','bank_transfer'=>'Transferencia bancaria','uala_bis'=>'Ualá']as $key=>$label){ ?><option value="<?= $key ?>" <?= $overviewFilters['provider']===$key?'selected':'' ?>><?= $label ?></option><?php } ?></select></label><label>Estado<select name="estado"><?php foreach([''=>'Todos','pending'=>'Pago o revisión pendiente','approved'=>'Aprobada · esperando entrega','credited'=>'Eryns acreditados','rejected'=>'No aprobada','review'=>'Revisión de Ualá','creating'=>'Creación pendiente']as $key=>$label){ ?><option value="<?= $key ?>" <?= $overviewFilters['state']===$key?'selected':'' ?>><?= $label ?></option><?php } ?></select></label><?php } ?>
<label>Mostrar<select name="ambiente"><?php foreach(['all'=>'Pruebas y producción','production'=>'Sólo producción','test'=>'Sólo pruebas']as $key=>$label){ ?><option value="<?= $key ?>" <?= $overviewFilters['environment']===$key?'selected':'' ?>><?= $label ?></option><?php } ?></select></label><button class="recharge-button">Aplicar filtros</button></form>
<div class="admin-overview-metrics">
<div class="admin-overview-metric is-money"><span>Pagos aprobados</span><strong>$ <?= number_format($summary['approved_cents']/100,0,',','.') ?></strong><small><?= $summary['approved_count'] ?> pagos de Ualá y transferencias</small></div>
<div class="admin-overview-metric is-delivered"><span>Eryns acreditados</span><strong><?= number_format($summary['delivered_coins'],0,',','.') ?></strong><small><?= $summary['credited'] ?> compras entregadas</small></div>
<div class="admin-overview-metric is-review"><span>Transferencias por aprobar</span><strong><?= $summary['bank_pending'] ?></strong><small>Pendientes de revisión manual</small></div>
<div class="admin-overview-metric is-waiting"><span>Esperando entrega</span><strong><?= $summary['waiting'] ?></strong><small>Pago aprobado; Eryns aún pendientes</small></div>
</div>
<p class="recharge-subtle">Totales <?= $isOverviewHome?'del ambiente seleccionado':'de los filtros seleccionados' ?>. <?= $overviewFilters['environment']==='all'?'Incluyen las pruebas; no representan sólo ingresos de producción.':'' ?></p>
<?php if($isOverviewHome){ ?><div class="admin-payment-split"><?php foreach(['bank_transfer'=>'Transferencias bancarias','uala_bis'=>'Ualá']as $key=>$label){ ?><article><span><?= $label ?></span><strong>$ <?= number_format($overview['providers'][$key]['cents']/100,0,',','.') ?></strong><small><?= $overview['providers'][$key]['count'] ?> pagos aprobados</small></article><?php } ?></div><div class="recharge-section-head"><h3>Últimas compras</h3><a class="recharge-text-button" href="<?= panicAccountEscape($adminUrl.'?vista=compras') ?>">Ver todas →</a></div><?php } ?>
<?php $overviewRows=$isOverviewHome?$overview['recent']:$overview['orders'];if(!$overviewRows){ ?><p class="recharge-history-empty">No hay compras con estos filtros.</p><?php } ?>
<div class="recharge-history-list"><?php foreach($overviewRows as $order){[$tone,$label,$copy]=$order['status'];$isBank=$order['provider']==='bank_transfer'; ?>
<article class="recharge-order" data-order-state="<?= $tone ?>" data-order-provider="<?= $order['provider'] ?>"><div class="recharge-order-top"><span><?= panicAccountEscape(panicRechargeArgDate($order['created_at'])) ?> · <?= $order['live']?'Producción':'Prueba' ?> · <b><?= $isBank?'Transferencia bancaria':'Ualá' ?></b></span><span class="recharge-status"><?= panicAccountEscape($label) ?></span></div><div class="recharge-order-value"><strong><?= panicAccountEscape($order['account']) ?><small><?= number_format($order['coins'],0,',','.') ?> Eryns</small></strong><b>$ <?= number_format($order['price_cents']/100,0,',','.') ?> ARS</b></div><p><?= panicAccountEscape($copy) ?></p>
<details><summary>Ver detalle de compra y entrega</summary><p class="recharge-order-reference">Compra: <?= panicAccountEscape($order['id']) ?><br>Operación: <?= panicAccountEscape($order['payment_id'] ?? 'Sin confirmar') ?></p>
<?php if(!empty($order['lines'])){ ?><ul><?php foreach($order['lines'] as $line){ ?><li><?= (int)$line['quantity'] ?> × <?= panicAccountEscape(panicAccountCurrencyLabel($line['title'])) ?></li><?php } ?></ul><?php } ?>
<?php if(!empty($order['approved_at'])){ ?><p>Pago confirmado por <?= panicAccountEscape($order['approved_by']) ?> · <?= panicAccountEscape(panicRechargeArgDate($order['approved_at'])) ?></p><?php } ?>
<?php if(!empty($order['credited_at'])){ ?><p>Eryns acreditados: <?= panicAccountEscape(panicRechargeArgDate($order['credited_at'])) ?></p><?php } ?>
<?php if(isset($order['before_coin'],$order['after_coin'])){ ?><p>Saldo: <?= number_format($order['before_coin'],0,',','.') ?> → <?= number_format($order['after_coin'],0,',','.') ?> Eryns</p><?php } ?>
<?php if($isBank && !empty($order['receipt_url'])){ ?><a class="recharge-text-button" href="<?= panicAccountEscape($order['receipt_url']) ?>" target="_blank" rel="noopener noreferrer">Ver comprobante en Discord ↗</a><?php } ?>
<?php if($isBank && !empty($order['ticket_channel'])){ ?><a class="recharge-text-button" href="<?= panicAccountEscape('https://discord.com/channels/'.$order['discord']['guild_id'].'/'.$order['ticket_channel']) ?>" target="_blank" rel="noopener noreferrer">Abrir ticket ↗</a><?php } ?>
<?php if($isBank && !empty($order['notified_at'])){ ?><p>Aviso de acreditación enviado a Discord: <?= panicAccountEscape(panicRechargeArgDate($order['notified_at'])) ?></p><?php } ?>
<?php if(!$isBank && in_array($order['payment_state'],['pending','creating'],true)){ ?><form method="post" action="<?= panicAccountEscape($adminUrl.'?vista=compras') ?>"><input type="hidden" name="admin_csrf" value="<?= $_SESSION['shop_admin_csrf'] ?>"><input type="hidden" name="order_id" value="<?= panicAccountEscape($order['id']) ?>"><button class="recharge-text-button" name="admin_action" value="check">Consultar estado en Ualá</button></form><?php } ?>
</details><?php if($isBank && $order['payment_state']==='pending'){ ?><a class="recharge-text-button" href="<?= panicAccountEscape($adminUrl.'?vista=transferencias') ?>">Ir a aprobar transferencias →</a><?php } ?></article>
<?php } ?></div>
<?php if(!$isOverviewHome && $overview['pages']>1){ ?><nav class="recharge-pagination" aria-label="Páginas de compras"><?php foreach([-1=>'← Anteriores',1=>'Siguientes →']as $delta=>$label){$target=$overview['page']+$delta;if($target<1 || $target>$overview['pages'])continue; ?><a href="<?= panicAccountEscape($adminUrl.'?'.http_build_query(['vista'=>'compras','buscar'=>$overviewFilters['query'],'estado'=>$overviewFilters['state'],'medio'=>$overviewFilters['provider'],'ambiente'=>$overviewFilters['environment'],'pagina'=>$target])) ?>"><?= $label ?></a><?php } ?><span>Página <?= $overview['page'] ?> de <?= $overview['pages'] ?></span></nav><?php } ?>
</section>
