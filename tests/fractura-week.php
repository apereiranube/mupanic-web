<?php
// Fixed SELECT fixtures: never connects to a server and never writes rewards.
$mode='open';$queries=[];
function fixtureRules() {return [
 ['result'=>'W','contribution'=>'2000','fragments'=>'3','bless'=>'2','soul'=>'2','chaos'=>'1'],
 ['result'=>'L','contribution'=>'500','fragments'=>'1','bless'=>'0','soul'=>'0','chaos'=>'1'],
 ['result'=>'T','contribution'=>'1000','fragments'=>'2','bless'=>'1','soul'=>'1','chaos'=>'0']];}
class Connection {
 static function Database($name){return new self;}
 function query_fetch($sql){
  global $mode,$queries;$queries[]=$sql;
  if(preg_match('/\b(UPDATE|INSERT|DELETE|EXEC|ALTER|CREATE|DROP|MERGE|TRUNCATE)\b/i',$sql)) throw new RuntimeException('Mutating SQL forbidden');
  if($mode==='unavailable') throw new RuntimeException('Unavailable');
  if(strpos($sql,'DECLARE @Now')!==false) return [[
   'weekStart'=>'2026-10-12','nowUnix'=>'1792024500','startUnix'=>'1791774000','closeUnix'=>'1792376100',
   'minimumQuests'=>'2','bossPoints'=>'1000','enabled'=>'1','isClosed'=>'0','nexoPoints'=>$mode==='zero'?'0':'1000','caosPoints'=>$mode==='zero'?'0':'2000']];
  if(strpos($sql,'MUPanicWeekFrozenRulesV31')!==false) {if($mode==='open' || $mode==='zero')return []; $r=fixtureRules();foreach($r as &$x){$x['weekStart']='2026-10-05';$x['contribution']='777';}return $mode==='missing-frozen'?[]:$r;}
  if(strpos($sql,'MUPanicWeekRulesV31')!==false) return fixtureRules();
  if(strpos($sql,'TOP(52)')!==false) return $mode==='open' || $mode==='zero'?[]:[[
   'weekStart'=>'2026-10-05','closeUnix'=>'1791771300','nexoPoints'=>$mode==='history-nexo'?'2000':'1000','caosPoints'=>$mode==='history-tie' || $mode==='history-nexo'?'1000':'2000','winnerFamily'=>$mode==='history-nexo'?'1':($mode==='history-tie'?'0':'2')]];
  throw new RuntimeException('Unexpected SQL');
 }
}
require __DIR__.'/../overlay/templates/mupanic/inc/fractura-data.php';
require __DIR__.'/../overlay/templates/mupanic/inc/fractura-week-data.php';
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
$d=panicFracturaWeekRead();check($d!==null && $d['current']['caosPoints']==='2000' && !$d['current']['isClosed'] && count($d['history'])===0,'Open week must not invent a winner');
$mode='zero';$d=panicFracturaWeekRead();check($d['current']['nexoPoints']==='0' && count($d['history'])===0,'Zero is legitimate, no fake tie champion');
$mode='history';$d=panicFracturaWeekRead();check($d['history'][0]['winnerFamily']===2 && $d['history'][0]['rules']['W']['contribution']==='777' && $d['rules']['W']['contribution']==='2000','Frozen history keeps original reward amounts');
$mode='history-nexo';check(panicFracturaWeekRead()['history'][0]['winnerFamily']===1,'Nexo winner');
$mode='history-tie';check(panicFracturaWeekRead()['history'][0]['winnerFamily']===0,'Tie winner');
$mode='missing-frozen';check(panicFracturaWeekRead()===null,'Missing frozen rewards must not silently show current amounts');
$mode='unavailable';check(panicFracturaWeekRead()===null,'Missing SQL is unavailable, not zero');
foreach($queries as $q){check(strpos($q,'AccountID')===false && strpos($q,'MUPanicWeekClaimsV31')===false,'Public endpoint must not expose accounts or reservations');}
echo "PASS: read-only weekly score, real zero, no premature winner, finalized history, frozen rewards, unavailable SQL and no private fields.\n";
