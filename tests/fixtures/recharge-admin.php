<?php
define('access',true);define('__BASE_URL__','https://preview.test/beta/');define('__PATH_TEMPLATE__',__BASE_URL__.'templates/mupanic/');
$inc=dirname(__DIR__,2).'/overlay/templates/mupanic/inc';require $inc.'/account.php';require $inc.'/recharge-orders.php';
function isLoggedIn(){return true;}function config($k,$r){return ['panic'=>1];}
class Connection {static function Database($n){return new self;}function query_fetch($s,$p=[]){return [['AccountLevel'=>1,'AccountExpireDate'=>'2026-11-01 00:00:00']];}}
$_SESSION=['username'=>getenv('RECHARGE_ADMIN_USER')?:'panic','shop_admin_csrf'=>str_repeat('a',64)];$fixturePost=json_decode(getenv('RECHARGE_ADMIN_POST')?:'null',true);$_POST=is_array($fixturePost)?$fixturePost:[];$_SERVER['REQUEST_METHOD']=is_array($fixturePost)?'POST':'GET';
$root=sys_get_temp_dir().'/panic-admin-preview-'.bin2hex(random_bytes(5));mkdir($root,0700);file_put_contents($root.'/recharge-worker-token',str_repeat('a',64));
try {
 $manager=new PanicRechargeManagement($root);$manager->report(['environment'=>'test','version'=>2],'ok',['waiting'=>1,'vip_config'=>['GS.CommandBuyVipSwitch'=>'1']]);
 $source=file_get_contents($inc.'/recharge-admin.php');$source=str_replace(['new PanicRechargeManagement()','new PanicRechargeOrders()'],['new PanicRechargeManagement('.var_export($root,true).')','new PanicRechargeOrders('.var_export($root,true).')'],$source);$source=str_replace('__DIR__',var_export($inc,true),$source);
 echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="https://preview.test/beta/"><link rel="stylesheet" href="templates/mupanic/css/style.css"><link rel="stylesheet" href="templates/mupanic/css/account.css"><link rel="stylesheet" href="templates/mupanic/css/recharge.css"><style>body{margin:0;background:#f3ece2}main{max-width:1000px;margin:30px auto;padding:30px;background:#fffaf4}@media(max-width:600px){main{margin:0;padding:20px}}</style></head><body class="is-account"><main>';
 eval('?>'.$source);echo '</main></body></html>';
}finally{foreach(glob($root.'/*')as $path)unlink($path);rmdir($root);}
