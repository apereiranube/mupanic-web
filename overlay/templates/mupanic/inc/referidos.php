<?php
if(!defined('access') || !access) die();
if(!isLoggedIn()) return;
require_once __DIR__.'/referidos-cola.php';
$refAccount=(string)($_SESSION['username'] ?? '');
if(!preg_match('/^[A-Za-z0-9_]{1,10}$/D',$refAccount)) return;
if(!is_string($_SESSION['ref_csrf'] ?? null)) $_SESSION['ref_csrf']=bin2hex(random_bytes(32));
$refMessage=null; $refMessageTone='info';

// Confirmacion del invitado (se aplica en el VPS dentro del minuto)
if(($_SERVER['REQUEST_METHOD'] ?? '')==='POST' && ($_POST['ref_action'] ?? '')==='vincular') {
    $code=strtoupper(trim((string)($_POST['ref_code'] ?? '')));
    if(!is_string($_POST['ref_csrf'] ?? null) || !hash_equals($_SESSION['ref_csrf'],$_POST['ref_csrf'])) { $refMessage='La sesión venció. Recargá la página e intentá de nuevo.'; $refMessageTone='error'; }
    elseif(!preg_match('/^[A-Z0-9]{8}$/D',$code)) { $refMessage='El código tiene 8 letras y números. Revisalo e intentá de nuevo.'; $refMessageTone='error'; }
    else {
        try { (new PanicReferidosCola())->requestLink($refAccount,$code); panicReferidosGuardarCodigo($code); $refMessage='¡Recibimos tu código! Lo confirmamos en menos de un minuto.'; $refMessageTone='ok'; }
        catch(RuntimeException $e) { $refMessage=$e->getMessage()==='Demasiados intentos'?'Hiciste muchos intentos hoy. Probá de nuevo mañana.':'No pudimos registrar tu código ahora. Probá en unos minutos.'; $refMessageTone='error'; }
        catch(Throwable $e) { $refMessage='No pudimos registrar tu código ahora. Probá en unos minutos.'; $refMessageTone='error'; }
    }
}

$refCode=null; $refInvited=null; $refList=[]; $refError=false; $refLast=null;
try { $refCode=panicReferidosMiCodigo($refAccount); $refInvited=panicReferidosComoInvitado($refAccount); $refList=panicReferidosMisInvitados($refAccount); }
catch(Throwable $e) { $refError=true; }
try { $refLast=(new PanicReferidosCola())->lastLink($refAccount); } catch(Throwable $e) {}
$refPendingCode=panicReferidosCodigoPendiente();
$refLink=$refCode?__BASE_URL__.'register/?ref='.$refCode:null;

$refOnWay=0; $refDone=0; $refDays=0;
foreach($refList as $r) {
    if(in_array($r['Estado'],['EN_CAMINO','REVISAR'],true)) $refOnWay++;
    if(in_array($r['Estado'],['CUMPLIDO','APROBADO','PAGADO'],true)) $refDone++;
    if(!empty($r['PagoInvitaAR'])) $refDays+=(int)$r['DiasInvita'];
}
function panicRefEstado($e,$paga) {
    switch($e) {
        case 'EN_CAMINO': return ['camino','En camino'];
        case 'REVISAR': return ['revision','En revisión'];
        case 'CUMPLIDO': case 'APROBADO': return ['cumplido','Meta cumplida · entregando VIP'];
        case 'PAGADO': return ['pagado',((string)$paga==='0')?'Meta cumplida · tope del mes':'VIP entregado'];
        case 'NO_VALIDO': return ['no','No válido'];
        case 'ANULADO': return ['no','Anulado'];
    }
    return ['camino',$e];
}
function panicRefBarra($valor,$meta,$label) {
    $v=max(0,min((int)$valor,$meta)); $pct=(int)round($v*100/$meta);
    return '<div class="ref-bar"><span>'.panicAccountEscape($label).' <b>'.$v.'/'.$meta.'</b></span><i style="--p:'.$pct.'%"></i></div>';
}
$refResultados=['OK'=>['ok','¡Listo! Quedó confirmada tu invitación y tus 3 días de VIP se cargan en tu cuenta (si estás jugando, al salir).'],
    'CODIGO_INVALIDO'=>['error','Ese código no existe. Pedile a tu amigo que te pase su link de nuevo.'],
    'FUERA_DE_PLAZO'=>['error','La invitación solo se puede confirmar durante los primeros 7 días de tu cuenta.'],
    'YA_VINCULADO'=>['info','Tu cuenta ya tiene una invitación confirmada.'],
    'MISMA_CUENTA'=>['error','No podés usar tu propio código.'],
    'CUENTA_NO_EXISTE'=>['error','No encontramos tu cuenta. Probá de nuevo en unos minutos.']];
?>
<link rel="stylesheet" href="<?php echo __PATH_TEMPLATE__; ?>css/referidos.css?v=<?php echo substr(hash_file('sha256',__DIR__.'/../css/referidos.css'),0,12); ?>">
<section class="panic-ref" aria-labelledby="ref-title">
  <header class="ref-hero">
    <div class="ref-hero-copy">
      <span class="ref-overline">MU PANIC / REFERIDOS</span>
      <h2 id="ref-title">Invitá a tus amigos.<br><em>Ganá días de VIP.</em></h2>
      <p>Tu amigo arranca con <strong>3 días de VIP</strong> apenas crea su cuenta. Cuando llegue al reset 2 y juegue 3 días, vos recibís <strong>7 días de VIP</strong>.</p>
      <?php if($refLink) { ?>
      <div class="ref-link" data-ref-copy>
        <span class="ref-overline">TU LINK DE INVITACIÓN</span>
        <div class="ref-link-row"><input type="text" readonly value="<?php echo panicAccountEscape($refLink); ?>" aria-label="Tu link de invitación"><button type="button" data-ref-copy-button>Copiar link</button></div>
        <small>Tu código: <b><?php echo panicAccountEscape($refCode); ?></b> · También se puede cargar a mano.</small>
      </div>
      <?php } elseif($refError) { ?><p class="ref-note">No pudimos consultar tu link en este momento. Volvé a abrir la página en un rato.</p>
      <?php } else { ?><p class="ref-note">Tu link se está generando. Volvé a abrir esta página en un minuto.</p><?php } ?>
    </div>
  </header>

  <?php if($refMessage) { ?><div class="ref-alert ref-alert--<?php echo $refMessageTone; ?>" role="status"><?php echo panicAccountEscape($refMessage); ?></div><?php } ?>

  <div class="ref-cards">
    <article><strong><?php echo $refOnWay; ?></strong><span>Amigos en camino</span></article>
    <article><strong><?php echo $refDone; ?></strong><span>Cumplieron la meta</span></article>
    <article class="ref-card-gold"><strong><?php echo $refDays; ?></strong><span>Días de VIP ganados</span></article>
  </div>

  <?php if($refInvited) { [$tone,$label]=panicRefEstado($refInvited['Estado'],1); ?>
  <section class="ref-panel ref-invited">
    <span class="ref-overline">TE INVITÓ UN AMIGO</span>
    <h3><?php echo !empty($refInvited['PagoInvitadoAR'])?'Ya tenés tus 3 días de VIP de bienvenida':'Tus 3 días de VIP de bienvenida'; ?></h3>
    <p><?php echo !empty($refInvited['PagoInvitadoAR'])?'Arrancaste con VIP por venir invitado.':'Se cargan en tu cuenta en menos de un minuto. Si estás jugando, se acreditan al salir.'; ?> Cuando llegues al reset 2 y juegues 3 días, tu amigo gana 7 días de VIP. Y vos también podés invitar: tu link está arriba.</p>
    <div class="ref-progress"><?php echo panicRefBarra($refInvited['Resets'],2,'Resets'); echo panicRefBarra($refInvited['Dias'],3,'Días jugados (30 min o más)'); ?></div>
    <p class="ref-state ref-state--<?php echo $tone; ?>"><?php echo panicAccountEscape(in_array($refInvited['Estado'],['CUMPLIDO','APROBADO','PAGADO'],true)?'¡Meta cumplida! Tu amigo recibe su premio.':$label); ?></p>
  </section>
  <?php } else {
      $show=$refLast && $refLast['state']==='pending'; $done=$refLast && $refLast['state']==='done' && isset($refResultados[$refLast['result']]);
  ?>
  <section class="ref-panel ref-confirm">
    <span class="ref-overline">¿TE INVITÓ ALGUIEN?</span>
    <h3><?php echo $refPendingCode?'Confirmá quién te invitó':'Cargá el código de tu amigo'; ?></h3>
    <p>Si llegaste por la invitación de un amigo, confirmalo acá. Solo se puede durante los primeros 7 días de tu cuenta.</p>
    <?php if($show) { ?><p class="ref-state ref-state--camino">Estamos confirmando tu invitación. Tarda menos de un minuto: actualizá la página.</p>
    <?php } else { if($done) { $res=$refResultados[$refLast['result']]; ?><p class="ref-state ref-state--<?php echo $res[0]==='ok'?'pagado':($res[0]==='error'?'no':'camino'); ?>"><?php echo panicAccountEscape($res[1]); ?></p><?php } ?>
    <form method="post" class="ref-form" action="<?php echo panicAccountEscape(__BASE_URL__.'usercp/referidos/'); ?>">
      <input type="hidden" name="ref_csrf" value="<?php echo panicAccountEscape($_SESSION['ref_csrf']); ?>"><input type="hidden" name="ref_action" value="vincular">
      <label><span>Código de invitación</span><input name="ref_code" maxlength="8" autocomplete="off" spellcheck="false" value="<?php echo panicAccountEscape($refPendingCode ?? ''); ?>" placeholder="Ej: K7MPQ2XA"></label>
      <button type="submit">Confirmar invitación</button>
    </form>
    <?php } ?>
  </section>
  <?php } ?>

  <section class="ref-panel">
    <span class="ref-overline">TUS INVITADOS</span>
    <h3>Cómo vienen tus amigos</h3>
    <?php if(!$refList) { ?><p class="ref-empty">Todavía no invitaste a nadie. Compartí tu link en Discord, WhatsApp o donde juegues con tus amigos.</p>
    <?php } else { ?><div class="ref-list">
      <?php foreach($refList as $r) { [$tone,$label]=panicRefEstado($r['Estado'],$r['PagaInvita']); ?>
      <article class="ref-friend">
        <div class="ref-friend-head"><strong><?php echo panicAccountEscape($r['Personaje'] ?: 'Todavía sin personaje'); ?></strong><small>Desde el <?php echo panicAccountEscape($r['Creado']); ?></small></div>
        <div class="ref-progress"><?php echo panicRefBarra($r['Resets'],2,'Resets'); echo panicRefBarra($r['Dias'],3,'Días jugados'); ?></div>
        <span class="ref-state ref-state--<?php echo $tone; ?>"><?php echo panicAccountEscape($label); ?></span>
      </article>
      <?php } ?>
    </div><?php } ?>
  </section>

  <section class="ref-panel ref-how">
    <span class="ref-overline">CÓMO FUNCIONA</span>
    <ol>
      <li><b>01</b><div><strong>Compartí tu link</strong><p>Tu amigo crea su cuenta desde tu link: el código ya aparece cargado en el formulario.</p></div></li>
      <li><b>02</b><div><strong>Tu amigo arranca con VIP</strong><p>Recibe 3 días de VIP apenas crea su cuenta, y ya puede invitar a otros con su propio link.</p></div></li>
      <li><b>03</b><div><strong>Vos ganás 7 días</strong><p>Cuando tu amigo llega al reset 2 y juega 3 días distintos (30 minutos o más cada día). Se suman solos a tu VIP actual.</p></div></li>
    </ol>
    <details><summary>Reglas</summary><ul>
      <li>El VIP se entrega cuando la cuenta está desconectada del juego. Si estás jugando, se acredita al salir.</li>
      <li>Para que vos cobres, tu amigo tiene que jugar desde otra PC y otra conexión. Cuentas propias o desde la misma casa no cuentan.</li>
      <li>Podés ganar VIP por hasta 5 amigos por mes.</li>
      <li>La invitación se confirma solo durante los primeros 7 días de la cuenta del invitado. Los 3 días de bienvenida se dan una sola vez por cuenta.</li>
      <li>El equipo puede revisar o anular invitaciones sospechosas.</li>
    </ul></details>
  </section>
  <p class="ref-data-note">Datos actualizados cada minuto.</p>
</section>
<script>
(function(){var b=document.querySelector('[data-ref-copy-button]');if(!b)return;b.addEventListener('click',function(){var i=document.querySelector('[data-ref-copy] input');var done=function(){b.textContent='¡Copiado!';setTimeout(function(){b.textContent='Copiar link';},1800);};if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(i.value).then(done,function(){i.select();document.execCommand('copy');done();});}else{i.select();document.execCommand('copy');done();}});})();
</script>
