<?php
define('access',true);
require __DIR__.'/../overlay/templates/mupanic/inc/recharge-orders.php';
function isLoggedIn(){return $GLOBALS['logged'] ?? true;}
function config($key,$return){return $GLOBALS['admins'] ?? ['panic'=>1,'otheradmin'=>1];}
function assertManagement($ok,$why){if(!$ok)throw new RuntimeException($why);}
function denyManagement($fn){try{$fn();}catch(Throwable $e){return;}throw new RuntimeException('Operation was allowed');}
$_SESSION=['username'=>'panic'];
$root=sys_get_temp_dir().'/panic-management-'.bin2hex(random_bytes(5));mkdir($root,0700);
try {
 $manager=new PanicRechargeManagement($root);$catalogue=$manager->catalogue();
 assertManagement(count($catalogue['packages'])===5,'Initial packages');
 $_SESSION['username']='otheradmin';assertManagement(!panicRechargeAdminAllowed(),'Other admin denied');denyManagement(fn()=>$manager->saveCatalogue($catalogue['packages'],0));
 $_SESSION['username']='panic';$GLOBALS['logged']=false;assertManagement(!panicRechargeAdminAllowed(),'Anonymous denied');$GLOBALS['logged']=true;
 $GLOBALS['admins']=['otheradmin'=>1];assertManagement(!panicRechargeAdminAllowed(),'Removed CMS admin denied');$GLOBALS['admins']=['panic'=>1];
 $rows=$catalogue['packages'];$rows[0]['bonus']=250;$rows[0]['title']='Paquete aventura';$rows[1]['enabled']=false;
 $saved=$manager->saveCatalogue($rows,0);assertManagement($saved['revision']===1,'Revision');denyManagement(fn()=>$manager->saveCatalogue($rows,0));
 assertManagement($manager->offers()[0]['bonus']===0,'Old worker disables bonuses');
 $manager->report(['environment'=>'test','version'=>2],'ok',['waiting'=>1,'vip_config'=>['GS.CommandBuyVipSwitch'=>'1']]);
 $offers=$manager->offers();assertManagement(count($offers)===4 && $offers[0]['bonus']===250 && $offers[0]['title']==='Paquete aventura','Visibility bonus and title');
 $cart=PanicRecharge::cart($offers,[$offers[0]['id']=>5]);assertManagement($cart['coins']===6250 && $cart['price_cents']===500000,'Bonus independent of exact price');
 $rows[0]['starts_at']='2099-01-01T00:00-03:00';$manager->saveCatalogue($rows,1);assertManagement($manager->offers()[0]['bonus']===0,'Future bonus');
 assertManagement($manager->offers(time()+3600)[0]['bonus']===0,'Stale worker');
 $bad=$rows;$bad[0]['price_cents']=1;denyManagement(fn()=>$manager->saveCatalogue($bad,2));
 denyManagement(fn()=>PanicRecharge::cart([$offers[0],$offers[0]],[$offers[0]['id']=>1]));
 denyManagement(fn()=>$manager->report(['environment'=>'test','version'=>2],'ok',['vip_config'=>['GS.Password'=>'secret']]));
 foreach(['UTC','America/Argentina/Buenos_Aires'] as $zone){date_default_timezone_set($zone);assertManagement(panicRechargeArgDate('2026-10-01T23:40:00+00:00','H:i')==='20:40','UTC converted once');}
 $manager->report(['environment'=>'test','version'=>2],'error');assertManagement($manager->worker()['last_error']==='WORKER_FAILED','Safe error');
 echo "Management passed: exclusive admin, revocation, catalogue concurrency, promotions, bonus amounts, private reports and Argentine dates.\n";
} finally {foreach(glob($root.'/*') as $path)unlink($path);rmdir($root);}
