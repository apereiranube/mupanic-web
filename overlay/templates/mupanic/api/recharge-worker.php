<?php
define('access','api');
require_once __DIR__.'/../inc/recharge-orders.php';
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
function rechargeWorkerError($code) { http_response_code($code); echo '{"error":"Recharge unavailable"}'; exit; }
if(($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') rechargeWorkerError(405);
try { $store=new PanicRechargeOrders(); $token=$store->token(); }
catch(Throwable $exception) { rechargeWorkerError(503); }
$body=file_get_contents('php://input',false,null,0,8193); $stamp=$_SERVER['HTTP_X_PANIC_TIMESTAMP'] ?? ''; $signature=$_SERVER['HTTP_X_PANIC_SIGNATURE'] ?? '';
$request=panicRechargeWorkerRequest($body,$stamp,$signature,$token,time());
if($request===null) rechargeWorkerError(401);
try {
    if(($request['action'] ?? '')==='ack') {
        // Accept stored SQL receipts even during provider outages or environment changes.
        $result=$store->acknowledge($request['receipt'] ?? []);
    } elseif(in_array($request['action'] ?? '',['poll','report'],true)) {
        [$api,$merchant,$settings]=panicRechargeShopGateway();
        if(($request['environment'] ?? '')!==$settings['environment']) rechargeWorkerError(409);
        $management=new PanicRechargeManagement();
        if($request['action']==='report') {
            $result=$management->report($request,$request['phase'] ?? '',$request);
        } else {
            $management->report($request,'polling');
            $result=$store->poll($api,$merchant,$settings['environment']==='production',($request['version'] ?? 1)>=2);
        }
    } else rechargeWorkerError(422);
    $result['nonce']=$request['nonce']; $reply=json_encode($result,JSON_THROW_ON_ERROR);
    header('X-Panic-Signature: '.hash_hmac('sha256',$request['nonce']."\n".$reply,$token)); echo $reply;
} catch(Throwable $exception) { rechargeWorkerError(503); }
