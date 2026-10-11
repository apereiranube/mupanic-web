<?php
require __DIR__.'/../overlay/templates/mupanic/inc/market-data.php';
class Connection {
    public static $mode='normal';public static $queries=[];
    public static function Database($name) { if($name!=='MuOnline')throw new Exception('Alias');return new self; }
    public function query_fetch($sql) {
        self::$queries[]=$sql;
        if(self::$mode==='error')return false;
        if(strpos($sql,'AS serverDay')!==false)return [['total'=>self::$mode==='empty'?'0':'2','today'=>self::$mode==='empty'?'0':'2','zen'=>'9007199254740993','eryns'=>'100','serverDay'=>'2026-10-10']];
        if(strpos($sql,'SELECT TOP 40')!==false) return self::$mode==='empty'?null:[['id'=>'00000000-0000-0000-0000-000000000001','buyer'=>'Juanita','seller'=>'MGTeste','item'=>'<script>alert(1)</script>','itemIndex'=>2593,'level'=>15,'currency'=>1,'price'=>100,'soldAt'=>'2026-10-10T20:02:35.000','AccountID'=>'SECRET','InventoryBefore'=>'SECRET']];
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
check(strpos(panicMarketSalesSource(),"'CREDIT_ATTEMPTED'")!==false && strpos(panicMarketSalesSource(),"'PAID'")===false,'count delivered sales while payout remains pending');
foreach(Connection::$queries as $q)check(!preg_match('/\b(UPDATE|INSERT|DELETE|EXEC|DROP|ALTER)\b/i',$q),'query is read only');
Connection::$mode='empty';$d=panicMarketRead(true);
check($d['sales']===[] && count($d['days'])===7 && $d['metrics']['total']==='0','successful empty queries return genuine empty state');
Connection::$mode='error';check(panicMarketRead(true)===null,'database errors do not invent totals');
