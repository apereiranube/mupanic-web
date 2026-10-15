<?php
$mode='ok';$lastSQL='';
class Connection {
 static function Database($name){return new self;}
 function query_fetch($sql) {
  global $mode,$lastSQL;$lastSQL=$sql;
  if(preg_match('/\b(INSERT|UPDATE|DELETE|EXEC|ALTER|CREATE|DROP)\b/i',$sql)) throw new RuntimeException('Write forbidden');
  if($mode==='unavailable') throw new RuntimeException('Unavailable');
  if($mode==='false') return false;
  if($mode==='null') return null;
  if($mode==='none') return strpos($sql,'UNION ALL')!==false?[["hasRow"=>'0']]:false;
  return [['hasRow'=>$mode==='invalid'?'9':'1','name'=>$mode==='xss'?'A<script>':'LuisADM','family'=>'1','contribution'=>'1520','untilUnix'=>'1792980900']];
 }
}
require __DIR__.'/../overlay/templates/mupanic/inc/fractura-ranks.php';
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
check(panicFracturaRankName(1,1499)==='Juramentado','Before boundary');
check(panicFracturaRankName(1,1500)==='Portador del Sello','Boundary');
check(panicFracturaRankName(2,100)==='Desatado','Caos base');
check(panicFracturaRankName(2,50000)==='Soberano de las Cenizas','Maximum');
check(panicFracturaRankName(0,50000)==='Sin bando','No faction');
$r=panicFracturaDistinctionsRead();check(count($r)===1 && $r[0]['rank']==='Portador del Sello','Real contribution tier');
check(strpos($lastSQL,'MUPanicFracturaLaureatesV34')!==false && strpos($lastSQL,'MUPanicWeekClaimsV31')===false,'Only the curated public laureates view is queried');
check(array_keys($r[0])===['name','family','rank','untilUnix'],'No account, IP or reward receipt exposure');
$mode='none';check(panicFracturaDistinctionsRead()===[],'No awarded title is an empty list');
$mode='false';check(panicFracturaDistinctionsRead()===null,'Failed query is not a successful empty result');
$mode='null';check(panicFracturaDistinctionsRead()===null,'Null query is not a successful empty result');
$mode='invalid';check(panicFracturaDistinctionsRead()===null,'Invalid marker rejected');
$mode='unavailable';check(panicFracturaDistinctionsRead()===null,'SQL unavailable is not an empty winner list');
$mode='xss';check(panicFracturaDistinctionsRead()[0]['name']==='A<script>','Display must escape even unusual names');
echo "PASS: rank boundaries, public-view contract, empty/unavailable and private-field exclusion.\n";
