<?php
// MU PANIC - Administracion de referidos (solo la cuenta admin; se entra desde Administrar tienda -> Referidos).
if(!defined('access') || !access || !function_exists('panicRechargeAdminAllowed') || !panicRechargeAdminAllowed() || ($_REQUEST['page'] ?? '')!=='usercp' || ($_REQUEST['subpage'] ?? '')!=='shopadmin') { http_response_code(403); exit('Acceso no autorizado.'); }
require_once __DIR__.'/referidos-cola.php';
$raAdmin=(string)($_SESSION['username'] ?? '');
if(!is_string($_SESSION['ref_admin_csrf'] ?? null)) $_SESSION['ref_admin_csrf']=bin2hex(random_bytes(32));
$raMsg=null; $raTone='ok';
if(($_SERVER['REQUEST_METHOD'] ?? '')==='POST') {
    $id=(int)($_POST['ref_id'] ?? 0); $action=(string)($_POST['ref_action'] ?? '');
    if(!is_string($_POST['ref_admin_csrf'] ?? null) || !hash_equals($_SESSION['ref_admin_csrf'],$_POST['ref_admin_csrf'])) { $raMsg='La sesión venció. Recargá la página.'; $raTone='error'; }
    elseif($id<1 || !in_array($action,['ANULAR','APROBAR'],true)) { $raMsg='Acción inválida.'; $raTone='error'; }
    else {
        try { (new PanicReferidosCola())->requestAdmin($id,$action,$raAdmin); $raMsg=($action==='APROBAR'?'Aprobación':'Anulación').' del referido #'.$id.' enviada. Se aplica en menos de un minuto.'; }
        catch(Throwable $e) { $raMsg='No se pudo registrar la acción. Probá de nuevo.'; $raTone='error'; }
    }
}
$raFiltro=in_array($_GET['estado'] ?? '',['EN_CAMINO','REVISAR','CUMPLIDO','APROBADO','PAGADO','NO_VALIDO','ANULADO'],true)?$_GET['estado']:'';
$raRows=null; $raPend=[];
try {
    $db=Connection::Database('MuOnline');
    $sql='SELECT TOP 300 Id, Invitado, Invita, Estado, Motivo, Resets, Dias, CONVERT(varchar(16),CreadoAR,120) AS Creado, PagaInvita, PagoInvitadoAR, PagoInvitaAR, InvitadoIP, InvitadoPC, InvitaIP, InvitaPC, IPCompartida, PCIgual, PCParecida FROM [MuOnline43].[dbo].[vMUPanicRefAdmin]';
    $raRows=$raFiltro!==''?$db->query_fetch($sql.' WHERE Estado = ? ORDER BY Id DESC',[$raFiltro]):$db->query_fetch($sql.' ORDER BY Id DESC');
    if(!is_array($raRows)) $raRows=[];
} catch(Throwable $e) { $raRows=null; }
try { foreach((new PanicReferidosCola())->pendingAdmin() as $p) $raPend[(int)$p['vinculo']]=$p['action']; } catch(Throwable $e) {}
$raCount=[]; foreach(($raRows ?? []) as $r) $raCount[$r['Estado']]=($raCount[$r['Estado']] ?? 0)+1;
$raEsc=function($v){ return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); };
$raUrl=__BASE_URL__.'usercp/shopadmin/?ref=1';
?>
<link rel="stylesheet" href="<?php echo __PATH_TEMPLATE__; ?>css/admin-economy.css?v=<?php echo substr(hash_file('sha256',__DIR__.'/../css/admin-economy.css'),0,12); ?>">
<link rel="stylesheet" href="<?php echo __PATH_TEMPLATE__; ?>css/referidos.css?v=<?php echo substr(hash_file('sha256',__DIR__.'/../css/referidos.css'),0,12); ?>">
<section class="eco ref-admin" id="referidos-admin" aria-labelledby="ref-admin-title">
<header class="eco-hero"><div><span class="eco-eyebrow">MU PANIC / ADMINISTRACIÓN</span><h2 id="ref-admin-title">Referidos</h2><p>Quién invitó a quién, cómo vienen y qué hay que revisar.</p></div><a class="eco-back" href="<?php echo $raEsc(__BASE_URL__.'usercp/shopadmin/'); ?>">← Administrar tienda</a><div class="eco-hero-meta"><span>Se actualiza cada minuto</span><span>Acceso exclusivo: panic</span></div></header>
<?php if($raMsg) { ?><div class="eco-alert <?php echo $raTone==='error'?'eco-alert--error':''; ?>" role="status"><?php echo $raEsc($raMsg); ?></div><?php } ?>
<nav class="eco-tabs" aria-label="Filtrar referidos"><a href="<?php echo $raEsc($raUrl); ?>" <?php if($raFiltro==='') echo 'aria-current="page"'; ?>>Todos</a><?php foreach(['REVISAR'=>'Para revisar','EN_CAMINO'=>'En camino','PAGADO'=>'Pagados','NO_VALIDO'=>'No válidos','ANULADO'=>'Anulados'] as $k=>$l) { ?><a href="<?php echo $raEsc($raUrl.'&estado='.$k); ?>" <?php if($raFiltro===$k) echo 'aria-current="page"'; ?>><?php echo $l; ?></a><?php } ?></nav>
<?php if($raRows===null) { ?><div class="eco-alert eco-alert--error" role="alert"><strong>No se pudieron leer los referidos.</strong><span>Revisá que las vistas de referidos estén instaladas (Referidos V2 en el VPS).</span></div>
<?php } else { ?>
<div class="eco-cards"><?php foreach(['EN_CAMINO'=>'En camino','REVISAR'=>'Para revisar','PAGADO'=>'Pagados','NO_VALIDO'=>'No válidos'] as $k=>$l) { ?><article class="eco-card"><strong><?php echo (int)($raCount[$k] ?? 0); ?></strong><span><?php echo $l; ?></span></article><?php } ?></div>
<section class="eco-panel"><h3>Vínculos</h3>
<?php if(!$raRows) { ?><p class="eco-empty">Todavía no hay referidos<?php echo $raFiltro?' con ese estado':''; ?>.</p><?php } else { ?>
<div class="eco-table-scroll"><table class="eco-table ref-admin-table"><thead><tr><th>#</th><th>Invitado</th><th>Invita</th><th>Estado</th><th>Avance</th><th>Señales</th><th>PC / IP</th><th>Acciones</th></tr></thead><tbody>
<?php foreach($raRows as $r) { $id=(int)$r['Id']; $flags=[]; if((int)$r['PCIgual']) $flags[]='<b class="ref-flag ref-flag--no">Misma PC</b>'; if((int)$r['IPCompartida']) $flags[]='<b class="ref-flag ref-flag--no">IP compartida</b>'; if((int)$r['PCParecida'] && !(int)$r['PCIgual']) $flags[]='<b class="ref-flag">PC parecida</b>'; ?>
<tr><td><?php echo $id; ?><br><small><?php echo $raEsc($r['Creado']); ?></small></td>
<td><?php echo $raEsc($r['Invitado']); ?></td><td><?php echo $raEsc($r['Invita']); ?></td>
<td><?php echo $raEsc($r['Estado']); ?><?php if($r['Motivo']) { ?><br><small><?php echo $raEsc($r['Motivo']); ?></small><?php } ?></td>
<td>Resets <?php echo (int)$r['Resets']; ?>/2<br>Días <?php echo (int)$r['Dias']; ?>/3</td>
<td><?php echo $flags?implode(' ',$flags):'—'; ?></td>
<td><small>Invitado: <?php echo $raEsc($r['InvitadoIP'] ?: '—'); ?><br><?php echo $raEsc($r['InvitadoPC'] ?: '—'); ?><br>Invita: <?php echo $raEsc($r['InvitaIP'] ?: '—'); ?><br><?php echo $raEsc($r['InvitaPC'] ?: '—'); ?></small></td>
<td><?php if(isset($raPend[$id])) { ?><small>Pendiente: <?php echo $raEsc($raPend[$id]); ?></small><?php } else { ?>
<?php if(in_array($r['Estado'],['EN_CAMINO','REVISAR','NO_VALIDO'],true)) { ?><form method="post" action="<?php echo $raEsc($raUrl); ?>" class="ref-admin-form"><input type="hidden" name="ref_admin_csrf" value="<?php echo $raEsc($_SESSION['ref_admin_csrf']); ?>"><input type="hidden" name="ref_id" value="<?php echo $id; ?>"><button name="ref_action" value="APROBAR">Aprobar</button></form><?php } ?>
<?php if(!in_array($r['Estado'],['PAGADO','ANULADO'],true)) { ?><form method="post" action="<?php echo $raEsc($raUrl); ?>" class="ref-admin-form"><input type="hidden" name="ref_admin_csrf" value="<?php echo $raEsc($_SESSION['ref_admin_csrf']); ?>"><input type="hidden" name="ref_id" value="<?php echo $id; ?>"><button name="ref_action" value="ANULAR" class="ref-admin-danger">Anular</button></form><?php } ?>
<?php } ?></td></tr>
<?php } ?>
</tbody></table></div><?php } ?>
</section>
<p class="eco-note">Aprobar paga aunque no haya llegado a la meta o el anti trampa lo haya frenado. Anular cancela el referido antes de que se pague. El VIP se entrega con la cuenta desconectada.</p>
<?php } ?>
</section>
