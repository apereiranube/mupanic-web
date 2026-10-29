<?php
/**
 * MU PANIC · Socios (estilo nuevo, color propio: verde esmeralda; protagonista: Muse Elf).
 * Programa de socios: cada jugador cobra el 5% en Eryns de lo que recargan sus invitados
 * durante 3 meses; los creadores aprobados cobran el 10% en pesos, una vez por mes (acento magenta).
 * Página pública de venta del programa. El link propio vive en Mi cuenta → Referidos.
 * Arte en inc/socios-art.php: sin archivo, cada lugar queda limpio. Animaciones en js/socios.js.
 */
if(!defined('access') || !access) die();
if(($_REQUEST['subpage'] ?? '')==='creadores') { include __DIR__.'/socios-creadores.php'; return; }
require_once __DIR__.'/socios-art.php';
$socLogged = function_exists('isLoggedIn') && isLoggedIn();
$socLink = __BASE_URL__.($socLogged ? 'usercp/referidos/' : 'register/');
$socLinkLabel = $socLogged ? 'Sacá tu link' : 'Creá tu cuenta y sacá tu link';
$socCommunity = require __DIR__.'/community-config.php';
$socDiscord = (string)($socCommunity['invite'] ?? 'https://discord.gg/fP4Mxcsee');
$socVipPrecio = 25000; // Eryns por 30 días de VIP (CustomBuyVip.txt)
function socE($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
require_once __DIR__.'/referidos-cola.php';
$socRank=[];
try { if(class_exists('Connection')) $socRank=panicSociosRanking(); } catch(Throwable $e) { $socRank=[]; }
$socArt = [
    'elfa' => panicSociosArt('socios-elfa'),
    'fondo' => panicSociosArt('socios-fondo'),
    'banda' => panicSociosArt('socios-fondo-banda'),
    'socio' => panicSociosArt('socios-emblema-socio'),
    'creador' => panicSociosArt('socios-emblema-creador'),
    'mes' => panicSociosArt('socios-emblema-mes'),
    'cierre' => panicSociosArt('socios-cierre'),
];
$socGema = panicSociosGema();
$socElfaSize = panicSociosArtSize('socios-elfa', 2000, 1600);
$socBg = function($src) { return $src ? ' style="background-image:url(\''.socE($src).'\')"' : ''; };
$socGemaImg = function($cls='') use ($socGema) { return $socGema ? '<img class="soc-gem '.$cls.'" src="'.socE($socGema).'" alt="" width="64" height="64" decoding="async">' : ''; };
$socPasos = [
    ['link', 'Copiá tu link', 'Está en <strong>Mi cuenta → Referidos</strong>. Compartilo en WhatsApp, Discord, grupos de Facebook o en tu stream.'],
    ['amigo', 'Tu amigo se suma', 'Crea su cuenta con tu link y arranca con <strong>3 días de VIP de regalo</strong>. Queda vinculado a vos.'],
    ['cobro', 'Vos cobrás', 'Cada vez que recarga Eryns, el <strong>5% te llega a tu cuenta</strong>. Y cuando llega al reset 2, sumás <strong>7 días de VIP</strong>.'],
];
$socReglas = [
    ['¿Sobre qué cobro?', 'Sobre las recargas de Eryns aprobadas de cada invitado, durante los 3 meses siguientes a que crea su cuenta.'],
    ['¿Cuándo me llega?', 'Socios: los Eryns se acreditan solos al aprobarse cada recarga (si estás jugando, al salir del juego). Creadores: se liquida una vez por mes, por transferencia.'],
    ['¿Hay límite?', 'No hay tope de invitados para la comisión. Los días de VIP por invitado activo tienen un máximo de 5 por mes.'],
    ['¿Qué no vale?', 'Invitarte a vos mismo: las cuentas de la misma PC o la misma conexión no cuentan. Ante una trampa, la comisión se anula.'],
];
?>
<div id="soc-page" class="soc pui" data-vip="<?php echo (int)$socVipPrecio; ?>">

  <!-- 1 · Portada: bloque verde en diagonal con la Elfa -->
  <section class="soc-hero" aria-labelledby="soc-title">
    <div class="soc-hero-art" aria-hidden="true">
      <div class="soc-hero-block"><?php if($socArt['fondo']) { ?><i class="soc-hero-fondo" data-soc-parallax<?php echo $socBg($socArt['fondo']); ?>></i><?php } ?></div>
      <?php if($socArt['elfa']) { ?><div class="soc-hero-fig" data-soc-fig style="--fw:<?php echo $socElfaSize[0]; ?>;--fh:<?php echo $socElfaSize[1]; ?>"><img src="<?php echo socE($socArt['elfa']); ?>" alt="" width="<?php echo $socElfaSize[0]; ?>" height="<?php echo $socElfaSize[1]; ?>" decoding="async" fetchpriority="high"></div><?php } ?>
    </div>
    <div class="pui-wrap soc-hero-copy">
      <p class="pui-kicker">Programa de socios · MU PANIC</p>
      <h1 class="pui-h1 soc-hero-title" id="soc-title">Jugá. Invitá. <em>Ganá.</em></h1>
      <p class="soc-lead">Cada amigo que entra con tu link te deja el <strong>5% de todo lo que recargue</strong>, en Eryns, durante 3 meses. Sin tope de invitados.</p>
      <div class="soc-actions">
        <a class="pui-btn pui-btn--lg soc-btn soc-punch" href="<?php echo socE($socLink); ?>"><?php echo socE($socLinkLabel); ?></a>
        <a class="soc-link soc-link--pro" href="<?php echo socE(__BASE_URL__.'socios/creadores/'); ?>">Soy streamer o creador <span aria-hidden="true">→</span></a>
      </div>
    </div>
  </section>

  <!-- Cifras: cuentan una vez al aparecer -->
  <section class="soc-strip-band" aria-label="El programa en números">
    <ul class="pui-wrap soc-strip" data-soc-count>
      <li class="is-lead"><b><span data-to="5">5</span>%<?php echo $socGemaImg('soc-gem--strip'); ?></b><span>de cada recarga de tus invitados</span></li>
      <li><b><span data-to="3">3</span> meses</b><span>cobrando por cada invitado</span></li>
      <li><b>+<span data-to="7">7</span> días</b><span>de VIP por cada invitado activo</span></li>
      <li class="is-pro"><b><span data-to="10">10</span>%</b><span>en plata para creadores</span></li>
    </ul>
  </section>

  <!-- 2 · Cómo funciona -->
  <section class="soc-band soc-how<?php echo $socArt['banda'] ? ' has-bg' : ''; ?>" id="como-funciona" aria-labelledby="soc-how-title"<?php echo $socBg($socArt['banda']); ?>>
    <div class="pui-wrap">
      <div class="soc-head">
        <div>
          <p class="pui-kicker">Cómo funciona</p>
          <h2 class="pui-h2" id="soc-how-title"><span class="pui-reveal">Tres pasos y cobrás solo</span></h2>
        </div>
      </div>
      <div class="soc-steps-wrap" data-soc-steps>
        <div class="soc-steps-bar" aria-hidden="true"><i></i></div>
        <ol class="soc-steps">
          <?php foreach($socPasos as $i => $p) { $ico = panicSociosArt('socios-paso-'.$p[0]); ?>
          <li class="soc-step" style="--i:<?php echo $i; ?>">
            <span class="soc-step-ico<?php echo $ico ? ' has-art' : ''; ?>" aria-hidden="true"><?php if($ico) { ?><img src="<?php echo socE($ico); ?>" alt="" width="96" height="96" loading="lazy" decoding="async"><?php } else { echo $i + 1; } ?></span>
            <p class="soc-step-n">Paso <?php echo $i + 1; ?></p>
            <h3><?php echo socE($p[1]); ?></h3>
            <p><?php echo $p[2]; ?></p>
          </li>
          <?php } ?>
        </ol>
      </div>
    </div>
  </section>

  <!-- 3 · Calculadora (banda hueso) -->
  <section class="soc-band soc-band--bone soc-calc" id="calculadora" aria-labelledby="soc-calc-title" data-soc-calc>
    <div class="pui-wrap">
      <div class="soc-head">
        <div>
          <p class="pui-kicker">Calculadora</p>
          <h2 class="pui-h2" id="soc-calc-title"><span class="pui-reveal">¿Cuánto podés ganar?</span></h2>
        </div>
        <p class="soc-sub">Mové los valores y mirá lo que te dejan tus invitados en 3 meses.</p>
      </div>
      <div class="soc-calc-grid">
        <div class="soc-calc-inputs">
          <label class="soc-range"><span>Amigos que invitás <output data-out="amigos">10</output></span>
            <input type="range" min="1" max="100" step="1" value="10" data-in="amigos"></label>
          <label class="soc-range"><span>Lo que recarga cada uno por mes <output data-out="recarga">$5.000</output></span>
            <input type="range" min="1000" max="30000" step="500" value="5000" data-in="recarga"></label>
          <div class="soc-toggle" role="radiogroup" aria-label="Tipo de socio">
            <i class="soc-toggle-ind" aria-hidden="true"></i>
            <button type="button" role="radio" aria-checked="true" data-tipo="socio" class="is-on">Socio · 5% en Eryns</button>
            <button type="button" role="radio" aria-checked="false" data-tipo="creador">Creador · 10% en plata</button>
          </div>
        </div>
        <div class="soc-calc-result" aria-live="polite">
          <small data-out="titulo">Ganás en 3 meses</small>
          <div class="soc-calc-total">
            <span class="soc-calc-gem" aria-hidden="true"><?php echo $socGemaImg(); ?></span>
            <strong data-out="total">7.500</strong>
          </div>
          <span class="soc-calc-unit" data-out="unit">Eryns</span>
          <p data-out="equiv">Te alcanza para 0,3 meses de VIP, o para jugar, comprar en la tienda y comerciar en Vessara.</p>
          <span class="soc-calc-note">Estimación. Cobrás sobre lo que tus invitados recargan de verdad.</span>
        </div>
      </div>
    </div>
  </section>

  <!-- 4 · Niveles -->
  <section class="soc-band soc-tiers" id="creadores" aria-labelledby="soc-tiers-title">
    <div class="pui-wrap">
      <div class="soc-head">
        <div>
          <p class="pui-kicker">Dos niveles</p>
          <h2 class="pui-h2" id="soc-tiers-title"><span class="pui-reveal">Elegí cómo jugás el negocio</span></h2>
        </div>
      </div>
      <div class="soc-tier-grid">
        <article class="soc-tier soc-tier--socio<?php echo $socArt['socio'] ? ' has-art' : ''; ?>">
          <?php if($socArt['socio']) { ?><span class="soc-tier-emb" aria-hidden="true"><img src="<?php echo socE($socArt['socio']); ?>" alt="" loading="lazy" decoding="async"></span><?php } ?>
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
          <a class="pui-btn pui-btn--dark soc-punch" href="<?php echo socE($socLink); ?>"><?php echo socE($socLinkLabel); ?></a>
        </article>
        <article class="soc-tier soc-tier--pro<?php echo $socArt['creador'] ? ' has-art' : ''; ?>">
          <?php if($socArt['creador']) { ?><span class="soc-tier-emb" aria-hidden="true"><img src="<?php echo socE($socArt['creador']); ?>" alt="" loading="lazy" decoding="async"></span><?php } ?>
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
          <a class="pui-btn soc-btn-pro soc-punch" href="<?php echo socE(__BASE_URL__.'socios/creadores/'); ?>">Postulate como creador <span aria-hidden="true">→</span></a>
        </article>
      </div>
    </div>
  </section>

  <!-- 5 · Ranking (banda hueso) -->
  <section class="soc-band soc-band--bone soc-rank" id="ranking" aria-labelledby="soc-rank-title">
    <div class="pui-wrap">
      <div class="soc-head">
        <div>
          <p class="pui-kicker">Ranking del mes</p>
          <h2 class="pui-h2" id="soc-rank-title"><span class="pui-reveal">Los socios que más mueven</span></h2>
        </div>
        <p class="soc-sub">El primero de cada mes gana un premio exclusivo que se ve en el juego.</p>
      </div>
      <?php if(!$socRank) { ?>
      <div class="soc-rank-empty" data-soc-rank>
        <span class="soc-rank-mes<?php echo $socArt['mes'] ? ' has-art' : ''; ?>" aria-hidden="true"><?php if($socArt['mes']) { ?><img src="<?php echo socE($socArt['mes']); ?>" alt="" loading="lazy" decoding="async"><?php } else { ?>1<?php } ?></span>
        <p><strong>Todavía nadie entró al ranking de este mes.</strong><br>Sacá tu link ahora y largá primero.</p>
        <a class="pui-btn pui-btn--dark soc-punch" href="<?php echo socE($socLink); ?>"><?php echo socE($socLinkLabel); ?></a>
      </div>
      <?php } else { ?>
      <ol class="soc-rank-list" data-soc-rank>
        <?php foreach($socRank as $i=>$r) { $act=(int)$r['Activos']; ?>
        <li class="<?php echo $i===0?'is-first':''; ?>" style="--i:<?php echo $i; ?>">
          <span class="soc-rank-pos"><?php if($i===0) { ?><span class="soc-rank-mes<?php echo $socArt['mes'] ? ' has-art' : ''; ?>"><?php if($socArt['mes']) { ?><img src="<?php echo socE($socArt['mes']); ?>" alt="Puesto 1" loading="lazy" decoding="async"><?php } else { ?>1<?php } ?></span><?php } else { echo $i+1; } ?></span>
          <strong><?php echo socE($r['Personaje'] ?: 'Socio'); ?></strong>
          <b><?php echo $act; ?> <small><?php echo $act===1?'invitado activo':'invitados activos'; ?></small></b>
        </li>
        <?php } ?>
      </ol>
      <p class="soc-rank-note">Cuentan los invitados de este mes que ya llegaron al reset 2 y jugaron 3 días.</p>
      <?php } ?>
    </div>
  </section>

  <!-- 6 · Reglas: damero verde/hueso -->
  <section class="soc-band soc-rules<?php echo $socArt['banda'] ? ' has-bg' : ''; ?>" id="reglas" aria-labelledby="soc-rules-title"<?php echo $socBg($socArt['banda']); ?>>
    <div class="pui-wrap">
      <div class="soc-head">
        <div>
          <p class="pui-kicker">Reglas claras</p>
          <h2 class="pui-h2" id="soc-rules-title"><span class="pui-reveal">Juego limpio para todos</span></h2>
        </div>
      </div>
      <ul class="soc-rules-grid" data-soc-reveal>
        <?php foreach($socReglas as $i => $q) { ?>
        <li style="--i:<?php echo $i; ?>"><span class="soc-rule-n" aria-hidden="true"><?php echo sprintf('%02d', $i + 1); ?></span><div><h3><?php echo socE($q[0]); ?></h3><p><?php echo socE($q[1]); ?></p></div></li>
        <?php } ?>
      </ul>
    </div>
  </section>

  <!-- 7 · Cierre -->
  <section class="soc-end<?php echo $socArt['cierre'] ? ' has-art' : ''; ?>" aria-labelledby="soc-end-title">
    <div class="soc-end-bg" aria-hidden="true"><?php if($socArt['cierre']) { ?><i<?php echo $socBg($socArt['cierre']); ?>></i><?php } ?></div>
    <div class="pui-wrap soc-end-copy">
      <p class="pui-kicker">Tu turno</p>
      <h2 class="pui-h2" id="soc-end-title"><span class="pui-reveal">Tu próximo amigo</span> <em>ya te está pagando el VIP.</em></h2>
      <div class="soc-actions">
        <a class="pui-btn pui-btn--lg soc-btn soc-punch" href="<?php echo socE($socLink); ?>"><?php echo socE($socLinkLabel); ?></a>
        <a class="soc-link" href="<?php echo socE($socDiscord); ?>" target="_blank" rel="noopener noreferrer">Dudas en el Discord ↗</a>
      </div>
    </div>
  </section>
</div>
