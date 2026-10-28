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
$votArt=['hero'=>'votar-darklord-3cd38766111c.webp','heroMobile'=>'votar-darklord-m-c121c9ccf867.webp','bg'=>'votar-fondo-f30ac25662e2.webp','emblem'=>'votar-emblema-top-4ebcd84af4b5.webp','podium'=>'votar-podio-4d8adde295c4.webp','gold'=>'votar-rango-oro-11585d11064a.webp','silver'=>'votar-rango-plata-c17777fad770.webp','bronze'=>'votar-rango-bronce-991fffb83f81.webp','gem'=>'votar-gema-b551bbad6c0d.webp','gemSmall'=>'votar-gema-64-4559844ddcec.webp'];
$votMineBefore=(int)$votStats['mine_total']-(int)($votClaimed['coins'] ?? 0);
$fmt=function($n){ return number_format((int)$n,0,',','.'); };
?>
<div class="vot-page" data-now="<?php echo (int)$votNow; ?>"<?php if($votClaimed) { ?> data-claimed="<?php echo (int)$votClaimed['coins']; ?>" data-claimed-site="<?php echo votE($votClaimed['site']); ?>"<?php } ?> data-gem="<?php echo votE($votImg.$votArt['gemSmall']); ?>">

  <section class="vot-hero">
    <div class="vot-hero-bg" aria-hidden="true" style="background-image:url('<?php echo votE($votImg.$votArt['bg']); ?>')"><span class="vot-hero-word">VOTÁ</span><i class="vot-glyph g1"></i><i class="vot-glyph g2"></i><i class="vot-glyph g3"></i></div>
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
    <span class="vot-feed-tag">En vivo</span>
    <div class="vot-feed-run"><div class="vot-feed-track">
      <?php for($loop=0;$loop<2;$loop++) foreach($votStats['feed'] as $f) { ?>
      <span class="vot-feed-item"<?php if($loop) echo ' aria-hidden="true"'; ?>><img src="<?php echo votE($votImg.$votArt['gemSmall']); ?>" alt="" width="18" height="20"><b><?php echo votE(panicVotarPublicName($f['account'],$votNames)); ?></b> votó <?php echo votE(panicVotarHace($f['created'],$votNow)); ?> <em>+<?php echo $fmt($f['coins']); ?> Eryns</em></span>
      <?php } ?>
    </div></div>
  </div>
  <?php } ?>

  <dl class="vot-stats">
    <div><dt>Votos este mes</dt><dd><?php echo $fmt($votStats['votes_month']); ?></dd></div>
    <div><dt>Jugadores que votaron</dt><dd><?php echo $fmt($votStats['voters_month']); ?></dd></div>
    <div class="is-gem"><dt>Eryns entregados por votar</dt><dd><img src="<?php echo votE($votImg.$votArt['gemSmall']); ?>" alt="" width="36" height="40"><?php echo $fmt($votStats['coins_all']); ?></dd></div>
  </dl>

  <section class="vot-band vot-band--light" aria-labelledby="vot-lists-title">
    <span class="vot-band-word" aria-hidden="true">TOP</span>
    <div class="vot-wrap">
      <?php if($votMsg) { ?><div class="vot-alert vot-alert--<?php echo votE($votTone); ?>" role="status"><?php echo votE($votMsg); ?></div><?php } ?>
      <header class="vot-head"><h2 id="vot-lists-title">Listas de votación</h2><p>Votá en cada lista y volvé acá a reclamar tus Eryns.</p></header>
      <div class="vot-sites">
        <?php foreach($votSites as $id=>$site) {
            $siteOn=$votRewardsOn && !empty($site['enabled']) && !empty($site['has_token']);
            $next=(int)($votMine['next'][$id] ?? 0); $wait=$votLogged && $siteOn && $next>$votNow;
            $period=(int)$site['hours']*3600;
            $pct=$wait?max(0,min(100,100-($next-$votNow)/$period*100)):100;
        ?>
        <article class="vot-site<?php echo $wait?' is-wait':' is-ready'; ?>" data-site="<?php echo votE($id); ?>" data-next="<?php echo $wait?$next:0; ?>" data-period="<?php echo $period; ?>">
          <img class="vot-site-emblem" src="<?php echo votE($votImg.$votArt['emblem']); ?>" alt="" width="400" height="415" loading="lazy">
          <div class="vot-site-info">
            <h3><?php echo votE($site['name']); ?></h3>
            <p class="vot-site-sub">Top de servidores de MU Online · un voto cada <?php echo (int)$site['hours']; ?> horas</p>
            <p class="vot-state"><?php if($wait) { ?>Próximo voto en <b class="vot-count" data-count><?php echo votE(gmdate('H:i:s',$next-$votNow)); ?></b><?php } else { ?><span class="vot-ready">Disponible</span> · ya podés votar<?php } ?></p>
            <div class="vot-bar" aria-hidden="true"><i class="vot-bar-fill" style="width:<?php echo round($pct,2); ?>%"></i></div>
          </div>
          <?php if($siteOn) { ?><div class="vot-site-prize"><img src="<?php echo votE($votImg.$votArt['gem']); ?>" alt="" width="160" height="177" loading="lazy"><b>+<?php echo $fmt($votReward); ?></b><span>Eryns por voto</span></div><?php } ?>
          <div class="vot-actions">
            <a class="vot-btn vot-btn--red" href="<?php echo votE($site['vote_url']); ?>" target="_blank" rel="noopener">Votar<span class="vot-sr"> (se abre en otra pestaña)</span></a>
            <?php if($votLogged && $siteOn) { ?>
            <form method="post" action="<?php echo votE(__BASE_URL__.'votar/'); ?>">
              <input type="hidden" name="votar_csrf" value="<?php echo votE($_SESSION['votar_csrf']); ?>">
              <input type="hidden" name="votar_action" value="reclamar">
              <input type="hidden" name="votar_site" value="<?php echo votE($id); ?>">
              <button class="vot-btn vot-btn--blue vot-btn--claim" type="submit" <?php if($wait) echo 'disabled'; ?>>Reclamar</button>
            </form>
            <?php } ?>
          </div>
        </article>
        <?php } ?>
        <div class="vot-soon">Pronto sumamos más listas para que ganes más Eryns por día.</div>
      </div>
    </div>
  </section>

  <section class="vot-band vot-band--rank" aria-labelledby="vot-rank-title">
    <span class="vot-band-word" aria-hidden="true">#1</span>
    <div class="vot-wrap">
      <header class="vot-head"><h2 id="vot-rank-title">Ranking del mes</h2><p>Los que más votan este mes. Se reinicia el 1° de cada mes.</p></header>
      <?php if(!$votStats['ranking']) { ?>
        <p class="vot-empty">Todavía nadie votó este mes. ¡Podés ser el primero del ranking!</p>
      <?php } else { $rest=array_slice($votStats['ranking'],3); $medal=[1=>'gold',2=>'silver',3=>'bronze']; ?>
      <div class="vot-podium">
        <ol class="vot-podium-stage" style="background-image:url('<?php echo votE($votImg.$votArt['podium']); ?>')">
          <?php for($pos=1;$pos<=3;$pos++) { $r=$votStats['ranking'][$pos-1] ?? null; $isMe=$r && $votLogged && strtolower($r['account'])===strtolower($votAccount); ?>
          <li class="vot-pod vot-pod-<?php echo $pos; ?><?php echo $r?'':' is-empty'; ?><?php echo $isMe?' is-me':''; ?>">
            <img src="<?php echo votE($votImg.$votArt[$medal[$pos]]); ?>" alt="" width="400" height="350" loading="lazy">
            <div class="vot-pod-tag"><b><?php echo $r?votE(panicVotarPublicName($r['account'],$votNames)):'Libre'; ?></b><span><?php echo $pos; ?>° · <?php echo $r?$fmt($r['votes']).((int)$r['votes']===1?' voto':' votos'):'sin votos'; ?></span></div>
          </li>
          <?php } ?>
        </ol>
      </div>
      <?php if($rest) { ?><ol class="vot-ranklist" start="4"><?php foreach($rest as $r) { $isMe=$votLogged && strtolower($r['account'])===strtolower($votAccount); ?><li<?php echo $isMe?' class="is-me"':''; ?>><span><?php echo (int)$r['pos']; ?></span><b><?php echo votE(panicVotarPublicName($r['account'],$votNames)); ?><?php if($isMe) { ?> <i>Vos</i><?php } ?></b><em><?php echo $fmt($r['votes']).((int)$r['votes']===1?' voto':' votos'); ?></em></li><?php } ?></ol><?php } ?>
      <?php if($votLogged) { ?><p class="vot-myrank"><?php echo $votStats['me'] ? 'Estás en el puesto <b>'.(int)$votStats['me']['pos'].'</b> con <b>'.$fmt($votStats['me']['votes']).'</b> '.((int)$votStats['me']['votes']===1?'voto':'votos').' este mes.' : 'Todavía no votaste este mes.'; ?></p><?php } ?>
      <?php } ?>
    </div>
  </section>

  <section class="vot-band vot-band--light vot-band--steps" aria-labelledby="vot-how-title">
    <div class="vot-wrap">
      <header class="vot-head"><h2 id="vot-how-title">Cómo funciona</h2></header>
      <ol class="vot-steps">
        <li><span>01</span><strong>Tocá Votar</strong><p>Se abre la lista en otra pestaña.</p></li>
        <li><span>02</span><strong>Escribí tu cuenta y votá</strong><p>Usá el nombre de tu cuenta, no el del personaje.</p></li>
        <li><span>03</span><strong>Volvé y tocá Reclamar</strong><p>Revisamos tu voto y te damos los Eryns.</p></li>
      </ol>
    </div>
  </section>

  <section class="vot-band vot-band--end">
    <div class="vot-wrap">
      <?php if($votLogged && !empty($votMine['premios'])) { ?>
      <header class="vot-head"><h2 id="vot-history-title">Tus premios</h2></header>
      <ul class="vot-history" aria-labelledby="vot-history-title">
        <?php foreach($votMine['premios'] as $p) { $done=($p['state'] ?? '')==='done'; ?>
        <li><span><?php echo votE(date('d/m H:i',(int)$p['created'])); ?> · <?php echo votE($votSites[$p['site']]['name'] ?? $p['site']); ?></span><strong>+<?php echo $fmt($p['coins']); ?> Eryns</strong><em class="<?php echo $done?'is-done':'is-pending'; ?>"><?php echo $done?'Acreditados':'Se acreditan al desconectarte'; ?></em></li>
        <?php } ?>
      </ul>
      <?php } ?>
      <p class="vot-note">Cada voto se cobra una sola vez: la lista lo confirma antes de darte los Eryns. Las listas cuentan un voto cada 12 horas por conexión, así que votar con varias cuentas desde la misma conexión no suma.</p>
    </div>
  </section>
  <div class="vot-toast" role="status" aria-live="polite" hidden><img src="<?php echo votE($votImg.$votArt['gem']); ?>" alt="" width="44" height="49"><div><b>¡Voto confirmado! +<span data-toast-coins>0</span> Eryns</b><span>Se acreditan en tu cuenta cuando te desconectes del juego.</span></div></div>
</div>
<script src="<?php echo votE(__PATH_TEMPLATE__.'js/votar.js?v='.substr(hash_file('sha256',__DIR__.'/../js/votar.js'),0,12)); ?>" defer></script>
