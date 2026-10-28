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

$votMsg=null; $votTone='info'; $votClaimed=null;
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
                    $p=$votStore->addPremio($votAccount,$site,$claim['ref'],$now); $votClaimed=['coins'=>(int)$p['coins'],'site'=>$site];
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

/** Nombre del personaje principal de cada cuenta (el de más resets y nivel). Nunca se muestra el nombre de cuenta. */
function panicVotarCharacterNames(array $accounts) {
    $accounts=array_values(array_unique(array_filter($accounts,function($a){ return PanicVotarStore::validAccount($a); })));
    $out=[];
    if(!$accounts || !class_exists('Connection')) return $out;
    try {
        $db=Connection::Database('MuOnline'); if(!$db) return $out;
        $in=implode(',',array_fill(0,count($accounts),'?'));
        $rows=$db->query_fetch('SELECT [AccountID] AS [acc], [Name] AS [name], [cLevel] AS [lvl], [ResetCount] AS [res] FROM [MuOnline43].[dbo].[Character] WHERE [AccountID] IN ('.$in.')',$accounts);
        foreach(is_array($rows)?$rows:[] as $r) {
            $k=strtolower((string)$r['acc']); $score=[(int)$r['res'],(int)$r['lvl']];
            if(!isset($out[$k]) || $score>$out[$k]['score']) $out[$k]=['name'=>(string)$r['name'],'score'=>$score];
        }
    } catch(Throwable $e) {}
    return array_map(function($v){ return $v['name']; },$out);
}
function panicVotarPublicName($account,array $names) {
    $k=strtolower((string)$account);
    if(isset($names[$k]) && $names[$k]!=='') return $names[$k];
    return mb_substr((string)$account,0,2).'···';
}
function panicVotarHace($ts,$now) {
    $s=max(0,$now-(int)$ts);
    if($s<60) return 'recién';
    if($s<3600) return 'hace '.intdiv($s,60).' min';
    if($s<86400) return 'hace '.intdiv($s,3600).' h';
    return 'hace '.intdiv($s,86400).' d';
}

$votNow=time();
$votMine=['next'=>[],'premios'=>[]];
if($votLogged && $votStore) { try { $votMine=$votStore->account($votAccount); } catch(Throwable $e) {} }
$votStats=['votes_month'=>0,'coins_all'=>0,'voters_month'=>0,'ranking'=>[],'me'=>null,'mine_total'=>0,'feed'=>[]];
if($votStore) { try { $votStats=$votStore->publicStats($votAccount,$votNow); } catch(Throwable $e) {} }
$votNames=panicVotarCharacterNames(array_merge(array_column($votStats['ranking'],'account'),array_column($votStats['feed'],'account')));
$votImg=__PATH_TEMPLATE__.'img/votar/';
$votArt=['hero'=>'votar-darklord-d402a36cc4b3.webp','heroMobile'=>'votar-darklord-m-c121c9ccf867.webp','runeOff'=>'votar-runa-apagada-4718567a55be.webp','runeOn'=>'votar-runa-encendida-fca7a59d15ad.webp','gem'=>'eryns-gema-f0215b9908c0.webp','gemSmall'=>'eryns-gema-64-eaac67236425.webp'];
$votMineBefore=(int)$votStats['mine_total']-(int)($votClaimed['coins'] ?? 0);
$fmt=function($n){ return number_format((int)$n,0,',','.'); };
?>
<div class="vot-page" data-now="<?php echo (int)$votNow; ?>"<?php if($votClaimed) { ?> data-claimed="<?php echo (int)$votClaimed['coins']; ?>" data-claimed-site="<?php echo votE($votClaimed['site']); ?>"<?php } ?> data-gem="<?php echo votE($votImg.$votArt['gemSmall']); ?>">

  <section class="vot-hero">
    <div class="vot-hero-bg" aria-hidden="true"><span class="vot-hero-word">VOTÁ</span><i class="vot-glyph g1"></i><i class="vot-glyph g2"></i><i class="vot-glyph g3"></i></div>
    <picture class="vot-hero-char" aria-hidden="true">
      <source media="(max-width:760px)" srcset="<?php echo votE($votImg.$votArt['heroMobile']); ?>">
      <img src="<?php echo votE($votImg.$votArt['hero']); ?>" alt="" width="1122" height="1402" decoding="async" fetchpriority="high">
    </picture>
    <span class="vot-hero-pulse" aria-hidden="true"></span>
    <div class="vot-hero-copy">
      <p class="vot-kicker">Top de servidores</p>
      <h1>Votá por<br>MU PANIC</h1>
      <?php if($votRewardsOn) { ?><p class="vot-prize"><img src="<?php echo votE($votImg.$votArt['gemSmall']); ?>" alt="" width="34" height="34"><span>Ganá <b><?php echo $fmt($votReward); ?> Eryns</b> por cada voto</span></p><?php } ?>
      <p class="vot-lead">Cada voto nos sube en los rankings de servidores de MU y trae jugadores nuevos.<?php if($votRewardsOn) { ?> Podés votar de nuevo cada 12 horas.<?php } ?></p>
      <?php if($votLogged) { ?>
      <div class="vot-me">
        <div class="vot-me-account"><span>Tu cuenta</span><b><?php echo votE($votAccount); ?></b><button type="button" class="vot-copy" data-copy="<?php echo votE($votAccount); ?>">Copiar</button></div>
        <?php if($votRewardsOn) { ?><div class="vot-me-earned" id="vot-earned"><img src="<?php echo votE($votImg.$votArt['gemSmall']); ?>" alt="" width="32" height="32"><span>Ganaste votando</span><b data-earned="<?php echo (int)$votStats['mine_total']; ?>" data-from="<?php echo max(0,$votMineBefore); ?>"><?php echo $fmt($votClaimed?$votMineBefore:$votStats['mine_total']); ?></b><em>Eryns</em></div><?php } ?>
      </div>
      <?php } else { ?>
      <div class="vot-login"><p>Ingresá con tu cuenta antes de votar para cobrar el premio.</p><a class="vot-btn" href="<?php echo votE(__BASE_URL__.'login/'); ?>"><span>Ingresar</span></a><a class="vot-btn vot-btn--ghost" href="<?php echo votE(__BASE_URL__.'register/'); ?>"><span>Crear cuenta gratis</span></a></div>
      <?php } ?>
    </div>
  </section>

  <?php if($votStats['feed']) { ?>
  <div class="vot-feed" aria-label="Últimos votos">
    <div class="vot-feed-track">
      <?php for($loop=0;$loop<2;$loop++) foreach($votStats['feed'] as $f) { ?>
      <span class="vot-feed-item"<?php if($loop) echo ' aria-hidden="true"'; ?>><img src="<?php echo votE($votImg.$votArt['gemSmall']); ?>" alt="" width="18" height="18"><b><?php echo votE(panicVotarPublicName($f['account'],$votNames)); ?></b> votó <?php echo votE(panicVotarHace($f['created'],$votNow)); ?> · +<?php echo $fmt($f['coins']); ?> Eryns</span>
      <?php } ?>
    </div>
  </div>
  <?php } ?>

  <div class="vot-wrap">
    <?php if($votMsg) { ?><div class="vot-alert vot-alert--<?php echo votE($votTone); ?>" role="status"><?php echo votE($votMsg); ?></div><?php } ?>

    <dl class="vot-stats">
      <div><dt>Votos este mes</dt><dd><?php echo $fmt($votStats['votes_month']); ?></dd></div>
      <div><dt>Jugadores que votaron este mes</dt><dd><?php echo $fmt($votStats['voters_month']); ?></dd></div>
      <div><dt>Eryns entregados por votar</dt><dd><?php echo $fmt($votStats['coins_all']); ?></dd></div>
    </dl>

    <section class="vot-section" aria-labelledby="vot-lists-title">
      <header class="vot-section-head"><h2 id="vot-lists-title">Listas de votación</h2><p>Votá en cada lista y reclamá tu premio.</p></header>
      <div class="vot-sites">
        <?php foreach($votSites as $id=>$site) {
            $siteOn=$votRewardsOn && !empty($site['enabled']) && !empty($site['has_token']);
            $next=(int)($votMine['next'][$id] ?? 0); $wait=$votLogged && $siteOn && $next>$votNow;
            $period=(int)$site['hours']*3600;
        ?>
        <article class="vot-site<?php echo $wait?' is-wait':' is-ready'; ?>" data-site="<?php echo votE($id); ?>" data-next="<?php echo $wait?$next:0; ?>" data-period="<?php echo $period; ?>">
          <div class="vot-rune" aria-hidden="true">
            <img class="vot-rune-off" src="<?php echo votE($votImg.$votArt['runeOff']); ?>" alt="" width="546" height="560" loading="lazy">
            <img class="vot-rune-on" src="<?php echo votE($votImg.$votArt['runeOn']); ?>" alt="" width="546" height="560" loading="lazy">
            <svg class="vot-ring" viewBox="0 0 120 120"><circle class="vot-ring-bg" cx="60" cy="60" r="54"/><circle class="vot-ring-fill" cx="60" cy="60" r="54" pathLength="100"/></svg>
          </div>
          <div class="vot-site-body">
            <h3><?php echo votE($site['name']); ?></h3>
            <p class="vot-state"><?php if($wait) { ?>Podés volver a votar en <b class="vot-count" data-count><?php echo votE(gmdate('H:i:s',$next-$votNow)); ?></b><?php } else { ?><span class="vot-ready">Disponible</span> · <?php echo $siteOn ? '+'.$fmt($votReward).' Eryns por voto' : 'Votá para ayudar al server'; ?><?php } ?></p>
            <div class="vot-actions">
              <a class="vot-btn" href="<?php echo votE($site['vote_url']); ?>" target="_blank" rel="noopener">Votar<span aria-hidden="true"> ↗</span></a>
              <?php if($votLogged && $siteOn) { ?>
              <form method="post" action="<?php echo votE(__BASE_URL__.'votar/'); ?>">
                <input type="hidden" name="votar_csrf" value="<?php echo votE($_SESSION['votar_csrf']); ?>">
                <input type="hidden" name="votar_action" value="reclamar">
                <input type="hidden" name="votar_site" value="<?php echo votE($id); ?>">
                <button class="vot-btn vot-btn--claim" type="submit" <?php if($wait) echo 'disabled'; ?>>Reclamar <?php echo $fmt($votReward); ?> Eryns</button>
              </form>
              <?php } ?>
            </div>
          </div>
        </article>
        <?php } ?>
        <article class="vot-site is-soon" aria-label="Próximamente más listas">
          <div class="vot-rune" aria-hidden="true"><img class="vot-rune-off" src="<?php echo votE($votImg.$votArt['runeOff']); ?>" alt="" width="546" height="560" loading="lazy"></div>
          <div class="vot-site-body"><h3>Próximamente</h3><p class="vot-state">Vamos a sumar más listas para que ganes más Eryns por día.</p></div>
        </article>
      </div>
    </section>

    <section class="vot-section" aria-labelledby="vot-rank-title">
      <header class="vot-section-head"><h2 id="vot-rank-title">Ranking de votantes del mes</h2><p>El ranking se reinicia el 1° de cada mes.</p></header>
      <?php if(!$votStats['ranking']) { ?>
        <p class="vot-empty">Todavía nadie votó este mes. ¡Podés ser el primero del ranking!</p>
      <?php } else { $top=array_slice($votStats['ranking'],0,3); $rest=array_slice($votStats['ranking'],3); ?>
      <ol class="vot-podium">
        <?php foreach($top as $r) { ?>
        <li class="vot-podium-<?php echo (int)$r['pos']; ?>"><span class="vot-podium-pos"><?php echo (int)$r['pos']; ?></span><img src="<?php echo votE($votImg.$votArt['gem']); ?>" alt="" width="96" height="94" loading="lazy"><b><?php echo votE(panicVotarPublicName($r['account'],$votNames)); ?></b><span><?php echo $fmt($r['votes']).((int)$r['votes']===1?' voto':' votos'); ?></span></li>
        <?php } ?>
      </ol>
      <?php if($rest) { ?><ol class="vot-ranklist" start="4"><?php foreach($rest as $r) { ?><li><span><?php echo (int)$r['pos']; ?></span><b><?php echo votE(panicVotarPublicName($r['account'],$votNames)); ?></b><em><?php echo $fmt($r['votes']).((int)$r['votes']===1?' voto':' votos'); ?></em></li><?php } ?></ol><?php } ?>
      <?php if($votLogged) { ?><p class="vot-myrank"><?php echo $votStats['me'] ? 'Vos estás en el puesto <b>'.(int)$votStats['me']['pos'].'</b> con <b>'.$fmt($votStats['me']['votes']).'</b> '.((int)$votStats['me']['votes']===1?'voto':'votos').' este mes.' : 'Todavía no votaste este mes.'; ?></p><?php } ?>
      <?php } ?>
    </section>

    <section class="vot-section" aria-labelledby="vot-how-title">
      <header class="vot-section-head"><h2 id="vot-how-title">Cómo funciona</h2></header>
      <ol class="vot-steps">
        <li><span>1</span><div><strong>Tocá Votar</strong><p>Se abre la lista en otra pestaña.</p></div></li>
        <li><span>2</span><div><strong>Escribí tu cuenta y votá</strong><p>Usá el nombre de tu cuenta, no el del personaje.</p></div></li>
        <li><span>3</span><div><strong>Volvé y tocá Reclamar</strong><p>Revisamos tu voto y te damos los Eryns.</p></div></li>
      </ol>
    </section>

    <?php if($votLogged && !empty($votMine['premios'])) { ?>
    <section class="vot-section vot-history" aria-labelledby="vot-history-title">
      <header class="vot-section-head"><h2 id="vot-history-title">Tus premios</h2></header>
      <ul>
        <?php foreach($votMine['premios'] as $p) { $done=($p['state'] ?? '')==='done'; ?>
        <li><span><?php echo votE(date('d/m H:i',(int)$p['created'])); ?> · <?php echo votE($votSites[$p['site']]['name'] ?? $p['site']); ?></span><strong>+<?php echo $fmt($p['coins']); ?> Eryns</strong><em class="<?php echo $done?'is-done':'is-pending'; ?>"><?php echo $done?'Acreditados':'Se acreditan al desconectarte'; ?></em></li>
        <?php } ?>
      </ul>
    </section>
    <?php } ?>

    <p class="vot-note">Cada voto se cobra una sola vez: la lista lo confirma antes de darte los Eryns. Las listas cuentan un voto cada 12 horas por conexión, así que votar con varias cuentas desde la misma conexión no suma.</p>
  </div>
  <div class="vot-toast" role="status" aria-live="polite" hidden><img src="<?php echo votE($votImg.$votArt['gemSmall']); ?>" alt="" width="40" height="40"><div><b>¡Voto confirmado! +<span data-toast-coins>0</span> Eryns</b><span>Se acreditan en tu cuenta cuando te desconectes del juego.</span></div></div>
</div>
<script src="<?php echo votE(__PATH_TEMPLATE__.'js/votar.js?v='.substr(hash_file('sha256',__DIR__.'/../js/votar.js'),0,12)); ?>" defer></script>
