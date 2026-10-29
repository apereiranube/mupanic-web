<?php
if(!defined('access') || !access) die();
if(!isLoggedIn()) return;
require_once __DIR__.'/recharge-domain.php';
require_once __DIR__.'/recharge-wallet.php';
require_once __DIR__.'/recharge-management.php';
$rechargeConfig=require __DIR__.'/recharge-config.php';
$rechargeAccount=(string)($_SESSION['username'] ?? '');
$rechargeBalance=panicRechargeWalletBalance($rechargeAccount);
require_once __DIR__.'/recharge-vip-status.php';
$shopVip=panicRechargeVipStatus($rechargeAccount);
$rechargePackages=[];
try { $publicOffers=(new PanicRechargeManagement())->offers(); } catch(Throwable $ignored) { $publicOffers=[]; }
foreach($publicOffers as $package) {
    try { $rechargePackages[]=PanicRecharge::package($package); } catch(InvalidArgumentException $ignored) {}
}
require __DIR__.'/recharge-shop-controller.php';
$shopUalaEnabled=panicRechargeUalaCheckoutEnabled();
if(!$shopUalaEnabled) { $shopCanBuy=false; $bankSelected=true; }
if(!$rechargePackages) { $shopCanBuy=false; $bankCanBuy=false; }
$shopAnyCanBuy=$shopCanBuy || $bankCanBuy;
// Editorial highlight only. Offers, prices and bonuses remain controlled by the shop.
$shopHasFeatured=false;
foreach($rechargePackages as $package) if(!empty($package['featured'])) $shopHasFeatured=true;
$shopSpotlightIndex=(int)floor(count($rechargePackages)/2);

$shopFeaturedIndex=$shopSpotlightIndex;
foreach($rechargePackages as $i=>$package) if(!empty($package['featured'])) { $shopFeaturedIndex=$i; break; }
$shopBestValueIndex=$shopFeaturedIndex;
foreach($rechargePackages as $i=>$package) if(($package['coins']+$package['bonus'])*$rechargePackages[$shopBestValueIndex]['price_cents']>($rechargePackages[$shopBestValueIndex]['coins']+$rechargePackages[$shopBestValueIndex]['bonus'])*$package['price_cents']) $shopBestValueIndex=$i;
$shopUniqueBest=true;
foreach($rechargePackages as $i=>$package) if($i!==$shopBestValueIndex && ($package['coins']+$package['bonus'])*$rechargePackages[$shopBestValueIndex]['price_cents']>=($rechargePackages[$shopBestValueIndex]['coins']+$rechargePackages[$shopBestValueIndex]['bonus'])*$package['price_cents']) $shopUniqueBest=false;
if($shopUniqueBest) $shopFeaturedIndex=$shopBestValueIndex;
$shopRecommendation=$shopUniqueBest?'MEJOR VALOR':'RECOMENDADO';
$shopInitialQuantities=$shopQuantities;
if(($_POST['recharge_action'] ?? '')!=='create' && !array_sum(array_map('floatval',$shopInitialQuantities)) && isset($rechargePackages[$shopFeaturedIndex])) $shopInitialQuantities[$rechargePackages[$shopFeaturedIndex]['id']]='1';
$shopBonusIndex=null;
foreach($rechargePackages as $i=>$package) if($package['bonus']>0 && ($shopBonusIndex===null || $package['bonus']*$rechargePackages[$shopBonusIndex]['coins']>$rechargePackages[$shopBonusIndex]['bonus']*$package['coins'])) $shopBonusIndex=$i;
$shopLore=json_decode(file_get_contents(__DIR__.'/atlas-vinculos.json'),true);
$shopCreatures=[]; foreach(($shopLore['entries'] ?? []) as $creature) $shopCreatures[$creature['slug']]=$creature;
try { $shopProducts=(new PanicRechargeManagement())->storefront(); } catch(Throwable $ignored) { $shopProducts=PanicRechargeManagement::storefrontDefaults(); }
$shopVipOffer=$shopProducts['vip'];
$shopMountCopy=['theryon'=>'Que te vean llegar.','nerathys'=>'Tres cabezas. Una presencia.','vaeraxes'=>'El cielo lleva tu nombre.'];
$shopPackOrder=array_values(array_filter(array_keys($rechargePackages),function($i) use ($shopFeaturedIndex) { return $i!==$shopFeaturedIndex; }));
if(isset($rechargePackages[$shopFeaturedIndex])) array_splice($shopPackOrder,(int)floor(count($rechargePackages)/2),0,[$shopFeaturedIndex]);
$shopPackColumns=implode(' ',array_map(function($i) use ($shopFeaturedIndex) { return $i===$shopFeaturedIndex?'minmax(0,1.2fr)':'minmax(0,1fr)'; },$shopPackOrder));
?>
<?php
// Presentación (estilo nuevo): arte de img/tienda (inc/tienda-art.php). No cambia precios, packs ni pagos.
require_once __DIR__.'/tienda-art.php';
$tndFig=panicTiendaArt('tienda-summoner'); $tndFigSize=panicTiendaArtSize('tienda-summoner',2000,1600); $tndFondo=panicTiendaArt('tienda-fondo');
$tndGem=panicTiendaGema();
// Arte de cada pack según su tamaño: el más chico la bolsa, después la caja y del tercero en adelante el cofre.
$tndBySize=array_keys($rechargePackages); usort($tndBySize,function($a,$b) use ($rechargePackages) { return ($rechargePackages[$a]['coins']+$rechargePackages[$a]['bonus'])<=>($rechargePackages[$b]['coins']+$rechargePackages[$b]['bonus']) ?: $a<=>$b; });
$tndPackArt=[]; $tndPackTier=[]; foreach($tndBySize as $rank=>$i) { $tndPackTier[$i]=min(3,$rank+1); $tndPackArt[$i]=panicTiendaArt('tienda-pack-'.$tndPackTier[$i]); }
$tndExplainBank='Verás la cuenta bancaria. Adjuntá el comprobante en Discord. La acreditación puede demorar hasta 48 horas y requiere la confirmación del ingreso.';
$tndCanPay=$bankSelected?$bankCanBuy:$shopCanBuy;
$tndPayLabel=$tndCanPay?($bankSelected?'Ver datos de transferencia →':'Pagar con Ualá →'):'Pagos próximamente';
?>
<section class="recharge-shop eryns-store tnd" aria-labelledby="recharge-title" data-recharge-shop<?php if($shopMessage) { $continuationTarget=!empty($bankContinueOrder)?((($_POST['recharge_action'] ?? '')==='bank_ready'?'bank-ticket-':'bank-order-').$bankContinueOrder['id']):'recharge-checkout'; ?> data-checkout-target="<?= panicAccountEscape($continuationTarget) ?>"<?php } ?>>
    <nav class="eryns-store-top tnd-top" aria-label="Secciones de la tienda"><span class="tnd-top-name">Tienda de Eryns</span><a href="#recharge-packages">Packs</a><a href="#eryns-bestias">Bestias</a><a href="#eryns-vip">VIP</a><a href="#recharge-history">Mis compras</a></nav>

    <header class="tnd-hero<?php echo $tndFig?' has-fig':''; ?><?php echo $tndFondo?' has-fondo':''; ?>" data-tnd-hero>
        <div class="tnd-hero-block" aria-hidden="true"><?php if($tndFondo) { ?><i class="tnd-hero-fondo" data-tnd-parallax style="background-image:url('<?php echo panicAccountEscape($tndFondo); ?>')"></i><?php } ?></div>
        <?php if($tndFig) { ?><div class="tnd-hero-fig" data-tnd-fig aria-hidden="true" style="--fw:<?php echo $tndFigSize[0]; ?>;--fh:<?php echo $tndFigSize[1]; ?>"><img src="<?php echo panicAccountEscape($tndFig); ?>" alt="" width="<?php echo $tndFigSize[0]; ?>" height="<?php echo $tndFigSize[1]; ?>" decoding="async" fetchpriority="high"></div><?php } ?>
        <div class="tnd-hero-copy">
            <p class="tnd-kicker">Tienda de Eryns</p>
            <h2 class="tnd-hero-title" id="recharge-title">Que te vean llegar.<em>Que recuerden tu nombre.</em></h2>
            <p class="tnd-hero-lead">Elegí tus Eryns y llevá a PANIC la presencia que querés: bestias, VIP y todo lo de la Tienda del juego.</p>
            <div class="tnd-hero-wallet"><span>Saldo de <b><?php echo panicAccountEscape($rechargeAccount); ?></b></span><strong><?php echo panicTiendaGemaImg(30); ?><?php echo $rechargeBalance===null?'No disponible':number_format($rechargeBalance,0,',','.'); ?><?php if($rechargeBalance!==null) { ?> <small>Eryns</small><?php } ?></strong></div>
            <a href="#recharge-packages" class="pui-btn tnd-btn tnd-punch">Elegí tus Eryns <span aria-hidden="true">↓</span></a>
        </div>
    </header>

    <?php if(!$shopAnyCanBuy) { ?><div class="recharge-notice tnd-notice"><strong>Armá tu próxima recarga</strong><p>Podés probar el selector y comparar cantidades. El pago se habilitará cuando esté listo el servicio de recargas.</p></div><?php } ?>
    <?php if($shopMessage || $shopCreated) { ?><div id="recharge-checkout" class="eryns-checkout-feedback tnd-feedback" tabindex="-1">
    <?php if($shopMessage) { $bankFeedback=($bankSelected || ($_POST['recharge_action'] ?? '')==='bank_ready') && !empty($bankHistory['orders']); ?><div class="recharge-notice recharge-feedback" role="status"><div class="recharge-feedback-content"><span class="recharge-feedback-title">Seguimiento de tu recarga</span><p><?php echo panicAccountEscape($shopMessage); ?></p><a class="recharge-feedback-link" href="<?= $bankFeedback?'#bank-transfers':'#recharge-history' ?>"><?= $bankFeedback?'Ver mi transferencia':'Ver mis compras' ?> ↓</a></div></div><?php } ?>
    <?php if($shopUalaEnabled && $shopCreated && isset($shopCreated['checkout_url']) && $shopCreated['payment_state']==='pending') { ?>
    <div class="recharge-ready"><span class="tnd-kicker">TU COMPRA ESTÁ LISTA</span><h3><?php echo panicTiendaGemaImg(34); ?><?php echo number_format($shopCreated['coins'],0,',','.'); ?> Eryns para <?php echo panicAccountEscape($shopCreated['account']); ?></h3><p>Total: <b>$ <?php echo number_format($shopCreated['price_cents']/100,0,',','.'); ?> ARS</b>. Revisá que Ualá muestre este mismo importe antes de pagar.</p><a class="recharge-button pui-btn tnd-btn" href="<?php echo panicAccountEscape($shopCreated['checkout_url']); ?>" target="_blank" rel="noopener noreferrer">Continuar a Ualá ↗︎</a><p class="recharge-subtle">Tu compra ya está guardada en el historial.</p></div>
    <?php } ?>
    </div><?php } ?>
    <?php if($shopAdmin) { ?><p class="tnd-admin-link"><a class="recharge-button recharge-button-secondary" href="<?php echo panicAccountEscape(__BASE_URL__.'usercp/shopadmin/'); ?>">Administrar tienda →</a></p><?php } ?>

    <section id="recharge-packages" class="eryns-buy tnd-buy" aria-labelledby="eryns-buy-title">
      <div class="tnd-head"><div><p class="tnd-kicker">Paso 1 · Elegí</p><h3 class="tnd-h" id="eryns-buy-title">Tu pack de Eryns</h3></div><p class="tnd-head-note">Los Eryns son de tu cuenta y los comparten todos tus personajes.</p></div>
      <form id="eryns-purchase-form" method="post" action="<?php echo panicAccountEscape(__BASE_URL__.'usercp/recharge/'.($bankSelected?'#bank-transfers':'#recharge-checkout')); ?>" data-cart-form>
        <input type="hidden" name="recharge_csrf" value="<?php echo panicAccountEscape($_SESSION['recharge_csrf']); ?>">
        <input type="hidden" name="recharge_nonce" value="<?php echo panicAccountEscape($_SESSION['recharge_nonce']); ?>">
        <input type="hidden" name="recharge_action" value="create">
        <div class="tnd-packs eryns-denominations" style="--eryns-pack-columns:<?php echo $shopPackColumns; ?>;--tnd-packs:<?php echo max(1,count($shopPackOrder)); ?>" role="group" aria-label="Elegí una recarga"><?php foreach($shopPackOrder as $i) { $package=$rechargePackages[$i]; $bonusPercent=100*$package['bonus']/$package['coins']; $featured=$i===$shopFeaturedIndex; ?><button type="button" class="eryns-denomination tnd-pack<?php echo $featured?' eryns-denomination--featured is-featured':''; ?><?php echo $tndPackArt[$i]?' has-art':''; ?> tnd-pack--t<?php echo $tndPackTier[$i]; ?>" data-choose-package="<?php echo panicAccountEscape($package['id']); ?>" aria-pressed="false">
            <span class="tnd-pack-tags"><?php if($featured) { ?><span class="eryns-card-recommendation tnd-tag tnd-tag--feat"><?php echo $shopRecommendation; ?></span><?php } ?><?php if($package['bonus']>0) { ?><em class="eryns-card-bonus has-bonus tnd-tag tnd-tag--bonus">+<?php echo number_format($bonusPercent,$bonusPercent==floor($bonusPercent)?0:1,',','.'); ?>% extra</em><?php } ?></span>
            <span class="tnd-pack-art"><?php if($tndPackArt[$i]) { ?><?php $tndSz=panicTiendaArtSize('tienda-pack-'.$tndPackTier[$i],600,600); ?><img src="<?php echo panicAccountEscape($tndPackArt[$i]); ?>" alt="" width="<?php echo $tndSz[0]; ?>" height="<?php echo $tndSz[1]; ?>" decoding="async"><?php } elseif($tndGem) { ?><img class="tnd-pack-gem" src="<?php echo panicAccountEscape($tndGem); ?>" alt="" width="64" height="64" loading="lazy" decoding="async"><?php } ?></span>
            <strong class="tnd-pack-num"><?php echo number_format($package['coins']+$package['bonus'],0,',','.'); ?></strong><small class="tnd-pack-unit">Eryns</small>
            <span class="eryns-card-breakdown tnd-pack-break"><?php echo $package['bonus']>0?number_format($package['coins'],0,',','.').' + '.number_format($package['bonus'],0,',','.').' de regalo':'&nbsp;'; ?></span>
            <b class="eryns-card-price tnd-pack-price">$ <?php echo number_format($package['price_cents']/100,0,',','.'); ?> <small>ARS</small></b>
            <span class="eryns-card-choice tnd-pack-choice">Elegir pack</span>
        </button><?php } ?></div>

        <div id="recharge-cart" class="eryns-offer-grid tnd-sum" aria-labelledby="cart-title" tabindex="-1">
          <div class="eryns-selected-deal tnd-sum-get">
            <p class="tnd-kicker" id="cart-title" data-selected-name>Recibís en tu cuenta</p>
            <div class="tnd-sum-amount"><?php echo panicTiendaGemaImg(56,'tnd-gem tnd-sum-gem'); ?><strong class="eryns-deal-amount" data-total-coins>0 <small>Eryns</small></strong></div>
            <div class="tnd-sum-bonus"><div class="eryns-real-bonus tnd-tag tnd-tag--bonus" data-bonus-row hidden><strong data-bonus-rate></strong><span>EXTRA</span></div><p class="eryns-deal-breakdown" data-bonus-breakdown hidden></p></div>
            <dl class="tnd-sum-dest"><div><dt>Para la cuenta</dt><dd><?php echo panicAccountEscape($rechargeAccount); ?></dd></div><div><dt>Saldo actual</dt><dd><?php echo $rechargeBalance===null?'No disponible':number_format($rechargeBalance,0,',','.').' Eryns'; ?></dd></div></dl>
            <button type="button" class="eryns-upgrade tnd-upgrade" <?php if($shopBonusIndex!==null) { $bonusOffer=$rechargePackages[$shopBonusIndex]; ?>data-upgrade-package="<?php echo panicAccountEscape($bonusOffer['id']); ?>" data-upgrade-coins="<?php echo $bonusOffer['coins']+$bonusOffer['bonus']; ?>" data-upgrade-price="<?php echo $bonusOffer['price_cents']; ?>"<?php } ?> hidden><?php if($shopBonusIndex!==null) { ?><span><strong>Recibí <?php echo number_format($bonusOffer['bonus'],0,',','.'); ?> Eryns extra</strong><small>Pack de <?php echo number_format($bonusOffer['coins']+$bonusOffer['bonus'],0,',','.'); ?> · $ <?php echo number_format($bonusOffer['price_cents']/100,0,',','.'); ?> ARS</small></span><b>Elegir →</b><?php } ?></button>
          </div>
          <div class="eryns-purchase-action tnd-sum-pay">
            <fieldset class="bank-methods tnd-methods"><legend>Medio de pago</legend><?php if($shopUalaEnabled) { ?><label><input type="radio" name="payment_method" value="uala_bis" data-method-enabled="<?= $shopCanBuy?'1':'0' ?>" <?= !$bankSelected?'checked':'' ?>><span>Ualá</span></label><?php } if($bankConfig['enabled'] || !$shopUalaEnabled) { ?><label><input type="radio" name="payment_method" value="bank_transfer" data-method-enabled="<?= $bankCanBuy?'1':'0' ?>" <?= $bankSelected?'checked':'' ?>><span>Transferencia bancaria</span></label><?php } ?></fieldset>
            <div class="tnd-cuit-slot"><label class="bank-cuit tnd-cuit" data-bank-cuit <?= !$bankSelected?'hidden':'' ?>><span>CUIT de quien realiza el aporte</span><input name="buyer_cuit" inputmode="numeric" autocomplete="off" maxlength="13" pattern="(?:[0-9]{11}|[0-9]{2}-[0-9]{8}-[0-9])" value="<?= panicAccountEscape($bankCuit) ?>" <?= $bankSelected?'required':'' ?>><small>Se usa para identificar tu transferencia. El comprobante se adjunta en un ticket privado de Discord. La acreditación puede demorar hasta 48 horas.</small></label><p class="tnd-cuit-alt">Pagás con Ualá: no hace falta cargar CUIT. Al continuar, Ualá te muestra el importe para confirmar.</p></div>
            <input type="hidden" name="terms_version" value="<?= PanicBankTransfers::TERMS_VERSION ?>">
            <label class="eryns-consent tnd-consent"><input type="checkbox" name="accept_terms" value="1" required><span>Acepto los <a href="<?= __BASE_URL__ ?>tos/" target="_blank" rel="noopener">Términos</a>, la <a href="<?= __BASE_URL__ ?>refunds/" target="_blank" rel="noopener">política de recargas y reembolsos</a> y la <a href="<?= __BASE_URL__ ?>privacy/" target="_blank" rel="noopener">Privacidad</a>. Entiendo que mi aporte acredita los Eryns indicados y que el proceso puede demorar hasta 48 horas, según las condiciones informadas. No se ofrecen reintegros voluntarios por Eryns gastados o beneficios utilizados.</span></label>
            <div class="tnd-total"><span>Total a pagar</span><strong data-total-price>$ 0 ARS</strong><p class="tnd-stack"><span class="eryns-price-includes" data-price-includes>Pago único. Sin suscripción.</span><span class="tnd-sizer" aria-hidden="true">Incluye 100.000 Eryns de regalo.</span></p></div>
            <button class="recharge-button pui-btn tnd-btn tnd-pay" type="submit" data-cart-submit data-enabled="<?php echo $tndCanPay?'1':'0'; ?>" <?php if(!$tndCanPay) echo 'disabled'; ?>><?php echo $tndPayLabel; ?></button>
            <p class="tnd-stack tnd-explain"><span class="eryns-payment-explanation" data-payment-explanation><?= $bankSelected?$tndExplainBank:'Continuás en Ualá para completar el pago.' ?></span><span class="tnd-sizer" aria-hidden="true"><?= $tndExplainBank ?></span></p>
            <p class="tnd-stack tnd-hint"><span data-cart-hint>Tu recarga va a la cuenta de arriba.</span><span class="tnd-sizer" aria-hidden="true">Elegí al menos un paquete para continuar.</span></p>
          </div>
        </div>
      </form>

      <section class="eryns-combine tnd-combine" aria-labelledby="tnd-combine-title">
        <div class="tnd-combine-head"><h4 id="tnd-combine-title">¿Otra cantidad? Combiná packs</h4><p>Sumá packs con + y −. Hasta 99 por pack y $1.000.000 por compra. Los bonos activos se incluyen en el total.</p></div>
        <div class="eryns-quantity-list tnd-qty-list"><?php foreach($rechargePackages as $package) { ?><div class="eryns-quantity-row tnd-qty" data-package data-bonus="<?php echo $package['bonus']; ?>" data-base-coins="<?php echo $package['coins']; ?>" data-coins="<?php echo $package['coins']+$package['bonus']; ?>" data-price="<?php echo $package['price_cents']; ?>" data-title="<?php echo panicAccountEscape(panicAccountCurrencyLabel($package['title'])); ?>"><div class="tnd-qty-info"><label for="quantity-<?php echo $package['id']; ?>"><?php echo panicTiendaGemaImg(18); ?><?php echo number_format($package['coins']+$package['bonus'],0,',','.'); ?> Eryns</label><small>$ <?php echo number_format($package['price_cents']/100,0,',','.'); ?> ARS c/u<?php if($package['bonus']>0) { ?> · <?php echo number_format($package['bonus'],0,',','.'); ?> extra<?php } ?></small><p data-line-total>Sin seleccionar</p></div><div class="eryns-stepper tnd-stepper"><button type="button" data-quantity-change="-1" aria-label="Quitar un paquete de <?php echo $package['coins']+$package['bonus']; ?> Eryns">−</button><input id="quantity-<?php echo $package['id']; ?>" form="eryns-purchase-form" name="quantity[<?php echo $package['id']; ?>]" type="number" inputmode="numeric" min="0" max="99" step="1" value="<?php echo panicAccountEscape($shopInitialQuantities[$package['id']] ?? '0'); ?>"><button type="button" data-quantity-change="1" aria-label="Agregar un paquete de <?php echo $package['coins']+$package['bonus']; ?> Eryns">+</button></div></div><?php } ?></div>
        <div data-cart-lines class="eryns-cart-lines tnd-lines" style="--tnd-rows:<?php echo max(1,count($rechargePackages)); ?>"></div>
      </section>
      <ul class="eryns-confidence tnd-trust"><li><b>Transferencia bancaria</b><span>Acreditación en hasta 48 horas</span></li><li><b>Eryns para tu cuenta</b><span>Los comparten tus personajes</span></li><li><b>Con el pago confirmado</b><span>Desconectate del juego para recibir la recarga</span></li></ul>
    </section>

    <section id="eryns-bestias" class="eryns-wishlist tnd-beasts" aria-labelledby="eryns-wishlist-title">
      <div class="tnd-head"><div><p class="tnd-kicker">Las bestias primordiales</p><h3 class="tnd-h" id="eryns-wishlist-title">No pases desapercibido</h3></div><p class="tnd-head-note">Tres monturas legendarias para la Tienda del juego. Tocá cada una para ver su historia.</p></div>
      <div class="eryns-mount-shelf tnd-beast-row">
      <?php foreach(($shopProducts['products'] ?? []) as $product) { if(!isset($shopCreatures[$product['slug']])) continue; $creature=$shopCreatures[$product['slug']]; $productPrice=$shopVip!==null && $shopVip['active']?($product['vip_price_coins'] ?? null):$product['price_coins']; $published=!empty($product['available']); $available=$published && is_int($productPrice) && $productPrice>0; $beastArt=panicTiendaArt('tienda-'.$product['slug']); ?>
        <article class="eryns-mount-product tnd-beast tnd-beast--<?php echo panicAccountEscape($product['slug']); ?><?php echo !$published?' is-sealed':''; ?><?php echo $beastArt?' has-art':''; ?>">
          <button type="button" class="eryns-mount-preview tnd-beast-art" data-open-creature="eryns-creature-<?php echo panicAccountEscape($product['slug']); ?>" aria-label="Ver la historia de <?php echo panicAccountEscape($creature['name']); ?>"><span class="tnd-beast-word" aria-hidden="true"><?php echo panicAccountEscape($creature['name']); ?></span><?php if($beastArt) { ?><img src="<?php echo panicAccountEscape($beastArt); ?>" alt="<?php echo panicAccountEscape($creature['name']); ?>" width="1200" height="1200" loading="lazy" decoding="async"><?php } ?><?php if(!$published) { ?><span class="eryns-seal tnd-seal">Sellado</span><?php } ?><span class="tnd-beast-more" aria-hidden="true">Ver historia +</span></button>
          <div class="eryns-mount-caption tnd-beast-copy"><span class="tnd-beast-title"><?php echo panicAccountEscape($creature['title']); ?></span><h4><?php echo panicAccountEscape($creature['name']); ?></h4><p class="eryns-mount-status"><?php echo panicAccountEscape($shopMountCopy[$product['slug']] ?? 'Una presencia que se recuerda.'); ?></p><p class="eryns-mount-price tnd-beast-price"><?php if($available) { echo panicTiendaGemaImg(20); echo number_format($productPrice,0,',','.'); ?> Eryns<small><?php echo $shopVip!==null && $shopVip['active']?'Precio VIP':'Precio normal'; ?></small><?php } else { ?><small><?php echo $published?'Precio VIP por confirmar':'Se revela en la apertura'; ?></small><?php } ?></p><button type="button" class="tnd-beast-buy" data-target-price="<?php echo $available?$productPrice:0; ?>" data-target-name="<?php echo panicAccountEscape($creature['name']); ?>" <?php if(!$available) echo 'disabled'; ?>><?php echo $available?'Recargar para este →':($published?'Precio VIP por confirmar':'Se revela en la apertura'); ?></button></div>
        </article><?php } ?>
      </div><p class="eryns-target-message tnd-target" data-target-message role="status"></p>
    </section>

    <?php require __DIR__.'/recharge-vip-showcase.php'; ?>

    <?php require __DIR__.'/recharge-bank-history.php'; ?>
    <div class="eryns-utilities tnd-utils"><details id="recharge-history" class="eryns-support-panel tnd-panel"><summary><span class="tnd-panel-ic"><?php echo panicAccountIcon('coin','Mis compras'); ?></span><div><strong>Mis compras</strong><small>Historial y estado de tus recargas</small></div><b><?php echo (int)$shopHistory['total']; ?> <i aria-hidden="true">+</i></b></summary><div class="eryns-support-body">        <?php if(!$shopHistory['orders']) { ?><div class="recharge-history-empty"><strong>Acá empieza tu historial</strong><p>Cuando prepares una compra, la vas a encontrar acá con su precio y estado.</p></div><?php } ?>
        <div class="recharge-history-list"><?php foreach($shopHistory['orders'] as $order) { [$tone,$label,$copy]=panicRechargeStatus($order); ?>
            <article class="recharge-order" data-order-id="<?php echo $order['id']; ?>" data-order-state="<?php echo $tone; ?>"><div class="recharge-order-top"><span class="recharge-order-date"><?php echo panicAccountEscape(panicRechargeArgDate($order['created_at'])); ?> ARG</span><span class="recharge-status" data-order-label><?php echo panicAccountEscape($label); ?></span></div><div class="recharge-order-value"><strong><?php echo number_format($order['coins'],0,',','.'); ?> <small>Eryns</small></strong><b>$ <?php echo number_format($order['price_cents']/100,0,',','.'); ?> ARS</b></div><p data-order-copy><?php echo panicAccountEscape($copy); ?></p><div class="recharge-order-actions"><?php if($shopUalaEnabled && $order['payment_state']==='pending' && isset($order['checkout_url'])) { ?><a class="recharge-button recharge-button-secondary" data-order-pay href="<?php echo panicAccountEscape($order['checkout_url']); ?>" target="_blank" rel="noopener noreferrer">Continuar pago ↗︎</a><?php } ?><?php if($shopSettings && $order['live']===($shopSettings['environment']==='production') && $order['payment_state']!=='creating') { ?><form method="post" action="<?php echo panicAccountEscape(__BASE_URL__.'usercp/recharge/#recharge-history'); ?>"><input type="hidden" name="recharge_csrf" value="<?php echo panicAccountEscape($_SESSION['recharge_csrf']); ?>"><input type="hidden" name="order_id" value="<?php echo $order['id']; ?>"><button type="submit" name="recharge_action" value="refresh" class="recharge-text-button">Actualizar estado</button></form><?php } ?></div><details><summary>Ver detalle y número de compra</summary><ul><?php foreach($order['lines'] as $line) { ?><li><?php echo $line['quantity']; ?> × <?php echo panicAccountEscape(panicAccountCurrencyLabel($line['title'])); ?></li><?php } ?></ul><p>Cuenta: <b><?php echo panicAccountEscape($order['account']); ?></b><br><span class="recharge-order-reference"><?php echo $order['id']; ?></span></p></details></article>
        <?php } ?></div>
        <?php if($shopHistory['total']>10) { ?><nav class="recharge-pagination" aria-label="Páginas de compras"><?php if($shopPage>1) { ?><a href="?compras=<?php echo $shopPage-1; ?>#recharge-history">← Anteriores</a><?php } ?><span>Página <?php echo $shopPage; ?> de <?php echo (int)ceil($shopHistory['total']/10); ?></span><?php if($shopPage*10<$shopHistory['total']) { ?><a href="?compras=<?php echo $shopPage+1; ?>#recharge-history">Siguientes →</a><?php } ?></nav><?php } ?>
        <p class="recharge-subtle" data-history-update role="status">Si acabás de pagar, volvé a abrir esta página para ver el estado.</p>
</div></details>
    <details id="recharge-guide" class="eryns-support-panel tnd-panel"><summary><span class="tnd-panel-ic"><?php echo panicAccountIcon('shield','Ayuda'); ?></span><div><strong>¿Cómo recibo mis Eryns?</strong><small>Pago, entrega y preguntas frecuentes</small></div><b aria-hidden="true">+</b></summary><div class="eryns-support-body"><ol class="eryns-help-steps"><li><b>1. Elegí tu pack</b><span>Revisá la cantidad, el precio y la cuenta de destino.</span></li><li><b>2. Hacé la transferencia</b><span>Usá los datos indicados y adjuntá el comprobante en tu ticket privado de Discord. La acreditación puede demorar hasta 48 horas.</span></li><li><b>3. Recibilos en el juego</b><span>Con el pago aprobado, desconectate. La acreditación se completa automáticamente.</span></li></ol><details><summary>¿Dónde puedo usarlos?</summary><p>En la tienda del juego: X o F11 → Tienda. Los Eryns pertenecen a tu cuenta y los comparten sus personajes.</p></details><details><summary>¿Qué pasa si estoy jugando?</summary><p>La recarga espera a que cierres la sesión del juego. Después se reintenta la entrega automáticamente. No hace falta volver a pagar.</p></details><details><summary>¿Cómo compruebo la entrega?</summary><p>Abrí Mis compras. «Eryns acreditados» confirma la entrega. Volvé a entrar al juego para ver el saldo. Si aparece «En revisión», contactá al equipo con el número de compra.</p></details><p class="eryns-terms">Al continuar aceptás los <a href="<?php echo __BASE_URL__; ?>tos/" target="_blank" rel="noopener">Términos</a>, la <a href="<?php echo __BASE_URL__; ?>refunds/" target="_blank" rel="noopener">política de recargas y reembolsos</a> y la <a href="<?php echo __BASE_URL__; ?>privacy/" target="_blank" rel="noopener">Privacidad</a>.</p></div></details></div>

    <div class="eryns-thanks tnd-thanks"><p class="tnd-thanks-big">Hecho para seguir jugando.</p><p>Cada recarga ayuda a mantener y desarrollar MU PANIC. <b>Gracias por construir este mundo con nosotros.</b></p><a class="eryns-account-return" data-account-return href="<?php echo panicAccountEscape(__BASE_URL__.'usercp/'); ?>">← Volver a mi cuenta</a></div>

    <?php foreach(['theryon','nerathys','vaeraxes'] as $slug) { if(!isset($shopCreatures[$slug])) continue; $creature=$shopCreatures[$slug]; $beastArt=panicTiendaArt('tienda-'.$slug); ?><dialog class="eryns-creature-dialog tnd-dialog tnd-beast--<?php echo $slug; ?><?php echo $beastArt?' has-art':''; ?>" id="eryns-creature-<?php echo $slug; ?>" aria-labelledby="eryns-creature-title-<?php echo $slug; ?>"><button type="button" class="eryns-dialog-close tnd-dialog-close" data-close-creature aria-label="Cerrar presentación">×</button><div class="eryns-dialog-grid tnd-dialog-grid"><div class="tnd-dialog-art"><span class="tnd-beast-word" aria-hidden="true"><?php echo panicAccountEscape($creature['name']); ?></span><?php if($beastArt) { ?><img src="<?php echo panicAccountEscape($beastArt); ?>" alt="<?php echo panicAccountEscape($creature['name']); ?>" width="1200" height="1200" loading="lazy"><?php } ?></div><div class="tnd-dialog-copy"><span class="tnd-kicker"><?php echo panicAccountEscape($creature['title']); ?></span><h3 id="eryns-creature-title-<?php echo $slug; ?>"><?php echo panicAccountEscape($creature['name']); ?></h3><p><?php echo panicAccountEscape($creature['role']); ?></p><ul><?php foreach($creature['stats'] as $stat) { ?><li><?php echo panicAccountEscape($stat); ?></li><?php } ?></ul><p class="eryns-availability"><?php echo panicAccountEscape($creature['obtain']); ?>.<?php if($slug==='theryon') { ?> Precios y disponibilidad: X / F11 → Tienda.<?php } ?></p><button type="button" class="eryns-dialog-buy pui-btn tnd-btn" data-return-to-buy>Volver a elegir Eryns →</button></div></div></dialog><?php } ?>

    <div class="eryns-mobile-summary tnd-mobile" data-mobile-summary hidden><div><strong data-mobile-coins>0 Eryns</strong><span data-mobile-price>$ 0 ARS</span></div><button type="submit" form="eryns-purchase-form" data-mobile-submit <?php if(!$tndCanPay) echo 'disabled'; ?>><?php echo $tndCanPay?($bankSelected?'Ver datos de transferencia':'Pagar con Ualá'):'Pagos próximamente'; ?></button></div>
    <?php if($shopAdmin) { ?><details class="recharge-admin"><summary>Administración · recargas y prueba anterior</summary><div class="recharge-admin-content"><p>Ambiente: <b><?php echo panicAccountEscape($shopSettings['environment'] ?? 'Sin configurar'); ?></b>.</p><p>Las compras de jugadores usan su propio historial y registro de entrega. La prueba anterior de pruebacoin se conserva por separado.</p><?php include __DIR__.'/recharge-check.php'; include __DIR__.'/recharge-pilot-panel.php'; ?><?php if($shopStore) { $adminHistory=$shopStore->history('',1,true); ?><h3>Últimas compras del servidor</h3><?php foreach($adminHistory['orders'] as $entry) { [, $statusText]=panicRechargeStatus($entry); ?><p><b><?php echo panicAccountEscape($entry['account']); ?></b> · <?php echo number_format($entry['coins'],0,',','.'); ?> Eryns · <?php echo panicAccountEscape($statusText); ?><br><small class="recharge-order-reference"><?php echo $entry['id']; ?></small></p><?php } } ?></div></details><?php } ?>
</section>
