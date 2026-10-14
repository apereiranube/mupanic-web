<?php
require_once __DIR__.'/market-item-display.php';
/**
 * Public, read-only listing projection. Requires dbo.PanicMarketPublicListings.
 * Never reads account IDs, serial numbers, inventories or raw item bytes.
 */
function panicMarketListingsSource() { return '[MuOnline43].[dbo].[PanicMarketPublicListings]'; }
function panicMarketListingsDate($value) {
    if($value===null || $value==='') return null;
    $value=(string)$value;
    return preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,3})?$/D',$value) ? $value.'-03:00' : null;
}
function panicMarketListingsProject($rows) {
    if($rows===false) throw new RuntimeException('No listing data');
    $clean=[];
    foreach(is_array($rows)?$rows:[] as $r) {
        $price=(string)($r['price']??'');
        $index=filter_var($r['itemIndex']??null,FILTER_VALIDATE_INT);
        $level=filter_var($r['level']??null,FILTER_VALIDATE_INT);
        $currency=filter_var($r['currency']??null,FILTER_VALIDATE_INT);
        $expires=panicMarketListingsDate($r['expiresAt']??null);
        if(!preg_match('/^[0-9]{1,20}$/D',$price) || $index===false || $index<0 ||
           $level===false || $level<0 || $level>15 || !in_array($currency,[0,1],true) ||
           !$expires || empty($r['id']) || empty($r['item'])) throw new RuntimeException('Invalid listing');
        $featuredUntil=panicMarketListingsDate($r['featuredUntil']??null);
        $clean[]=[
            'id'=>substr((string)$r['id'],0,64),
            'seller'=>substr((string)($r['seller']??''),0,40),
            'item'=>substr((string)$r['item'],0,120),
            'itemIndex'=>$index,'level'=>$level,'currency'=>$currency,'price'=>$price,
            'expiresAt'=>$expires,'featuredUntil'=>$featuredUntil,
            'featured'=>((int)($r['featured']??0)===1 && $featuredUntil!==null),
            'photo'=>panicMarketPublicImage($index),
            'details'=>panicMarketItemDetails($r)
        ];
    }
    return $clean;
}
function panicMarketListingsRead($page=1,$currency='all',$search='',$onlyFeatured=false,$sort='recent') {
    $page=max(1,min(1000,(int)$page));
    $currency=in_array((string)$currency,['0','1'],true)?(string)$currency:'all';
    $sort=in_array((string)$sort,['recent','price-asc','price-desc'],true)?$sort:'recent';
    $search=trim((string)$search);
    // UTF-16LE hex string keeps user input out of executable SQL.
    if(!preg_match('/^[\pL\pN .+\-]{0,60}$/uD',$search)) $search='';
    $where='b.ExpiresAt>GETDATE() AND b.Currency IN (0,1) AND b.Price>0';
    if($currency!=='all') $where.=' AND b.Currency='.(int)$currency;
    if($onlyFeatured) $where.=' AND b.FeaturedUntil>GETDATE()';
    if($search!=='') {
        $encoded=function_exists('iconv')?iconv('UTF-8','UTF-16LE',$search):false;
        if($encoded===false) return null;
        $literal='CONVERT(nvarchar(60),0x'.strtoupper(bin2hex($encoded)).')';
        $where.=" AND (b.Item LIKE N'%' + $literal + N'%' OR b.Seller LIKE N'%' + $literal + N'%')";
    }
    $src=panicMarketListingsSource();
    $columns="CONVERT(varchar(64),b.ListingID) AS id,b.Seller AS seller,b.Item AS item,"
       ."b.ItemIndex AS itemIndex,b.Level AS level,b.Currency AS currency,"
       ."CONVERT(varchar(24),b.Price) AS price,"
       ."CONVERT(varchar(23),b.ExpiresAt,126) AS expiresAt,"
       ."CONVERT(varchar(23),b.FeaturedUntil,126) AS featuredUntil,"
       ."CASE WHEN b.FeaturedUntil>GETDATE() THEN 1 ELSE 0 END AS featured";
    $order=$sort==='price-asc'?'b.Price ASC,b.ListingID DESC':
        ($sort==='price-desc'?'b.Price DESC,b.ListingID DESC':
         'CASE WHEN b.FeaturedUntil>GETDATE() THEN 0 ELSE 1 END,b.ExpiresAt DESC,b.ListingID DESC');
    try {
        $db=Connection::Database('MuOnline');
        if(!$db) throw new RuntimeException('Unavailable');
        // Reads a public view schema property only; no private inventory is queried.
        $v2=$db->query_fetch("SELECT CASE WHEN COL_LENGTH('MuOnline43.dbo.PanicMarketPublicListings','SkillFlag') IS NULL THEN 0 ELSE 1 END AS canDetails");
        $canDetails=is_array($v2) && isset($v2[0]['canDetails']) && (int)$v2[0]['canDetails']===1;
        if($canDetails) {
            $columns.=',b.Durability AS durability,b.SkillFlag AS skillFlag,b.LuckFlag AS luckFlag,b.AdditionalOption AS additionalOption,b.ExcellentMask AS excellentMask,b.AncientCode AS ancientCode,b.HarmonyCode AS harmonyCode,b.Option380Flag AS option380Flag,b.Socket1 AS socket1,b.Socket2 AS socket2,b.Socket3 AS socket3,b.Socket4 AS socket4,b.Socket5 AS socket5,b.LotCount AS lotCount';
        }
        $totals=$db->query_fetch("SELECT CONVERT(varchar(24),COUNT_BIG(*)) AS totalListings FROM $src b WHERE $where");
        if(!is_array($totals) || count($totals)!==1 ||
            !preg_match('/^[0-9]{1,16}$/D',(string)($totals[0]['totalListings']??''))) throw new RuntimeException('Bad total');
        $total=(int)$totals[0]['totalListings'];
        $pages=max(1,(int)ceil($total/24));$page=min($page,$pages);$offset=($page-1)*24;
        $rows=$db->query_fetch("SELECT $columns FROM $src b WHERE $where ORDER BY $order OFFSET $offset ROWS FETCH NEXT 24 ROWS ONLY");
        $promos=$db->query_fetch("SELECT TOP 10 $columns FROM $src b WHERE b.ExpiresAt>GETDATE() AND b.Currency IN (0,1) AND b.Price>0 AND b.FeaturedUntil>GETDATE() ORDER BY b.FeaturedUntil DESC,b.ListingID DESC");
        return ['schema'=>1,'available'=>true,'updated'=>time(),'itemOptions'=>$canDetails,
            'page'=>$page,'pages'=>$pages,'pageSize'=>24,'total'=>(string)$total,
            'items'=>panicMarketListingsProject($rows),
            'featured'=>panicMarketListingsProject($promos)];
    } catch(Throwable $error) {
        // Never serve stale offers: they might already be sold or cancelled.
        return null;
    }
}
