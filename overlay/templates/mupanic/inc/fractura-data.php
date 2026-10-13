<?php
/** Native contribution plus official faction victories: SELECT only. */
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
    $cache=defined('__PATH_CACHE__') ? rtrim(__PATH_CACHE__,'/\\').'/mupanic-fractura-totals-v6.json' : null;
    $old=$cache && is_file($cache) && !is_link($cache) ? json_decode((string)@file_get_contents($cache),true) : null;
    $valid=panicFracturaValid($old);
    if($valid && $old['updated']>time()-60) return $old;
    try {
        $db=Connection::Database('MuOnline');
        if(!$db) throw new RuntimeException('Unavailable');
        // Missing view or permission is unavailable, never a partial native-only total.
        $schema=$db->query_fetch("SELECT c.[name] AS [columnName] FROM [MuOnline43].[sys].[columns] c INNER JOIN [MuOnline43].[sys].[views] t ON t.[object_id]=c.[object_id] INNER JOIN [MuOnline43].[sys].[schemas] s ON s.[schema_id]=t.[schema_id] WHERE s.[name]='dbo' AND t.[name]='MUPanicFracturaFactionTotalsV6' AND c.[name] IN ('Family','Points','Members','Contribution','VictoryPoints')");
        $columns=[];
        foreach(is_array($schema)?$schema:[] as $v) $columns[]=(string)($v['columnName'] ?? '');
        if(array_diff(['Family','Points','Members','Contribution','VictoryPoints'],$columns)) throw new RuntimeException('Schema unavailable');
        // View sums contribution and IsTest=0 ledgers; individual 500 is already native.
        $rows=$db->query_fetch("SELECT [Family] AS [family], [Points] AS [points], [Members] AS [members] FROM [MuOnline43].[dbo].[MUPanicFracturaFactionTotalsV6]");
        if(!is_array($rows) || count($rows)!==2) throw new RuntimeException('Unavailable');
        $data=['schema'=>1,'updated'=>time(),'stale'=>false,'factions'=>[1=>['points'=>'0','members'=>'0'],2=>['points'=>'0','members'=>'0']]];
        $seen=[];
        foreach($rows as $row) {
            $id=(int)($row['family'] ?? 0);
            if(!in_array($id,[1,2],true) || isset($seen[$id])) throw new RuntimeException('Invalid family');
            $seen[$id]=true;
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
