<?php
/**
 * MU PANIC · Socios → Creadores: postulación de streamers.
 * 1) Formulario (con sesión).  2) Código para confirmar en Discord (botón "Postularme" en #creadores o /creador).
 * 3) El staff aprueba en Discord; el bot da el rol y manda el link; el VPS lo da de alta al 10%.
 */
if(!defined('access') || !access) die();
require_once __DIR__.'/creadores-cola.php';
$crImg = __PATH_TEMPLATE__.'img/';
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
?>
<div class="soc-page soc-cre">
  <section class="soc-cre-hero">
    <div class="soc-cre-art" aria-hidden="true"><img src="<?php echo crE($crImg.'socios/socios-hero.webp'); ?>" alt="" width="1672" height="941" decoding="async"></div>
    <div class="soc-wrap">
      <p class="soc-eyebrow"><a href="<?php echo crE(__BASE_URL__.'socios/'); ?>">Socios</a> · Creadores</p>
      <h1>Hacé contenido de MU PANIC.<br><em>Cobrá por tu comunidad.</em></h1>
      <p class="soc-lead">Streamers, YouTubers y TikTokers: te llevás el <strong>10% en plata</strong> de lo que recargan los jugadores que entran con tu link, durante 3 meses por cada uno. Además, VIP para sortear en vivo, título de creador en el juego y un torneo de Arena con tu nombre.</p>
    </div>
  </section>

  <section class="soc-section soc-cre-steps">
    <ol class="soc-steps">
      <li><span class="soc-num"><i>1</i></span><h3>Completá el formulario</h3><p>Tu canal, en qué plataforma y cuánta gente te mira. Con tu cuenta del juego iniciada.</p></li>
      <li><span class="soc-num"><i>2</i></span><h3>Confirmá en Discord</h3><p>Te damos un código. En el canal <strong>#creadores</strong> tocás <strong>Postularme</strong> y lo pegás. Así sabemos que sos vos.</p></li>
      <li><span class="soc-num"><i>3</i></span><h3>El staff te aprueba</h3><p>Te llega por Discord tu link, el rol de Creador y todo para arrancar. Desde ahí cada recarga de tu gente suma.</p></li>
    </ol>
  </section>

  <section class="soc-section soc-cre-box" id="postulacion">
    <?php if($crMsg) { ?><div class="soc-alert soc-alert--<?php echo $crTone; ?>" role="status"><?php echo crE($crMsg); ?></div><?php } ?>

    <?php if(!$crLogged) { ?>
      <div class="soc-cre-card">
        <h2>Primero, entrá con tu cuenta</h2>
        <p>La postulación queda atada a tu cuenta del juego: ahí es donde se cuentan los jugadores que traés.</p>
        <div class="soc-actions"><a class="soc-btn" href="<?php echo crE(__BASE_URL__.'login/'); ?>">Ingresar</a><a class="soc-btn soc-btn-ghost" href="<?php echo crE(__BASE_URL__.'register/'); ?>">Crear cuenta gratis</a></div>
      </div>
    <?php } elseif($crError) { ?>
      <div class="soc-cre-card"><h2>No pudimos cargar tu postulación</h2><p>Volvé a abrir esta página en unos minutos.</p></div>
    <?php } elseif($crState==='esperando_discord') { ?>
      <div class="soc-cre-card soc-cre-code">
        <p class="soc-eyebrow">Falta un paso</p>
        <h2>Confirmá tu postulación en Discord</h2>
        <ol class="soc-cre-list">
          <li>Entrá al Discord de MU PANIC.</li>
          <li>Andá al canal <strong>#creadores</strong> y tocá <strong>Postularme</strong> (o escribí <strong>/creador</strong>).</li>
          <li>Pegá este código:</li>
        </ol>
        <div class="soc-cre-codebox" data-cre-copy><input type="text" readonly value="<?php echo crE($crApp['code']); ?>" aria-label="Tu código de postulación"><button type="button" data-cre-copy-btn>Copiar</button></div>
        <div class="soc-actions"><a class="soc-btn soc-btn-pro" href="<?php echo crE($crDiscord); ?>" target="_blank" rel="noopener noreferrer">Abrir el Discord ↗</a></div>
        <p class="soc-cre-note">El código es solo para vos. Vence en 14 días si no lo confirmás.</p>
      </div>
    <?php } elseif($crState==='pendiente') { ?>
      <div class="soc-cre-card"><p class="soc-eyebrow">En revisión</p><h2>El staff está viendo tu canal</h2><p>Ya recibimos tu postulación en Discord. Te avisamos por ahí apenas la revisemos.</p></div>
    <?php } elseif($crState==='aprobado') { ?>
      <div class="soc-cre-card soc-cre-ok"><p class="soc-eyebrow">Aprobado</p><h2>¡Ya sos creador de MU PANIC!</h2><p>Tu link y lo que vas cobrando están en <a href="<?php echo crE(__BASE_URL__.'usercp/referidos/'); ?>">Mi cuenta → Referidos</a>. También te lo mandamos por Discord.</p>
        <div class="soc-actions"><a class="soc-btn" href="<?php echo crE(__BASE_URL__.'usercp/referidos/'); ?>">Ver mi link</a></div></div>
    <?php } ?>

    <?php if($crShowForm) { ?>
      <form class="soc-cre-card soc-cre-form" method="post" action="<?php echo crE(__BASE_URL__.'socios/creadores/#postulacion'); ?>">
        <?php if($crState==='rechazado') { ?><p class="soc-cre-note">Tu postulación anterior no fue aprobada. Podés volver a postularte cuando quieras.</p><?php } ?>
        <h2>Postulate como creador</h2>
        <input type="hidden" name="cre_csrf" value="<?php echo crE($_SESSION['cre_csrf']); ?>"><input type="hidden" name="cre_action" value="postular">
        <div class="soc-cre-grid">
          <label><span>Plataforma</span><select name="platform" required><option value="">Elegí…</option><?php foreach(PanicCreadores::PLATAFORMAS as $k=>$v) { ?><option value="<?php echo crE($k); ?>"<?php echo (($_POST['platform'] ?? '')===$k)?' selected':''; ?>><?php echo crE($v); ?></option><?php } ?></select></label>
          <label><span>Link de tu canal</span><input type="url" name="channel_url" required maxlength="200" placeholder="https://kick.com/tucanal" value="<?php echo crE($_POST['channel_url'] ?? ''); ?>"></label>
          <label><span>Espectadores promedio por stream (o vistas por video)</span><input type="number" name="audience" min="0" max="1000000" required value="<?php echo crE($_POST['audience'] ?? ''); ?>"></label>
          <label><span>Días y horarios en los que hacés contenido</span><input type="text" name="schedule" maxlength="120" placeholder="Lun a vie de 21 a 24 h" value="<?php echo crE($_POST['schedule'] ?? ''); ?>"></label>
          <label class="soc-cre-wide"><span>Contanos algo más (opcional)</span><textarea name="note" maxlength="400" rows="3" placeholder="Qué juegos hacés, si ya jugaste MU, ideas para tu comunidad…"><?php echo crE($_POST['note'] ?? ''); ?></textarea></label>
        </div>
        <p class="soc-cre-note">Cuenta del juego: <strong><?php echo crE($crAccount); ?></strong>. Ahí se cuentan los jugadores que entran con tu link.</p>
        <button class="soc-btn soc-btn-pro" type="submit">Enviar postulación</button>
      </form>
    <?php } ?>
  </section>

  <section class="soc-section soc-rules" id="reglas-creadores">
    <header class="soc-head"><p class="soc-eyebrow">Reglas para creadores</p><h2>Claro desde el principio</h2></header>
    <div class="soc-rules-grid">
      <div><h3>¿Qué tengo que hacer?</h3><p>Streams o videos de MU PANIC con el nombre del server en el título y tu link en la descripción o en el chat.</p></div>
      <div><h3>¿Cuánto y cuándo cobro?</h3><p>El 10% de las recargas aprobadas de tus invitados, durante los 3 meses siguientes a que se suman. Se transfiere una vez por mes.</p></div>
      <div><h3>¿Qué más recibo?</h3><p>VIP para sortear en vivo entre tu chat, el título de creador dentro del juego, un torneo de Arena con tu nombre y lugar destacado en la web y en el Discord.</p></div>
      <div><h3>¿Qué no vale?</h3><p>Cuentas propias o de tu misma casa no cuentan. Si hay trampa, la comisión se anula y se da de baja al creador.</p></div>
    </div>
  </section>
</div>
<script>
(function(){var b=document.querySelector('[data-cre-copy-btn]');if(!b)return;b.addEventListener('click',function(){var i=document.querySelector('[data-cre-copy] input');var ok=function(){b.textContent='¡Copiado!';setTimeout(function(){b.textContent='Copiar';},1800);};if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(i.value).then(ok,function(){i.select();document.execCommand('copy');ok();});}else{i.select();document.execCommand('copy');ok();}});})();
</script>
