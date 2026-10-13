<?php
define('__PATH_CACHE__',sys_get_temp_dir().'/panic-fractura-test-'.bin2hex(random_bytes(8)));
mkdir(__PATH_CACHE__);
require __DIR__.'/../overlay/templates/mupanic/inc/fractura-data.php';
class Connection {
    public static $mode='normal';
    public static $queries=[];
    public static function Database($alias) {
        if($alias!=='MuOnline') throw new Exception('Wrong alias');
        return new self;
    }
    public function query_fetch($sql) {
        self::$queries[]=$sql;
        if(self::$mode==='failure') return false;
        if(strpos($sql,'columnName')!==false) return self::$mode==='schema' ? [['columnName'=>'Name']] : [['columnName'=>'Name'],['columnName'=>'Family'],['columnName'=>'Contribution']];
        if(self::$mode==='empty') return null;
        if(self::$mode==='invalid') return [['family'=>1,'points'=>'-2','members'=>'1']];
        return [['family'=>1,'points'=>'9007199254740993','members'=>'3'],['family'=>2,'points'=>'2000','members'=>'2']];
    }
}
function assertFractura($ok,$message) { if(!$ok) throw new RuntimeException($message); echo "PASS: $message\n"; }
assertFractura(panicFracturaCompare('9007199254740993','9007199254740992')>0,'lead distinguishes totals beyond float precision');
$cache=__PATH_CACHE__.'/mupanic-fractura-v1.json';
try {
    $data=panicFracturaRead();
    assertFractura($data['factions'][1]['points']==='9007199254740993','large totals preserve every digit');
    assertFractura(panicFracturaNumber($data['factions'][1]['points'])==='9.007.199.254.740.993','display preserves large counters');
    $calls=count(Connection::$queries); panicFracturaRead();
    assertFractura(count(Connection::$queries)===$calls,'fresh cache avoids repeated SQL');
    $data['updated']=time()-120;file_put_contents($cache,json_encode($data));Connection::$mode='failure';
    $stale=panicFracturaRead();
    assertFractura($stale['stale']===true && $stale['factions'][1]['points']===$data['factions'][1]['points'],'failure explicitly retains last valid snapshot');
    unlink($cache);
    assertFractura(panicFracturaRead()===null,'failure without snapshot is unavailable, not zero');
    Connection::$mode='schema';
    assertFractura(panicFracturaRead()===null,'missing native columns are unavailable');
    Connection::$mode='invalid';
    assertFractura(panicFracturaRead()===null,'invalid SQL counters are rejected');
    Connection::$mode='empty';$empty=panicFracturaRead();
    assertFractura($empty['factions'][1]['points']==='0' && $empty['factions'][2]['points']==='0','successful empty table gives actual zeros');
    $empty['updated']=time()-86500;file_put_contents($cache,json_encode($empty));Connection::$mode='failure';
    assertFractura(panicFracturaRead()===null,'expired fallback is unavailable');
    foreach(Connection::$queries as $sql) assertFractura(!preg_match('/\b(UPDATE|INSERT|DELETE|EXEC|DROP|ALTER)\b/i',$sql),'all SQL is read only');
    $totals=implode("\n",array_filter(Connection::$queries,function($q){return strpos($q,'AS [points]')!==false;}));
    assertFractura(strpos($totals,'TOP ')===false && strpos($totals,'GROUP BY [Family]')!==false,'totals use all family members, not top ranking');
} finally {
    if(is_file($cache)) unlink($cache);
    rmdir(__PATH_CACHE__);
}
