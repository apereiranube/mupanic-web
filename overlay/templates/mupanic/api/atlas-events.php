<?php
require_once(__DIR__.'/../inc/atlas-event-runtime.php');
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
define('access',true);define('__PATH_TEMPLATE__',rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])),'/').'/');
function panicWikiEscape($v) {return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$wiki=panicAtlasBalance();$atlasAssets=json_decode(file_get_contents(__DIR__.'/../inc/atlas-assets.json'),true);
ob_start();include(__DIR__.'/../inc/atlas-events.php');$html=ob_get_clean();
echo json_encode(['version'=>$eventSnapshot['sourceHash'],'html'=>$html],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
