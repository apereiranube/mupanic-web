<?php
// MU PANIC - Referidos: puente firmado para el proceso del VPS (poll / ack). Sin acceso a SQL.
define('access','api');
require_once __DIR__.'/../inc/referidos-cola.php';
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
function referidosWorkerError($code) { http_response_code($code); echo '{"error":"Referidos unavailable"}'; exit; }
if(($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') referidosWorkerError(405);
try { $cola=new PanicReferidosCola(); $key=$cola->key(); }
catch(Throwable $e) { referidosWorkerError(503); }
$body=file_get_contents('php://input',false,null,0,8193);
$request=panicReferidosWorkerRequest($body,$_SERVER['HTTP_X_PANIC_TIMESTAMP'] ?? '',$_SERVER['HTTP_X_PANIC_SIGNATURE'] ?? '',$key,time());
if($request===null) referidosWorkerError(401);
try {
    if(($request['action'] ?? '')==='poll') $result=$cola->poll();
    elseif(($request['action'] ?? '')==='ack') $result=$cola->acknowledge($request['results'] ?? []);
    else referidosWorkerError(422);
    $result['nonce']=$request['nonce'];
    $reply=json_encode($result,JSON_THROW_ON_ERROR);
    header('X-Panic-Signature: '.hash_hmac('sha256',$request['nonce']."\n".$reply,$key));
    echo $reply;
} catch(Throwable $e) { referidosWorkerError(503); }
