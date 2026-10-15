<?php
function panicFracturaRanks() {
    static $ranks;
    if($ranks===null) {
        $ranks=json_decode(file_get_contents(__DIR__.'/fractura-ranks.json'),true);
        if(!is_array($ranks) || count($ranks)!==6) throw new RuntimeException('Ranks unavailable');
    }
    return $ranks;
}
function panicFracturaRankName($family,$points) {
    if(!in_array((int)$family,[1,2],true)) return 'Sin bando';
    $key=(int)$family===1?'nexo':'caos';$name='';
    foreach(panicFracturaRanks() as $rank) {
        if((int)$points<(int)$rank['minimum']) break;
        $name=$rank[$key];
    }
    return $name;
}
function panicFracturaDistinctionsRead() {
    try {
        $db=Connection::Database('MuOnline');if(!$db) throw new RuntimeException('Unavailable');
        $rows=$db->query_fetch("SELECT 1 AS hasRow,CharacterName AS name,Family AS family,Contribution AS contribution,DistinctionUntilUnix AS untilUnix FROM [MuOnline43].[dbo].[MUPanicFracturaLaureatesV34] UNION ALL SELECT 0,CAST(NULL AS varchar(10)),CAST(NULL AS int),CAST(NULL AS int),CAST(NULL AS bigint) WHERE NOT EXISTS(SELECT 1 FROM [MuOnline43].[dbo].[MUPanicFracturaLaureatesV34]) ORDER BY family,name");
        if(!is_array($rows) || !$rows) throw new RuntimeException('Unavailable');
        $out=[];
        foreach($rows as $r) {
            // The SQL sentinel proves a successful empty read. false/null still means failure.
            $hasRow=(string)($r['hasRow']??'');
            if($hasRow==='0' && count($rows)===1) return [];
            if($hasRow!=='1') throw new RuntimeException('Invalid distinction result');
            $name=$r['name']??null;$family=(string)($r['family']??'');
            if(!is_string($name) || strlen($name)<1 || strlen($name)>10 || preg_match('/[\x00-\x1f\x7f]/',$name) || !in_array($family,['1','2'],true)) throw new RuntimeException('Invalid distinction');
            foreach(['contribution','untilUnix'] as $key) if(!preg_match('/^[0-9]{1,18}$/D',(string)($r[$key]??''))) throw new RuntimeException('Invalid distinction value');
            $out[]=['name'=>$name,'family'=>(int)$family,'rank'=>panicFracturaRankName((int)$family,$r['contribution']),'untilUnix'=>(string)$r['untilUnix']];
        }
        return $out;
    } catch(Throwable $e) { return null; }
}
