<?php
// MU PANIC - Configuración -> Páginas de votación: listas, si dan premio y su token (se guarda en payments-private, nunca en git).
if(!defined('access') || !access || !function_exists('panicRechargeAdminAllowed') || !panicRechargeAdminAllowed()) return;
require_once __DIR__.'/votar-store.php';
$vsEsc=function($v){ return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); };
$vsDate=function($v){ return function_exists('panicRechargeArgDate')?panicRechargeArgDate($v):$v; };
if(!is_string($_SESSION['votar_admin_csrf'] ?? null)) $_SESSION['votar_admin_csrf']=bin2hex(random_bytes(32));
$vsUrl=__BASE_URL__.'usercp/shopadmin/?vista=configuracion#shop-votar';
$vsMsg=null; $vsError=false; $vsStore=null; $vsCfg=null;
try { $vsStore=new PanicVotarStore(); } catch(Throwable $e) { $vsStore=null; }
if($vsStore && ($_SERVER['REQUEST_METHOD'] ?? '')==='POST' && ($_POST['admin_action'] ?? '')==='votar_sitios') {
    if(!is_string($_POST['votar_admin_csrf'] ?? null) || !hash_equals($_SESSION['votar_admin_csrf'],$_POST['votar_admin_csrf'])) { $vsMsg='La sesión venció. Recargá la página.'; $vsError=true; }
    else {
        $sites=[];
        foreach(panicVotarSites() as $id=>$s) $sites[$id]=['enabled'=>!empty($_POST['site'][$id]['enabled']),'token'=>is_string($_POST['site'][$id]['token'] ?? null)?$_POST['site'][$id]['token']:''];
        try { $vsStore->saveSites($sites,(string)($_SESSION['username'] ?? '')); $vsMsg='Páginas de votación guardadas.'; }
        catch(InvalidArgumentException $e) { $vsMsg=$e->getMessage(); $vsError=true; }
        catch(Throwable $e) { $vsMsg='No se pudo guardar. Probá de nuevo.'; $vsError=true; }
    }
}
if($vsStore) { try { $vsCfg=$vsStore->config(); } catch(Throwable $e) { $vsCfg=null; } }
$vsSites=panicVotarSites();
?>
<section id="shop-votar" class="recharge-section"><div class="recharge-section-head"><div><span class="eyebrow">06 / VOTACIÓN</span><h3>Páginas de votación</h3></div><span><?php echo $vsCfg?count(array_filter($vsCfg['sites'],function($s){ return $s['enabled'] && $s['has_token']; })).' de '.count($vsCfg['sites']).' dan premio':'Sin datos'; ?></span></div>
<p>Las listas donde está MU PANIC. Cada una necesita su token para confirmar votos. El premio en Eryns se configura en <a href="<?php echo $vsEsc(__BASE_URL__.'usercp/shopadmin/?vista=votos'); ?>">Votos</a>.</p>
<?php if($vsMsg) { ?><div class="recharge-notice<?php echo $vsError?' shop-error':''; ?>" role="status"><?php echo $vsEsc($vsMsg); ?></div><?php } ?>
<?php if(!$vsCfg) { ?><p class="recharge-subtle">No se pudo abrir la configuración de votos. Revisá la carpeta privada payments-private.</p><?php } else { ?>
<form method="post" action="<?php echo $vsEsc($vsUrl); ?>" autocomplete="off">
  <input type="hidden" name="votar_admin_csrf" value="<?php echo $vsEsc($_SESSION['votar_admin_csrf']); ?>">
  <input type="hidden" name="admin_action" value="votar_sitios">
  <?php foreach($vsCfg['sites'] as $id=>$s) { ?>
  <fieldset style="margin:16px 0;padding:14px;border:1px solid #c6a57836">
    <legend><?php echo $vsEsc($s['name']); ?></legend>
    <p><label><input type="checkbox" name="site[<?php echo $vsEsc($id); ?>][enabled]" value="1" <?php if($s['enabled']) echo 'checked'; ?>> Dar premio por votar en esta lista</label></p>
    <p><label>Token de la API <input type="password" name="site[<?php echo $vsEsc($id); ?>][token]" value="" placeholder="<?php echo $s['has_token']?'Cargado (termina en …'.$vsEsc($s['token_tail']).'). Dejá vacío para conservarlo':'Pegá acá el token de la lista'; ?>" style="width:min(520px,100%)" autocomplete="new-password" spellcheck="false"></label></p>
    <p class="recharge-subtle" style="margin:0">Estado: <?php echo $s['has_token']?'token cargado':'falta el token'; ?> · Voto cada <?php echo (int)$s['hours']; ?> h · <a href="<?php echo $vsEsc($s['vote_url']); ?>" target="_blank" rel="noopener">Ver la ficha ↗</a></p>
  </fieldset>
  <?php } ?>
  <button class="recharge-button" type="submit">Guardar páginas de votación</button>
  <?php if(!empty($vsCfg['sites_updated_at'])) { ?><p class="recharge-subtle">Último cambio: <?php echo $vsEsc($vsDate($vsCfg['sites_updated_at'])); ?></p><?php } ?>
</form>
<?php if(!empty($vsCfg['last_api'])) { ?>
<details style="margin-top:16px"><summary>Última respuesta de una lista</summary>
<p style="margin:8px 0 6px"><?php echo $vsEsc(($vsSites[$vsCfg['last_api']['site']]['name'] ?? $vsCfg['last_api']['site']).' · '.$vsDate($vsCfg['last_api']['at'])); ?></p>
<code style="display:block;white-space:pre-wrap;word-break:break-all;font-size:12px"><?php echo $vsEsc($vsCfg['last_api']['summary']); ?></code>
<p class="recharge-subtle">Qué contestó la lista la última vez que alguien reclamó. No incluye el token.</p></details>
<?php } ?>
<?php } ?>
</section>
