<?php
/**
 * MU PANIC · Votar
 * Votar en las listas de servidores sube a MU PANIC en los rankings y trae jugadores nuevos.
 * Con la sesión iniciada, cada voto confirmado por la lista da un premio en Eryns
 * (se acredita desde el VPS, con la cuenta desconectada del juego).
 */
if(!defined('access') || !access) die();
require_once __DIR__.'/votar-store.php';
require_once __DIR__.'/votar-sites.php';
function votE($s) { return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

$votLogged=function_exists('isLoggedIn') && isLoggedIn();
$votAccount=$votLogged?(string)($_SESSION['username'] ?? ''):'';
if(!PanicVotarStore::validAccount($votAccount)) { $votLogged=false; $votAccount=''; }
if(!is_string($_SESSION['votar_csrf'] ?? null)) $_SESSION['votar_csrf']=bin2hex(random_bytes(32));

$votStore=null; $votCfg=null;
try { $votStore=new PanicVotarStore(); $votCfg=$votStore->config(); } catch(Throwable $e) { $votStore=null; }
$votSites=$votCfg['sites'] ?? array_map(function($s){ return $s+['enabled'=>false,'has_token'=>false]; }, panicVotarSites());
$votReward=(int)($votCfg['reward'] ?? PanicVotarStore::DEFAULT_REWARD);
$votRewardsOn=$votStore && !empty($votCfg['enabled']);

$votMsg=null; $votTone='info';
if(($_SERVER['REQUEST_METHOD'] ?? '')==='POST' && ($_POST['votar_action'] ?? '')==='reclamar') {
    $site=is_string($_POST['votar_site'] ?? null)?$_POST['votar_site']:'';
    $now=time();
    if(!$votLogged) { $votMsg='Ingresá con tu cuenta para cobrar el premio.'; $votTone='error'; }
    elseif(!is_string($_POST['votar_csrf'] ?? null) || !hash_equals($_SESSION['votar_csrf'],$_POST['votar_csrf'])) { $votMsg='La sesión venció. Recargá la página e intentá de nuevo.'; $votTone='error'; }
    elseif(!$votStore || !isset($votSites[$site])) { $votMsg='No pudimos revisar tu voto ahora. Probá en unos minutos.'; $votTone='error'; }
    else {
        try {
            $begin=$votStore->beginClaim($votAccount,$site,$now);
            if(!$begin['ok']) {
                $votTone='error';
                if($begin['reason']==='disabled') $votMsg='Los premios por votar están pausados por ahora. ¡Igual gracias por votar!';
                elseif($begin['reason']==='cooldown') $votMsg='Ya cobraste el premio de '.$votSites[$site]['name'].'. Podés volver a votar en '.panicVotarFalta($begin['until']-$now).'.';
                else $votMsg='Esperá unos segundos y probá de nuevo.';
            } else {
                $claim=panicVotarClaim($site,$votAccount,$votStore->token($site));
                try { $votStore->noteApi($site,$claim['summary']); } catch(Throwable $e) {}
                if($claim['ok']) {
                    $p=$votStore->addPremio($votAccount,$site,$claim['ref'],$now);
                    $votMsg='¡Voto confirmado! Ganaste '.number_format((int)$p['coins'],0,',','.').' Eryns. Se acreditan en tu cuenta cuando estés desconectado del juego.'; $votTone='ok';
                } elseif($claim['error']) { $votMsg='No pudimos comunicarnos con '.$votSites[$site]['name'].'. Probá de nuevo en unos minutos.'; $votTone='error'; }
                else { $votMsg='Todavía no vemos tu voto en '.$votSites[$site]['name'].'. Asegurate de escribir tu cuenta ('.$votAccount.') al votar, esperá un minuto y tocá "Reclamar" de nuevo.'; $votTone='error'; }
            }
        } catch(Throwable $e) { $votMsg='No pudimos revisar tu voto ahora. Probá en unos minutos.'; $votTone='error'; }
    }
}

function panicVotarFalta($s) {
    $s=max(60,(int)$s); $h=intdiv($s,3600); $m=intdiv($s%3600,60);
    if($h>0) return $h.' h'.($m>0?' '.$m.' min':'');
    return $m.' min';
}

$votMine=['next'=>[],'premios'=>[]];
if($votLogged && $votStore) { try { $votMine=$votStore->account($votAccount); } catch(Throwable $e) {} }
$votNow=time();
?>
<div class="vot-page">

  <section class="vot-hero">
    <div class="vot-hero-copy">
      <p class="vot-eyebrow">Votá por MU PANIC</p>
      <h1>Un voto tuyo,<br><em><?php echo $votRewardsOn ? number_format($votReward,0,',','.').' Eryns' : 'más jugadores'; ?></em> para vos.</h1>
      <p class="vot-lead">Las listas de servidores ordenan por votos. Cada voto sube a MU PANIC, más gente lo descubre y el server crece. <?php if($votRewardsOn) { ?>Por cada voto confirmado te damos <strong><?php echo number_format($votReward,0,',','.'); ?> Eryns</strong>, y podés votar de nuevo cada 12 horas.<?php } ?></p>
    </div>
  </section>

  <div class="vot-wrap">
    <?php if($votMsg) { ?><div class="vot-alert vot-alert--<?php echo votE($votTone); ?>" role="status"><?php echo votE($votMsg); ?></div><?php } ?>

    <ol class="vot-steps" aria-label="Cómo votar">
      <li><span>1</span><div><strong>Tocá "Votar"</strong><p>Se abre la lista en otra pestaña.</p></div></li>
      <li><span>2</span><div><strong>Escribí tu cuenta y votá</strong><p><?php if($votLogged) { ?>En el campo de cuenta poné <b class="vot-account"><?php echo votE($votAccount); ?></b> <button type="button" class="vot-copy" data-copy="<?php echo votE($votAccount); ?>">Copiar</button><?php } else { ?>Usá el nombre de tu cuenta de MU PANIC, no el del personaje.<?php } ?></p></div></li>
      <li><span>3</span><div><strong>Volvé y tocá "Reclamar"</strong><p><?php echo $votRewardsOn ? 'Revisamos tu voto y te damos los Eryns.' : 'Cuando los premios estén activos, vas a poder cobrar acá.'; ?></p></div></li>
    </ol>

    <?php if(!$votLogged) { ?>
      <div class="vot-login"><p><strong>¿Querés cobrar el premio?</strong> Ingresá con tu cuenta antes de votar.</p><a class="vot-btn" href="<?php echo votE(__BASE_URL__.'login/'); ?>">Ingresar</a><a class="vot-btn vot-btn--ghost" href="<?php echo votE(__BASE_URL__.'register/'); ?>">Crear cuenta gratis</a></div>
    <?php } ?>

    <div class="vot-sites">
      <?php foreach($votSites as $id=>$site) {
          $siteOn=$votRewardsOn && !empty($site['enabled']) && !empty($site['has_token']);
          $next=(int)($votMine['next'][$id] ?? 0); $wait=$votLogged && $siteOn && $next>$votNow;
          $voteUrl=$site['vote_url'];
      ?>
      <article class="vot-site">
        <header><h2><?php echo votE($site['name']); ?></h2><span class="vot-chip"><?php echo $siteOn ? '+'.number_format($votReward,0,',','.').' Eryns' : 'Sin premio por ahora'; ?></span></header>
        <p class="vot-meta">Podés votar cada <?php echo (int)$site['hours']; ?> horas.</p>
        <?php if($wait) { ?><p class="vot-wait">Ya cobraste este voto. Volvé en <strong><?php echo votE(panicVotarFalta($next-$votNow)); ?></strong>.</p><?php } ?>
        <div class="vot-actions">
          <a class="vot-btn" href="<?php echo votE($voteUrl); ?>" target="_blank" rel="noopener">Votar ↗</a>
          <?php if($votLogged && $siteOn) { ?>
          <form method="post" action="<?php echo votE(__BASE_URL__.'votar/'); ?>">
            <input type="hidden" name="votar_csrf" value="<?php echo votE($_SESSION['votar_csrf']); ?>">
            <input type="hidden" name="votar_action" value="reclamar">
            <input type="hidden" name="votar_site" value="<?php echo votE($id); ?>">
            <button class="vot-btn vot-btn--claim" type="submit" <?php if($wait) echo 'disabled'; ?>>Reclamar <?php echo number_format($votReward,0,',','.'); ?> Eryns</button>
          </form>
          <?php } ?>
        </div>
      </article>
      <?php } ?>
    </div>

    <?php if($votLogged && !empty($votMine['premios'])) { ?>
    <section class="vot-history" aria-labelledby="vot-history-title">
      <h2 id="vot-history-title">Tus últimos premios</h2>
      <ul>
        <?php foreach($votMine['premios'] as $p) { $done=($p['state'] ?? '')==='done'; ?>
        <li><span><?php echo votE(date('d/m H:i',(int)$p['created'])); ?> · <?php echo votE($votSites[$p['site']]['name'] ?? $p['site']); ?></span><strong>+<?php echo number_format((int)$p['coins'],0,',','.'); ?> Eryns</strong><em class="<?php echo $done?'is-done':'is-pending'; ?>"><?php echo $done?'Acreditados':'Se acreditan al desconectarte'; ?></em></li>
        <?php } ?>
      </ul>
    </section>
    <?php } ?>

    <p class="vot-note">Los premios se cobran una vez por voto real: la lista confirma cada voto antes de acreditarlo. Votar con varias cuentas desde la misma conexión no suma, porque las listas cuentan un voto cada 12 horas por conexión.</p>
  </div>
</div>
<script>
document.querySelectorAll('.vot-copy').forEach(function(b){b.addEventListener('click',function(){var t=b.getAttribute('data-copy');function ok(){b.textContent='Copiado';setTimeout(function(){b.textContent='Copiar';},1600);}if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(t).then(ok,function(){});}});});
</script>
