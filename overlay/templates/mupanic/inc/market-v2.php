<?php
if(!defined('access') || !access) die();
// MU PANIC · Mercado de Vessara (estilo nuevo). Datos: inc/market-data.php (ventas) y
// inc/market-listings-data.php (publicaciones) vía los endpoints market_data y market_listings.
require_once __DIR__.'/market-data.php';
$market = panicMarketRead();
$metrics = $market['metrics'] ?? [];
$mk = function($v) { return panicMarketEscape($v); };
$mkImg = __PATH_TEMPLATE__.'img/';
$mkBase = __BASE_URL__;
$mkReveal = function($lines) { return implode('<br>', array_map(function($l){ return '<span class="pui-reveal">'.$l.'</span>'; }, $lines)); };
$mkIcons = ['paso-publicar-0dad5f0a3391','paso-guardado-bcca7e281ad1','paso-todos-6c48dc8a90ad','paso-sellado-0364948c6517','paso-cobrar-1a24ecabf5d7'];
$mkSteps = [
  ['Publicás','Hablá con Vessara en Loren Market, elegí el objeto, la moneda y el precio. Publicar es gratis.'],
  ['Vessara lo guarda','Tu objeto queda bajo su sello por 7 días. Si cambiás de idea, lo retirás sin costo.'],
  ['Todos lo ven','Aparece en el mercado del juego y en esta vidriera, con su ficha completa.'],
  ['Se sella el trato','Alguien lo compra: paga y el objeto cambia de manos en el momento.'],
  ['Cobrás','El pago te espera aunque estés desconectado. Vessara se queda con su tributo del 5%.'],
];
?>
<div class="mk pui vessara-page" id="mk-page" data-market-url="<?php echo $mk($mkBase.'market/?market_data=1'); ?>" data-market-listings-url="<?php echo $mk($mkBase.'market/?market_listings=1'); ?>">

  <!-- Portada -->
  <section class="mk-hero" aria-labelledby="mk-title">
    <div class="mk-hero-block" aria-hidden="true"><span class="mk-hero-word">VESSARA</span></div>
    <div class="mk-hero-art" aria-hidden="true"><img src="<?php echo $mk($mkImg.'mercado/mercado-vessara-21aac8b393dc.webp'); ?>" alt="" width="853" height="1300" decoding="async" fetchpriority="high"></div>
    <div class="pui-wrap mk-hero-copy">
      <p class="pui-kicker">Loren Market</p>
      <h1 class="pui-h1" id="mk-title">Mercado de<br>Vessara</h1>
      <p class="pui-lead">Comprá y vendé con otros jugadores en Zen y Eryns. Vessara guarda cada objeto hasta que se vende y garantiza el trato.</p>
      <div class="mk-actions">
        <span class="pui-btn-wrap"><a class="pui-btn pui-btn--lg" href="#publicaciones">Ver objetos en venta</a></span>
        <a class="pui-btn pui-btn--ghost" href="#trato">Cómo se vende</a>
      </div>
    </div>
  </section>

  <section class="mk-score" aria-label="El mercado hoy">
    <dl class="pui-wrap mk-stats">
      <div class="mk-stat mk-stat--lead"><dt>Ventas de hoy</dt><dd><b class="pui-num" data-mk-metric="today"><?php echo panicMarketNumber($metrics['today'] ?? ''); ?></b></dd></div>
      <div class="mk-stat"><dt><img class="mk-cur" src="<?php echo __PATH_TEMPLATE__; ?>img/mercado/moneda-zen-f296546732d4.webp" alt="" width="18" height="18">Volumen en Zen</dt><dd><b class="pui-num mk-zen" data-mk-metric="zen"><?php echo panicMarketNumber($metrics['zen'] ?? ''); ?></b></dd></div>
      <div class="mk-stat"><dt><img class="mk-cur" src="<?php echo __PATH_TEMPLATE__; ?>img/votar/votar-gema-64-4559844ddcec.webp" alt="" width="18" height="18">Volumen en Eryns</dt><dd><b class="pui-num mk-eryns" data-mk-metric="eryns"><?php echo panicMarketNumber($metrics['eryns'] ?? ''); ?></b></dd></div>
      <div class="mk-stat"><dt>Objetos en venta</dt><dd><b class="pui-num" data-mk-count>—</b></dd></div>
    </dl>
    <div class="pui-wrap mk-tape" aria-label="Últimas ventas">
      <span class="mk-tape-lbl">Últimas ventas</span>
      <div class="mk-tape-win"><div class="mk-tape-run" data-mk-tape><span class="mk-tape-empty">Todavía no hay ventas.</span></div></div>
    </div>
  </section>

  <!-- Objetos en venta -->
  <section class="pui-band pui-band--bone mk-shop" id="publicaciones" aria-labelledby="mk-shop-t">
    <div class="pui-wrap">
      <header class="mk-head">
        <div><p class="pui-kicker">La vidriera de Loren Market</p><h2 class="pui-h2" id="mk-shop-t"><?php echo $mkReveal(['Objetos','en venta']); ?></h2></div>
        <p class="pui-lead">Lo que publican los jugadores en el juego aparece acá. Pasá el mouse o tocá una tarjeta para ver la ficha completa.</p>
      </header>
      <div class="mk-store">
        <span class="mk-store-tab">Mercado de Vessara</span>
      <div class="mk-featrow" data-listings-featured-wrap>
        <div class="mk-featrow-head"><h3>Destacados</h3><p>Van primero en la web y se ofrecen a los gritos por Loren Market.</p></div>
        <div class="mk-marquee" data-listings-featured tabindex="0" role="region" aria-label="Publicaciones destacadas"><div class="mk-marquee-run" data-mk-feat-run></div></div>
        <p class="mk-feat-none" data-mk-feat-none hidden>Todavía no hay publicaciones destacadas.</p>
      </div>
      <div class="mk-board">
        <div class="mk-controls">
          <div class="pui-tabs mk-tabs" role="group" aria-label="Moneda">
            <button type="button" class="pui-tab is-active" data-listings-currency="all" aria-pressed="true">Todas</button>
            <button type="button" class="pui-tab" data-listings-currency="1" aria-pressed="false">Eryns</button>
            <button type="button" class="pui-tab" data-listings-currency="0" aria-pressed="false">Zen</button>
          </div>
          <label class="mk-search"><span class="pui-sr">Buscar objeto o vendedor</span><input class="pui-input" type="search" maxlength="60" data-listings-search placeholder="Buscar objeto o vendedor"></label>
          <label class="mk-sort"><span class="pui-sr">Orden</span><select class="pui-input" data-listings-sort><option value="recent">Más recientes</option><option value="price-asc">Menor precio</option><option value="price-desc">Mayor precio</option></select></label>
        </div>
        <div class="mk-gridwrap">
          <div class="mk-grid" data-listings-grid></div>
          <p class="mk-state" data-listings-state role="status">Consultando las publicaciones del juego…</p>
        </div>
        <div class="mk-pages" data-listings-pagination>
          <button type="button" class="pui-btn pui-btn--dark pui-btn--sm" data-listings-prev>Anterior</button>
          <span data-listings-page>Página 1</span>
          <button type="button" class="pui-btn pui-btn--dark pui-btn--sm" data-listings-next>Siguiente</button>
        </div>
      </div>
      </div>
    </div>
  </section>

  <!-- Así se sella un trato -->
  <section class="mk-deal" id="trato" aria-labelledby="mk-deal-t">
    <div class="pui-wrap">
      <header class="mk-head">
        <div><p class="pui-kicker">Paso a paso</p><h2 class="pui-h2" id="mk-deal-t"><?php echo $mkReveal(['Así se sella','un trato']); ?></h2></div>
        <p class="pui-lead">Nadie entrega nada a ciegas. Vessara tiene el objeto hasta que alguien paga.</p>
      </header>
      <div class="mk-deal-grid">
        <ol class="mk-steps" data-mk-steps>
          <?php foreach($mkSteps as $i => $s) { ?>
          <li<?php echo $i ? '' : ' class="on"'; ?>><button type="button"><span class="mk-step-ic" aria-hidden="true"><img src="<?php echo $mk($mkImg.'mercado/'.$mkIcons[$i].'.webp'); ?>" alt="" width="240" height="240" loading="lazy"></span><span class="mk-step-n">0<?php echo $i + 1; ?></span><span class="mk-step-tx"><b><?php echo $mk($s[0]); ?></b><span class="t"><?php echo $mk($s[1]); ?></span></span><i class="pg"></i></button></li>
          <?php } ?>
        </ol>
        <div class="mk-stage" data-mk-stage data-step="1" aria-hidden="true">
          <div class="mk-stage-card">
            <div class="mk-stage-media"><img src="<?php echo $mk($mkImg.'atlas/drops/jewel-chaos-809384516512.webp'); ?>" alt="" width="512" height="512" loading="lazy"></div>
            <b>Jewel of Chaos</b>
            <span class="pr"><img src="<?php echo $mk($mkImg.'votar/votar-gema-64-4559844ddcec.webp'); ?>" alt="" width="20" height="20">1.000 Eryns</span>
          </div>
          <span class="mk-stage-lock"><img src="<?php echo $mk($mkImg.'mercado/paso-guardado-bcca7e281ad1.webp'); ?>" alt="" width="240" height="240" loading="lazy"></span>
          <div class="mk-stage-in"><img src="<?php echo $mk($mkImg.'mercado/paso-publicar-0dad5f0a3391.webp'); ?>" alt="" width="240" height="240" loading="lazy"><b>Loren Market</b><small>Publicado por 7 días</small></div>
          <div class="mk-stage-views"><img src="<?php echo $mk($mkImg.'mercado/paso-todos-6c48dc8a90ad.webp'); ?>" alt="" width="240" height="240" loading="lazy"><b data-mk-views>0</b><small>jugadores la vieron</small></div>
          <span class="mk-seal-mark"></span>
          <span class="mk-seal"><img src="<?php echo $mk($mkImg.'mercado/mercado-sello-7cb6746c9c13.webp'); ?>" alt="" width="524" height="700" loading="lazy"></span>
          <div class="mk-stage-pay"><div><small>Precio</small><b>1.000</b></div><div><small>Tributo 5%</small><b>−50</b></div><div class="net"><small>Cobrás</small><b><img src="<?php echo $mk($mkImg.'votar/votar-gema-64-4559844ddcec.webp'); ?>" alt="" width="20" height="20">950</b></div></div>
          <p class="mk-stage-cap" data-mk-cap>Publicás tu Jewel of Chaos en Loren Market.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Registro del mercado -->
  <section class="mk-log" id="tratos" aria-labelledby="mk-log-t">
    <div class="pui-wrap">
      <header class="mk-head">
        <div><p class="pui-kicker">El registro del mercado</p><h2 class="pui-h2" id="mk-log-t"><?php echo $mkReveal(['Últimas','ventas']); ?></h2></div>
        <div class="pui-tabs mk-tabs" role="group" aria-label="Moneda de las ventas">
          <button type="button" class="pui-tab is-active" data-mk-cur="all" aria-pressed="true">Todas</button>
          <button type="button" class="pui-tab" data-mk-cur="1" aria-pressed="false">Eryns</button>
          <button type="button" class="pui-tab" data-mk-cur="0" aria-pressed="false">Zen</button>
        </div>
      </header>
      <div class="mk-log-grid">
        <div class="mk-ledger">
          <div class="mk-ledger-head" aria-hidden="true"><span>Objeto</span><span>Comprador</span><span>Vendedor</span><span>Precio</span><span>Fecha</span></div>
          <ol class="mk-ledger-rows" data-mk-rows></ol>
          <p class="mk-state" data-mk-empty hidden></p>
          <div class="mk-pages mk-log-pages" data-mk-pager><button type="button" class="pui-btn pui-btn--ghost pui-btn--sm" data-mk-prev>Anterior</button><span data-mk-page>Página 1 de 1</span><button type="button" class="pui-btn pui-btn--ghost pui-btn--sm" data-mk-next>Siguiente</button></div>
        </div>
        <aside class="mk-records" aria-labelledby="mk-rec-t">
          <h3 class="mk-sub" id="mk-rec-t">Récords de hoy</h3>
          <div class="mk-rec-list" data-mk-records></div>
        </aside>
      </div>
      <div class="mk-week">
        <div class="mk-week-head"><h3 class="mk-sub">Últimos 7 días</h3><span>Ventas por día</span></div>
        <div class="mk-bars" data-mk-days></div>
      </div>
      <p class="mk-total"><b class="pui-num" data-mk-metric="total"><?php echo panicMarketNumber($metrics['total'] ?? ''); ?></b> tratos sellados en total</p>
    </div>
  </section>

  <!-- La garantía de Vessara -->
  <section class="pui-band pui-band--bone mk-rules" aria-labelledby="mk-rules-t">
    <div class="pui-wrap">
      <header class="mk-head">
        <div><p class="pui-kicker">La garantía de Vessara</p><h2 class="pui-h2" id="mk-rules-t"><?php echo $mkReveal(['Bajo su sello,','ningún trato se rompe']); ?></h2></div>
        <p class="pui-lead">Vessara custodia tu objeto desde Loren Market hasta que se vende, y garantiza que las dos partes reciban lo acordado.</p>
      </header>
      <dl class="mk-rule-grid">
        <div class="mk-rule"><dt>Gratis</dt><dd><b>Publicar</b>Y si cambiás de idea, retirás tu objeto sin costo.</dd></div>
        <div class="mk-rule"><dt>5%</dt><dd><b>Tributo al vender</b>Las publicaciones destacadas pagan 8% en total, y solo cuando se venden.</dd></div>
        <div class="mk-rule"><dt>3<small>/</small>10</dt><dd><b>Publicaciones a la vez</b>3 con cuenta Free y 10 con VIP, entre Zen y Eryns.</dd></div>
        <div class="mk-rule"><dt>7<small>días</small></dt><dd><b>En la vidriera</b>Cada publicación dura una semana.</dd></div>
      </dl>
    </div>
  </section>

  <section class="mk-close" aria-labelledby="mk-end-t">
    <span class="mk-close-word" aria-hidden="true">LOREN</span>
    <div class="pui-wrap">
      <p class="pui-kicker">Encontrá a Vessara en Loren Market</p>
      <h2 class="pui-h1" id="mk-end-t">Lo que cazás<br>tiene precio.</h2>
      <div class="mk-actions">
        <span class="pui-btn-wrap"><a class="pui-btn pui-btn--lg" href="<?php echo $mk($mkBase.'register/'); ?>">Crear cuenta</a></span>
        <a class="pui-btn pui-btn--ghost" href="<?php echo $mk($mkBase.'downloads/'); ?>">Descargar</a>
      </div>
    </div>
  </section>
</div>
<script>window.MK_CUR={zen:<?php echo json_encode(__PATH_TEMPLATE__."img/mercado/moneda-zen-f296546732d4.webp"); ?>,eryns:<?php echo json_encode(__PATH_TEMPLATE__."img/votar/votar-gema-64-4559844ddcec.webp"); ?>};</script>
<script type="application/json" id="vessara-initial"><?php echo json_encode($market, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE); ?></script>
