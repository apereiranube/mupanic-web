<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/market-data.php';
$market=panicMarketRead();$metrics=$market['metrics']??[];$last=$market['sales'][0]??null;
$art=__PATH_TEMPLATE__.'img/market/';
$v3=__PATH_TEMPLATE__.'img/server/v3/';
// Escenas del recorrido (fondo + unión con la escena anterior).
// Arte provisorio: se reemplaza por las piezas nuevas sin tocar el resto de la página.
$scenes=[
 'portada'=>['bg'=>$art.'bolsa/mkt-01-bolsa-e0d5061d940a.webp','pos'=>'70% 45%'],
 'trato'=>['bg'=>$art.'bolsa/mkt-03-sello-5a61a204aafa.webp','pos'=>'72% 50%','join'=>$art.'bolsa/mkt-join-02-telas-9d86a6fa39bb.webp'],
 'bolsa'=>['bg'=>$art.'bolsa/mkt-02-piso-56680b9b0cb6.webp','pos'=>'50% 50%','join'=>$art.'bolsa/mkt-join-01-monedas-b400421271cc.webp'],
 'registro'=>['bg'=>$art.'bolsa/mkt-04-archivo-7699db410f1a.webp','pos'=>'70% 50%','join'=>$art.'bolsa/mkt-join-03-registros-253b8df00b45.webp'],
 'garantia'=>['bg'=>$art.'bolsa/mkt-05-boveda-b150649ec587.webp','pos'=>'60% 50%','join'=>$art.'bolsa/mkt-join-04-cadenas-67b1d5757768.webp'],
];
function panicMarketScene($scenes,$key,$tone='l') {
 $s=$scenes[$key];
 echo '<div class="vs-bg vs-'.$tone.'" aria-hidden="true"><div class="vs-stick"><img src="'.panicMarketEscape($s['bg']).'" alt=""'.($key==='portada'?' fetchpriority="high"':' loading="lazy" decoding="async"').' style="object-position:'.panicMarketEscape($s['pos']).'"></div></div>';
 if(!empty($s['join'])) echo '<img class="vs-join" src="'.panicMarketEscape($s['join']).'" alt="" aria-hidden="true" loading="lazy" decoding="async">';
}
?>
<div class="vessara-page vessara-hall" data-market-url="<?php echo panicMarketEscape(__BASE_URL__.'market/?market_data=1'); ?>" data-market-listings-url="<?php echo panicMarketEscape(__BASE_URL__.'market/?market_listings=1'); ?>" data-market-emblems-url="<?php echo panicMarketEscape(__PATH_TEMPLATE__.'img/market/emblems/'); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=VT323&display=swap">

<section class="vs-sc vs-sc-1 vessara-hero" aria-labelledby="vessara-title">
 <?php panicMarketScene($scenes,'portada'); ?>
 <div class="vs-wrap">
  <div class="vessara-market-brand"><img src="<?php echo panicMarketEscape($art.'vessara-seal-v2.svg'); ?>" alt="" width="42" height="42"><span>EL SELLO DE VESSARA <b>LA ECONOMÍA DE MU PANIC</b></span></div>
  <div class="vessara-intro">
   <h1 id="vessara-title">Mercado de <em>Vessara</em></h1>
   <p class="vessara-lead">Comerciá con jugadores en Zen y Eryns.</p>
   <p class="vessara-intro-description">Reliquias, equipamiento y tesoros cambian de manos bajo el sello de Vessara. Jugadores que impulsan un mismo mundo.</p>
   <div class="vessara-hero-actions"><a class="vessara-cta" href="<?php echo __BASE_URL__; ?>register/">Quiero ser parte <span aria-hidden="true">↗︎</span></a><a class="vessara-link" href="#publicaciones">Ver objetos en venta <span aria-hidden="true">↓</span></a></div>
  </div>
 </div>
</section>

<section class="vs-sc vs-sc-2 vs-bolsa" aria-label="El mercado en vivo">
 <?php panicMarketScene($scenes,'bolsa','v'); ?>
 <div class="vessara-ticker" aria-label="Cinta de últimas ventas">
  <div class="vessara-ticker-label"><i aria-hidden="true"></i>ÚLTIMOS<br>TRATOS</div><div class="vessara-ticker-window"><div class="vessara-ticker-track" data-market-ticker><span class="vessara-ticker-empty">Esperando el registro del mercado.</span></div></div><button type="button" class="vessara-ticker-pause" data-market-pause aria-pressed="false" aria-label="Pausar cinta de ventas">Ⅱ</button>
 </div>
 <div class="vs-wrap">
  <div class="vessara-stats vs-rv" aria-label="Actividad del mercado">
   <article class="vessara-stat-trades"><i class="vs-scan" aria-hidden="true"></i><div class="vessara-live-label" data-market-live-label><i aria-hidden="true"></i><span>MERCADO EN VIVO</span></div><span class="vs-k">Ventas de hoy</span><strong data-market-metric="today"><?php echo panicMarketNumber($metrics['today']??''); ?></strong><small class="vessara-card-connection" data-market-connection>Conectando con el mercado</small><div class="vessara-today-footer"><i aria-hidden="true">↗︎</i><span data-market-comparison>ACTIVIDAD DEL DÍA</span></div></article>
   <article class="vessara-stat-zen"><i class="vs-scan" aria-hidden="true"></i><img class="vs-coin" src="<?php echo panicMarketEscape($art.'vessara-zen-coins-v2.webp'); ?>" alt="" aria-hidden="true" width="256" height="256" loading="lazy"><span class="vs-k"><i aria-hidden="true">●</i> Volumen total en Zen</span><strong data-market-metric="zen"><?php echo panicMarketNumber($metrics['zen']??''); ?></strong><small>Hoy <b data-market-metric="zenToday"><?php echo panicMarketNumber($metrics['zenToday']??''); ?></b> Zen</small><svg class="vessara-spark" viewBox="0 0 280 55" preserveAspectRatio="none" aria-hidden="true" data-market-spark="zen"></svg></article>
   <article class="vessara-stat-eryns"><i class="vs-scan" aria-hidden="true"></i><img class="vs-coin" src="<?php echo panicMarketEscape($art.'eryns-vessara-v1.webp'); ?>" alt="" aria-hidden="true" width="160" height="160" loading="lazy"><span class="vs-k"><i aria-hidden="true">◆</i> Volumen total en Eryns</span><strong data-market-metric="eryns"><?php echo panicMarketNumber($metrics['eryns']??''); ?></strong><small>Hoy <b data-market-metric="erynsToday"><?php echo panicMarketNumber($metrics['erynsToday']??''); ?></b> Eryns</small><svg class="vessara-spark" viewBox="0 0 280 55" preserveAspectRatio="none" aria-hidden="true" data-market-spark="eryns"></svg></article>
  </div>
  <div class="vs-board vs-rv" aria-labelledby="vs-board-t">
   <i class="vs-bulbs" aria-hidden="true"></i><i class="vs-bulbs vs-bulbs-b" aria-hidden="true"></i>
   <div class="vs-board-head"><h3 id="vs-board-t"><i aria-hidden="true"></i>Tablero de cotizaciones</h3></div>
   <div class="vs-led" aria-hidden="true"><div class="vs-led-track" data-market-led>MERCADO DE VESSARA · ZEN · ERYNS · LOREN MARKET ·</div></div>
   <div class="vs-board-cols" aria-hidden="true"><span>Objeto</span><span>Último precio</span><span>Variación</span><span>Tratos</span><span>Tendencia</span></div>
   <div class="vs-board-rows" data-market-board role="list"><p class="vs-board-empty">Esperando las primeras cotizaciones del día.</p></div>
  </div>
 </div>
 <div class="vs-wrap vessara-listings" id="publicaciones" aria-labelledby="vessara-listings-title">
  <div class="vessara-listings-heading vs-rv">
   <div><p class="vs-ey">La vidriera de Loren Market</p><h2 id="vessara-listings-title">Objetos en venta</h2></div>
   <div class="vs-showcount"><span class="vs-live-dot" aria-hidden="true"></span><b data-listings-count-big>—</b><small>objetos en venta ahora</small><a class="vessara-listings-entry" href="<?php echo __BASE_URL__; ?>downloads/">Entrar a comerciar ↗︎</a></div>
  </div>
  <div class="vessara-promoted" data-listings-featured-wrap hidden>
   <div class="vessara-promoted-heading"><h3>Destacados</h3></div>
   <div class="vessara-promoted-grid" data-listings-featured tabindex="0" role="region" aria-roledescription="carrusel" aria-label="Publicaciones destacadas; desplazamiento continuo hacia la izquierda"></div>
  </div>
  <div class="vessara-listings-board">
   <div class="vessara-listings-top"><h3>Publicaciones de jugadores</h3><span data-listings-count>—</span></div>
   <div class="vessara-listings-controls">
    <div class="vessara-listings-currencies" role="group" aria-label="Moneda de publicaciones">
     <button type="button" data-listings-currency="all" aria-pressed="true">Todas</button>
     <button type="button" data-listings-currency="0" aria-pressed="false">Zen</button>
     <button type="button" data-listings-currency="1" aria-pressed="false">Eryns</button>
    </div>
    <label class="vessara-listings-search"><span class="sr-only">Buscar objetos o vendedores</span><input type="search" maxlength="60" data-listings-search placeholder="Buscar objeto o vendedor"></label>
    <label class="vessara-listings-sort"><span class="sr-only">Orden de las publicaciones</span><select data-listings-sort><option value="recent">Más recientes</option><option value="price-asc">Menor precio</option><option value="price-desc">Mayor precio</option></select></label>
   </div>
   <div class="vessara-offer-grid" data-listings-grid></div>
   <p class="vessara-listings-state" data-listings-state role="status">Consultando las publicaciones del juego…</p>
   <div class="vessara-listings-pagination" data-listings-pagination hidden><button type="button" data-listings-prev>← Anterior</button><span data-listings-page>Página 1</span><button type="button" data-listings-next>Siguiente →</button></div>
   <div class="vessara-listings-foot"><span data-listings-connection>Conectando al mercado…</span></div>
  </div>
 </div>
</section>

<section class="vs-sc vs-sc-3" id="como-funciona" aria-labelledby="vs-trato-t">
 <?php panicMarketScene($scenes,'trato'); ?>
 <div class="vs-wrap">
  <header class="vs-head vs-rv"><div><p class="vs-ey">Paso a paso</p><h2 id="vs-trato-t">Así se sella un trato.</h2></div><p class="vs-lead">Del puesto de Vessara a tu bolsillo, sin entregar nada a ciegas. Tocá cada paso o dejá que avance solo.</p></header>
  <div class="vs-deal vs-rv" data-deal>
   <div class="vs-stage" data-deal-stage data-step="1" aria-hidden="true" style="--deal-bg:url('<?php echo panicMarketEscape($scenes['trato']['bg']); ?>')">
    <div class="vs-npc vs-seller"><b>Vendedor</b><span>Kaelor</span></div>
    <div class="vs-npc vs-buyer"><b>Comprador</b><span>Nyxara</span></div>
    <div class="vs-item"><img src="<?php echo panicMarketEscape(__PATH_TEMPLATE__.'img/atlas/drops/jewel-chaos-809384516512.webp'); ?>" alt="" width="512" height="512" loading="lazy"><div><b>Jewel of Chaos</b><small>1.000 Eryns</small></div></div>
    <div class="vs-vault"><span>BAJO EL SELLO</span><i>7 días</i></div>
    <img class="vs-stamp" src="<?php echo panicMarketEscape($art.'vessara-seal-v2.svg'); ?>" alt="" width="120" height="120">
    <div class="vs-ring"></div>
    <div class="vs-coins"><?php for($i=0;$i<7;$i++) { ?><img src="<?php echo panicMarketEscape($art.'eryns-vessara-v1.webp'); ?>" alt="" width="160" height="160" loading="lazy"><?php } ?></div>
    <div class="vs-web"><span>MU PANIC · Mercado</span><b>Jewel of Chaos</b><i>1.000 Eryns</i></div>
    <div class="vs-pay"><div><span>Precio</span><b>1.000</b></div><div class="vs-tax"><span>Tributo 5%</span><b>−50</b></div><div class="vs-net"><span>Cobrás</span><b>950 Eryns</b></div></div>
    <p class="vs-caption" data-deal-caption>Kaelor publica su Jewel of Chaos en Loren Market.</p>
   </div>
   <ol class="vs-steps" data-deal-steps>
    <li class="on"><button type="button"><span class="vs-n">1</span><span><b>Publicás en Loren Market</b><small>Hablá con Vessara, elegí el objeto, la moneda y el precio. Publicar es gratis.</small></span><i class="vs-pg"></i></button></li>
    <li><button type="button"><span class="vs-n">2</span><span><b>Vessara lo guarda</b><small>Tu objeto queda bajo su sello por 7 días. Si cambiás de idea, lo retirás sin costo.</small></span><i class="vs-pg"></i></button></li>
    <li><button type="button"><span class="vs-n">3</span><span><b>Todos lo ven</b><small>Aparece en el mercado del juego y en esta vidriera, con su ficha completa.</small></span><i class="vs-pg"></i></button></li>
    <li><button type="button"><span class="vs-n">4</span><span><b>El sello cae</b><small>Alguien lo compra: el comprador paga y el objeto cambia de manos en el momento.</small></span><i class="vs-pg"></i></button></li>
    <li><button type="button"><span class="vs-n">5</span><span><b>Cobrás tu parte</b><small>El pago te espera aunque estés desconectado. Vessara se queda con su tributo del 5%.</small></span><i class="vs-pg"></i></button></li>
   </ol>
  </div>
 </div>
</section>

<section class="vs-sc vs-sc-4 vessara-floor" id="tratos" aria-labelledby="vessara-trades-title">
 <?php panicMarketScene($scenes,'registro'); ?>
 <div class="vs-wrap">
  <div class="vessara-ledger-heading vs-rv">
   <div class="vs-head"><div><p class="vs-ey">El registro del mercado</p><h2 id="vessara-trades-title">Últimas ventas.</h2></div><div class="vessara-update"><p class="vessara-live" data-market-status role="status"><?php echo !$market?'Datos no disponibles':(($market['stale']??false)?'Último registro disponible':'Registro actualizado'); ?></p><span>Próxima lectura <b data-market-countdown>15</b>s</span></div></div>
   <div class="vessara-toolbar"><div class="vessara-filters" role="group" aria-label="Filtrar moneda"><button type="button" data-market-currency="all" aria-pressed="true">Todas</button><button type="button" data-market-currency="0" aria-pressed="false">Zen</button><button type="button" data-market-currency="1" aria-pressed="false">Eryns</button></div><label class="vessara-search"><span class="sr-only">Buscar en los últimos tratos</span><input type="search" data-market-search placeholder="Buscar objeto o personaje" maxlength="96"></label></div>
  </div>
  <div class="vs-floor-grid">
   <div class="vessara-ledger vs-rv" aria-labelledby="vessara-trades-title">
    <div class="vessara-table-wrap"><table class="vessara-table"><caption class="sr-only">Últimos 40 intercambios confirmados en el juego</caption><thead><tr><th>Objeto</th><th>Comprador</th><th>Vendedor</th><th>Valor del trato</th><th>Sellado</th></tr></thead><tbody data-market-rows><?php foreach($market['sales']??[] as $s) { ?><tr><td><span class="vessara-item-mark" aria-hidden="true">◇</span><strong><?php echo panicMarketEscape($s['item'].($s['level']?' +'.$s['level']:'')); ?></strong></td><td><?php echo panicMarketEscape($s['buyer']); ?></td><td><?php echo panicMarketEscape($s['seller']); ?></td><td class="vessara-price <?php echo $s['currency']===1?'is-eryns':''; ?>"><?php echo panicMarketNumber($s['price']).' '.($s['currency']===0?'Zen':'Eryns'); ?></td><td><time datetime="<?php echo panicMarketEscape($s['soldAt']); ?>"><?php echo panicMarketEscape(substr($s['soldAt'],8,2).'/'.substr($s['soldAt'],5,2).' '.substr($s['soldAt'],11,5)); ?></time></td></tr><?php } ?></tbody></table><p class="vessara-empty" data-market-empty<?php echo !empty($market['sales'])?' hidden':''; ?>><?php echo $market?'Todavía no hay tratos sellados.':'El registro del mercado no está disponible en este momento.'; ?></p></div>
    <div class="vessara-ledger-footer"><span><b data-market-metric="total"><?php echo panicMarketNumber($metrics['total']??''); ?></b> tratos sellados en total</span><span>Ventas confirmadas · Lectura cada 15 segundos</span></div>
   </div>
   <aside class="vessara-tape vessara-highlights vs-rv" aria-labelledby="vessara-tape-title"><div class="vessara-tape-head"><span class="vs-ey">Los cinco puestos del día</span><h2 id="vessara-tape-title">Récords de hoy.</h2></div><div class="vessara-top-grid" data-market-highlights></div></aside>
  </div>
  <div class="vessara-bottom vs-rv"><div class="vessara-activity"><div class="vs-head"><div><p class="vs-ey">Últimos siete días</p><h2>El pulso de PANIC.</h2></div><span class="vessara-chart-unit">TRATOS / DÍA</span></div><div class="vessara-bars" data-market-days></div></div></div>
 </div>
</section>

<section class="vs-sc vs-sc-5 vessara-guarantee" aria-labelledby="vessara-guarantee-title">
 <?php panicMarketScene($scenes,'garantia','c'); ?>
 <div class="vs-wrap">
  <div class="vessara-guarantee-copy vs-rv">
   <p class="vs-ey">Donde el valor volvió al mundo</p>
   <h2 id="vessara-guarantee-title">La garantía de Vessara.</h2>
   <p>Después de la Fractura, entregar primero era arriesgarlo todo. Vessara cambió eso: su palabra convirtió el intercambio entre desconocidos en un trato justo.</p>
   <p>Hoy, desde Loren Market, custodia tu objeto hasta que se vende y garantiza que ambas partes reciban lo acordado.</p>
   <blockquote>“Bajo mi sello, ningún trato se rompe.”</blockquote>
  </div>
  <div class="vessara-guarantee-rules vs-rv">
   <article><span>Su resguardo</span><strong>Publicar es gratis.</strong><p>También podés cancelar y retirar tu objeto sin costo.</p></article>
   <article><span>Tributo de Vessara</span><strong>5% al vender.</strong><p>Se descuenta al vendedor y se redondea hacia abajo. Las publicaciones destacadas pagan un 8% total (5% + 3%) solamente cuando se venden.</p></article>
   <article><span>Tu lugar en el mercado</span><strong>Free: 3 · VIP: 10</strong><p>Publicaciones activas entre ambas monedas. Cada una dura 7 días.</p></article>
  </div>
  <div class="vessara-guarantee-join vs-rv">
   <p>Encontrá a Vessara en Loren Market.</p>
   <div class="vessara-hero-actions"><a class="vessara-cta" href="<?php echo __BASE_URL__; ?>register/">Crear mi cuenta <span aria-hidden="true">↗︎</span></a><a class="vessara-link" href="<?php echo __BASE_URL__; ?>downloads/">Descargar cliente <span aria-hidden="true">↓</span></a></div>
  </div>
 </div>
</section>

<div class="vessara-trade-burst" data-market-burst aria-hidden="true"><i></i><i></i><i></i></div>
<div class="vessara-new-trades" data-market-notification role="status" hidden></div>
</div>
<script type="application/json" id="vessara-initial"><?php echo json_encode($market,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE); ?></script>
