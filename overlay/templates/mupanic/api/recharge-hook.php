<?php
define('access','api');
require_once __DIR__.'/../inc/recharge-orders.php';
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
if(($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') { http_response_code(405); exit; }
$id=$_GET['id'] ?? ''; $key=$_GET['key'] ?? '';
if(!is_string($id) || !is_string($key) || !preg_match('/^PANIC-[a-f0-9]{32}$/D',$id) || !preg_match('/^[a-f0-9]{64}$/D',$key)) { http_response_code(401); exit; }
try {
    $store=new PanicRechargeOrders(); $order=$store->get($id);
    if(!$order || !hash_equals($order['callback_token'],$key)) { http_response_code(401); exit; }
    $body=file_get_contents('php://input',false,null,0,8193);
    if(strlen($body)>8192) { http_response_code(413); exit; }
    $hint=json_decode($body,true,8,JSON_THROW_ON_ERROR);
    // Webhook status is untrusted. Canonical authenticated GET supplies every decision.
    if(!is_array($hint) || ($hint['uuid'] ?? null)!==$order['payment_id']) { http_response_code(422); exit; }
    [$api,$merchant,$settings]=panicRechargeShopGateway();
    if(($settings['environment']==='production')!==$order['live']) { http_response_code(409); exit; }
    $store->refresh($id,$api,$merchant); echo '{"received":true}';
} catch(Throwable $exception) { http_response_code(503); echo '{"received":false}'; }
