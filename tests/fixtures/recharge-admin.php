<?php
define('access',true);define('__BASE_URL__','https://preview.test/beta/');define('__PATH_TEMPLATE__',__BASE_URL__.'templates/mupanic/');
$inc=dirname(__DIR__,2).'/overlay/templates/mupanic/inc';require $inc.'/account.php';require $inc.'/recharge-orders.php';require $inc.'/recharge-bank.php';
function isLoggedIn(){return true;}function config($k,$r){return ['panic'=>1];}
class Connection {static function Database($n){return new self;}function query_fetch($s,$p=[]){if(strpos($s,'vMUPanicEco')!==false)throw new RuntimeException('Economy data not available in this navigation fixture');return stripos($s,'SELECT AccountLevel')!==false?[['AccountLevel'=>1,'AccountExpireDate'=>'2026-11-01 00:00:00']]:[];}}
$fixtureView=getenv('RECHARGE_ADMIN_VIEW')?:'settings';
$_GET=$fixtureView==='bank'?['transferencias'=>'pendientes']:['vista'=>['settings'=>'configuracion','home'=>'inicio','purchases'=>'compras','economy'=>'economia','referrals'=>'referidos'][$fixtureView] ?? 'configuracion'];
$extraGet=json_decode(getenv('RECHARGE_ADMIN_GET')?:'{}',true);if(is_array($extraGet))$_GET=array_replace($_GET,$extraGet);
$_REQUEST=['page'=>'usercp','subpage'=>'shopadmin'];
$_SESSION=['username'=>getenv('RECHARGE_ADMIN_USER')?:'panic','shop_admin_csrf'=>str_repeat('a',64)];$fixturePost=json_decode(getenv('RECHARGE_ADMIN_POST')?:'null',true);$_POST=is_array($fixturePost)?$fixturePost:[];$_SERVER['REQUEST_METHOD']=is_array($fixturePost)?'POST':'GET';
$root=sys_get_temp_dir().'/panic-admin-preview-'.bin2hex(random_bytes(5));mkdir($root,0700);file_put_contents($root.'/recharge-worker-token',str_repeat('a',64));
try {
 $manager=new PanicRechargeManagement($root);$manager->report(['environment'=>'test','version'=>3],'ok',['waiting'=>1,'vip_config'=>['GS.CommandBuyVipSwitch'=>'1']]);
 file_put_contents($root.'/settings.json',json_encode(['environment'=>'test']));
 $bank=new PanicBankTransfers($root);
 $bank->saveConfig(['enabled'=>true,'holder'=>'Titular de prueba','bank'=>'Banco de prueba','alias'=>'mu.panic.prueba','guild_id'=>'1555229554920915057','support_channel_id'=>'1555229554920915058'],0);
 if(in_array($fixtureView,['bank','home','purchases'],true)) {
  $guild='1555229554920915057';$user='1555229554920915059';$channel='1555229554920915060';
  $bank->bot(['action'=>'heartbeat','environment'=>'test','guild_id'=>$guild,'support_channel_id'=>'1555229554920915058']);
  $catalogue=(require $inc.'/recharge-config.php')['packages'];$cart=PanicRecharge::cart($catalogue,['wcoin-1000'=>1]);
  for($i=0;$i<3;$i++) {
   $o=$bank->begin('panic',$cart,str_repeat(['b','c','d'][$i],32),'20123456786','test',$manager->worker(),PanicBankTransfers::TERMS_VERSION);
   $claim=$bank->ready($o['id'],'panic');
   $bank->bot(['action'=>'claim','environment'=>'test','guild_id'=>$guild,'discord_id'=>$user,'code'=>$claim['code']]);
   $ticket=['action'=>'ticket','environment'=>'test','guild_id'=>$guild,'discord_id'=>$user,'id'=>$o['id'],'channel_id'=>$channel];$bank->bot($ticket);
   $bank->bot(array_replace($ticket,['action'=>'receipt','message_id'=>'1555229554920915061','has_attachment'=>true]));
   if($i){$bank->approve($o['id'],'ALREADY_PAID_'.$i,true);if($i===2){$job=$bank->jobs('test',3);foreach($job as $j)if($j['id']===$o['id'])$bank->acknowledge($j+['state'=>'credited','before_coin'=>100,'after_coin'=>1100]);}}else $pendingBankID=$o['id'];
  }
  $uala=PanicRecharge::order('ualaUser',$cart,'uala_bis',false);
  $uala+=['merchant_id'=>'fixture','request_key'=>str_repeat('e',64),'callback_token'=>str_repeat('f',64),'amount_unit'=>'ARS','lines'=>$cart['lines']];
  $uala['payment_state']='approved';$uala['delivery_state']='credited';$uala['payment_id']='UALA_FIXTURE';$uala['created_at']=gmdate('c',time()-600);
  file_put_contents($root.'/recharge-orders.json',json_encode([$uala['id']=>$uala]));
  if(($_POST['bank_order'] ?? '')==='FIXTURE_BANK_ORDER')$_POST['bank_order']=$pendingBankID;
 }
 // Inline view templates so every fixture uses only its private temporary ledgers.
 $inlineViews=['recharge-bank-admin.php','recharge-bank-review.php','recharge-admin-settings.php','recharge-admin-overview.php','recharge-admin-storefront.php'];
 $compile=function($name)use(&$compile,$inc,$root,$inlineViews){
  $text=file_get_contents($inc.'/'.$name);
  foreach($inlineViews as $child)if(strpos($text,"require __DIR__.'/".$child."';")!==false)$text=str_replace("require __DIR__.'/".$child."';",'eval('.var_export('?>'.$compile($child),true).');',$text);
  $text=str_replace(['new PanicBankTransfers()','new PanicRechargeManagement()','new PanicRechargeOrders()'],['new PanicBankTransfers('.var_export($root,true).')','new PanicRechargeManagement('.var_export($root,true).')','new PanicRechargeOrders('.var_export($root,true).')'],$text);
  return str_replace('__DIR__',var_export($inc,true),$text);
 };
 $source=$compile('recharge-admin.php');
 echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="https://preview.test/beta/"><link rel="stylesheet" href="templates/mupanic/css/style.css"><link rel="stylesheet" href="templates/mupanic/css/account.css"><link rel="stylesheet" href="templates/mupanic/css/recharge.css"><link rel="stylesheet" href="templates/mupanic/css/recharge-admin-friendly.css"><style>body{margin:0;background:#f3ece2}main{max-width:1000px;margin:30px auto;padding:30px;background:#fffaf4}@media(max-width:600px){main{margin:0;padding:20px}}</style></head><body class="is-account"><main>';
 eval('?>'.$source);echo '</main></body></html>';
}finally{foreach(glob($root.'/*')as $path)unlink($path);rmdir($root);}
