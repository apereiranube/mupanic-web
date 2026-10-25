<?php
// Filters and generated SQL contract tests; no SQL Server connection required.
define('PANIC_ECO_ADMIN',true); require dirname(__DIR__).'/overlay/templates/mupanic/inc/admin-economy/filters.php';require dirname(__DIR__).'/overlay/templates/mupanic/inc/admin-economy/repository.php';
function check($condition,$message) { if(!$condition) throw new RuntimeException($message); }
$tz=new DateTimeZone('America/Argentina/Buenos_Aires');$now=new DateTimeImmutable('2026-10-18 23:30:00',$tz);
$f=panicEcoFilters([], $now);check($f['start']->format('Y-m-d')==='2026-10-12','7 days inclusive');check($f['end']->format('Y-m-d')==='2026-10-19','Exclusive upper bound');check($f['previous']->format('Y-m-d')==='2026-10-05','Equal comparison');check(!$f['tests'],'Real accounts default');
check(panicEcoFilters(['rango'=>'ayer'],$now)['start']->format('Y-m-d')==='2026-10-17','Yesterday');
check(panicEcoFilters(['rango'=>'mes'],$now)['start']->format('Y-m-d')==='2026-10-01','Month');
foreach([['rango'=>[]],['eco_tab'=>'../../config'],['rango'=>'personalizado','desde'=>'2026-02-30','hasta'=>'2026-03-01'],['rango'=>'personalizado','desde'=>'2026-10-19','hasta'=>'2026-10-18'],['pruebas'=>'9'],['agrupar'=>'dia;DELETE']] as $bad) { try{panicEcoFilters($bad,$now);throw new RuntimeException('Accepted invalid filters');}catch(InvalidArgumentException $expected){} }
class EcoSpy { public $queries=[];public $fail=false;function query_fetch($sql,$p){$this->queries[]=[$sql,$p];return $this->fail?false:null;} }
$views=[]; foreach(['dia','semana','mes'] as $group) foreach([false,true] as $tests) {
 $f=panicEcoFilters(['agrupar'=>$group,'pruebas'=>$tests?'1':'0'],$now);$spy=new EcoSpy;$r=new PanicEconomyRepository($f,$spy);
 $r->state();$r->balances();$r->openingBalance();$r->summary();$r->summary(true);$r->flows();$r->balanceSeries();$r->games();$r->gameSeries();$r->players();$r->players(true);$r->gameDetail('RULETA');$r->marketTotals('Eryns');$r->marketTotals('Zen',true);$r->marketSeries('Zen');$r->marketRanking('Eryns');$r->marketRanking('Zen',true);$r->marketDetail('Zen');$r->purchaseTotals('VIP');$r->purchaseTotals('TIENDA',true);$r->storeBuyers();$r->storeBuyers(true);$r->purchaseSeries();$r->storeProducts();$r->freeVip();$r->activeVip();$r->topupTotals();$r->topupTotals(true);$r->topupSeries();$attack="x' OR 1=1--";$r->topupDetail($attack);$r->reconciliation();$r->alerts('ERROR');$r->richest();$r->excluded();
 foreach($spy->queries as [$sql,$params]) {
  check(strpos($sql,'SELECT ')===0,'Only SELECT');check(!preg_match('/\b(TRY_CONVERT|CONCAT|IIF|FORMAT|LAG|OFFSET|FETCH|EOMONTH|DATEDIFF_BIG)\s*\(/i',$sql),'SQL 2008 compatibility');check(substr_count($sql,'?')===count($params),'Parameter count: '.$sql);check(strpos($sql,$attack)===false,'No interpolated filter');check(!preg_match('/AND\s+GROUP BY/',$sql),'Dangling WHERE');
  preg_match_all('/\b(?:FROM|JOIN)\s+([A-Za-z0-9_.]+)/i',$sql,$sources);foreach($sources[1] as $source)check(strpos($source,'dbo.vMUPanicEco')===0,'Only approved views: '.$source);
  preg_match_all('/dbo\.vMUPanicEco([A-Za-z]+)/',$sql,$m);foreach($m[1] as $view)$views[$view]=true;
  if(strpos($sql,'SELECT TOP 1000 OrderID')===0) {check(in_array($attack,$params,true),'Search bound');check($tests || strpos($sql,'Prueba = 0')!==false,'Test exclusion');}
  if(strpos($sql,'SaldoDia')!==false && strpos($sql,'ROW_NUMBER')!==false)check(strpos($sql,'SUM(')===false,'Do not sum snapshots');
 }
 $spy->fail=true;try{$r->state();throw new RuntimeException('False disguised as empty');}catch(RuntimeException $e){check($e->getMessage()==='No se pudo consultar Economía.','Error semantics');}
}
check(count($views)===14,'All fourteen views covered');
echo 'Economía: filtros, rangos, parámetros, catorce vistas, seis combinaciones de agrupación/pruebas y errores OK.'.PHP_EOL;
