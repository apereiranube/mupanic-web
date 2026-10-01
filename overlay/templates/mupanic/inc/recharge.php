<?php
if(!defined('access') || !access) die();
if(!isLoggedIn()) return;
require_once __DIR__.'/recharge-domain.php';
require_once __DIR__.'/recharge-wallet.php';
$rechargeConfig=require __DIR__.'/recharge-config.php';
$rechargeAccount=(string)($_SESSION['username'] ?? '');
$rechargeBalance=panicRechargeWalletBalance($rechargeAccount);
$rechargePackages=[];
foreach($rechargeConfig['packages'] as $package) {
    try { $rechargePackages[]=PanicRecharge::package($package); }
    catch(InvalidArgumentException $exception) { /* Never publish an invalid offer. */ }
}
?>
<section class="recharge-shop" aria-labelledby="recharge-title">
    <?php include __DIR__.'/recharge-check.php'; ?>
    <header class="recharge-hero">
        <span class="eyebrow">LA TIENDA DE TU AVENTURA</span>
        <h2 id="recharge-title">Recargá.<br><em>Elegí tu próxima conquista.</em></h2>
        <p>WCoin C para comprar en la tienda del juego.<br>La recarga corresponde a tu cuenta, no a un personaje.</p>
        <div class="recharge-wallet-row"><div class="recharge-account"><span>CUENTA DE DESTINO</span><strong><?php echo panicAccountEscape($rechargeAccount); ?></strong></div><div class="recharge-account recharge-balance"><span>SALDO REGISTRADO</span><strong><?php echo $rechargeBalance===null ? 'No disponible' : number_format($rechargeBalance,0,',','.').' WCoin C'; ?></strong></div></div>
        <small class="recharge-balance-note">Saldo consultado al abrir esta página. El juego puede mostrar cambios posteriores.</small>
    </header>
    <div class="recharge-notice" role="status"><strong>Estamos preparando las recargas</strong><p>Todavía no se pueden realizar compras. Los paquetes y sus precios aparecerán acá cuando la tienda esté habilitada.</p></div>
    <nav class="recharge-sections" aria-label="Secciones de recargas"><a href="#recharge-packages">Paquetes</a><a href="#recharge-guide">Cómo funciona</a><a href="#recharge-history">Mis compras</a></nav>
    <section id="recharge-packages" class="recharge-section">
        <div class="recharge-section-head"><div><span class="eyebrow">01 / TU RECARGA</span><h3>Elegí tus WCoin C</h3></div><span>Precios en pesos argentinos</span></div>
        <?php if(!$rechargePackages) { ?>
        <div class="recharge-empty-catalog"><span class="recharge-gem" aria-hidden="true"><?php echo panicAccountIcon('gem'); ?></span><div><strong>Los paquetes están en preparación</strong><p>Vas a poder comparar el precio, los WCoin C y cualquier bono antes de pagar.</p></div></div>
        <?php } else { ?>
        <div class="recharge-packages"><?php foreach($rechargePackages as $package) { ?>
            <article class="recharge-package"><span class="recharge-gem" aria-hidden="true"><?php echo panicAccountIcon('gem'); ?></span><h4><?php echo panicAccountEscape($package['title']); ?></h4><strong><?php echo number_format($package['coins']+$package['bonus'],0,',','.'); ?> <small>WCoin C</small></strong><p><?php echo $package['bonus']>0 ? number_format($package['coins'],0,',','.').' + '.number_format($package['bonus'],0,',','.').' de regalo' : 'Sin bono adicional'; ?></p><div class="recharge-price">$ <?php echo number_format($package['price_cents']/100,2,',','.'); ?> <small>ARS</small></div><span class="recharge-coming">Disponible próximamente</span></article>
        <?php } ?></div>
        <?php } ?>
        <?php $rechargeMethod=$rechargeConfig['providers'][$rechargeConfig['primary_provider']] ?? null; if($rechargeMethod) { ?>
        <div class="recharge-method"><span class="recharge-method-icon" aria-hidden="true"><?php echo panicAccountIcon('shield'); ?></span><div><strong><?php echo panicAccountEscape($rechargeMethod['label']); ?></strong><p><?php echo panicAccountEscape($rechargeMethod['copy']); ?></p></div><span>Próximamente</span></div>
        <?php } ?>
    </section>
    <section id="recharge-guide" class="recharge-section">
        <div class="recharge-section-head"><div><span class="eyebrow">02 / SIN VUELTAS</span><h3>De la web al juego</h3></div></div>
        <ol class="recharge-steps"><li><span>1</span><strong>Elegís un paquete</strong><p>Revisás el precio y el total de WCoin C para tu cuenta.</p></li><li><span>2</span><strong>Realizás el pago</strong><p>Completás la compra con el medio de pago disponible.</p></li><li><span>3</span><strong>Recibís tus monedas</strong><p>Cuando se confirme el pago y la entrega, podrás usarlas en la tienda del juego.</p></li></ol>
        <details><summary>¿Dónde se usan los WCoin C?</summary><p>En el Cash Shop, la tienda dentro de MU PANIC. Esta página estará dedicada a recargar monedas; los productos del juego se compran desde el cliente.</p></details>
        <details><summary>¿Qué pasa si pagué y todavía no veo mis monedas?</summary><p>El pago y la entrega tienen estados separados. El historial indicará si el pago sigue pendiente, está aprobado o si los WCoin C ya fueron acreditados. Si requiere revisión, podés consultar al equipo con el número de compra.</p></details>
        <details><summary>¿Las monedas son para un personaje?</summary><p>La recarga es para la cuenta que figura arriba. Antes de comprar, comprobá que hayas ingresado con la cuenta correcta.</p></details>
    </section>
    <section id="recharge-history" class="recharge-section">
        <div class="recharge-section-head"><div><span class="eyebrow">03 / TUS RECARGAS</span><h3>Mis compras</h3></div></div>
        <div class="recharge-history-empty"><strong>El historial se habilitará junto con las compras</strong><p>Acá vas a encontrar el paquete, el importe y el estado de cada recarga.</p></div>
        <details class="recharge-status-guide"><summary>Entendé el estado de tu compra</summary><dl><dt>Pago pendiente</dt><dd>El medio de pago todavía no confirmó el cobro.</dd><dt>Pago aprobado</dt><dd>El cobro está confirmado. Falta completar la entrega.</dd><dt>WCoin acreditados</dt><dd>La entrega a tu cuenta está confirmada.</dd><dt>En revisión</dt><dd>El equipo necesita revisar la operación antes de continuar.</dd></dl></details>
    </section>
</section>
