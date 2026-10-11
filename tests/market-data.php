<?php
require __DIR__.'/../overlay/templates/mupanic/inc/market-data.php';
class Connection {
    public static $mode='normal';public static $queries=[];
    public static function Database($name) { if($name!=='MuOnline')throw new Exception('Alias');return new self; }
    public function query_fetch($sql) {
        self::$queries[]=$sql;
        if(self::$mode==='error')return false;
        if(strpos($sql,'AS serverDay')!==false)return [['total'=>self::$mode==='empty'?'0':'2','today'=>self::$mode==='empty'?'0':'2','zen'=>'9007199254740993','eryns'=>'100','zenToday'=>'1000000','erynsToday'=>'100','serverDay'=>'2026-10-10']];
        if(strpos($sql,'SELECT TOP 40')!==false) return self::$mode==='empty'?null:[['id'=>'00000000-0000-0000-0000-000000000001','buyer'=>'Juanita','seller'=>'MGTeste','item'=>'<script>alert(1)</script>','itemIndex'=>2593,'level'=>15,'currency'=>1,'price'=>100,'soldAt'=>'2026-10-10T20:02:35.000','AccountID'=>'SECRET','InventoryBefore'=>'SECRET']];
        if(strpos($sql,'SELECT TOP 5')!==false)return self::$mode==='empty'?null:[['item'=>'Chromatic Staff','itemIndex'=>2593,'level'=>15,'trades'=>'120','AccountID'=>'SECRET']];
        if(strpos($sql,'priceRank')!==false)return self::$mode==='empty'?null:[['id'=>'expensive','buyer'=>'Juanita','seller'=>'MGTeste','item'=>'Ancient Staff','itemIndex'=>2594,'level'=>15,'currency'=>1,'price'=>900,'soldAt'=>'2026-10-10T12:02:00','AccountID'=>'SECRET']];
        if(strpos($sql,'sellerRank')!==false)return self::$mode==='empty'?null:[['seller'=>'MGTeste','currency'=>1,'volume'=>'9007199254740993','trades'=>'77','AccountID'=>'SECRET']];
        return self::$mode==='empty'?null:[['day'=>'2026-10-10','trades'=>'2','zen'=>'1000000','eryns'=>'100']];
    }
}
function check($test,$message){if(!$test)throw new Exception($message);echo "PASS: $message\n";}
$d=panicMarketRead(true);
check($d['metrics']['zen']==='9007199254740993','large totals preserve every digit');
check(panicMarketNumber($d['metrics']['zen'])==='9.007.199.254.740.993','large totals format without floating point');
check(panicMarketNumber('')==='—','unknown is not zero');
check(count($d['days'])===7 && $d['days'][0]['day']==='2026-10-04','seven days including zero activity');
check($d['days'][0]['trades']==='0' && $d['days'][6]['trades']==='2','days contain real counts');
check(strpos(json_encode($d),'SECRET')===false && !isset($d['sales'][0]['AccountID']),'public projection excludes account and inventory fields');
check($d['sales'][0]['soldAt']==='2026-10-10T20:02:35.000-03:00','server times display as Argentina');
check(panicMarketEscape('<script>')==='&lt;script&gt;','item labels cannot inject markup');
check(strpos(panicMarketSalesSource(),'PanicMarketPublicSales')!==false && strpos(panicMarketSalesSource(),'PanicMarketBuyProbe')===false,'read only the dedicated public SQL view');
foreach(Connection::$queries as $q)check(!preg_match('/\b(UPDATE|INSERT|DELETE|EXEC|DROP|ALTER)\b/i',$q),'query is read only');
check($d['highlights']['popular'][0]['trades']==='120','popular item uses full day beyond browser last-40 rows');
check($d['highlights']['sellers'][0]['volume']==='9007199254740993','seller volume preserves bigint precision');
check($d['highlights']['records'][0]['item']==='Ancient Staff','daily maximum is independent of latest transactions');
foreach(Connection::$queries as $q)if(strpos($q,'priceRank')!==false || strpos($q,'sellerRank')!==false || strpos($q,'SELECT TOP 5')!==false)check(strpos($q,'CONVERT(date,GETDATE())')!==false && strpos($q,'DATEADD(day,1')!==false,'daily highlights use bounded server calendar day');
Connection::$mode='empty';$d=panicMarketRead(true);
check($d['highlights']===['popular'=>[],'records'=>[],'sellers'=>[]] && $d['sales']===[] && count($d['days'])===7 && $d['metrics']['total']==='0','successful empty queries return genuine empty state');
Connection::$mode='error';check(panicMarketRead(true)===null,'database errors do not invent totals');
