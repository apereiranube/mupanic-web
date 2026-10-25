<?php
// Pure presentation decisions; never opens a database connection.
define('PANIC_ECO_ADMIN',true);define('__BASE_URL__','https://preview.test/beta/');function panicRechargeAdminAllowed(){return true;}
require dirname(__DIR__).'/overlay/templates/mupanic/inc/admin-economy/bootstrap.php';require dirname(__DIR__).'/overlay/templates/mupanic/inc/admin-economy/presentation.php';
function expectEco($ok,$message){if(!$ok)throw new RuntimeException($message);}
foreach([
 ['spending',null,false,'quiet'],['spending',0,false,'bad'],['spending',14.99,false,'bad'],['spending',15,false,'watch'],['spending',40,false,'watch'],['spending',40.01,false,'good'],
 ['payout',79.99,false,'bad'],['payout',80,false,'watch'],['payout',85,false,'good'],['payout',97,false,'good'],['payout',97.01,false,'watch'],['payout',102,false,'watch'],['payout',102.01,false,'bad'],
 ['payout',69.99,true,'bad'],['payout',70,true,'watch'],['payout',84,true,'watch'],['payout',94,true,'good'],['payout',110,true,'watch'],['payout',110.01,true,'bad'],
 ['check',null,false,'quiet'],['check',0,false,'good'],['check',0.99,false,'good'],['check',1,false,'watch'],['check',5,false,'watch'],['check',-5.01,false,'bad']
] as $case){[$kind,$value,$single,$expected]=$case;expectEco(panicEcoVerdict($kind,$value,$single)['tone']===$expected,'Threshold '.json_encode($case));}
expectEco(panicEcoRatio(0,0)===null,'No false green from 0/0');expectEco(panicEcoRatio(null,100)===null,'Missing is not zero');
expectEco(panicEcoLatestCheck([['NoIdentificado'=>null,'SaldoFin'=>100,'DiaAR'=>'2026-01-01']])['percent']===null,'No false reconciliation');
expectEco(panicEcoLatestCheck([['NoIdentificado'=>-5,'SaldoFin'=>100,'DiaAR'=>'2026-01-01']])['percent']===5.0,'Absolute discrepancy');
expectEco(strpos(panicEcoComparison(0,0),'Sin movimiento')!==false,'Zero comparison');expectEco(strpos(panicEcoComparison(120,100),'20,0% más')!==false,'Friendly comparison');
expectEco(panicEcoPeriodLabel('2026-S01')==='Semana 1 · 2026','Friendly week');
expectEco(panicEcoFriendlyValue('Evento','VIP_SIN_COBRO')==='VIP dado sin cobrar','Friendly VIP');
expectEco(panicEcoFriendlyValue('Prueba',1)==='Mis pruebas','Friendly test flag');
expectEco(panicEcoLoadedTone(['MinutosDesdeUltima'=>15,'Resultado'=>'OK'])==='good','15 minutes is fresh');expectEco(panicEcoLoadedTone(['MinutosDesdeUltima'=>16,'Resultado'=>'OK'])==='bad','16 minutes is stale');
$cash=panicEcoCashRows([['Periodo'=>'2026-01-01','Pesos'=>10],['Periodo'=>'2026-01-01','Pesos'=>20]]);expectEco($cash[0]['Pesos']===30.0,'Sum already-read provider groups');
ob_start();panicEcoEmpty(false);$empty=ob_get_clean();expectEco(strpos($empty,'Ver también mis pruebas')!==false,'Actionable empty state');
ob_start();panicEcoDetailStart();panicEcoDetailEnd();$detail=ob_get_clean();expectEco(strpos($detail,' open')===false,'Details closed by default');
ob_start();panicEcoFlow(100,1000,['Juegos'=>-50,'Tienda'=>10]);$flow=ob_get_clean();expectEco(strpos($flow,'Eryns devueltos de más')!==false,'Negative net is an entry');expectEco(strpos($flow,'width:-')===false,'No negative bars');
expectEco(strpos(panicEcoHelp('<script>'),'<script>')===false,'Escaped help text');
echo 'Amigable: semáforos, límites, ceros, fotos faltantes, ayudas, nombres, detalles y devoluciones netas OK.'.PHP_EOL;
