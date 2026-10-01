<?php
define('access',true);
define('__BASE_URL__','https://preview.test/');
$logged=false; $admins=['owner'=>1]; $_SESSION=[];
function isLoggedIn() { return $GLOBALS['logged']; }
function config($name,$return) { return $GLOBALS['admins']; }
function panicAccountEscape($value) { return htmlspecialchars($value,ENT_QUOTES,'UTF-8'); }
ob_start(); require __DIR__.'/../overlay/templates/mupanic/inc/recharge-check.php'; $output=ob_get_clean();
function check($ok,$message) { if(!$ok) throw new RuntimeException($message); }
check($output==='','Guests see no diagnostics');
$calls=0; $auth=function() use (&$calls) { $calls++; return 'ok'; };
$session=['uala_check_csrf'=>'fixture']; $post=['uala_check_csrf'=>'fixture'];
check(panicRechargeCheckRequest($post,$session,1000,$auth)===null && $calls===0,'Guest cannot authenticate');
$logged=true; $_SESSION['username']='player';
check(panicRechargeCheckRequest($post,$session,1000,$auth)===null && $calls===0,'Player cannot authenticate');
$_SESSION['username']='owner';
panicRechargeCheckRequest(['uala_check_csrf'=>['fixture']],$session,1000,$auth);
panicRechargeCheckRequest(['uala_check_csrf'=>'forged'],$session,1000,$auth);
check($calls===0,'Invalid CSRF cannot authenticate');
check(panicRechargeCheckRequest($post,$session,1000,$auth)==='ok' && $calls===1,'Authorized request authenticates once');
panicRechargeCheckRequest($post,$session,1001,$auth);
check($calls===1,'Immediate replay is throttled');
$failure=panicRechargeCheckRequest($post,$session,1060,function() { throw new RuntimeException('SECRET_RESPONSE'); });
check(strpos($failure,'SECRET_RESPONSE')===false,'Provider exception is not disclosed');
$_SERVER['REQUEST_METHOD']='GET';
ob_start(); panicRechargeCheckRender(); $output=ob_get_clean();
check(strpos($output,'Comprobar conexión con Ualá')!==false,'Administrator sees button');
check($calls===1,'GET does not authenticate');
echo "Recharge browser check: passed.\n";
