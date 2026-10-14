<?php
require __DIR__.'/../overlay/templates/mupanic/inc/market-listings-data.php';
class Connection {
 public static $mode='normal';public static $sql=[];
 public static function Database($alias){if($alias!=='MuOnline')throw new Exception('Wrong database');return new self;}
 public function query_fetch($query){
  self::$sql[]=$query;
  if(self::$mode==='offline')return false;
  if(strpos($query,'COL_LENGTH')!==false)return [['canDetails'=>self::$mode==='v2'?1:0]];
  if(strpos($query,'AS totalListings')!==false)return [['totalListings'=>self::$mode==='empty'?'0':'11']];
  $ordinary=['id'=>'21','seller'=>'JugadorUno','item'=>'Deadly Staff','itemIndex'=>2593,'level'=>15,'currency'=>1,'price'=>'9007199254740993','expiresAt'=>'2026-10-18T12:30:00.000','featuredUntil'=>null,'featured'=>0,'AccountID'=>'NEVER_PUBLIC','ItemCode'=>'NEVER_PUBLIC'];
  $featured=['id'=>'22','seller'=>'JugadorDos','item'=>'Crescent Axe','itemIndex'=>3,'level'=>15,'currency'=>1,'price'=>'1000','expiresAt'=>'2026-10-18T12:30:00.000','featuredUntil'=>'2026-10-15T12:30:00.000','featured'=>1,'AccountID'=>'NEVER_PUBLIC','ItemCode'=>'NEVER_PUBLIC'];
  if(self::$mode==='v2'){
   $options=['skillFlag'=>1,'luckFlag'=>1,'durability'=>255,'additionalOption'=>28,'excellentMask'=>40,'ancientCode'=>0,'harmonyCode'=>0,'option380Flag'=>0,'socket1'=>255,'socket2'=>255,'socket3'=>255,'socket4'=>255,'socket5'=>255,'lotCount'=>0];
   $ordinary+=$options;$featured+=$options;
  }
  if(strpos($query,'TOP 10')!==false)return self::$mode==='empty'?null:[$featured];
  if(strpos($query,'OFFSET')!==false)return self::$mode==='empty'?null:[$ordinary];
  throw new Exception('Unexpected SQL: '.$query);
 }
}
function check($value,$label){if(!$value)throw new Exception($label);echo "PASS: $label\n";}
$data=panicMarketListingsRead(1,'1','Deadly Staff','recent');
check($data['schema']===1 && $data['available'],'public listings schema');
check($data['pageSize']===8 && $data['pages']===2 && $data['total']==='11','eleven ordinary offers span pages of eight');
check($data['items'][0]['price']==='9007199254740993','exact BIGINT price');
check($data['items'][0]['featured']===false && $data['items'][0]['featuredUntil']===null,'ordinary card is never featured');
check($data['featured'][0]['featured']===true,'promoted item remains in the dedicated section');
check($data['featured'][0]['featuredUntil']==='2026-10-15T12:30:00.000-03:00','promotion expiry is normalized internally');
check($data['items'][0]['details']===null && $data['itemOptions']===false,'old SQL V1 does not fabricate options');
check(strpos(json_encode($data),'NEVER_PUBLIC')===false,'private fields are absent');
check(count(Connection::$sql)===4,'one schema read and three bounded listing queries');
foreach(Connection::$sql as $sql){
 check((bool)preg_match('/^\s*SELECT\b/i',$sql),'only SELECT operations');
 check(!preg_match('/\b(UPDATE|DELETE|INSERT|DROP|ALTER|EXEC|AccountID|ItemCode)\b/i',$sql),'no mutation or account item-code leakage');
}
check(strpos(Connection::$sql[1],'b.FeaturedUntil IS NULL OR b.FeaturedUntil<=GETDATE()')!==false,'the count excludes promoted offers');
check(strpos(Connection::$sql[2],'b.FeaturedUntil IS NULL OR b.FeaturedUntil<=GETDATE()')!==false,'the regular listing excludes promoted offers');
check(strpos(Connection::$sql[2],'FETCH NEXT 8 ROWS ONLY')!==false,'only eight offers are fetched');
check(strpos(Connection::$sql[2],'Deadly Staff')===false,'user search cannot interpolate executable SQL');
check(strpos(Connection::$sql[3],'b.FeaturedUntil>GETDATE()')!==false,'featured query uses only currently promoted offers');
Connection::$sql=[];
$data=panicMarketListingsRead(2,'0',"';DROP VIEW dbo.X--",'price-asc');
check($data['page']===2,'page two is accepted when eleven offers exist');
check(strpos(Connection::$sql[2],'OFFSET 8 ROWS FETCH NEXT 8 ROWS ONLY')!==false,'page two skips the first eight');
check(strpos(Connection::$sql[2],'DROP VIEW')===false,'invalid search is never embedded');
Connection::$mode='v2';
$data=panicMarketListingsRead();
check($data['itemOptions']===true && $data['items'][0]['details']['skill']===true,'SQL V2 exposes real skill');
check($data['items'][0]['details']['luck']===true && $data['items'][0]['details']['additional']===28,'real luck and +28 option');
check(count($data['items'][0]['details']['excellent'])===2,'excellent mask expands safely');
Connection::$mode='empty';
$data=panicMarketListingsRead();
check($data['total']==='0' && $data['items']===[] && $data['featured']===[],'empty state is not invented');
Connection::$mode='offline';
check(panicMarketListingsRead()===null,'offline view cannot display stale offers');
