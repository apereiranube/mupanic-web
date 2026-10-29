<?php
/**
 * MU PANIC · Socios → Creadores: postulación de streamers (estilo nuevo, acento magenta).
 * 1) Formulario (con sesión).  2) Código para confirmar en Discord (botón "Postularme" en #creadores o /creador).
 * 3) El staff aprueba en Discord; el bot da el rol y manda el link; el VPS lo da de alta al 10%.
 */
if(!defined('access') || !access) die();
require_once __DIR__.'/creadores-cola.php';
require_once __DIR__.'/socios-art.php';
$crEscena = panicSociosArt('socios-creadores-escena');
$crBanda = panicSociosArt('socios-fondo-banda');
$crCommunity = require __DIR__.'/community-config.php';
$crDiscord = (string)($crCommunity['invite'] ?? 'https://discord.gg/fP4Mxcsee');
$crLogged = function_exists('isLoggedIn') && isLoggedIn();
$crAccount = $crLogged ? (string)($_SESSION['username'] ?? '') : '';
if(!preg_match('/^[A-Za-z0-9_]{1,10}$/D',$crAccount)) $crLogged=false;
$crMsg=null; $crTone='info'; $crApp=null; $crError=false;
if($crLogged && !is_string($_SESSION['cre_csrf'] ?? null)) $_SESSION['cre_csrf']=bin2hex(random_bytes(32));
if($crLogged && ($_SERVER['REQUEST_METHOD'] ?? '')==='POST' && ($_POST['cre_action'] ?? '')==='postular') {
    if(!is_string($_POST['cre_csrf'] ?? null) || !hash_equals($_SESSION['cre_csrf'],$_POST['cre_csrf'])) { $crMsg='La sesión venció. Recargá la página e intentá de nuevo.'; $crTone='error'; }
    else {
        try { (new PanicCreadores())->apply($crAccount,$_POST); $crMsg='¡Listo! Ahora confirmá tu postulación en Discord con el código de abajo.'; $crTone='ok'; }
        catch(InvalidArgumentException $e) { $crMsg=$e->getMessage(); $crTone='error'; }
        catch(RuntimeException $e) { $crMsg=strpos($e->getMessage(),'muchas')!==false?$e->getMessage():'No pudimos guardar tu postulación ahora. Probá en unos minutos.'; $crTone='error'; }
        catch(Throwable $e) { $crMsg='No pudimos guardar tu postulación ahora. Probá en unos minutos.'; $crTone='error'; }
    }
}
if($crLogged) { try { $crApp=(new PanicCreadores())->mine($crAccount); } catch(Throwable $e) { $crError=true; } }
$crState = $crApp['state'] ?? '';
$crShowForm = $crLogged && !$crError && (!$crApp || $crState==='rechazado');
function crE($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$crPasos = [
    ['Completá el formulario', 'Tu canal, en qué plataforma y cuánta gente te mira. Con tu cuenta del juego iniciada.'],
    ['Confirmá en Discord', 'Te damos un código. En el canal <strong>#creadores</strong> tocás <strong>Postularme</strong> y lo pegás. Así sabemos que sos vos.'],
    ['El staff te aprueba', 'Te llega por Discord tu link, el rol de Creador y todo para arrancar. Desde ahí cada recarga de tu gente suma.'],
];
$crReglas = [
    ['¿Qué tengo que hacer?', 'Streams o videos de MU PANIC con el nombre del server en el título y tu link en la descripción o en el chat.'],
    ['¿Cuánto y cuándo cobro?', 'El 10% de las recargas aprobadas de tus invitados, durante los 3 meses siguientes a que se suman. Se transfiere una vez por mes.'],
    ['¿Qué más recibo?', 'VIP para sortear en vivo entre tu chat, el título de creador dentro del juego, un torneo de Arena con tu nombre y lugar destacado en la web y en el Discord.'],
    ['¿Qué no vale?', 'Cuentas propias o de tu misma casa no cuentan. Si hay trampa, la comisión se anula y se da de baja al creador.'],
];
$crBg = function($src) { return $src ? ' style="background-image:url(\''.crE($src).'\')"' : ''; };
?>
<div id="soc-page" class="soc soc--cre pui">
  <section class="soc-hero soc-cre-hero<?php echo $crEscena ? ' has-art' : ''; ?>" aria-labelledby="cre-title">
    <div class="soc-hero-art" aria-hidden="true">
      <div class="soc-hero-block"></div>
      <?php if($crEscena) { ?><div class="soc-cre-escena" data-soc-fig><img src="<?php echo crE($crEscena); ?>" alt="" width="2400" height="1200" decoding="async" fetchpriority="high"></div><?php } ?>
    </div>
    <div class="pui-wrap soc-hero-copy">
      <p class="pui-kicker"><a href="<?php echo crE(__BASE_URL__.'socios/'); ?>">Socios</a> · Creadores</p>
      <h1 class="pui-h1 soc-hero-title soc-cre-title" id="cre-title">Hacé contenido de MU PANIC. <em>Cobrá por tu comunidad.</em></h1>
      <p class="soc-lead">Streamers, YouTubers y TikTokers: te llevás el <strong>10% en plata</strong> de lo que recargan los jugadores que entran con tu link, durante 3 meses por cada uno. Además, VIP para sortear en vivo, título de creador en el juego y un torneo de Arena con tu nombre.</p>
      <div class="soc-actions"><a class="pui-btn pui-btn--lg soc-btn soc-punch" href="#postulacion">Postularme <span aria-hidden="true">↓</span></a></div>
    </div>
  </section>

  <section class="soc-band soc-how<?php echo $crBanda ? ' has-bg' : ''; ?>" aria-labelledby="cre-how-title"<?php echo $crBg($crBanda); ?>>
    <div class="pui-wrap">
      <div class="soc-head"><div><p class="pui-kicker">Cómo entrar</p><h2 class="pui-h2" id="cre-how-title"><span class="pui-reveal">Tres pasos y salís al aire</span></h2></div></div>
      <div class="soc-steps-wrap" data-soc-steps>
        <div class="soc-steps-bar" aria-hidden="true"><i></i></div>
        <ol class="soc-steps">
          <?php foreach($crPasos as $i => $p) { ?>
          <li class="soc-step" style="--i:<?php echo $i; ?>">
            <span class="soc-step-ico" aria-hidden="true"><?php echo $i + 1; ?></span>
            <p class="soc-step-n">Paso <?php echo $i + 1; ?></p>
            <h3><?php echo crE($p[0]); ?></h3>
            <p><?php echo $p[1]; ?></p>
          </li>
          <?php } ?>
        </ol>
      </div>
    </div>
  </section>

  <section class="soc-band soc-band--bone soc-cre-box" id="postulacion" aria-label="Postulación">
    <div class="pui-wrap soc-cre-grid-main">
      <aside class="soc-cre-side">
        <p class="soc-cre-side-k">Lo que te llevás</p>
        <p class="soc-cre-side-big">10%<small>en plata, 3 meses por cada jugador</small></p>
        <ul>
          <li>VIP para sortear en vivo</li>
          <li>Título de creador en el juego</li>
          <li>Torneo de Arena con tu nombre</li>
          <li>Destacado en la web y el Discord</li>
        </ul>
      </aside>
      <div class="soc-cre-main">
    <?php if($crMsg) { ?><div class="soc-alert soc-alert--<?php echo $crTone; ?>" role="status"><?php echo crE($crMsg); ?></div><?php } ?>

    <?php if(!$crLogged) { ?>
      <div class="soc-cre-card">
        <p class="pui-kicker">Postulación</p>
        <h2>Primero, entrá con tu cuenta</h2>
        <p>La postulación queda atada a tu cuenta del juego: ahí es donde se cuentan los jugadores que traés.</p>
        <div class="soc-actions"><a class="pui-btn soc-btn soc-punch" href="<?php echo crE(__BASE_URL__.'login/'); ?>">Ingresar</a><a class="pui-btn pui-btn--dark" href="<?php echo crE(__BASE_URL__.'register/'); ?>">Crear cuenta gratis</a></div>
      </div>
    <?php } elseif($crError) { ?>
      <div class="soc-cre-card"><p class="pui-kicker">Postulación</p><h2>No pudimos cargar tu postulación</h2><p>Volvé a abrir esta página en unos minutos.</p></div>
    <?php } elseif($crState==='esperando_discord') { ?>
      <div class="soc-cre-card soc-cre-code">
        <p class="pui-kicker">Falta un paso</p>
        <h2>Confirmá tu postulación en Discord</h2>
        <ol class="soc-cre-list">
          <li>Entrá al Discord de MU PANIC.</li>
          <li>Andá al canal <strong>#creadores</strong> y tocá <strong>Postularme</strong> (o escribí <strong>/creador</strong>).</li>
          <li>Pegá este código:</li>
        </ol>
        <div class="soc-cre-codebox" data-cre-copy><input type="text" readonly value="<?php echo crE($crApp['code']); ?>" aria-label="Tu código de postulación"><button type="button" data-cre-copy-btn>Copiar</button></div>
        <div class="soc-actions"><a class="pui-btn soc-btn soc-punch" href="<?php echo crE($crDiscord); ?>" target="_blank" rel="noopener noreferrer">Abrir el Discord ↗</a></div>
        <p class="soc-cre-note">El código es solo para vos. Vence en 14 días si no lo confirmás.</p>
      </div>
    <?php } elseif($crState==='pendiente') { ?>
      <div class="soc-cre-card"><p class="pui-kicker">En revisión</p><h2>El staff está viendo tu canal</h2><p>Ya recibimos tu postulación en Discord. Te avisamos por ahí apenas la revisemos.</p></div>
    <?php } elseif($crState==='aprobado') { ?>
      <div class="soc-cre-card soc-cre-ok"><p class="pui-kicker">Aprobado</p><h2>¡Ya sos creador de MU PANIC!</h2><p>Tu link y lo que vas cobrando están en <a href="<?php echo crE(__BASE_URL__.'usercp/referidos/'); ?>">Mi cuenta → Referidos</a>. También te lo mandamos por Discord.</p>
        <div class="soc-actions"><a class="pui-btn soc-btn soc-punch" href="<?php echo crE(__BASE_URL__.'usercp/referidos/'); ?>">Ver mi link</a></div></div>
    <?php } ?>

    <?php if($crShowForm) { ?>
      <form class="soc-cre-card soc-cre-form" method="post" action="<?php echo crE(__BASE_URL__.'socios/creadores/#postulacion'); ?>">
        <?php if($crState==='rechazado') { ?><p class="soc-cre-note soc-cre-note--top">Tu postulación anterior no fue aprobada. Podés volver a postularte cuando quieras.</p><?php } ?>
        <p class="pui-kicker">Postulación</p>
        <h2>Postulate como creador</h2>
        <input type="hidden" name="cre_csrf" value="<?php echo crE($_SESSION['cre_csrf']); ?>"><input type="hidden" name="cre_action" value="postular">
        <div class="soc-cre-grid">
          <label class="pui-field"><span class="pui-label">Plataforma</span><select class="pui-input" name="platform" required><option value="">Elegí…</option><?php foreach(PanicCreadores::PLATAFORMAS as $k=>$v) { ?><option value="<?php echo crE($k); ?>"<?php echo (($_POST['platform'] ?? '')===$k)?' selected':''; ?>><?php echo crE($v); ?></option><?php } ?></select></label>
          <label class="pui-field"><span class="pui-label">Link de tu canal</span><input class="pui-input" type="url" name="channel_url" required maxlength="200" placeholder="https://kick.com/tucanal" value="<?php echo crE($_POST['channel_url'] ?? ''); ?>"></label>
          <label class="pui-field"><span class="pui-label">Espectadores promedio por stream (o vistas por video)</span><input class="pui-input" type="number" name="audience" min="0" max="1000000" required value="<?php echo crE($_POST['audience'] ?? ''); ?>"></label>
          <label class="pui-field"><span class="pui-label">Días y horarios en los que hacés contenido</span><input class="pui-input" type="text" name="schedule" maxlength="120" placeholder="Lun a vie de 21 a 24 h" value="<?php echo crE($_POST['schedule'] ?? ''); ?>"></label>
          <label class="pui-field soc-cre-wide"><span class="pui-label">Contanos algo más (opcional)</span><textarea class="pui-input" name="note" maxlength="400" rows="3" placeholder="Qué juegos hacés, si ya jugaste MU, ideas para tu comunidad…"><?php echo crE($_POST['note'] ?? ''); ?></textarea></label>
        </div>
        <p class="soc-cre-note">Cuenta del juego: <strong><?php echo crE($crAccount); ?></strong>. Ahí se cuentan los jugadores que entran con tu link.</p>
        <button class="pui-btn pui-btn--lg soc-btn soc-punch" type="submit">Enviar postulación</button>
      </form>
    <?php } ?>
      </div>
    </div>
  </section>

  <section class="soc-band soc-rules<?php echo $crBanda ? ' has-bg' : ''; ?>" id="reglas-creadores" aria-labelledby="cre-rules-title"<?php echo $crBg($crBanda); ?>>
    <div class="pui-wrap">
      <div class="soc-head"><div><p class="pui-kicker">Reglas para creadores</p><h2 class="pui-h2" id="cre-rules-title"><span class="pui-reveal">Claro desde el principio</span></h2></div></div>
      <ul class="soc-rules-grid" data-soc-reveal>
        <?php foreach($crReglas as $i => $q) { ?>
        <li style="--i:<?php echo $i; ?>"><span class="soc-rule-n" aria-hidden="true"><?php echo sprintf('%02d', $i + 1); ?></span><div><h3><?php echo crE($q[0]); ?></h3><p><?php echo crE($q[1]); ?></p></div></li>
        <?php } ?>
      </ul>
    </div>
  </section>
</div>
<script>
(function(){var b=document.querySelector('[data-cre-copy-btn]');if(!b)return;b.addEventListener('click',function(){var i=document.querySelector('[data-cre-copy] input');var ok=function(){b.textContent='¡Copiado!';setTimeout(function(){b.textContent='Copiar';},1800);};if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(i.value).then(ok,function(){i.select();document.execCommand('copy');ok();});}else{i.select();document.execCommand('copy');ok();}});})();
</script>
