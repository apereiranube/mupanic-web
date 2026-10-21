<?php
define('access',true);define('__BASE_URL__','https://preview.test/beta/');define('__PATH_TEMPLATE__',__BASE_URL__.'templates/mupanic/');
$inc=dirname(__DIR__,2).'/overlay/templates/mupanic/inc';require $inc.'/account.php';require $inc.'/recharge-orders.php';require $inc.'/recharge-bank.php';
function isLoggedIn(){return true;}function config($k,$r){return ['panic'=>1];}
class Connection {static function Database($n){return new self;}function query_fetch($s,$p=[]){return [['AccountLevel'=>1,'AccountExpireDate'=>'2026-11-01 00:00:00']];}}
$_GET=getenv('RECHARGE_ADMIN_VIEW')==='bank'?['transferencias'=>'pendientes']:[];
$_SESSION=['username'=>getenv('RECHARGE_ADMIN_USER')?:'panic','shop_admin_csrf'=>str_repeat('a',64)];$fixturePost=json_decode(getenv('RECHARGE_ADMIN_POST')?:'null',true);$_POST=is_array($fixturePost)?$fixturePost:[];$_SERVER['REQUEST_METHOD']=is_array($fixturePost)?'POST':'GET';
$root=sys_get_temp_dir().'/panic-admin-preview-'.bin2hex(random_bytes(5));mkdir($root,0700);file_put_contents($root.'/recharge-worker-token',str_repeat('a',64));
try {
 $manager=new PanicRechargeManagement($root);$manager->report(['environment'=>'test','version'=>3],'ok',['waiting'=>1,'vip_config'=>['GS.CommandBuyVipSwitch'=>'1']]);
 file_put_contents($root.'/settings.json',json_encode(['environment'=>'test']));
 $bank=new PanicBankTransfers($root);
 $bank->saveConfig(['enabled'=>true,'holder'=>'Titular de prueba','bank'=>'Banco de prueba','alias'=>'mu.panic.prueba','guild_id'=>'1555229554920915057','support_channel_id'=>'1555229554920915058'],0);
 if(getenv('RECHARGE_ADMIN_VIEW')==='bank') {
  $guild='1555229554920915057';$user='1555229554920915059';$channel='1555229554920915060';
  $bank->bot(['action'=>'heartbeat','environment'=>'test','guild_id'=>$guild,'support_channel_id'=>'1555229554920915058']);
  $catalogue=(require $inc.'/recharge-config.php')['packages'];$cart=PanicRecharge::cart($catalogue,['wcoin-1000'=>1]);
  for($i=0;$i<2;$i++) {
   $o=$bank->begin('panic',$cart,str_repeat($i?'c':'b',32),'20123456786','test',$manager->worker());
   $claim=$bank->ready($o['id'],'panic');
   $bank->bot(['action'=>'claim','environment'=>'test','guild_id'=>$guild,'discord_id'=>$user,'code'=>$claim['code']]);
   $ticket=['action'=>'ticket','environment'=>'test','guild_id'=>$guild,'discord_id'=>$user,'id'=>$o['id'],'channel_id'=>$channel];$bank->bot($ticket);
   $bank->bot(array_replace($ticket,['action'=>'receipt','message_id'=>'1555229554920915061','has_attachment'=>true]));
   if($i)$bank->approve($o['id'],'ALREADY_PAID',true);else $pendingBankID=$o['id'];
  }
  if(($_POST['bank_order'] ?? '')==='FIXTURE_BANK_ORDER')$_POST['bank_order']=$pendingBankID;
 }
 $bankSource=file_get_contents($inc.'/recharge-bank-admin.php');
 $bankSource=str_replace(['new PanicBankTransfers()','__DIR__'],['new PanicBankTransfers('.var_export($root,true).')',var_export($inc,true)],$bankSource);
 $reviewSource=file_get_contents($inc.'/recharge-bank-review.php');
 $reviewSource=str_replace("require __DIR__.'/recharge-bank-admin.php';",'eval('.var_export('?>'.$bankSource,true).');',$reviewSource);
 $source=file_get_contents($inc.'/recharge-admin.php');
 $source=str_replace("require __DIR__.'/recharge-bank-review.php';",'eval('.var_export('?>'.$reviewSource,true).');',$source);
 $source=str_replace("require __DIR__.'/recharge-bank-admin.php';",'eval('.var_export('?>'.$bankSource,true).');',$source);$source=str_replace(['new PanicRechargeManagement()','new PanicRechargeOrders()'],['new PanicRechargeManagement('.var_export($root,true).')','new PanicRechargeOrders('.var_export($root,true).')'],$source);$source=str_replace('__DIR__',var_export($inc,true),$source);
 echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="https://preview.test/beta/"><link rel="stylesheet" href="templates/mupanic/css/style.css"><link rel="stylesheet" href="templates/mupanic/css/account.css"><link rel="stylesheet" href="templates/mupanic/css/recharge.css"><link rel="stylesheet" href="templates/mupanic/css/recharge-admin-friendly.css"><style>body{margin:0;background:#f3ece2}main{max-width:1000px;margin:30px auto;padding:30px;background:#fffaf4}@media(max-width:600px){main{margin:0;padding:20px}}</style></head><body class="is-account"><main>';
 eval('?>'.$source);echo '</main></body></html>';
}finally{foreach(glob($root.'/*')as $path)unlink($path);rmdir($root);}
