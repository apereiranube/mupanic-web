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
        $rows=$db->query_fetch("DECLARE @Now bigint=DATEDIFF(second,CONVERT(datetime2,'19700101',112),SYSUTCDATETIME()); SELECT r.CharacterName AS name,r.Family AS family,g.Contribution AS contribution,MAX(r.DistinctionUntilUnix) AS untilUnix FROM [MuOnline43].[dbo].[MUPanicWeekClaimsV31] r JOIN [MuOnline43].[dbo].[Character] c ON c.Name=r.CharacterName AND c.AccountID=r.AccountID JOIN [MuOnline43].[dbo].[Gens_Rank] g ON g.Name=c.Name AND g.Family=r.Family WHERE r.Result='W' AND r.State='DELIVERED' AND r.DistinctionUntilUnix>@Now GROUP BY r.CharacterName,r.Family,g.Contribution ORDER BY r.Family,r.CharacterName");
        if(!is_array($rows)) throw new RuntimeException('Unavailable');
        $out=[];
        foreach($rows as $r) {
            $name=$r['name']??null;$family=(string)($r['family']??'');
            if(!is_string($name) || strlen($name)<1 || strlen($name)>10 || preg_match('/[\x00-\x1f\x7f]/',$name) || !in_array($family,['1','2'],true)) throw new RuntimeException('Invalid distinction');
            foreach(['contribution','untilUnix'] as $key) if(!preg_match('/^[0-9]{1,18}$/D',(string)($r[$key]??''))) throw new RuntimeException('Invalid distinction value');
            $out[]=['name'=>$name,'family'=>(int)$family,'rank'=>panicFracturaRankName((int)$family,$r['contribution']),'untilUnix'=>(string)$r['untilUnix']];
        }
        return $out;
    } catch(Throwable $e) { return null; }
}
