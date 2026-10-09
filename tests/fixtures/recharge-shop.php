<?php
define('access',true);define('__BASE_URL__','https://preview.test/beta/');define('__PATH_TEMPLATE__',__BASE_URL__.'templates/mupanic/');
$inc=dirname(__DIR__,2).'/overlay/templates/mupanic/inc';
require $inc.'/account.php';
function isLoggedIn(){return true;}
function config($k,$r){return false;}
class Connection {static function Database($n){return new self;} function query_fetch($s,$p=[]){return [['balance'=>1011]];}}
$_SESSION=['username'=>'Agustin','recharge_csrf'=>str_repeat('a',64),'recharge_nonce'=>str_repeat('b',32)];
require $inc.'/recharge-orders.php';
$catalogue=(require $inc.'/recharge-config.php')['packages'];
$cart=PanicRecharge::cart($catalogue,['wcoin-20000'=>5]);
$one=PanicRecharge::order('Agustin',$cart,'uala_bis',false);$one['lines']=$cart['lines'];$one['payment_state']='approved';
$two=$one;$two['id']='PANIC-'.str_repeat('c',32);$two['coins']=55000;$two['price_cents']=5500000;$two['delivery_state']='credited';$two['lines']=PanicRecharge::cart($catalogue,['wcoin-20000'=>2,'wcoin-5000'=>3])['lines'];
$three=$one;$three['id']='PANIC-'.str_repeat('d',32);$three['payment_state']='pending';$three['checkout_url']='https://stage.uala-checkout.com/fixture';
$shopHistory=['total'=>3,'orders'=>[$one,$two,$three]];$shopCanBuy=true;$shopSettings=['environment'=>'test'];$shopAdmin=false;$shopCreated=null;$shopMessage=null;$shopQuantities=['wcoin-20000'=>'5'];$shopPage=1;
$fixtureMode=getenv('RECHARGE_FIXTURE');
if($fixtureMode==='closed') $shopCanBuy=false;
if($fixtureMode==='ready') $shopCreated=$three;
if($fixtureMode==='empty') { $shopHistory=['total'=>0,'orders'=>[]];$shopQuantities=[]; }
$markup=file_get_contents($inc.'/recharge.php');$markup=str_replace("require __DIR__.'/recharge-shop-controller.php';",'',$markup);$markup=str_replace('$publicOffers=[];', '$publicOffers=$rechargeConfig["packages"];',$markup);$markup=str_replace('__DIR__',var_export($inc,true),$markup);
echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="https://preview.test/beta/"><link rel="stylesheet" href="templates/mupanic/css/style.css"><link rel="stylesheet" href="templates/mupanic/css/account.css"><link rel="stylesheet" href="templates/mupanic/css/recharge.css"><style>body{margin:0;background:#f3ece2}.fixture-wrap{max-width:1280px;margin:30px auto;padding:0;display:block}.fixture-nav{display:none;padding:25px;background:#202b28;color:#ead7bd}.fixture-content{background:#142134;padding:0;min-width:0}@media(max-width:800px){.fixture-wrap{display:block;padding:0;margin:0}.fixture-nav{display:none}.fixture-content{padding:0}}</style></head><body class="is-account"><div class="fixture-wrap"><aside class="fixture-nav"><h2>MU PANIC</h2><p>Mi cuenta</p><hr><p>Inicio del panel</p><p>Mis personajes</p><p>Recargar Eryns</p></aside><main class="fixture-content module-surface">';
eval('?>'.$markup);
echo '</main></div><script src="templates/mupanic/js/recharge.js"></script></body></html>';
