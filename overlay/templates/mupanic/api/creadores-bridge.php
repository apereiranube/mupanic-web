<?php
// MU PANIC - Creadores: puente firmado para el bot de Discord y el worker de Socios del VPS. Sin acceso a SQL.
// Firma: X-Panic-Signature = HMAC(timestamp \n body) con la clave HMAC('creadores', token del worker); respuesta: HMAC(nonce \n respuesta).
define('access','api');
require_once __DIR__.'/../inc/creadores-cola.php';
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
function creadoresBridgeError($code) { http_response_code($code); echo '{"error":"Creadores unavailable"}'; exit; }
if(($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') creadoresBridgeError(405);
try { $store=new PanicCreadores(); $key=$store->key(); }
catch(Throwable $e) { creadoresBridgeError(503); }
$body=file_get_contents('php://input',false,null,0,32769);
$stamp=$_SERVER['HTTP_X_PANIC_TIMESTAMP'] ?? ''; $signature=$_SERVER['HTTP_X_PANIC_SIGNATURE'] ?? '';
if(!is_string($body) || strlen($body)>32768 || !is_string($stamp) || !preg_match('/^[0-9]{10}$/D',$stamp) || abs(time()-(int)$stamp)>300 ||
    !is_string($signature) || !hash_equals(hash_hmac('sha256',$stamp."\n".$body,$key),$signature)) creadoresBridgeError(401);
try { $request=json_decode($body,true,10,JSON_THROW_ON_ERROR); } catch(Throwable $e) { creadoresBridgeError(400); }
if(!is_array($request) || !is_string($request['nonce'] ?? null) || !preg_match('/^[a-f0-9]{32}$/D',$request['nonce'])) creadoresBridgeError(401);
try {
    $action=(string)($request['action'] ?? '');
    if($action==='poll') $result=$store->poll($request['snapshot'] ?? null);
    elseif($action==='ack') $result=$store->acknowledge($request['results'] ?? []);
    else $result=$store->bot($request);
    $result['nonce']=$request['nonce'];
    $reply=json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    header('X-Panic-Signature: '.hash_hmac('sha256',$request['nonce']."\n".$reply,$key));
    echo $reply;
} catch(InvalidArgumentException $e) { creadoresBridgeError(422); }
catch(Throwable $e) { creadoresBridgeError(503); }
