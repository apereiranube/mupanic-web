<?php
require __DIR__.'/../overlay/templates/mupanic/inc/market-listings-data.php';
class Connection {
 public static $mode='normal';public static $sql=[];
 public static function Database($alias){if($alias!=='MuOnline')throw new Exception('Wrong database');return new self;}
 public function query_fetch($q){
  self::$sql[]=$q;
  if(self::$mode==='offline')return false;
  if(strpos($q,'COL_LENGTH')!==false)return [['canDetails'=>self::$mode==='v2'?1:0]];
  if(strpos($q,'AS totalListings')!==false)return [['totalListings'=>self::$mode==='empty'?'0':'2']];
  $row=['id'=>'21','seller'=>'JugadorUno','item'=>'Deadly Staff','itemIndex'=>2593,'level'=>15,'currency'=>1,'price'=>'9007199254740993','expiresAt'=>'2026-10-18T12:30:00.000','featuredUntil'=>'2026-10-15T12:30:00.000','featured'=>1,'AccountID'=>'NEVER_PUBLIC','ItemCode'=>'NEVER_PUBLIC'];
  if(self::$mode==='v2')$row+=['skillFlag'=>1,'luckFlag'=>1,'durability'=>255,'additionalOption'=>28,'excellentMask'=>40,'ancientCode'=>0,'harmonyCode'=>0,'option380Flag'=>0,'socket1'=>255,'socket2'=>255,'socket3'=>255,'socket4'=>255,'socket5'=>255,'lotCount'=>0];
  if(strpos($q,'TOP 10')!==false || strpos($q,'OFFSET')!==false)return self::$mode==='empty'?null:[$row];
  throw new Exception('Unexpected SQL');
 }
}
function check($cond,$name){if(!$cond)throw new Exception($name);echo "PASS $name\n";}
$data=panicMarketListingsRead(1,'1','Deadly Staff',false,'recent');
check($data['schema']===1 && $data['available'],'correct public API schema');
check($data['items'][0]['price']==='9007199254740993','exact BIGINT price');
check($data['items'][0]['featured']===true,'real featured flag');
check($data['items'][0]['featuredUntil']==='2026-10-15T12:30:00.000-03:00','featured expiration timezone');
check(strpos(json_encode($data),'NEVER_PUBLIC')===false,'private item bytes and account not exposed');
check(count(Connection::$sql)===4,'bounded read query count including schema feature detection');
check($data['items'][0]['details']===null && $data['itemOptions']===false,'old SQL V1 degrades without fabricated options');
foreach(Connection::$sql as $s) {
 check((bool)preg_match('/^\s*SELECT\b/i',$s),'SQL is SELECT');
 check(!preg_match('/\b(UPDATE|DELETE|INSERT|DROP|ALTER|EXEC|AccountID|ItemCode)\b/i',$s),'no mutations or private columns');
}
check(strpos(Connection::$sql[2],'ExpiresAt>GETDATE()')!==false,'only nonexpired listings');
check(strpos(Connection::$sql[2],'Deadly Staff')===false,'search is not embedded in executable query');
check(strpos(Connection::$sql[3],'FeaturedUntil>GETDATE()')!==false,'only active promotions');
Connection::$sql=[];
$data=panicMarketListingsRead(2,'0',"';DROP VIEW dbo.X--",true,'price-asc');
check($data['page']===1,'page clamped to available data');
check(strpos(Connection::$sql[2],'DROP VIEW')===false,'invalid search rejected');
check(strpos(Connection::$sql[2],'b.FeaturedUntil>GETDATE()')!==false,'featured-only filter');
Connection::$mode='v2';$data=panicMarketListingsRead();
check($data['itemOptions']===true && $data['items'][0]['details']['skill']===true,'SQL V2 publishes verified Skill flag');
check($data['items'][0]['details']['luck']===true && $data['items'][0]['details']['additional']===28,'Luck and +28 option parsed');
check(count($data['items'][0]['details']['excellent'])===2,'Excellent bitmask expands to two options');
Connection::$mode='empty';
$data=panicMarketListingsRead();
check($data['total']==='0' && $data['items']===[] && $data['featured']===[],'empty market is not invented');
Connection::$mode='offline';
check(panicMarketListingsRead()===null,'offline market returns unavailable, never stale offers');
