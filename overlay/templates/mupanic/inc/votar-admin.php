<?php
// MU PANIC - Votos: premio en Eryns por votar y registro de entregas (Administrar tienda -> Votos).
// Las listas de votación (tokens) se configuran en Administrar tienda -> Configuración -> Páginas de votación.
if(!defined('access') || !access || !function_exists('panicRechargeAdminAllowed') || !panicRechargeAdminAllowed() || ($_REQUEST['page'] ?? '')!=='usercp' || ($_REQUEST['subpage'] ?? '')!=='shopadmin') { http_response_code(403); exit('Acceso no autorizado.'); }
require_once __DIR__.'/votar-store.php';
$vaEsc=function($v){ return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); };
$vaDate=function($v){ return function_exists('panicRechargeArgDate')?panicRechargeArgDate($v):$v; };
$vaAdmin=(string)($_SESSION['username'] ?? '');
if(!is_string($_SESSION['votar_admin_csrf'] ?? null)) $_SESSION['votar_admin_csrf']=bin2hex(random_bytes(32));
$vaUrl=__BASE_URL__.'usercp/shopadmin/?vista=votos';
$vaSitesUrl=__BASE_URL__.'usercp/shopadmin/?vista=configuracion#shop-votar';
$vaMsg=null; $vaTone='ok';
$vaStore=null;
try { $vaStore=new PanicVotarStore(); } catch(Throwable $e) { $vaStore=null; }

if($vaStore && ($_SERVER['REQUEST_METHOD'] ?? '')==='POST' && ($_POST['votar_admin_action'] ?? '')==='premio') {
    if(!is_string($_POST['votar_admin_csrf'] ?? null) || !hash_equals($_SESSION['votar_admin_csrf'],$_POST['votar_admin_csrf'])) { $vaMsg='La sesión venció. Recargá la página.'; $vaTone='error'; }
    else {
        $reward=filter_var($_POST['reward'] ?? '',FILTER_VALIDATE_INT);
        try { $vaStore->saveReward(!empty($_POST['enabled']),$reward===false?0:$reward,$vaAdmin); $vaMsg='Premio guardado.'; }
        catch(InvalidArgumentException $e) { $vaMsg=$e->getMessage(); $vaTone='error'; }
        catch(Throwable $e) { $vaMsg='No se pudo guardar. Probá de nuevo.'; $vaTone='error'; }
    }
}
$vaCfg=null; $vaSum=null;
if($vaStore) { try { $vaCfg=$vaStore->config(); $vaSum=$vaStore->summary(); } catch(Throwable $e) { $vaCfg=null; } }
$vaSites=panicVotarSites();
?>
<link rel="stylesheet" href="<?php echo __PATH_TEMPLATE__; ?>css/admin-economy.css?v=<?php echo substr(hash_file('sha256',__DIR__.'/../css/admin-economy.css'),0,12); ?>">
<section class="eco vot-admin" id="votar-admin" aria-labelledby="vot-admin-title">
<header class="eco-hero"><div><span class="eco-eyebrow">MU PANIC / ADMINISTRACIÓN</span><h2 id="vot-admin-title">Votos</h2><p>Los Eryns que se dan por votar y lo que ya se entregó.</p></div><a class="eco-back" href="<?php echo $vaEsc(__BASE_URL__.'usercp/shopadmin/'); ?>">← Inicio del panel</a><div class="eco-hero-meta"><span>Los premios los acredita el VPS con la cuenta desconectada</span></div></header>
<?php if($vaMsg) { ?><div class="eco-alert <?php echo $vaTone==='error'?'eco-alert--error':''; ?>" role="status"><?php echo $vaEsc($vaMsg); ?></div><?php } ?>
<?php if(!$vaStore || !$vaCfg) { ?><div class="eco-alert eco-alert--error" role="alert"><strong>No se pudo abrir el registro de votos.</strong><span>Revisá que exista la carpeta privada payments-private y sus permisos.</span></div></section><?php return; } ?>

<?php $t=$vaSum['totals']; $vaActive=array_filter($vaCfg['sites'],function($s){ return $s['enabled'] && $s['has_token']; }); ?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:18px 0">
  <div class="eco-card"><small>Eryns entregados</small><strong><?php echo number_format($t['done_coins'],0,',','.'); ?></strong><span><?php echo (int)$t['done']; ?> premios acreditados</span></div>
  <div class="eco-card"><small>Esperando al VPS</small><strong><?php echo number_format($t['pending_coins'],0,',','.'); ?></strong><span><?php echo (int)$t['pending']; ?> premios (cuenta conectada o VPS sin instalar)</span></div>
  <div class="eco-card"><small>Eryns este mes</small><strong><?php echo number_format($t['month_coins'],0,',','.'); ?></strong><span>Confirmados y pendientes</span></div>
  <div class="eco-card"><small>Cuentas que votaron</small><strong><?php echo number_format($t['accounts'],0,',','.'); ?></strong><span>Desde el inicio</span></div>
</div>

<form method="post" action="<?php echo $vaEsc($vaUrl); ?>" class="eco-panel" style="padding:20px;margin-bottom:22px" autocomplete="off">
  <input type="hidden" name="votar_admin_csrf" value="<?php echo $vaEsc($_SESSION['votar_admin_csrf']); ?>">
  <input type="hidden" name="votar_admin_action" value="premio">
  <h3 style="margin-top:0">Premio por voto</h3>
  <p><label><input type="checkbox" name="enabled" value="1" <?php if($vaCfg['enabled']) echo 'checked'; ?>> <strong>Dar Eryns por votar</strong></label> — si está apagado, la página deja votar pero no da premios.</p>
  <p><label>Eryns por voto <input type="number" name="reward" min="1" max="1000" step="1" value="<?php echo (int)$vaCfg['reward']; ?>" style="width:110px"></label></p>
  <p style="margin-bottom:14px">Listas que dan premio: <strong><?php echo $vaActive?$vaEsc(implode(', ',array_map(function($s){ return $s['name']; },$vaActive))):'ninguna'; ?></strong> · <a href="<?php echo $vaEsc($vaSitesUrl); ?>">Configurar páginas de votación →</a></p>
  <button class="recharge-button" type="submit">Guardar</button>
  <?php if($vaCfg['updated_at']) { ?><p class="recharge-subtle">Último cambio: <?php echo $vaEsc($vaDate($vaCfg['updated_at'])); ?></p><?php } ?>
</form>

<div class="eco-panel" style="padding:16px 20px">
  <h3 style="margin-top:0">Últimos premios</h3>
  <?php if(empty($vaSum['rows'])) { ?><p>Todavía no hay premios.</p><?php } else { ?>
  <div style="overflow-x:auto"><table class="eco-table" style="width:100%;border-collapse:collapse">
    <thead><tr><th style="text-align:left">Fecha</th><th style="text-align:left">Cuenta</th><th style="text-align:left">Lista</th><th style="text-align:right">Eryns</th><th style="text-align:left">Estado</th><th style="text-align:left">Comprobante</th></tr></thead>
    <tbody>
    <?php foreach($vaSum['rows'] as $p) { $done=($p['state'] ?? '')==='done'; ?>
      <tr><td><?php echo $vaEsc(date('d/m/Y H:i',(int)$p['created'])); ?></td><td><?php echo $vaEsc($p['account']); ?></td><td><?php echo $vaEsc($vaSites[$p['site']]['name'] ?? $p['site']); ?></td><td style="text-align:right"><?php echo number_format((int)$p['coins'],0,',','.'); ?></td><td><?php echo $done?'Acreditado'.(isset($p['after_coin'])?' ('.number_format((int)$p['before_coin'],0,',','.').' → '.number_format((int)$p['after_coin'],0,',','.').')':''):'Esperando al VPS'; ?></td><td><code style="font-size:11px"><?php echo $vaEsc($p['claim_ref']); ?></code></td></tr>
    <?php } ?>
    </tbody>
  </table></div>
  <?php } ?>
</div>
</section>
