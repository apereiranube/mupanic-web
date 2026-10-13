<?php
/** Public Gens projection: SELECT only, no rewards, resets or contribution writes. */
function panicFracturaNumber($v) {
    $v=(string)$v;
    return preg_match('/^[0-9]{1,38}$/D',$v) ? preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $v) : '—';
}
function panicFracturaCompare($a,$b) {
    $a=ltrim((string)$a,'0') ?: '0'; $b=ltrim((string)$b,'0') ?: '0';
    return strlen($a)===strlen($b) ? strcmp($a,$b) : (strlen($a)<strlen($b) ? -1 : 1);
}
function panicFracturaValid($d) {
    if(!is_array($d) || ($d['schema'] ?? null)!==1 || !is_int($d['updated'] ?? null) || $d['updated']>time()+60 || !is_array($d['factions'] ?? null)) return false;
    foreach([1,2] as $id) foreach(['points','members'] as $key) {
        $v=$d['factions'][$id][$key] ?? null;
        if(!is_string($v) || !preg_match('/^[0-9]{1,38}$/D',$v)) return false;
    }
    return true;
}
function panicFracturaRead() {
    $cache=defined('__PATH_CACHE__') ? rtrim(__PATH_CACHE__,'/\\').'/mupanic-fractura-v1.json' : null;
    $old=$cache && is_file($cache) && !is_link($cache) ? json_decode((string)@file_get_contents($cache),true) : null;
    $valid=panicFracturaValid($old);
    if($valid && $old['updated']>time()-60) return $old;
    try {
        $db=Connection::Database('MuOnline');
        if(!$db) throw new RuntimeException('Unavailable');
        // Check native columns before use. A mismatch is unavailable, never zero.
        $schema=$db->query_fetch("SELECT c.[name] AS [columnName] FROM [MuOnline43].[sys].[columns] c INNER JOIN [MuOnline43].[sys].[tables] t ON t.[object_id]=c.[object_id] INNER JOIN [MuOnline43].[sys].[schemas] s ON s.[schema_id]=t.[schema_id] WHERE s.[name]='dbo' AND t.[name]='Gens_Rank' AND c.[name] IN ('Name','Family','Contribution')");
        $columns=[];
        foreach(is_array($schema)?$schema:[] as $v) $columns[]=(string)($v['columnName'] ?? '');
        if(array_diff(['Name','Family','Contribution'],$columns)) throw new RuntimeException('Schema unavailable');
        // Complete faction totals, not a TOP ranking. Decimal avoids SQL overflow.
        $rows=$db->query_fetch("SELECT [Family] AS [family], CONVERT(varchar(38),COALESCE(SUM(CONVERT(decimal(38,0),CASE WHEN [Contribution]>0 THEN [Contribution] ELSE 0 END)),0)) AS [points], CONVERT(varchar(38),COUNT_BIG(*)) AS [members] FROM [MuOnline43].[dbo].[Gens_Rank] WHERE [Family] IN (1,2) GROUP BY [Family]");
        if($rows===false || ($rows!==null && !is_array($rows))) throw new RuntimeException('Unavailable');
        $data=['schema'=>1,'updated'=>time(),'stale'=>false,'factions'=>[1=>['points'=>'0','members'=>'0'],2=>['points'=>'0','members'=>'0']]];
        foreach(is_array($rows)?$rows:[] as $row) {
            $id=(int)($row['family'] ?? 0);
            if(!in_array($id,[1,2],true)) throw new RuntimeException('Invalid family');
            $data['factions'][$id]=['points'=>(string)($row['points'] ?? ''),'members'=>(string)($row['members'] ?? '')];
        }
        if(!panicFracturaValid($data)) throw new RuntimeException('Invalid totals');
        if($cache && !is_link($cache)) {
            $temp=@tempnam(dirname($cache),'panic-fractura-');
            if($temp!==false) {
                if(@file_put_contents($temp,json_encode($data),LOCK_EX)!==false) @rename($temp,$cache);
                if(is_file($temp)) @unlink($temp);
            }
        }
        return $data;
    } catch(Throwable $e) {
        if($valid && $old['updated']>time()-86400) { $old['stale']=true; return $old; }
        return null;
    }
}
