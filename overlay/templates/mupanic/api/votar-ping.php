<?php
// MU PANIC - Votar: aviso de voto de las listas con pingback (hoy GTop100).
// La lista llama a esta dirección después de cada voto; solo se anota si trae la clave correcta y el voto salió bien.
// El premio se cobra después, cuando el jugador toca "Reclamar" en la página de Votar.
define('access','api');
require_once __DIR__.'/../inc/votar-store.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store');
function votarPingEnd($code,$text) { http_response_code($code); echo $text; exit; }
if(($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') votarPingEnd(405,'POST only');
$site=is_string($_GET['s'] ?? null)?$_GET['s']:'';
$sites=panicVotarSites();
if(!isset($sites[$site]) || ($sites[$site]['api'] ?? '')!=='ping') votarPingEnd(404,'unknown');
$in=$_POST;
$raw=file_get_contents('php://input',false,null,0,8193);
if(!$in && is_string($raw) && $raw!=='') { $j=json_decode($raw,true,4); if(is_array($j)) $in=isset($j['Common']) && is_array($j['Common'])?array_merge($j,$j['Common']):$j; }
try { $store=new PanicVotarStore(); $key=$store->token($site); } catch(Throwable $e) { votarPingEnd(503,'unavailable'); }
$given=is_string($in['pingbackkey'] ?? null)?$in['pingbackkey']:'';
if($key==='' || $given==='' || !hash_equals($key,$given)) votarPingEnd(403,'forbidden');
$ok=abs((int)($in['Successful'] ?? 1))===0;
$account=is_string($in['pingUsername'] ?? null)?trim($in['pingUsername']):'';
if($ok && PanicVotarStore::validAccount($account)) {
    try { $store->addPing($site,$account,time()); } catch(Throwable $e) { votarPingEnd(503,'unavailable'); }
}
votarPingEnd(200,'OK');
