<?php
// A narrowly scoped, signed bridge for one sandbox order; no CMS or SQL credentials.
define('access','api');
require_once __DIR__.'/../inc/recharge-pilot.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function pilotWorkerError($code) { http_response_code($code); echo '{"error":"Pilot unavailable"}'; exit; }
if(($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') pilotWorkerError(405);
try { $store=new PanicRechargePilot(); $token=$store->token(); }
catch(Throwable $exception) { pilotWorkerError(503); }
$body=file_get_contents('php://input',false,null,0,8193);
$stamp=$_SERVER['HTTP_X_PANIC_TIMESTAMP'] ?? '';
$signature=$_SERVER['HTTP_X_PANIC_SIGNATURE'] ?? '';
if(strlen($body)>8192 || !preg_match('/^[0-9]{10}$/D',$stamp) || abs(time()-(int)$stamp)>300 ||
   !is_string($signature) || !hash_equals(hash_hmac('sha256',$stamp."\n".$body,$token),$signature)) pilotWorkerError(401);
try {
    $request=json_decode($body,true,8,JSON_THROW_ON_ERROR);
    if(!is_array($request) || !preg_match('/^[a-f0-9]{32}$/D',$request['nonce'] ?? '')) pilotWorkerError(422);
    [$api,$merchant]=panicRechargePilotGateway();
    if(($request['action'] ?? '')==='poll') {
        $state=$store->current();
        if($state && is_string($state['payment_id'] ?? null) && ($state['delivery_state'] ?? '')==='pending') $state=$store->refresh($api,$merchant);
        $result=['job'=>$state?PanicRechargePilot::job($state):null];
    } elseif(($request['action'] ?? '')==='ack') {
        $state=$store->acknowledge($request['receipt'] ?? []);
        $result=['state'=>$state['delivery_state']];
    } else { pilotWorkerError(422); }
    $result['nonce']=$request['nonce'];
    $reply=json_encode($result,JSON_THROW_ON_ERROR);
    header('X-Panic-Signature: '.hash_hmac('sha256',$request['nonce']."\n".$reply,$token));
    echo $reply;
} catch(Throwable $exception) { pilotWorkerError(503); }
