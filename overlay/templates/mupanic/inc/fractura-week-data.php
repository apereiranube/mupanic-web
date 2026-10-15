<?php
/** Read-only weekly contest. Never awards, closes weeks or exposes accounts. */
function panicFracturaWeekInteger($v) {
    $s=(string)$v;
    if(!preg_match('/^[0-9]{1,18}$/D',$s)) throw new RuntimeException('Invalid weekly number');
    return $s;
}
function panicFracturaWeekDate($v) {
    if(!is_string($v) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D',$v)) throw new RuntimeException('Invalid week date');
    $d=DateTimeImmutable::createFromFormat('!Y-m-d',$v,new DateTimeZone('America/Argentina/Buenos_Aires'));
    if(!$d || $d->format('Y-m-d')!==$v) throw new RuntimeException('Invalid week date');
    return $v;
}
function panicFracturaWeekRules($rows) {
    if(!is_array($rows) || count($rows)!==3) throw new RuntimeException('Weekly rules unavailable');
    $out=[];
    foreach($rows as $r) {
        $key=(string)($r['result']??'');
        if(!in_array($key,['W','L','T'],true) || isset($out[$key])) throw new RuntimeException('Invalid weekly rule');
        $out[$key]=[];
        foreach(['contribution','fragments','bless','soul','chaos'] as $field) $out[$key][$field]=panicFracturaWeekInteger($r[$field]??'');
    }
    return $out;
}
function panicFracturaWeekRead() {
    // No fallback to cumulative contribution: unavailable weekly data stays unavailable.
    try {
        $db=Connection::Database('MuOnline');
        if(!$db) throw new RuntimeException('Weekly connection unavailable');
        $current=$db->query_fetch("SELECT WeekStart AS weekStart,NowUnix AS nowUnix,StartUnix AS startUnix,CloseUnix AS closeUnix,MinimumQuests AS minimumQuests,BossPoints AS bossPoints,Enabled AS enabled,IsClosed AS isClosed,NexoPoints AS nexoPoints,CaosPoints AS caosPoints FROM [MuOnline43].[dbo].[MUPanicFracturaWeekCurrentV34]");
        if(!is_array($current) || count($current)!==1) throw new RuntimeException('Weekly settings unavailable');
        $r=$current[0]; $week=['weekStart'=>panicFracturaWeekDate($r['weekStart']??'')];
        foreach(['nowUnix','startUnix','closeUnix','minimumQuests','bossPoints','nexoPoints','caosPoints'] as $key) $week[$key]=panicFracturaWeekInteger($r[$key]??'');
        foreach(['enabled','isClosed'] as $key) {
            $flag=$r[$key]??null; $flag=is_bool($flag)?($flag?'1':'0'):(string)$flag;
            if(!in_array($flag,['0','1'],true)) throw new RuntimeException('Invalid weekly flag');
            $week[$key]=$flag==='1';
        }
        $history=$db->query_fetch("SELECT TOP(52) hasRow,weekStart,closeUnix,nexoPoints,caosPoints,winnerFamily FROM (SELECT 1 AS hasRow,WeekStart AS weekStart,CloseUnix AS closeUnix,NexoPoints AS nexoPoints,CaosPoints AS caosPoints,WinnerFamily AS winnerFamily FROM [MuOnline43].[dbo].[MUPanicFracturaWeekHistoryV34] UNION ALL SELECT 0,CAST(NULL AS varchar(10)),CAST(NULL AS bigint),CAST(NULL AS bigint),CAST(NULL AS bigint),CAST(NULL AS int) WHERE NOT EXISTS(SELECT 1 FROM [MuOnline43].[dbo].[MUPanicFracturaWeekHistoryV34])) AS historyRows ORDER BY weekStart DESC");
        if(!is_array($history) || !$history) throw new RuntimeException('Weekly history unavailable');
        $out=[];
        foreach($history as $row) {
            // A CMS adapter may return false for zero rows; a successful query now always has a row.
            $hasRow=(string)($row['hasRow']??'');
            if($hasRow==='0' && count($history)===1) continue;
            if($hasRow!=='1') throw new RuntimeException('Invalid history result');
            $date=panicFracturaWeekDate($row['weekStart']??'');
            $entry=['weekStart'=>$date];
            foreach(['closeUnix','nexoPoints','caosPoints'] as $key) $entry[$key]=panicFracturaWeekInteger($row[$key]??'');
            $winner=(string)($row['winnerFamily']??'');
            if(!in_array($winner,['0','1','2'],true)) throw new RuntimeException('Invalid winner');
            $entry['winnerFamily']=(int)$winner;
            // Trust only finalized SQL records, never predict a champion from live scores.
            $out[]=$entry;
        }
        return ['schema'=>2,'current'=>$week,'history'=>$out];
    } catch(Throwable $e) { return null; }
}
