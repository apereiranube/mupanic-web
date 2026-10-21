<?php
define('access','api');
require_once __DIR__.'/../inc/recharge-orders.php';
require_once __DIR__.'/../inc/recharge-bank.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
if(($_SERVER['REQUEST_METHOD'] ?? '')!=='POST'){http_response_code(405);exit;}
try {
    $token=(new PanicRechargeOrders())->token();
    $body=file_get_contents('php://input',false,null,0,8193);
    $r=panicRechargeWorkerRequest($body,$_SERVER['HTTP_X_PANIC_TIMESTAMP'] ?? '',$_SERVER['HTTP_X_PANIC_SIGNATURE'] ?? '',$token,time());
    if($r===null){http_response_code(401);exit;}
    $result=(new PanicBankTransfers())->bot($r);
    // Never expose the private ledger/claim hashes to the bot.
    if(in_array($r['action'] ?? '',['ticket','receipt'],true))$result=['state'=>'ok'];
    $result['nonce']=$r['nonce'];$reply=json_encode($result,JSON_THROW_ON_ERROR);
    header('X-Panic-Signature: '.hash_hmac('sha256',$r['nonce']."\n".$reply,$token));echo $reply;
} catch(Throwable $e){http_response_code(422);echo '{"error":"Transfer action unavailable"}';}
