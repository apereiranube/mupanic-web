<?php
/**
 * MU PANIC · Socios
 * Programa de socios: cada jugador cobra el 5% en Eryns de lo que recargan sus invitados
 * durante 3 meses; los creadores aprobados cobran el 10% en pesos, una vez por mes.
 * Página pública de venta del programa. El link propio vive en Mi cuenta → Referidos.
 */
if(!defined('access') || !access) die();
if(($_REQUEST['subpage'] ?? '')==='creadores') { include __DIR__.'/socios-creadores.php'; return; }
$socImg = __PATH_TEMPLATE__.'img/';
$socLogged = function_exists('isLoggedIn') && isLoggedIn();
$socLink = __BASE_URL__.($socLogged ? 'usercp/referidos/' : 'register/');
$socLinkLabel = $socLogged ? 'Sacá tu link' : 'Creá tu cuenta y sacá tu link';
$socCommunity = require __DIR__.'/community-config.php';
$socDiscord = (string)($socCommunity['invite'] ?? 'https://discord.gg/fP4Mxcsee');
$socHero = $socImg.'socios/socios-hero.webp';
if(!is_file(__DIR__.'/../img/socios/socios-hero.webp')) $socHero = $socImg.'server/v3/srv-08-tesoro-5b82e4129d10.webp';
$socVipPrecio = 25000; // Eryns por 30 días de VIP (CustomBuyVip.txt)
function socE($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
require_once __DIR__.'/referidos-cola.php';
$socRank=[];
try { if(class_exists('Connection')) $socRank=panicSociosRanking(); } catch(Throwable $e) { $socRank=[]; }
?>
<div class="soc-page" data-vip="<?php echo (int)$socVipPrecio; ?>">

  <!-- 1 · Portada -->
  <section class="soc-hero">
    <div class="soc-hero-art" aria-hidden="true"><img src="<?php echo socE($socHero); ?>" alt="" width="1920" height="880" decoding="async" fetchpriority="high"></div>
    <div class="soc-hero-copy">
      <p class="soc-eyebrow">Programa de socios · MU PANIC</p>
      <h1>Jugá. Invitá.<br><em>Ganá.</em></h1>
      <p class="soc-lead">Cada amigo que entra con tu link te deja el <strong>5% de todo lo que recargue</strong>, en Eryns, durante 3 meses. Sin tope de invitados.</p>
      <div class="soc-actions">
        <a class="soc-btn soc-btn-main" href="<?php echo socE($socLink); ?>"><?php echo socE($socLinkLabel); ?></a>
        <a class="soc-btn soc-btn-ghost" href="<?php echo socE(__BASE_URL__.'socios/creadores/'); ?>">Soy streamer o creador →</a>
      </div>
    </div>
    <ul class="soc-strip" aria-label="El programa en números">
      <li><b>5%</b><span>de cada recarga de tus invitados</span></li>
      <li><b>3 meses</b><span>cobrando por cada invitado</span></li>
      <li><b>+7 días</b><span>de VIP por cada invitado activo</span></li>
      <li><b>10%</b><span>en plata para creadores</span></li>
    </ul>
  </section>

  <!-- 2 · Cómo funciona -->
  <section class="soc-section soc-how" id="como-funciona">
    <header class="soc-head"><p class="soc-eyebrow">Cómo funciona</p><h2>Tres pasos y cobrás solo</h2></header>
    <ol class="soc-steps">
      <li><span class="soc-num"><i>1</i></span><h3>Copiá tu link</h3><p>Está en <strong>Mi cuenta → Referidos</strong>. Compartilo en WhatsApp, Discord, grupos de Facebook o en tu stream.</p></li>
      <li><span class="soc-num"><i>2</i></span><h3>Tu amigo se suma</h3><p>Crea su cuenta con tu link y arranca con <strong>3 días de VIP de regalo</strong>. Queda vinculado a vos.</p></li>
      <li><span class="soc-num"><i>3</i></span><h3>Vos cobrás</h3><p>Cada vez que recarga Eryns, el <strong>5% te llega a tu cuenta</strong>. Y cuando llega al reset 2, sumás <strong>7 días de VIP</strong>.</p></li>
    </ol>
  </section>

  <!-- 3 · Calculadora -->
  <section class="soc-section soc-calc" id="calculadora" data-soc-calc>
    <div class="soc-calc-box">
      <header class="soc-head soc-head-left"><p class="soc-eyebrow">Calculadora</p><h2>¿Cuánto podés ganar?</h2><p>Mové los valores y mirá lo que te dejan tus invitados en 3 meses.</p></header>
      <div class="soc-calc-grid">
        <div class="soc-calc-inputs">
          <label class="soc-range"><span>Amigos que invitás <output data-out="amigos">10</output></span>
            <input type="range" min="1" max="100" step="1" value="10" data-in="amigos"></label>
          <label class="soc-range"><span>Lo que recarga cada uno por mes <output data-out="recarga">$5.000</output></span>
            <input type="range" min="1000" max="30000" step="500" value="5000" data-in="recarga"></label>
          <div class="soc-toggle" role="radiogroup" aria-label="Tipo de socio">
            <button type="button" role="radio" aria-checked="true" data-tipo="socio" class="is-on">Socio · 5% en Eryns</button>
            <button type="button" role="radio" aria-checked="false" data-tipo="creador">Creador · 10% en plata</button>
          </div>
        </div>
        <div class="soc-calc-result" aria-live="polite">
          <small data-out="titulo">Ganás en 3 meses</small>
          <strong data-out="total">7.500 Eryns</strong>
          <p data-out="equiv">Te alcanza para 0,3 meses de VIP, o para jugar, comprar en la tienda y comerciar en Vessara.</p>
          <span class="soc-calc-note">Estimación. Cobrás sobre lo que tus invitados recargan de verdad.</span>
        </div>
      </div>
    </div>
  </section>

  <!-- 4 · Niveles -->
  <section class="soc-section soc-tiers" id="creadores">
    <header class="soc-head"><p class="soc-eyebrow">Dos niveles</p><h2>Elegí cómo jugás el negocio</h2></header>
    <div class="soc-tier-grid">
      <article class="soc-tier">
        <p class="soc-tier-tag">Para todos</p>
        <h3>Socio</h3>
        <p class="soc-tier-big">5% <small>en Eryns</small></p>
        <ul>
          <li>Tu link propio desde el primer día</li>
          <li>5% de cada recarga de tus invitados, durante 3 meses</li>
          <li>7 días de VIP por cada invitado que llega al reset 2</li>
          <li>Tu amigo arranca con 3 días de VIP</li>
          <li>Ranking mensual de socios con premio</li>
        </ul>
        <a class="soc-btn soc-btn-main" href="<?php echo socE($socLink); ?>"><?php echo socE($socLinkLabel); ?></a>
      </article>
      <article class="soc-tier soc-tier-pro">
        <p class="soc-tier-tag">Streamers · YouTubers · TikTokers</p>
        <h3>Creador</h3>
        <p class="soc-tier-big">10% <small>en plata</small></p>
        <ul>
          <li>10% de las recargas de tus invitados, por transferencia, una vez por mes</li>
          <li>VIP para sortear en vivo entre tu chat</li>
          <li>Título exclusivo de creador dentro del juego</li>
          <li>Torneo de Arena con tu nombre, con apuestas del público</li>
          <li>Destacado en la web y en el Discord</li>
        </ul>
        <p class="soc-tier-req"><b>Requisito:</b> streams o videos de MU PANIC con el nombre del server en el título y tu link en la descripción.</p>
        <a class="soc-btn soc-btn-pro" href="<?php echo socE(__BASE_URL__.'socios/creadores/'); ?>">Postulate como creador →</a>
      </article>
    </div>
  </section>

  <!-- 5 · Ranking -->
  <section class="soc-section soc-rank" id="ranking">
    <header class="soc-head"><p class="soc-eyebrow">Ranking del mes</p><h2>Los socios que más mueven</h2><p>El primero de cada mes gana un premio exclusivo que se ve en el juego.</p></header>
    <?php if(!$socRank) { ?>
    <div class="soc-rank-empty">
      <span class="soc-rank-crown" aria-hidden="true">♛</span>
      <p><strong>Todavía nadie entró al ranking de este mes.</strong><br>Sacá tu link ahora y largá primero.</p>
    </div>
    <?php } else { ?>
    <ol class="soc-rank-list">
      <?php foreach($socRank as $i=>$r) { ?>
      <li class="<?php echo $i===0?'is-first':''; ?>"><span class="soc-rank-pos"><?php echo $i===0?'♛':($i+1); ?></span><strong><?php echo socE($r['Personaje'] ?: 'Socio'); ?></strong><b><?php echo (int)$r['Activos']; ?> <small><?php echo (int)$r['Activos']===1?'invitado activo':'invitados activos'; ?></small></b></li>
      <?php } ?>
    </ol>
    <p class="soc-rank-note">Cuentan los invitados de este mes que ya llegaron al reset 2 y jugaron 3 días. Se actualiza cada minuto.</p>
    <?php } ?>
  </section>

  <!-- 6 · Reglas -->
  <section class="soc-section soc-rules" id="reglas">
    <header class="soc-head"><p class="soc-eyebrow">Reglas claras</p><h2>Juego limpio para todos</h2></header>
    <div class="soc-rules-grid">
      <div><h3>¿Sobre qué cobro?</h3><p>Sobre las recargas de Eryns aprobadas de cada invitado, durante los 3 meses siguientes a que crea su cuenta.</p></div>
      <div><h3>¿Cuándo me llega?</h3><p>Socios: los Eryns se acreditan solos al aprobarse cada recarga (si estás jugando, al salir del juego). Creadores: se liquida una vez por mes, por transferencia.</p></div>
      <div><h3>¿Hay límite?</h3><p>No hay tope de invitados para la comisión. Los días de VIP por invitado activo tienen un máximo de 5 por mes.</p></div>
      <div><h3>¿Qué no vale?</h3><p>Invitarte a vos mismo: las cuentas de la misma PC o la misma conexión no cuentan. Ante una trampa, la comisión se anula.</p></div>
    </div>
  </section>

  <!-- 7 · Cierre -->
  <section class="soc-final">
    <h2>Tu próximo amigo<br><em>ya te está pagando el VIP.</em></h2>
    <a class="soc-btn soc-btn-main" href="<?php echo socE($socLink); ?>"><?php echo socE($socLinkLabel); ?></a>
  </section>
</div>
