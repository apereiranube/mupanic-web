<?php
// Invented fixture only; never deployed or used by production.
define('access',true);define('__BASE_URL__','/');
class Connection {
 static function Database($name){return new self;}
 function query_fetch($q){
  if(strpos($q,'AS serverDay')!==false)return [['total'=>'138','today'=>'12','zen'=>'9007199254740993','eryns'=>'25400','serverDay'=>'2026-10-10']];
  if(strpos($q,'SELECT TOP 40')!==false)return [
   ['id'=>'1','buyer'=>'Juanita','seller'=>'MGTeste','item'=>'Chromatic Staff','itemIndex'=>2593,'level'=>15,'currency'=>1,'price'=>100,'soldAt'=>'2026-10-10T20:02:35'],
   ['id'=>'2','buyer'=>'PANIC','seller'=>'Juanita','item'=>'Hurricane Armor','itemIndex'=>4129,'level'=>13,'currency'=>0,'price'=>1000000,'soldAt'=>'2026-10-10T19:02:35']
  ];
  return [['day'=>'2026-10-10','trades'=>'12','zen'=>'1000000','eryns'=>'100']];
 }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Vessara fixture</title><link rel="stylesheet" href="/templates/mupanic/css/style.css"><link rel="stylesheet" href="/templates/mupanic/css/market.css"></head><body class="is-inner is-market"><header class="site-header"><div class="shell nav-shell"><a class="brand brand-crystal" href="/"><img class="brand-logo" src="/templates/mupanic/img/brand/mu-panic-crystal-bronze-matte-v2.webp" alt="MU PANIC" width="960" height="320"></a><nav class="main-nav" aria-label="Principal"><?php foreach(['El continente','El servidor','Mercado','Atlas','Rankings','Descargas'] as $s) { ?><a class="nav-main-link<?php echo $s==='Mercado'?' is-current':''; ?>" href="#"><?php echo $s; ?></a><?php } ?><a class="nav-discord" href="#">Discord ↗</a></nav><div class="nav-actions"><a class="account-link" href="#">Ingresar</a><a class="nav-cta" href="#">Crear cuenta</a></div></div></header><main><section class="inner-content"><div class="shell"><div class="module-surface"><?php include __DIR__.'/../../overlay/templates/mupanic/inc/market.php'; ?></div></div></section></main><script src="/templates/mupanic/js/market.js"></script></body></html>
