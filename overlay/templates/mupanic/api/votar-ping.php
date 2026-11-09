<?php
// MU PANIC - Votar: aviso de voto de las listas con pingback (hoy GTop100).
// La lista llama a esta dirección después de cada voto; solo se anota si trae la clave correcta y el voto salió bien.
// Si se puede pagar, el premio queda ganado al instante (el jugador no toca nada).
define('access','api');
require_once __DIR__.'/../inc/votar-store.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store');
function votarPingEnd($code,$text) { http_response_code($code); echo $text; exit; }
// GTop100 no siempre respeta el ?s= de la dirección (lo saca o le pega sus propios datos con otro '?').
// Si no es una lista con aviso conocida, se asume GTop100 (hoy es la única lista con aviso).
$sites=panicVotarSites();
$site=is_string($_GET['s'] ?? null)?strtolower(preg_replace('/[^a-z0-9].*$/is','',$_GET['s'])):'';
if(!isset($sites[$site]) || ($sites[$site]['api'] ?? '')!=='ping') $site='gtop100';
// Si GTop100 pegó sus datos dentro del ?s= (s=gtop100?pingUsername=...), se recuperan.
if(is_string($_GET['s'] ?? null) && strpos($_GET['s'],'?')!==false) { parse_str(substr($_GET['s'],strpos($_GET['s'],'?')+1),$extra); if(is_array($extra)) $_GET=array_merge($extra,$_GET); }
$method=(string)($_SERVER['REQUEST_METHOD'] ?? '');
$in=array_merge($_GET,$_POST);
$raw=file_get_contents('php://input',false,null,0,8193);
if(is_string($raw) && $raw!=='' && !isset($in['pingbackkey'])) { $j=json_decode($raw,true,4); if(is_array($j)) $in=array_merge($in,isset($j['Common']) && is_array($j['Common'])?array_merge($j,$j['Common']):$j); }
try { $store=new PanicVotarStore(); $key=$store->token($site); } catch(Throwable $e) { votarPingEnd(503,'unavailable'); }
$given=is_string($in['pingbackkey'] ?? null)?$in['pingbackkey']:'';
$keyOk=$key!=='' && $given!=='' && hash_equals($key,$given);
$ok=abs((int)($in['Successful'] ?? 1))===0;
$account=is_string($in['pingUsername'] ?? null)?trim($in['pingUsername']):'';
// Diagnóstico para el panel (sin la clave): se ve en Configuración -> Páginas de votación -> Última respuesta.
try { $store->noteApi($site,'PINGBACK '.$method.' clave='.($key===''?'sin_cargar':($keyOk?'ok':($given===''?'no_vino':'distinta'))).' Successful='.(string)($in['Successful'] ?? '-').' cuenta='.substr($account,0,20).' campos='.implode(',',array_slice(array_keys($in),0,12))); } catch(Throwable $e) {}
if(!$keyOk) votarPingEnd(403,'forbidden');
if($ok && PanicVotarStore::validAccount($account)) {
    try { $store->autoPremioPing($site,$account,time()); } catch(Throwable $e) { votarPingEnd(503,'unavailable'); }
}
votarPingEnd(200,'OK');
