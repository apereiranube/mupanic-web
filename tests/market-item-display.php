<?php
require __DIR__.'/../overlay/templates/mupanic/inc/market-item-display.php';
function check($v,$name){if(!$v)throw new Exception($name);echo "PASS $name\n";}
$real=panicMarketPublicImage(6887);
check(is_array($real) && str_ends_with($real['url'],'/atlas/vinculos/vaelkar.webp'),'client model Vaelkar reuses verified Atlas 2D render');
check(panicMarketPublicImage(7181)===null,'illustrative jewel asset is not misrepresented as an authentic icon');
check(panicMarketPublicImage(8191)===null,'unknown item has no invented image mapping');
$weapon=panicMarketItemDetails([
 'itemIndex'=>2593,'skillFlag'=>1,'luckFlag'=>1,'additionalOption'=>28,'durability'=>255,'excellentMask'=>40,
 'ancientCode'=>0,'harmonyCode'=>0,'option380Flag'=>0,
 'socket1'=>255,'socket2'=>255,'socket3'=>255,'socket4'=>255,'socket5'=>255,'lotCount'=>0
]);
check($weapon['skill'] && $weapon['luck'] && $weapon['additional']===28,'accurate skill luck and additional fields');
check($weapon['excellentCount']===2 && count($weapon['excellent'])===2,'Excellent weapon mask expands two flags');
check($weapon['lot']===1 && !$weapon['ancient'] && $weapon['sockets']===[],'normal item has no invented rare option');
$armor=panicMarketItemDetails([
 'itemIndex'=>4129,'skillFlag'=>0,'luckFlag'=>0,'additionalOption'=>16,'durability'=>145,'excellentMask'=>12,
 'ancientCode'=>5,'harmonyCode'=>23,'option380Flag'=>1,
 'socket1'=>254,'socket2'=>2,'socket3'=>255,'socket4'=>255,'socket5'=>255,'lotCount'=>5
]);
check($armor['excellentCount']===2 && strpos($armor['excellent'][0],'Reflejo')!==false,'armor Excellent flags use defensive labels');
check($armor['ancient'] && $armor['harmony'] && $armor['option380'],'rare option families are exposed without assuming unknown bonuses');
check(count($armor['sockets'])===2 && $armor['sockets'][0]['state']==='vacío','socket flags preserve empties and filled slots');
check($armor['lot']===5,'five-piece lot count included');
check(panicMarketItemDetails(['itemIndex'=>2593])===null,'missing V2 information remains unknown');
