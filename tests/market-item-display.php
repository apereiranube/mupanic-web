<?php
require __DIR__.'/../overlay/templates/mupanic/inc/market-item-display.php';
function check($v,$name){if(!$v)throw new Exception($name);echo "PASS $name\n";}
$real=panicMarketPublicImage(6887);
check(is_array($real) && str_ends_with($real['url'],'/atlas/vinculos/vaelkar.webp'),'client model Vaelkar reuses verified Atlas 2D render');
// Exact ItemIndex -> refreshed Atlas illustration. These are renewed
// high-resolution editorial artworks, not claims of identical in-game icons.
$atlasRenovado=[
 6159=>'jewel-chaos-809384516512.webp',
 7181=>'jewel-bless-2c80b97e4d75.webp',
 7182=>'jewel-soul-05f3890104b2.webp',
 7184=>'jewel-life-6e1a802e1074.webp',
 7190=>'jewel-creation-9b7e2a322074.webp',
 7209=>'jewel-gemstone-5edf360037d7.webp',
 7221=>'luck-hd.webp',
 7264=>'assembly-hd.webp'
];
foreach($atlasRenovado as $index=>$name){
 $mapped=panicMarketPublicImage($index);
 check(is_array($mapped) && $mapped['type']==='atlas_renovado' &&
   str_ends_with($mapped['url'],$name),'renewed Atlas asset for item '.$index);
 check($mapped['width']>=512 && $mapped['height']>=512,'HD dimensions for item '.$index);
 $relative=substr($mapped['url'],strlen('/templates/mupanic/'));
 $file=__DIR__.'/../overlay/templates/mupanic/'.$relative;
 check(is_file($file) && filesize($file)>30000,'high quality WebP is present locally: '.$index);
 $stream=fopen($file,'rb');$header=fread($stream,12);fclose($stream);
 check(substr($header,0,4)==='RIFF' && substr($header,8,4)==='WEBP','valid WebP file header: '.$index);
}

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
