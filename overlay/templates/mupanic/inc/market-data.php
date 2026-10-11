<?php
/** Public, read-only market projection. Never reads balances or inventory snapshots. */
function panicMarketEscape($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function panicMarketNumber($v) {
    $s=(string)$v;
    return preg_match('/^[0-9]+$/D',$s) ? preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $s) : '—';
}
function panicMarketSalesSource() {
    return "[MuOnline43].[dbo].[PanicMarketPublicSales] b WHERE b.SoldAt IS NOT NULL";
}
function panicMarketRead($refresh=false) {
    $cache=defined('__PATH_CACHE__') ? rtrim(__PATH_CACHE__,'/\\').'/mupanic-vessara-v2.json' : null;
    $old=$cache && is_file($cache) && !is_link($cache) ? json_decode((string)@file_get_contents($cache),true) : null;
    $valid=is_array($old) && ($old['schema'] ?? null)===1 && isset($old['updated'],$old['metrics'],$old['sales'],$old['days']);
    if(!$refresh && $valid && $old['updated']>time()-15) return $old;
    try {
        $db=Connection::Database('MuOnline');
        if(!$db) throw new RuntimeException('Unavailable');
        $source=panicMarketSalesSource();
        $summary=$db->query_fetch("SELECT COUNT_BIG(*) AS total, COALESCE(SUM(CONVERT(bigint,CASE WHEN CONVERT(date,b.SoldAt)=CONVERT(date,GETDATE()) THEN 1 ELSE 0 END)),0) AS today, COALESCE(SUM(CASE WHEN b.Currency=0 THEN CONVERT(bigint,b.Price) ELSE 0 END),0) AS zen, COALESCE(SUM(CASE WHEN b.Currency=1 THEN CONVERT(bigint,b.Price) ELSE 0 END),0) AS eryns, COALESCE(SUM(CASE WHEN b.Currency=0 AND CONVERT(date,b.SoldAt)=CONVERT(date,GETDATE()) THEN CONVERT(bigint,b.Price) ELSE 0 END),0) AS zenToday, COALESCE(SUM(CASE WHEN b.Currency=1 AND CONVERT(date,b.SoldAt)=CONVERT(date,GETDATE()) THEN CONVERT(bigint,b.Price) ELSE 0 END),0) AS erynsToday, CONVERT(varchar(10),GETDATE(),23) AS serverDay FROM $source");
        if(!is_array($summary) || count($summary)!==1) throw new RuntimeException('Unavailable');
        $sales=$db->query_fetch("SELECT TOP 40 CONVERT(varchar(36),b.SaleID) AS id,b.Buyer AS buyer,b.Seller AS seller,b.Item AS item,b.ItemIndex AS itemIndex,b.Level AS level,b.Currency AS currency,b.Price AS price,CONVERT(varchar(23),b.SoldAt,126) AS soldAt FROM $source ORDER BY b.SoldAt DESC,b.SaleID DESC");
        $days=$db->query_fetch("SELECT CONVERT(varchar(10),b.SoldAt,23) AS day,COUNT_BIG(*) AS trades,COALESCE(SUM(CASE WHEN b.Currency=0 THEN CONVERT(bigint,b.Price) ELSE 0 END),0) AS zen,COALESCE(SUM(CASE WHEN b.Currency=1 THEN CONVERT(bigint,b.Price) ELSE 0 END),0) AS eryns FROM $source AND b.SoldAt>=DATEADD(day,-6,CONVERT(date,GETDATE())) GROUP BY CONVERT(varchar(10),b.SoldAt,23) ORDER BY day");
        if($sales===false || $days===false) throw new RuntimeException('Unavailable');
        $metrics=[];
        foreach(['total','today','zen','eryns','zenToday','erynsToday'] as $key) {
            $v=(string)$summary[0][$key];
            if(!preg_match('/^[0-9]{1,20}$/D',$v)) throw new RuntimeException('Invalid counter');
            $metrics[$key]=$v;
        }
        $clean=[];
        foreach(is_array($sales)?$sales:[] as $s) {
            // Explicit allowlist: account identifiers, serials, item bytes and balances cannot escape.
            $clean[]=['id'=>(string)$s['id'],'buyer'=>(string)$s['buyer'],'seller'=>(string)$s['seller'],'item'=>(string)$s['item'],'itemIndex'=>(int)$s['itemIndex'],'level'=>(int)$s['level'],'currency'=>(int)$s['currency'],'price'=>(int)$s['price'],'soldAt'=>(string)$s['soldAt'].'-03:00'];
        }
        $series=[]; $dayMap=[];
        foreach(is_array($days)?$days:[] as $d) $dayMap[$d['day']]=$d;
        $end=new DateTimeImmutable($summary[0]['serverDay'],new DateTimeZone('America/Argentina/Buenos_Aires'));
        for($i=6;$i>=0;$i--) {
            $day=$end->modify('-'.$i.' days')->format('Y-m-d');$d=$dayMap[$day] ?? [];
            $series[]=['day'=>$day,'trades'=>(string)($d['trades']??'0'),'zen'=>(string)($d['zen']??'0'),'eryns'=>(string)($d['eryns']??'0')];
        }
        $data=['schema'=>1,'updated'=>time(),'serverDay'=>$summary[0]['serverDay'],'metrics'=>$metrics,'sales'=>$clean,'days'=>$series,'stale'=>false];
        if($cache && is_dir(dirname($cache)) && !is_link($cache)) {
            $tmp=@tempnam(dirname($cache),'vessara-');
            if($tmp!==false) {
                if(@file_put_contents($tmp,json_encode($data,JSON_UNESCAPED_UNICODE),LOCK_EX)!==false) { @chmod($tmp,0600);@rename($tmp,$cache); }
                if(is_file($tmp)) @unlink($tmp);
            }
        }
        return $data;
    } catch(Throwable $error) {
        if($valid && $old['updated']>time()-86400) { $old['stale']=true;return $old; }
        return null;
    }
}
