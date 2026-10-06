<?php
require_once(__DIR__.'/../inc/atlas-event-runtime.php');
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function atlasEventReply($status,$message) {http_response_code($status);echo json_encode(['message'=>$message]);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST') atlasEventReply(405,'POST required');
$runtime=panicAtlasRuntimeDirectory();$file=$runtime.'/token';
if(!is_file($file) || is_link($file) || strlen($token=trim(file_get_contents($file)))<32) atlasEventReply(503,'Synchronization is not configured');
$stamp=$_SERVER['HTTP_X_PANIC_TIMESTAMP'] ?? '';$signature=$_SERVER['HTTP_X_PANIC_SIGNATURE'] ?? '';
if(!preg_match('/^[0-9]+$/D',$stamp) || abs(time()-(int)$stamp)>300) atlasEventReply(401,'Invalid signature');
$body=file_get_contents('php://input',false,null,0,1000001);
if(strlen($body)>1000000) atlasEventReply(413,'Snapshot too large');
if(!hash_equals(hash_hmac('sha256',$stamp."\n".$body,$token),$signature)) atlasEventReply(401,'Invalid signature');
$data=json_decode($body,true);if(!panicAtlasEventsValid($data)) atlasEventReply(422,'Invalid event catalogue');
$lock=fopen($runtime.'/events.lock','c');if(!$lock || !flock($lock,LOCK_EX)) atlasEventReply(503,'Cannot lock snapshot');
$path=$runtime.'/public-events.json';
if(is_file($path)) {$old=json_decode(file_get_contents($path),true);if(isset($old['generatedAt']) && strtotime($old['generatedAt'])>strtotime($data['generatedAt'])) atlasEventReply(409,'A newer snapshot is already available');}
$tmp=tempnam($runtime,'events-');if(!$tmp || file_put_contents($tmp,json_encode($data,JSON_UNESCAPED_UNICODE))===false) atlasEventReply(503,'Cannot store snapshot');
chmod($tmp,0600);if(!rename($tmp,$path)) {unlink($tmp);atlasEventReply(503,'Cannot commit snapshot');}
flock($lock,LOCK_UN);fclose($lock);atlasEventReply(200,'Snapshot updated');
