<?php
define('access',true);define('__BASE_URL__','https://preview.test/beta/');define('__PATH_TEMPLATE__',__BASE_URL__.'templates/mupanic/');
$inc=realpath(__DIR__.'/../../overlay/templates/mupanic/inc');require $inc.'/account.php';require $inc.'/recharge-management.php';
function isLoggedIn(){return true;}
class Connection {static function Database($name){return new self;}function query_fetch($sql,$params){$mode=getenv('VIP_FIXTURE');if($mode==='unknown')return [];return [['active'=>$mode==='active'?1:0,'expiry'=>'2026-10-31 23:31:00']];}}
$_SESSION=['username'=>'Agustin'];
$directory=sys_get_temp_dir().'/panic-vip-preview-'.bin2hex(random_bytes(6));mkdir($directory,0700);
$offer=PanicRechargeManagement::storefrontDefaults();if(getenv('VIP_FIXTURE')!=='pending'){$offer['vip']['price_coins']=25000;$offer['vip']['days']=30;}$offer['vip']['benefits'][1]=['title'=>'<Prueba>','detail'=>'Texto <seguro> & detalle','enabled'=>true];file_put_contents($directory.'/recharge-storefront.json',json_encode($offer));
$markup=file_get_contents($inc.'/vip.php');$markup=str_replace('(new PanicRechargeManagement())->storefront()','(new PanicRechargeManagement('.var_export($directory,true).'))->storefront()',$markup);$markup=str_replace('__DIR__',var_export($inc,true),$markup);
echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="'.__PATH_TEMPLATE__.'css/style.css"><link rel="stylesheet" href="'.__PATH_TEMPLATE__.'css/account.css"><link rel="stylesheet" href="'.__PATH_TEMPLATE__.'css/vip.css"></head><body class="is-account"><main class="module-surface" style="max-width:1200px;margin:auto">';
try{eval('?>'.$markup);}finally{foreach(glob($directory.'/*') as $file)unlink($file);rmdir($directory);}
echo '</main></body></html>';
