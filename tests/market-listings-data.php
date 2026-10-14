<?php
require __DIR__.'/../overlay/templates/mupanic/inc/market-listings-data.php';
class Connection {
 public static $mode='normal';public static $sql=[];
 public static function Database($alias){if($alias!=='MuOnline')throw new Exception('Wrong database');return new self;}
 public function query_fetch($q){
  self::$sql[]=$q;
  if(self::$mode==='offline')return false;
  if(strpos($q,'AS totalListings')!==false)return [['totalListings'=>self::$mode==='empty'?'0':'2']];
  $row=['id'=>'21','seller'=>'JugadorUno','item'=>'Deadly Staff','itemIndex'=>2593,'level'=>15,'currency'=>1,'price'=>'9007199254740993','expiresAt'=>'2026-10-18T12:30:00.000','featuredUntil'=>'2026-10-15T12:30:00.000','featured'=>1,'AccountID'=>'NEVER_PUBLIC','ItemCode'=>'NEVER_PUBLIC'];
  if(strpos($q,'TOP 6')!==false || strpos($q,'OFFSET')!==false)return self::$mode==='empty'?null:[$row];
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
check(count(Connection::$sql)===3,'bounded read query count');
foreach(Connection::$sql as $s) {
 check((bool)preg_match('/^\s*SELECT\b/i',$s),'SQL is SELECT');
 check(!preg_match('/\b(UPDATE|DELETE|INSERT|DROP|ALTER|EXEC|AccountID|ItemCode)\b/i',$s),'no mutations or private columns');
}
check(strpos(Connection::$sql[1],'ExpiresAt>GETDATE()')!==false,'only nonexpired listings');
check(strpos(Connection::$sql[1],'Deadly Staff')===false,'search is not embedded in executable query');
check(strpos(Connection::$sql[2],'FeaturedUntil>GETDATE()')!==false,'only active promotions');
Connection::$sql=[];
$data=panicMarketListingsRead(2,'0',"';DROP VIEW dbo.X--",true,'price-asc');
check($data['page']===1,'page clamped to available data');
check(strpos(Connection::$sql[1],'DROP VIEW')===false,'invalid search rejected');
check(strpos(Connection::$sql[1],'b.FeaturedUntil>GETDATE()')!==false,'featured-only filter');
Connection::$mode='empty';
$data=panicMarketListingsRead();
check($data['total']==='0' && $data['items']===[] && $data['featured']===[],'empty market is not invented');
Connection::$mode='offline';
check(panicMarketListingsRead()===null,'offline market returns unavailable, never stale offers');
