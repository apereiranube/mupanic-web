<?php
// Body status is never trusted. Refresh only the stored UUID through authenticated Uala GET.
define('access','api');
require_once __DIR__.'/../inc/recharge-pilot.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if(($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') { http_response_code(405); exit; }
$key=$_GET['key'] ?? '';
if(!is_string($key) || !preg_match('/^[a-f0-9]{64}$/D',$key)) { http_response_code(401); exit; }
try {
    $store=new PanicRechargePilot();
    $state=$store->current();
    if(!$state || !hash_equals($state['callback_token'],$key)) { http_response_code(401); exit; }
    $body=file_get_contents('php://input',false,null,0,8193);
    if(strlen($body)>8192) { http_response_code(413); exit; }
    $hint=json_decode($body,true,8,JSON_THROW_ON_ERROR);
    if(!is_array($hint) || ($hint['uuid'] ?? null)!==$state['payment_id']) { http_response_code(422); exit; }
    [$api,$merchant]=panicRechargePilotGateway();
    $store->refresh($api,$merchant,$key);
    echo '{"received":true}';
} catch(Throwable $exception) { http_response_code(503); echo '{"received":false}'; }
