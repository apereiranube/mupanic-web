<?php
// MU PANIC - Administración de premios por votar (solo la cuenta admin; Administrar tienda -> Votos).
if(!defined('access') || !access || !function_exists('panicRechargeAdminAllowed') || !panicRechargeAdminAllowed() || ($_REQUEST['page'] ?? '')!=='usercp' || ($_REQUEST['subpage'] ?? '')!=='shopadmin') { http_response_code(403); exit('Acceso no autorizado.'); }
require_once __DIR__.'/votar-store.php';
$vaEsc=function($v){ return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); };
$vaAdmin=(string)($_SESSION['username'] ?? '');
if(!is_string($_SESSION['votar_admin_csrf'] ?? null)) $_SESSION['votar_admin_csrf']=bin2hex(random_bytes(32));
$vaUrl=__BASE_URL__.'usercp/shopadmin/?vista=votos';
$vaMsg=null; $vaTone='ok';
$vaStore=null;
try { $vaStore=new PanicVotarStore(); } catch(Throwable $e) { $vaStore=null; }

if($vaStore && ($_SERVER['REQUEST_METHOD'] ?? '')==='POST' && ($_POST['votar_admin_action'] ?? '')==='guardar') {
    if(!is_string($_POST['votar_admin_csrf'] ?? null) || !hash_equals($_SESSION['votar_admin_csrf'],$_POST['votar_admin_csrf'])) { $vaMsg='La sesión venció. Recargá la página.'; $vaTone='error'; }
    else {
        $reward=filter_var($_POST['reward'] ?? '',FILTER_VALIDATE_INT);
        $sites=[];
        foreach(panicVotarSites() as $id=>$s) $sites[$id]=['enabled'=>!empty($_POST['site'][$id]['enabled']),'token'=>is_string($_POST['site'][$id]['token'] ?? null)?$_POST['site'][$id]['token']:''];
        try { $vaStore->saveConfig(!empty($_POST['enabled']),$reward===false?0:$reward,$sites,$vaAdmin); $vaMsg='Configuración guardada.'; }
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
<header class="eco-hero"><div><span class="eco-eyebrow">MU PANIC / ADMINISTRACIÓN</span><h2 id="vot-admin-title">Votos</h2><p>Premios por votar en listas de servidores: cuánto se da, en qué listas y qué se entregó.</p></div><a class="eco-back" href="<?php echo $vaEsc(__BASE_URL__.'usercp/shopadmin/'); ?>">← Inicio del panel</a><div class="eco-hero-meta"><span>Los premios los acredita el VPS con la cuenta desconectada</span></div></header>
<?php if($vaMsg) { ?><div class="eco-alert <?php echo $vaTone==='error'?'eco-alert--error':''; ?>" role="status"><?php echo $vaEsc($vaMsg); ?></div><?php } ?>
<?php if(!$vaStore || !$vaCfg) { ?><div class="eco-alert eco-alert--error" role="alert"><strong>No se pudo abrir el registro de votos.</strong><span>Revisá que exista la carpeta privada payments-private y sus permisos.</span></div><?php return; } ?>

<?php $t=$vaSum['totals']; ?>
<div class="eco-kpis" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:18px 0">
  <div class="eco-card"><small>Eryns entregados</small><strong><?php echo number_format($t['done_coins'],0,',','.'); ?></strong><span><?php echo (int)$t['done']; ?> premios acreditados</span></div>
  <div class="eco-card"><small>Esperando al VPS</small><strong><?php echo number_format($t['pending_coins'],0,',','.'); ?></strong><span><?php echo (int)$t['pending']; ?> premios (cuenta conectada o VPS sin instalar)</span></div>
  <div class="eco-card"><small>Eryns este mes</small><strong><?php echo number_format($t['month_coins'],0,',','.'); ?></strong><span>Confirmados y pendientes</span></div>
  <div class="eco-card"><small>Cuentas que votaron</small><strong><?php echo number_format($t['accounts'],0,',','.'); ?></strong><span>Desde el inicio</span></div>
</div>

<form method="post" action="<?php echo $vaEsc($vaUrl); ?>" class="eco-panel" style="padding:20px;margin-bottom:22px" autocomplete="off">
  <input type="hidden" name="votar_admin_csrf" value="<?php echo $vaEsc($_SESSION['votar_admin_csrf']); ?>">
  <input type="hidden" name="votar_admin_action" value="guardar">
  <h3 style="margin-top:0">Configuración</h3>
  <p><label><input type="checkbox" name="enabled" value="1" <?php if($vaCfg['enabled']) echo 'checked'; ?>> <strong>Premios por votar activados</strong></label> — si está apagado, la página deja votar pero no da premios.</p>
  <p><label>Eryns por voto <input type="number" name="reward" min="1" max="1000" step="1" value="<?php echo (int)$vaCfg['reward']; ?>" style="width:110px"></label></p>
  <?php foreach($vaCfg['sites'] as $id=>$s) { ?>
  <fieldset style="margin:16px 0;padding:14px;border:1px solid #c6a57836">
    <legend><?php echo $vaEsc($s['name']); ?></legend>
    <p><label><input type="checkbox" name="site[<?php echo $vaEsc($id); ?>][enabled]" value="1" <?php if($s['enabled']) echo 'checked'; ?>> Dar premio por votar en esta lista</label></p>
    <p><label>Token de la API <input type="password" name="site[<?php echo $vaEsc($id); ?>][token]" value="" placeholder="<?php echo $s['has_token']?'Cargado (termina en …'.$vaEsc($s['token_tail']).'). Dejá vacío para conservarlo':'Pegá acá el token de la lista'; ?>" style="width:min(520px,100%)" autocomplete="new-password" spellcheck="false"></label></p>
    <p style="margin:0">Estado: <?php echo $s['has_token']?'<strong>token cargado</strong>':'<strong style="color:#ffb39a">falta el token</strong>'; ?> · Voto cada <?php echo (int)$s['hours']; ?> h · <a href="<?php echo $vaEsc($s['vote_url']); ?>" target="_blank" rel="noopener">Ver la ficha ↗</a></p>
  </fieldset>
  <?php } ?>
  <button class="recharge-button" type="submit">Guardar</button>
  <?php if($vaCfg['updated_at']) { ?><p class="recharge-subtle">Último cambio: <?php echo $vaEsc(function_exists('panicRechargeArgDate')?panicRechargeArgDate($vaCfg['updated_at']):$vaCfg['updated_at']); ?></p><?php } ?>
</form>

<?php if(!empty($vaCfg['last_api'])) { ?>
<div class="eco-panel" style="padding:16px 20px;margin-bottom:22px"><h3 style="margin-top:0">Última respuesta de una lista</h3><p style="margin:0 0 6px"><?php echo $vaEsc(($vaSites[$vaCfg['last_api']['site']]['name'] ?? $vaCfg['last_api']['site']).' · '.(function_exists('panicRechargeArgDate')?panicRechargeArgDate($vaCfg['last_api']['at']):$vaCfg['last_api']['at'])); ?></p><code style="display:block;white-space:pre-wrap;word-break:break-all;font-size:12px"><?php echo $vaEsc($vaCfg['last_api']['summary']); ?></code><p class="recharge-subtle">Sirve para revisar qué contesta la lista cuando alguien reclama. No incluye el token.</p></div>
<?php } ?>

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
