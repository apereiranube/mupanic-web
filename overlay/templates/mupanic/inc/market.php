<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/market-data.php';
$market=panicMarketRead();$metrics=$market['metrics']??[];$last=$market['sales'][0]??null;
$art=__PATH_TEMPLATE__.'img/market/';
?>
<div class="vessara-page" data-market-url="<?php echo panicMarketEscape(__BASE_URL__.'market/?market_data=1'); ?>" data-market-listings-url="<?php echo panicMarketEscape(__BASE_URL__.'market/?market_listings=1'); ?>" data-market-emblems-url="<?php echo panicMarketEscape(__PATH_TEMPLATE__.'img/market/emblems/'); ?>">
<canvas class="vessara-atmosphere" data-market-atmosphere aria-hidden="true"></canvas>
<div class="vessara-aurora" aria-hidden="true"><i></i><i></i><i></i></div>
<section class="vessara-hero shell" aria-labelledby="vessara-title">
 <img class="vessara-hero-art" src="<?php echo panicMarketEscape($art.'vessara-trade-v3.webp'); ?>" alt="Vessara sella un intercambio en el mercado de Loren" fetchpriority="high">
 <button type="button" class="vessara-effects-toggle" data-market-effects aria-pressed="false">Pausar efectos Ⅱ</button>
 <div class="vessara-market-brand"><img src="<?php echo panicMarketEscape($art.'vessara-seal-v2.svg'); ?>" alt="" width="42" height="42"><span>EL SELLO DE VESSARA <b>LA ECONOMÍA DE MU PANIC</b></span></div>
 <div class="vessara-intro"><h1 id="vessara-title">Mercado de <em>Vessara.</em></h1><p class="vessara-lead">Comerciá con jugadores en Zen y Eryns<br>bajo el sello de Vessara.</p><div class="vessara-hero-actions"><a class="vessara-cta" href="<?php echo __BASE_URL__; ?>register/">Quiero ser parte <span aria-hidden="true">↗</span></a><a class="vessara-link" href="#publicaciones">Ver objetos en venta <span aria-hidden="true">↓</span></a></div></div>
 <div class="vessara-seal"><span>BAJO MI SELLO</span><strong>Ningún trato se rompe.</strong><small>VESSARA / LOREN MARKET</small></div>
</section>
<section class="vessara-ticker shell" aria-label="Cinta de últimas ventas">
 <div class="vessara-ticker-label"><i aria-hidden="true"></i>ÚLTIMOS<br>TRATOS</div><div class="vessara-ticker-window"><div class="vessara-ticker-track" data-market-ticker><span class="vessara-ticker-empty">Esperando el registro del mercado.</span></div></div><button type="button" class="vessara-ticker-pause" data-market-pause aria-pressed="false" aria-label="Pausar cinta de ventas">Ⅱ</button>
</section>
<div class="shell vessara-session"><span>ZEN <i>●</i> ERYNS <i>●</i> LOREN MARKET</span><span class="vessara-session-clock"><time data-market-clock></time> <b>ARG</b></span></div>
<section class="shell vessara-stats" aria-label="Actividad del mercado">
 <article class="vessara-stat-trades"><div class="vessara-live-label" data-market-live-label><i aria-hidden="true"></i><span>MERCADO EN VIVO</span></div><span>VENTAS CONFIRMADAS HOY</span><strong data-market-metric="today"><?php echo panicMarketNumber($metrics['today']??''); ?></strong><small class="vessara-card-connection" data-market-connection>Conectando con el mercado</small><div class="vessara-today-footer"><i aria-hidden="true">↗</i><span data-market-comparison>ACTIVIDAD DEL DÍA</span></div></article>
 <article class="vessara-stat-zen"><div class="vessara-value-aura vessara-value-aura-zen" aria-hidden="true"><i></i><i></i></div><span><i aria-hidden="true">●</i> ZEN / VOLUMEN TOTAL</span><strong data-market-metric="zen"><?php echo panicMarketNumber($metrics['zen']??''); ?></strong><small>Hoy <b data-market-metric="zenToday"><?php echo panicMarketNumber($metrics['zenToday']??''); ?></b> Zen</small><svg class="vessara-spark" viewBox="0 0 280 55" preserveAspectRatio="none" aria-hidden="true" data-market-spark="zen"></svg><i class="vessara-card-caption">VOLUMEN DIARIO / 7 DÍAS</i></article>
 <article class="vessara-stat-eryns"><img class="vessara-eryns-art" src="<?php echo panicMarketEscape($art.'eryns-vessara-v1.webp'); ?>" alt="" aria-hidden="true" width="160" height="160"><div class="vessara-value-aura vessara-value-aura-eryns" aria-hidden="true"><i></i><i></i></div><span><i aria-hidden="true">◆</i> ERYNS / VOLUMEN TOTAL</span><strong data-market-metric="eryns"><?php echo panicMarketNumber($metrics['eryns']??''); ?></strong><small>Hoy <b data-market-metric="erynsToday"><?php echo panicMarketNumber($metrics['erynsToday']??''); ?></b> Eryns</small><svg class="vessara-spark" viewBox="0 0 280 55" preserveAspectRatio="none" aria-hidden="true" data-market-spark="eryns"></svg><i class="vessara-card-caption">VOLUMEN DIARIO / 7 DÍAS</i></article>
</section>
<section class="shell vessara-listings" id="publicaciones" aria-labelledby="vessara-listings-title">
 <div class="vessara-listings-heading">
  <div><p class="eyebrow">LA VIDRIERA DE LOREN MARKET</p><h2 id="vessara-listings-title">Objetos en venta.</h2><p>Publicaciones reales de jugadores. Consultá precios en Zen y Eryns antes de entrar al juego.</p></div>
  <a class="vessara-listings-entry" href="<?php echo __BASE_URL__; ?>downloads/">Entrar a comerciar ↗</a>
 </div>
 <div class="vessara-promoted" data-listings-featured-wrap hidden>
  <div class="vessara-promoted-heading"><div><span class="vessara-promoted-star" aria-hidden="true">✦</span><div><p class="eyebrow">BAJO EL SELLO MAYOR DE VESSARA</p><h3>Publicaciones destacadas.</h3></div></div><span>Promoción vigente en el juego · Hasta 24 h</span></div>
  <div class="vessara-promoted-grid" data-listings-featured></div>
 </div>
 <div class="vessara-listings-board">
  <div class="vessara-listings-top"><h3>Explorá las publicaciones</h3><span data-listings-count>—</span></div>
  <div class="vessara-listings-controls">
   <div class="vessara-listings-currencies" role="group" aria-label="Moneda de publicaciones">
    <button type="button" data-listings-currency="all" aria-pressed="true">Todas</button>
    <button type="button" data-listings-currency="0" aria-pressed="false">Zen</button>
    <button type="button" data-listings-currency="1" aria-pressed="false">Eryns</button>
   </div>
   <label class="vessara-listings-search"><span class="sr-only">Buscar objetos o vendedores</span><input type="search" maxlength="60" data-listings-search placeholder="Buscar objeto o vendedor"></label>
   <button class="vessara-listings-feature-filter" type="button" data-listings-onlyfeatured aria-pressed="false">✦ Solo destacados</button>
   <label class="vessara-listings-sort"><span class="sr-only">Orden de las publicaciones</span><select data-listings-sort><option value="recent">Destacados primero</option><option value="price-asc">Menor precio</option><option value="price-desc">Mayor precio</option></select></label>
  </div>
  <div class="vessara-offer-grid" data-listings-grid></div>
  <p class="vessara-listings-state" data-listings-state role="status">Consultando las publicaciones del juego…</p>
  <div class="vessara-listings-pagination" data-listings-pagination hidden><button type="button" data-listings-prev>← Anterior</button><span data-listings-page>Página 1</span><button type="button" data-listings-next>Siguiente →</button></div>
  <div class="vessara-listings-foot"><span data-listings-connection>Conectando con el mercado…</span><span>Las compras, cancelaciones y promociones se realizan únicamente en MU PANIC.</span></div>
 </div>
</section>
<section class="shell vessara-floor" id="tratos">
 <div class="vessara-ledger" aria-labelledby="vessara-trades-title">
  <div class="vessara-section-head"><div><p class="eyebrow">EL REGISTRO DEL MERCADO</p><h2 id="vessara-trades-title">Últimas ventas.</h2></div><div class="vessara-update"><p class="vessara-live" data-market-status role="status"><?php echo !$market?'Datos no disponibles':(($market['stale']??false)?'Último registro disponible':'Registro actualizado'); ?></p><span>Próxima lectura <b data-market-countdown>15</b>s</span></div></div>
  <div class="vessara-toolbar"><div class="vessara-filters" role="group" aria-label="Filtrar moneda"><button type="button" data-market-currency="all" aria-pressed="true">Todas</button><button type="button" data-market-currency="0" aria-pressed="false">Zen</button><button type="button" data-market-currency="1" aria-pressed="false">Eryns</button></div><label class="vessara-search"><span class="sr-only">Buscar en los últimos tratos</span><input type="search" data-market-search placeholder="Buscar objeto o personaje" maxlength="96"></label></div>
  <div class="vessara-table-wrap"><table class="vessara-table"><caption class="sr-only">Últimos 40 intercambios confirmados en el juego</caption><thead><tr><th>Objeto</th><th>Comprador</th><th>Vendedor</th><th>Valor del trato</th><th>Sellado</th></tr></thead><tbody data-market-rows><?php foreach($market['sales']??[] as $s) { ?><tr><td><span class="vessara-item-mark" aria-hidden="true">◇</span><strong><?php echo panicMarketEscape($s['item'].($s['level']?' +'.$s['level']:'')); ?></strong></td><td><?php echo panicMarketEscape($s['buyer']); ?></td><td><?php echo panicMarketEscape($s['seller']); ?></td><td class="vessara-price <?php echo $s['currency']===1?'is-eryns':''; ?>"><?php echo panicMarketNumber($s['price']).' '.($s['currency']===0?'Zen':'Eryns'); ?></td><td><time datetime="<?php echo panicMarketEscape($s['soldAt']); ?>"><?php echo panicMarketEscape(substr($s['soldAt'],8,2).'/'.substr($s['soldAt'],5,2).' '.substr($s['soldAt'],11,5)); ?></time></td></tr><?php } ?></tbody></table><p class="vessara-empty" data-market-empty<?php echo !empty($market['sales'])?' hidden':''; ?>><?php echo $market?'Todavía no hay tratos sellados.':'El registro del mercado no está disponible en este momento.'; ?></p></div>
  <div class="vessara-ledger-footer"><span><b data-market-metric="total"><?php echo panicMarketNumber($metrics['total']??''); ?></b> tratos sellados en total</span><span>Ventas confirmadas · Lectura cada 15 segundos</span></div>
 </div>
 <aside class="vessara-tape vessara-highlights" aria-labelledby="vessara-tape-title"><div class="vessara-tape-head"><span class="eyebrow">LOS CINCO PUESTOS DEL DÍA</span><h2 id="vessara-tape-title">Récords<br>de hoy.</h2></div><div class="vessara-top-grid" data-market-highlights></div></aside>
</section>
<section class="shell vessara-bottom"><div class="vessara-activity"><div class="vessara-section-head"><div><p class="eyebrow">ÚLTIMOS SIETE DÍAS</p><h2>El pulso de PANIC.</h2></div><span class="vessara-chart-unit">TRATOS / DÍA</span></div><div class="vessara-bars" data-market-days></div></div></section>
<section class="shell vessara-guarantee" aria-labelledby="vessara-guarantee-title"><div class="vessara-guarantee-art"><img src="<?php echo panicMarketEscape($art.'vessara-guarantee-v1.webp'); ?>" alt="Vessara sella un acuerdo entre dos comerciantes" loading="lazy" width="1672" height="941"></div><div class="vessara-guarantee-copy"><p class="eyebrow">DONDE EL VALOR VOLVIÓ AL MUNDO</p><h2 id="vessara-guarantee-title">La garantía<br>de Vessara.</h2><p>Después de la Fractura, entregar primero era arriesgarlo todo. Vessara cambió eso: su palabra convirtió el intercambio entre desconocidos en un trato justo.</p><p>Hoy, desde Loren Market, custodia tu objeto hasta que se vende y garantiza que ambas partes reciban lo acordado.</p><blockquote>“Bajo mi sello, ningún trato se rompe.”</blockquote></div><div class="vessara-guarantee-rules"><article><span>SU RESGUARDO</span><strong>Publicar es gratis.</strong><p>También podés cancelar y retirar tu objeto sin costo.</p></article><article><span>Tributo de Vessara</span><strong>5% al vender.</strong><p>Se descuenta al vendedor y se redondea hacia abajo. Las publicaciones destacadas pagan un 8% total (5% + 3%) solamente cuando se venden.</p></article><article><span>TU LUGAR EN EL MERCADO</span><strong>Free: 3 · VIP: 10</strong><p>Publicaciones activas entre ambas monedas. Cada una dura 7 días.</p></article></div><div class="vessara-guarantee-join"><p>Encontrá a Vessara en Loren Market.</p><a class="vessara-cta" href="<?php echo __BASE_URL__; ?>register/">Crear mi cuenta ↗</a><a class="vessara-link" href="<?php echo __BASE_URL__; ?>downloads/">Descargar cliente ↓</a></div></section>
<div class="vessara-trade-burst" data-market-burst aria-hidden="true"><i></i><i></i><i></i></div>
<div class="vessara-new-trades" data-market-notification role="status" hidden></div>
</div>
<script type="application/json" id="vessara-initial"><?php echo json_encode($market,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE); ?></script>
