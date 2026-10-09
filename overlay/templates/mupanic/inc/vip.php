<?php
if(!defined('access') || !access) die();
if(!isLoggedIn()) return;
$vipAccount=(string)($_SESSION['username'] ?? '');
$vipMembership=null;
if(preg_match('/^[A-Za-z0-9_]{1,10}$/D',$vipAccount)) {
    try {
        $db=Connection::Database('MuOnline');
        $rows=$db?$db->query_fetch('SELECT CASE WHEN AccountLevel BETWEEN 1 AND 3 AND AccountExpireDate > GETDATE() THEN 1 ELSE 0 END AS active, CONVERT(varchar(19),AccountExpireDate,120) AS expiry FROM [MuOnline43].[dbo].[MEMB_INFO] WHERE memb___id = ?',[$vipAccount]):null;
        if(is_array($rows) && count($rows)===1 && isset($rows[0]['active'],$rows[0]['expiry']) && in_array($rows[0]['active'],[0,1,'0','1'],true) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D',(string)$rows[0]['expiry'])) {
            $vipMembership=['active'=>(bool)$rows[0]['active'],'expiry'=>$rows[0]['expiry']];
        }
    } catch(Throwable $ignored) {}
}
require_once __DIR__.'/recharge-management.php';
$vipOffer=PanicRechargeManagement::storefrontDefaults()['vip'];
try { $vipOffer=(new PanicRechargeManagement())->storefront()['vip']; } catch(Throwable $ignored) {}
$vipBenefits=array_values(array_filter($vipOffer['benefits'],function($benefit){return $benefit['enabled'];}));
$vipHasPrice=is_int($vipOffer['price_coins']) && $vipOffer['price_coins']>0;
$vipActive=$vipMembership!==null && $vipMembership['active'];
$vipCta=$vipHasPrice?($vipActive?'Extender VIP':'Activá tu VIP'):'Conseguí tus Eryns';
$vipRechargeUrl=__BASE_URL__.'usercp/recharge/'.($vipHasPrice?'?recharge_goal=vip#recharge-cart':'#eryns-vip');
?>
<section class="panic-vip panic-vip--salon" aria-labelledby="vip-title">
  <header class="vip-salon-hero">
    <img class="vip-salon-art" src="<?php echo __PATH_TEMPLATE__; ?>img/recharge/vip-champion-scene-v5.webp" alt="Caballero de MU con armadura negra y aura dorada" width="810" height="1080" fetchpriority="high">
    <div class="vip-salon-foil" aria-hidden="true"></div>
    <div class="vip-salon-hero-copy"><span class="vip-salon-overline">MU PANIC / UNA SOLA MEMBRESÍA</span><img class="vip-salon-insignia" src="<?php echo __PATH_TEMPLATE__; ?>img/recharge/vip-insignia-v5.svg" alt="VIP" width="100" height="80"><h2 id="vip-title">Tu cuenta.<br><em>Otro nivel.</em></h2><p>El descuento de X, con vos en cada compra.</p><a class="vip-salon-button" href="<?php echo panicAccountEscape($vipRechargeUrl); ?>"><?php echo $vipCta; ?> <span aria-hidden="true">→</span></a><span class="vip-salon-payment-note">Recargá con Ualá. Activá tu VIP dentro del juego.</span></div>
    <div class="vip-salon-player"><span>LA CUENTA DE</span><strong><?php echo panicAccountEscape($vipAccount); ?></strong><b><?php echo $vipMembership===null?'Estado no disponible':($vipActive?'✦ VIP activo':'Cuenta normal'); ?></b></div>
  </header>
  <div class="vip-salon-body">
    <div class="vip-salon-overview"><div><span class="vip-salon-overline">TU MEMBRESÍA</span><h3><?php echo panicAccountEscape($vipOffer['name']); ?></h3><p>Un solo VIP. Todos tus beneficios.</p></div><aside class="vip-salon-state" aria-label="Estado de tu cuenta"><?php if($vipMembership===null) { ?><strong>No pudimos consultar tu VIP</strong><p>Volvé a abrir esta página para consultar el estado.</p><?php } elseif($vipActive) { ?><strong>✦ VIP activo</strong><p>Vence el <b><?php echo panicAccountEscape(substr($vipMembership['expiry'],8,2).'/'.substr($vipMembership['expiry'],5,2).'/'.substr($vipMembership['expiry'],0,4)); ?></b> a las <?php echo panicAccountEscape(substr($vipMembership['expiry'],11,5)); ?> (hora del servidor).</p><?php } else { ?><strong>Cuenta normal</strong><p>Activá tu membresía dentro del juego.</p><?php } ?></aside></div>
    <div class="vip-salon-offer-layout"><div class="vip-salon-benefit-grid">
      <?php foreach($vipBenefits as $i=>$benefit) { $icon=['crown','shield','star'][$i%3]; ?><article class="vip-salon-benefit"><img src="<?php echo __PATH_TEMPLATE__; ?>img/recharge/vip-benefit-<?php echo $icon; ?>-v5.svg" width="72" height="72" alt=""><h4><?php echo panicAccountEscape($benefit['title']); ?></h4><details><summary>Ver beneficio <span aria-hidden="true">+</span></summary><p><?php echo panicAccountEscape($benefit['detail']); ?></p></details></article><?php } ?>
      <?php if(count($vipBenefits)<2) { ?><article class="vip-salon-coming"><span aria-hidden="true">✦</span><h4>El próximo capítulo</h4><p>Más beneficios por definir.</p></article><?php } ?>
    </div><article class="vip-salon-offer"><span class="vip-salon-overline">UNA MEMBRESÍA PARA RENOVAR</span><h4><?php echo panicAccountEscape($vipOffer['name']); ?></h4><p class="vip-salon-price"><strong><?php echo $vipHasPrice?number_format($vipOffer['price_coins'],0,',','.'):'[COMPLETAR]'; ?></strong><span>Eryns / <?php echo $vipOffer['days']===null?'[COMPLETAR]':$vipOffer['days']; ?> días</span></p><a class="vip-salon-button" href="<?php echo panicAccountEscape($vipRechargeUrl); ?>"><?php echo $vipCta; ?> <span aria-hidden="true">→</span></a><p>El pago acredita Eryns en tu cuenta. La membresía se compra dentro del juego.</p><div class="vip-salon-trust"><span>Pago con Ualá</span><span>Sin cobro recurrente automático</span></div></article></div>
    <section class="vip-salon-steps" aria-labelledby="vip-steps-title"><span class="vip-salon-overline">DE LA WEB AL JUEGO</span><h3 id="vip-steps-title">Tu VIP, en tres pasos.</h3><ol><li><b>01</b><div><strong>Recargá Eryns</strong><p>Elegí tu recarga y pagá con Ualá.</p></div></li><li><b>02</b><div><strong>Esperá la acreditación</strong><p>Seguí el estado en Mis compras.</p></div></li><li><b>03</b><div><strong>Activá tu VIP</strong><p>Abrí Comprar VIP dentro del juego y confirmá la oferta.</p></div></li></ol></section>
    <div class="vip-salon-faq"><details><summary>¿La recarga activa el VIP?</summary><p>La recarga acredita Eryns. Para activar o renovar la membresía, comprala dentro del juego con el saldo disponible.</p></details><details><summary>¿Cómo funciona el descuento de X?</summary><p>Con VIP activo, tenés un 10% de descuento en los objetos de la tienda X. Revisá el precio mostrado en el juego antes de comprar.</p></details><details><summary>¿Cómo renuevo mi VIP?</summary><p>Recargá los Eryns que necesites y revisá las condiciones de renovación en Comprar VIP dentro del juego. No hay débitos automáticos.</p></details></div>
    <p class="vip-salon-status-note">Estado consultado al abrir esta página.</p>
  </div>
  <div class="vip-salon-mobile"><span><?php echo $vipHasPrice?number_format($vipOffer['price_coins'],0,',','.').' Eryns':panicAccountEscape($vipOffer['name']); ?></span><a class="vip-salon-button" href="<?php echo panicAccountEscape($vipRechargeUrl); ?>"><?php echo $vipCta; ?> →</a></div>
</section>
