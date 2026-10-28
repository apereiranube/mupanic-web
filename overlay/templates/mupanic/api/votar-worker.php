<?php
// MU PANIC - Votar: puente firmado para el proceso del VPS (poll / ack). Sin acceso a SQL.
define('access','api');
require_once __DIR__.'/../inc/votar-store.php';
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
function votarWorkerError($code) { http_response_code($code); echo '{"error":"Votar unavailable"}'; exit; }
if(($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') votarWorkerError(405);
try { $store=new PanicVotarStore(); $key=$store->key(); }
catch(Throwable $e) { votarWorkerError(503); }
$body=file_get_contents('php://input',false,null,0,8193);
$request=panicVotarWorkerRequest($body,$_SERVER['HTTP_X_PANIC_TIMESTAMP'] ?? '',$_SERVER['HTTP_X_PANIC_SIGNATURE'] ?? '',$key,time());
if($request===null) votarWorkerError(401);
try {
    if(($request['action'] ?? '')==='poll') $result=$store->poll();
    elseif(($request['action'] ?? '')==='ack') $result=$store->acknowledge($request['results'] ?? []);
    else votarWorkerError(422);
    $result['nonce']=$request['nonce'];
    $reply=json_encode($result,JSON_THROW_ON_ERROR);
    header('X-Panic-Signature: '.hash_hmac('sha256',$request['nonce']."\n".$reply,$key));
    echo $reply;
} catch(Throwable $e) { votarWorkerError(503); }
