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
        $current=$db->query_fetch("DECLARE @Now datetime2=SYSUTCDATETIME();
DECLARE @Arg datetime2=DATEADD(hour,-3,@Now);
DECLARE @Week date=DATEADD(day,-(DATEDIFF(day,CONVERT(date,'19000101',112),@Arg)%7),CONVERT(date,@Arg));
DECLARE @Start bigint=DATEDIFF(second,CONVERT(datetime2,'19700101',112),DATEADD(hour,3,CONVERT(datetime2,@Week)));
DECLARE @Close bigint=(SELECT DATEDIFF(second,CONVERT(datetime2,'19700101',112),DATEADD(hour,3,DATEADD(minute,CloseMinute,DATEADD(day,6,CONVERT(datetime2,@Week))))) FROM [MuOnline43].[dbo].[MUPanicWeekSettingsV31] WHERE Id=1);
SELECT CONVERT(varchar(10),@Week,23) AS weekStart,DATEDIFF(second,CONVERT(datetime2,'19700101',112),@Now) AS nowUnix,
 @Start AS startUnix,@Close AS closeUnix,s.MinimumQuests AS minimumQuests,s.BossPoints AS bossPoints,s.Enabled AS enabled,
 CASE WHEN w.WeekStart IS NULL THEN 0 ELSE 1 END AS isClosed,
 COALESCE(w.NexoPoints,(SELECT COUNT_BIG(*) FROM [MuOnline43].[dbo].[MUPanicFracturaKillVictoriesV6] v WHERE v.IsTest=0 AND v.WinnerFamily=1 AND v.DeathUnix>=@Start AND v.DeathUnix<@Close)*CONVERT(bigint,s.BossPoints)) AS nexoPoints,
 COALESCE(w.CaosPoints,(SELECT COUNT_BIG(*) FROM [MuOnline43].[dbo].[MUPanicFracturaKillVictoriesV6] v WHERE v.IsTest=0 AND v.WinnerFamily=2 AND v.DeathUnix>=@Start AND v.DeathUnix<@Close)*CONVERT(bigint,s.BossPoints)) AS caosPoints
FROM [MuOnline43].[dbo].[MUPanicWeekSettingsV31] s LEFT JOIN [MuOnline43].[dbo].[MUPanicWeeksV31] w ON w.WeekStart=@Week
WHERE s.Id=1 AND @Week>=s.EffectiveStart");
        if(!is_array($current) || count($current)!==1) throw new RuntimeException('Weekly settings unavailable');
        $r=$current[0]; $week=['weekStart'=>panicFracturaWeekDate($r['weekStart']??'')];
        foreach(['nowUnix','startUnix','closeUnix','minimumQuests','bossPoints','nexoPoints','caosPoints'] as $key) $week[$key]=panicFracturaWeekInteger($r[$key]??'');
        foreach(['enabled','isClosed'] as $key) {
            if(!in_array((string)($r[$key]??''),['0','1'],true)) throw new RuntimeException('Invalid weekly flag');
            $week[$key]=(string)$r[$key]==='1';
        }
        $rules=panicFracturaWeekRules($db->query_fetch("SELECT Result AS result,Contribution AS contribution,Fragments AS fragments,Bless AS bless,Soul AS soul,Chaos AS chaos FROM [MuOnline43].[dbo].[MUPanicWeekRulesV31]"));
        $history=$db->query_fetch("SELECT TOP(52) CONVERT(varchar(10),WeekStart,23) AS weekStart,CloseUnix AS closeUnix,NexoPoints AS nexoPoints,CaosPoints AS caosPoints,WinnerFamily AS winnerFamily FROM [MuOnline43].[dbo].[MUPanicWeeksV31] ORDER BY WeekStart DESC");
        $frozen=$db->query_fetch("SELECT CONVERT(varchar(10),r.WeekStart,23) AS weekStart,r.Result AS result,r.Contribution AS contribution,r.Fragments AS fragments,r.Bless AS bless,r.Soul AS soul,r.Chaos AS chaos FROM [MuOnline43].[dbo].[MUPanicWeekFrozenRulesV31] r INNER JOIN (SELECT TOP(52) WeekStart FROM [MuOnline43].[dbo].[MUPanicWeeksV31] ORDER BY WeekStart DESC) w ON w.WeekStart=r.WeekStart ORDER BY r.WeekStart DESC,r.Result");
        if(!is_array($history) || !is_array($frozen)) throw new RuntimeException('Weekly history unavailable');
        $byWeek=[];foreach($frozen as $row) $byWeek[panicFracturaWeekDate($row['weekStart']??'')][]=$row;
        $out=[];
        foreach($history as $row) {
            $date=panicFracturaWeekDate($row['weekStart']??'');
            $entry=['weekStart'=>$date,'rules'=>panicFracturaWeekRules($byWeek[$date]??null)];
            foreach(['closeUnix','nexoPoints','caosPoints'] as $key) $entry[$key]=panicFracturaWeekInteger($row[$key]??'');
            $winner=(string)($row['winnerFamily']??'');
            if(!in_array($winner,['0','1','2'],true)) throw new RuntimeException('Invalid winner');
            $entry['winnerFamily']=(int)$winner;
            // Trust only finalized SQL records, never predict a champion from live scores.
            $out[]=$entry;
        }
        return ['schema'=>1,'current'=>$week,'rules'=>$rules,'history'=>$out];
    } catch(Throwable $e) { return null; }
}
