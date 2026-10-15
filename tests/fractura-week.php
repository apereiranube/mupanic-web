<?php
$mode='open';$queries=[];
class Connection {
 static function Database($name){return new self;}
 function query_fetch($sql) {
  global $mode,$queries;$queries[]=$sql;
  if(!preg_match('/^SELECT\b/i',trim($sql)) || preg_match('/\b(INSERT|UPDATE|DELETE|EXEC|ALTER|CREATE|DROP|DECLARE)\b/i',$sql)) throw new RuntimeException('Only a SELECT result set is allowed');
  if($mode==='unavailable')throw new RuntimeException('Unavailable');
  if(strpos($sql,'MUPanicFracturaWeekCurrentV34')!==false)return [[
   'weekStart'=>'2026-10-12','nowUnix'=>'1792024500','startUnix'=>'1791774000','closeUnix'=>'1792376100','minimumQuests'=>'2','bossPoints'=>'1000','enabled'=>true,'isClosed'=>false,'nexoPoints'=>$mode==='zero'?'0':'1000','caosPoints'=>$mode==='zero'?'0':'2000']];
  if(strpos($sql,'MUPanicFracturaWeekHistoryV34')!==false)return $mode==='false'?false:($mode==='open' || $mode==='zero'?(strpos($sql,'UNION ALL')!==false?[['hasRow'=>'0']]:false):[[
   'hasRow'=>'1',
   'weekStart'=>'2026-10-05','closeUnix'=>'1791771300','nexoPoints'=>$mode==='nexo'?'2000':'1000','caosPoints'=>$mode==='tie' || $mode==='nexo'?'1000':'2000','winnerFamily'=>$mode==='nexo'?'1':($mode==='tie'?'0':'2')]]);
  throw new RuntimeException('Unexpected query');
 }
}
require __DIR__.'/../overlay/templates/mupanic/inc/fractura-week-data.php';
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
$d=panicFracturaWeekRead();check($d!==null && $d['current']['caosPoints']==='2000' && !$d['current']['isClosed'] && !$d['history'],'Open week has no invented champion');
$mode='zero';check(panicFracturaWeekRead()['current']['nexoPoints']==='0','Legitimate zero');
foreach(['history'=>2,'nexo'=>1,'tie'=>0] as $key=>$winner){$mode=$key;check(panicFracturaWeekRead()['history'][0]['winnerFamily']===$winner,'Finalized SQL winner');}
$mode='false';check(panicFracturaWeekRead()===null,'Failed history query is not empty history');
$mode='unavailable';check(panicFracturaWeekRead()===null,'Unavailable stays unavailable');
foreach($queries as $q)check(!preg_match('/\b(MUPanicWeekClaimsV31|AccountID|Gens_Rank|Character)\b/',$q),'Public reader uses only curated public views');
echo "PASS: single SELECT/public-view contract, boolean flags, legitimate zero, real closed winners and unavailable SQL.\n";
