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
require_once __DIR__.'/tienda-art.php';
$vipEmblem=panicTiendaEmblemaVip();
$vipExpBase=is_string($GLOBALS['serverExp'] ?? null) ? $GLOBALS['serverExp'] : '15x';
$vipDropBase=is_string($GLOBALS['serverDrop'] ?? null) ? $GLOBALS['serverDrop'] : '25%';
// Free o VIP: los mismos datos que muestra El servidor (inc/server-v2.php, sección VIP).
$vipCompare=[
  ['Experiencia',$vipExpBase,'+33% por monstruo'],
  ['Drop general',$vipDropBase,'+20% de objetos'],
  ['Vidriera de Vessara','3 a la venta','10 a la venta'],
  ['AutoPost en el chat','Lo escribís vos','Se publica solo'],
  ['Helper offline','—','Sigue cazando'],
  ['Banco de joyas','—','Van solas al banco'],
  ['Baúl extra','—','Un baúl más'],
  ['Tienda del juego','Precio de lista','10% menos'],
];
?>
<section class="panic-vip vipv" aria-labelledby="vip-title">
  <header class="vipv-hero">
    <div class="vipv-hero-block" aria-hidden="true"></div>
    <div class="vipv-emblem" aria-hidden="true"><?php if($vipEmblem) { ?><img src="<?php echo panicAccountEscape($vipEmblem); ?>" alt="" width="900" height="872" decoding="async" fetchpriority="high" data-stamp><?php } else { ?><b data-stamp>VIP</b><?php } ?></div>
    <div class="vipv-hero-copy">
      <p class="cta-kicker">MU PANIC · Una sola membresía</p>
      <h2 class="vipv-title" id="vip-title">Tu cuenta.<em>Otro nivel.</em></h2>
      <div class="vipv-state<?php echo $vipActive?' is-on':''; ?>" aria-label="Estado de tu cuenta"><span>La cuenta de <b><?php echo panicAccountEscape($vipAccount); ?></b></span><?php if($vipMembership===null) { ?><strong>Estado no disponible</strong><small>Volvé a abrir esta página para consultarlo.</small><?php } elseif($vipActive) { ?><strong>VIP activo</strong><small>Vence el <?php echo panicAccountEscape(substr($vipMembership['expiry'],8,2).'/'.substr($vipMembership['expiry'],5,2).'/'.substr($vipMembership['expiry'],0,4)); ?> a las <?php echo panicAccountEscape(substr($vipMembership['expiry'],11,5)); ?> (hora del servidor)</small><?php } else { ?><strong>Cuenta normal</strong><small>Activá tu membresía dentro del juego.</small><?php } ?></div>
      <div class="vipv-price"><span><?php echo panicAccountEscape($vipOffer['name']); ?></span><strong><?php echo panicTiendaGemaImg(40); ?><?php echo $vipHasPrice?number_format($vipOffer['price_coins'],0,',','.'):'[COMPLETAR]'; ?> <small>Eryns / <?php echo $vipOffer['days']===null?'[COMPLETAR]':$vipOffer['days']; ?> días</small></strong></div>
      <div class="vipv-actions"><a class="pui-btn vipv-btn vipv-punch" href="<?php echo panicAccountEscape($vipRechargeUrl); ?>"><?php echo $vipCta; ?> <span aria-hidden="true">→</span></a><p>Recargá Eryns en la web y activá tu VIP dentro del juego. Sin cobro recurrente.</p></div>
    </div>
  </header>

  <section class="vipv-compare" aria-labelledby="vipv-compare-title">
    <div class="vipv-head"><div><p class="cta-kicker">Free o VIP</p><h3 class="cta-h" id="vipv-compare-title">Todo se juega en Free.<br>El VIP te ahorra tiempo.</h3></div></div>
    <div class="vipv-table" role="table" aria-label="Comparación entre cuenta Free y VIP">
      <div class="vipv-row vipv-row--head" role="row"><span role="columnheader">Beneficio</span><span role="columnheader">Cuenta Free</span><span role="columnheader">VIP PANIC</span></div>
      <?php foreach($vipCompare as $row) { ?><div class="vipv-row" role="row"><span role="rowheader"><?php echo panicAccountEscape($row[0]); ?></span><span role="cell"><?php echo panicAccountEscape($row[1]); ?></span><span role="cell"><?php echo panicAccountEscape($row[2]); ?></span></div><?php } ?>
    </div>
  </section>

  <?php if($vipBenefits) { ?><section class="vipv-benefits" aria-labelledby="vipv-benefits-title"><div class="vipv-head"><div><p class="cta-kicker">Tu membresía</p><h3 class="cta-h" id="vipv-benefits-title"><?php echo panicAccountEscape($vipOffer['name']); ?></h3></div></div>
    <div class="vipv-benefit-grid"><?php foreach($vipBenefits as $i=>$benefit) { $icon=['crown','shield','star'][$i%3]; ?><article class="vipv-benefit"><?php echo panicAccountIcon($icon,$benefit['title']); ?><h4><?php echo panicAccountEscape($benefit['title']); ?></h4><p><?php echo panicAccountEscape($benefit['detail']); ?></p></article><?php } ?><?php if(count($vipBenefits)<2) { ?><article class="vipv-benefit vipv-benefit--soon"><h4>El próximo capítulo</h4><p>Más beneficios por definir.</p></article><?php } ?></div>
  </section><?php } ?>

  <section class="vipv-steps" aria-labelledby="vip-steps-title"><div class="vipv-head"><div><p class="cta-kicker">De la web al juego</p><h3 class="cta-h" id="vip-steps-title">Tu VIP, en tres pasos</h3></div></div>
    <ol><li><b>01</b><strong>Recargá Eryns</strong><p>Elegí tu recarga en la Tienda de Eryns.</p></li><li><b>02</b><strong>Esperá la acreditación</strong><p>Seguí el estado en Mis compras.</p></li><li><b>03</b><strong>Activá tu VIP</strong><p>Abrí Comprar VIP dentro del juego y confirmá la oferta.</p></li></ol>
  </section>

  <div class="vipv-faq"><details><summary>¿La recarga activa el VIP?</summary><p>La recarga acredita Eryns. Para activar o renovar la membresía, comprala dentro del juego con el saldo disponible.</p></details><details><summary>¿Cómo funciona el descuento de la Tienda?</summary><p>Con VIP activo, tenés un 10% de descuento en los objetos de la Tienda del juego (X o F11). Revisá el precio mostrado en el juego antes de comprar.</p></details><details><summary>¿Cómo renuevo mi VIP?</summary><p>Recargá los Eryns que necesites y revisá las condiciones de renovación en Comprar VIP dentro del juego. No hay débitos automáticos.</p></details></div>
  <p class="cta-note">Estado consultado al abrir esta página.</p>
  <div class="vipv-mobile"><span><?php echo panicTiendaGemaImg(20); ?><?php echo $vipHasPrice?number_format($vipOffer['price_coins'],0,',','.').' Eryns':panicAccountEscape($vipOffer['name']); ?></span><a href="<?php echo panicAccountEscape($vipRechargeUrl); ?>"><?php echo $vipCta; ?> →</a></div>
</section>
