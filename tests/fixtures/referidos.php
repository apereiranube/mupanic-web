<?php
// Vista previa aislada de Mi cuenta -> Referidos (sin SQL real). REF_MODE=invita|nuevo|admin
define('access',true);define('__BASE_URL__','https://preview.test/beta/');define('__PATH_TEMPLATE__',__BASE_URL__.'templates/mupanic/');
$inc=realpath(__DIR__.'/../../overlay/templates/mupanic/inc');require $inc.'/account.php';
function isLoggedIn(){return true;}function config($k,$b=false){return $k==='admins'?['panic'=>1]:[];}
function loadConfig($n){return array_map(function($k){return ['active'=>true,'visibility'=>'user','type'=>'internal','link'=>'usercp/'.$k.'/','newtab'=>false,'phrase'=>$k];},['vote','myaccount']);}
function lang($k,$r=false){return $k;}
$mode=getenv('REF_MODE')?:'invita';
class Connection{static function Database($n){return new self;}function query_fetch($sql,$p=[]){
  global $mode;
  if(strpos($sql,'vMUPanicRefCodigo')!==false) return [['Codigo'=>'K7MPQ2XA']];
  if(strpos($sql,'vMUPanicRefAdmin')!==false) return [
    ['Id'=>3,'Invitado'=>'nuevo3','Invita'=>'Agustin','Estado'=>'REVISAR','Motivo'=>'PC_PARECIDA (primer bloque del HardID igual)','Resets'=>2,'Dias'=>3,'Creado'=>'2026-10-21 10:50','PagaInvita'=>1,'PagoInvitadoAR'=>null,'PagoInvitaAR'=>null,'InvitadoIP'=>'181.46.10.20','InvitadoPC'=>'12BA1074-AAAA1111-BBBB2222-CCCC3333','InvitaIP'=>'190.2.3.4','InvitaPC'=>'12BA1074-DDDD4444-EEEE5555-FFFF6666','IPCompartida'=>0,'PCIgual'=>0,'PCParecida'=>1],
    ['Id'=>2,'Invitado'=>'amigo2','Invita'=>'Agustin','Estado'=>'EN_CAMINO','Motivo'=>null,'Resets'=>1,'Dias'=>2,'Creado'=>'2026-10-20 18:00','PagaInvita'=>1,'PagoInvitadoAR'=>null,'PagoInvitaAR'=>null,'InvitadoIP'=>'200.1.1.1','InvitadoPC'=>'7781AA00-1234ABCD-5678EF00-11223344','InvitaIP'=>'190.2.3.4','InvitaPC'=>'12BA1074-DDDD4444-EEEE5555-FFFF6666','IPCompartida'=>0,'PCIgual'=>0,'PCParecida'=>0],
    ['Id'=>1,'Invitado'=>'reftest1','Invita'=>'louismk','Estado'=>'PAGADO','Motivo'=>'Aprobado para prueba','Resets'=>0,'Dias'=>0,'Creado'=>'2026-10-21 10:53','PagaInvita'=>1,'PagoInvitadoAR'=>'x','PagoInvitaAR'=>'x','InvitadoIP'=>null,'InvitadoPC'=>null,'InvitaIP'=>'181.46.185.3','InvitaPC'=>'12BA1074-F6403CAF-6F45C270-45CA2039','IPCompartida'=>0,'PCIgual'=>0,'PCParecida'=>0]];
  if(strpos($sql,'WHERE Invitado')!==false) return $mode==='nuevo'?[]:[];
  if(strpos($sql,'WHERE Invita =')!==false) return $mode==='nuevo'?[]:[
    ['Id'=>3,'Personaje'=>'DarkLobo','Estado'=>'EN_CAMINO','Motivo'=>null,'Resets'=>1,'Dias'=>2,'DiasInvita'=>7,'PagaInvita'=>1,'PagoInvitaAR'=>null,'Creado'=>'21/10/2026'],
    ['Id'=>2,'Personaje'=>'Arquera','Estado'=>'PAGADO','Motivo'=>null,'Resets'=>2,'Dias'=>3,'DiasInvita'=>7,'PagaInvita'=>1,'PagoInvitaAR'=>'x','Creado'=>'15/10/2026'],
    ['Id'=>1,'Personaje'=>null,'Estado'=>'NO_VALIDO','Motivo'=>'MISMA_IP','Resets'=>2,'Dias'=>4,'DiasInvita'=>7,'PagaInvita'=>1,'PagoInvitaAR'=>null,'Creado'=>'10/10/2026']];
  return [];
}}
$_SESSION=['username'=>$mode==='admin'?'panic':'Agustin'];$_REQUEST=['page'=>'usercp','subpage'=>$mode==='admin'?'shopadmin':'referidos'];$_SERVER['REQUEST_METHOD']='GET';
if($mode==='nuevo') $_SESSION['mupanic_ref']='MY5BC9WG';
if($mode==='admin') $_GET['ref']='1';
echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="'.__PATH_TEMPLATE__.'css/style.css"><link rel="stylesheet" href="'.__PATH_TEMPLATE__.'css/account.css"></head><body class="is-inner is-account"><main><div class="inner-content"><div class="shell"><div class="account-layout"><aside class="account-nav"><div class="account-nav-head"><small>MU PANIC</small><strong>Mi cuenta</strong></div><nav>';
panicAccountNavigation();
echo '</nav></aside><div class="module-surface"><div class="account-module" data-account-module="referidos">';
if($mode==='admin') require $inc.'/referidos-admin.php'; else require $inc.'/referidos.php';
echo '</div></div></div></div></div></main></body></html>';
