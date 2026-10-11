<?php
define('access',true);define('__BASE_URL__','/beta/');
class Connection { public static function Database($name){throw new RuntimeException('PRIVATE_PASSWORD');} }
ob_start();include __DIR__.'/../overlay/templates/mupanic/inc/market.php';$html=ob_get_clean();
foreach(['El Sello','Vessara.','Datos no disponibles','Tributo de Vessara','/beta/market/?market_data=1','data-market-currency="1"'] as $value)if(strpos($html,$value)===false)throw new Exception('Missing '.$value);
if(strpos($html,'PRIVATE_PASSWORD')!==false)throw new Exception('Secret exposed');
if(substr_count($html,'data-market-metric=')!==5)throw new Exception('Metrics');
echo "PASS: page renders with unavailable SQL, correct API URL and no private errors\n";
