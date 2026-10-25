<?php
// Read-only SQL fixture: no production connection or reward writes.
define('__PATH_CACHE__',sys_get_temp_dir().'/panic-fractura-test-'.getmypid());
mkdir(__PATH_CACHE__,0700);
$cache=__PATH_CACHE__.'/mupanic-fractura-totals-v6.json';
$rows=[];$queries=0;
class Connection {
    static function Database($name) { return new self; }
    function query_fetch($sql) {
        global $rows,$queries;
        $queries++;
        if(strpos($sql,'[sys].[views]')!==false) return array_map(function($v){return ['columnName'=>$v];},['Family','Points','Members','Contribution','VictoryPoints']);
        if(strpos($sql,'[MUPanicFracturaFactionTotalsV6]')===false || strpos($sql,'SELECT ')!==0) throw new RuntimeException('Unexpected query');
        return $rows;
    }
}
require __DIR__.'/../overlay/templates/mupanic/inc/fractura-data.php';
function check($ok,$message) { if(!$ok) throw new RuntimeException($message); }
try {
    $rows=[['family'=>1,'points'=>'245','members'=>'1'],['family'=>2,'points'=>'2100','members'=>'3']];
    $d=panicFracturaRead();check($d['factions'][2]['points']==='2100','Combined total');
    $q=$queries;$d=panicFracturaRead();check($queries===$q,'60-second cache');
    unlink($cache);
    $rows=[['family'=>1,'points'=>'0','members'=>'0'],['family'=>2,'points'=>'99999999999999999999999999999999999999','members'=>'3']];
    $d=panicFracturaRead();check($d['factions'][1]['points']==='0','Legitimate zero');
    check($d['factions'][2]['points']===$rows[1]['points'],'Exact 38-digit total');
    unlink($cache);
    $rows=[['family'=>1,'points'=>'0','members'=>'0']];check(panicFracturaRead()===null,'Missing faction');
    $rows=[['family'=>1,'points'=>'0','members'=>'0'],['family'=>1,'points'=>'0','members'=>'0']];check(panicFracturaRead()===null,'Duplicate faction');
    $rows=[['family'=>1,'points'=>'bad','members'=>'0'],['family'=>2,'points'=>'0','members'=>'0']];check(panicFracturaRead()===null,'Invalid number');
    $d['updated']=time()-61;file_put_contents($cache,json_encode($d));$rows=false;
    $stale=panicFracturaRead();check($stale!==null && $stale['stale']===true,'Stale fallback');
    $d['updated']=time()-86401;file_put_contents($cache,json_encode($d));check(panicFracturaRead()===null,'Expired cache');
    echo "PASS: combined and exact totals, zero, cache, stale/failure, invalid rows. SQL mocked.\n";
} finally {
    if(is_file($cache)) unlink($cache);
    rmdir(__PATH_CACHE__);
}
