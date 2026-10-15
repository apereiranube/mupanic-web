<?php
// Synthetic, read-only fixtures: no live SQL and no reward writes.
$fixtureMode=$argv[1]??'empty';$fixturePart=$argv[2]??'hall';
if(!in_array($fixtureMode,['empty','winner','tie','offline','xss','history'],true))$fixtureMode='empty';
class Connection {
 static function Database($name){return new self;}
 function query_fetch($sql){
  global $fixtureMode;
  if(!preg_match('/^SELECT\b/i',trim($sql)))throw new RuntimeException('Not a single SELECT');
  if($fixtureMode==='offline')throw new RuntimeException('Fixture unavailable');
  if(strpos($sql,'MUPanicFracturaWeekCurrentV34')!==false)return [[
   'weekStart'=>'2026-10-19','nowUnix'=>'1792530000','startUnix'=>'1792378800','closeUnix'=>'1792980900','minimumQuests'=>'2','bossPoints'=>'1000','enabled'=>true,'isClosed'=>false,'nexoPoints'=>'1000','caosPoints'=>'2000']];
  if(strpos($sql,'MUPanicFracturaWeekHistoryV34')!==false){
   if($fixtureMode==='empty')return strpos($sql,'UNION ALL')!==false?[['hasRow'=>'0']]:false;$rows=[];
   for($i=0;$i<($fixtureMode==='history'?9:1);$i++)$rows[]=['hasRow'=>'1','weekStart'=>gmdate('Y-m-d',1791774000-$i*604800),'closeUnix'=>(string)(1792376100-$i*604800),'nexoPoints'=>'2000','caosPoints'=>$fixtureMode==='tie'?'2000':'1000','winnerFamily'=>$fixtureMode==='tie'?'0':'1'];
   return $rows;
  }
  if(strpos($sql,'MUPanicFracturaLaureatesV34')!==false)return $fixtureMode==='empty' || $fixtureMode==='tie'?(strpos($sql,'UNION ALL')!==false?[['hasRow'=>'0']]:false):[['hasRow'=>'1','name'=>$fixtureMode==='xss'?'A<script>':'LuisADM','family'=>'1','contribution'=>'1520','untilUnix'=>'1792980900'],['hasRow'=>'1','name'=>'MGTeste','family'=>'2','contribution'=>'1482','untilUnix'=>'1792980900']];
  if(strpos($sql,'MUPanicFracturaWeekRulesV34')!==false)return [
   ['result'=>'W','contribution'=>'2000','fragments'=>'3','bless'=>'2','soul'=>'2','chaos'=>'1'],
   ['result'=>'L','contribution'=>'500','fragments'=>'1','bless'=>'0','soul'=>'0','chaos'=>'1'],
   ['result'=>'T','contribution'=>'1000','fragments'=>'2','bless'=>'1','soul'=>'1','chaos'=>'0']];
  throw new RuntimeException('Unexpected view');
 }
}
define('access',true);define('__BASE_URL__','/');define('__PATH_TEMPLATE__','/overlay/templates/mupanic/');
$art=__PATH_TEMPLATE__.'img/fractura/';
require_once __DIR__.'/../../overlay/templates/mupanic/inc/fractura-data.php';
echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="/overlay/templates/mupanic/css/fractura.css"><link rel="stylesheet" href="/overlay/templates/mupanic/css/fractura-hall.css"><style>body{margin:0;background:#070b12;color:#dccab2;font-family:Arial,sans-serif}*{box-sizing:border-box}a{color:inherit;text-decoration:none}.eyebrow{font-size:10px;letter-spacing:.15em;color:#b99b6d}h2,h3,h4{font-family:Georgia,serif}body{--display:Georgia,serif}.wiki-section{max-width:1000px;margin:40px auto;padding:0 20px}.wiki-table-scroll table{width:100%;border-collapse:collapse}.wiki-table-scroll :is(td,th){padding:15px;border-bottom:1px solid #8b6a4138;text-align:left}.wiki-table-scroll th{color:#c6a777}.wiki-section h3{font-size:33px;font-weight:400}.wiki-section h2{font-weight:400}</style></head><body class="is-fractura">';
if($fixturePart==='page'){require __DIR__.'/../../overlay/templates/mupanic/inc/fractura.php';echo '<script src="/overlay/templates/mupanic/js/fractura.js"></script>';}
elseif($fixturePart==='atlas') {echo '<div class="panic-wiki">';require __DIR__.'/../../overlay/templates/mupanic/inc/atlas-fractura.php';echo '</div>';}
else {echo '<main class="fractura-page">';require __DIR__.'/../../overlay/templates/mupanic/inc/fractura-hall.php';echo '</main>';}
echo '</body></html>';
