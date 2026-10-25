<?php
/**
 * MU PANIC / Vessara: public display metadata, never raw ItemCode, serials,
 * inventories or account IDs. S6 flags are derived within the SQL view.
 */
function panicMarketPublicImage($itemIndex) {
    static $images=null;
    if($images===null) {
        $images=[];
        $path=__DIR__.'/market-item-images.json';
        if(is_file($path) && !is_link($path)) {
            $json=json_decode((string)file_get_contents($path),true);
            if(is_array($json) && ($json['schema']??0)===1 && is_array($json['items']??null)) {
                $images=$json['items'];
            }
        }
    }
    $data=$images[(string)(int)$itemIndex]??null;
    if(!is_array($data) || !isset($data['src']) ||
       !preg_match('~^img/atlas/(drops|rewards|vinculos)/[a-zA-Z0-9._/-]+\.webp$~D',(string)$data['src'])) return null;
    $base=defined('__PATH_TEMPLATE__')?__PATH_TEMPLATE__:'/templates/mupanic/';
    return ['url'=>rtrim($base,'/').'/'.$data['src'],
        'type'=>(string)($data['source']??'atlas_original'),
        'width'=>max(1,(int)($data['width']??32)),
        'height'=>max(1,(int)($data['height']??32))];
}
function panicMarketExcellentLines($itemIndex,$mask) {
    $group=intdiv((int)$itemIndex,512);
    $weapon=[
        0=>'Recuperación de maná al eliminar monstruos',
        1=>'Recuperación de vida al eliminar monstruos',
        2=>'Velocidad de ataque +7',
        3=>'Daño +2%',
        4=>'Daño + nivel/20',
        5=>'Probabilidad de daño Excellent +10%'
    ];
    $defense=[
        0=>'Zen obtenido +40%',
        1=>'Probabilidad de defensa +10%',
        2=>'Reflejo de daño +5%',
        3=>'Reducción de daño +4%',
        4=>'Maná máximo +4%',
        5=>'Vida máxima +4%'
    ];
    $map=($group>=0 && $group<=5)?$weapon:(($group>=6 && $group<=11)?$defense:[]);
    $lines=[];
    for($i=0;$i<6;$i++) {
        if(((int)$mask & (1<<$i))!==0) {
            // Wings, pets, jewels and customs do not share standard equipment labels.
            $lines[]=$map[$i]??('Opción especial Excellent '.($i+1));
        }
    }
    return $lines;
}
function panicMarketItemDetails($row) {
    // Existing V1 public view has no option columns; never guess missing flags.
    if(!array_key_exists('skillFlag',$row)) return null;
    $itemIndex=(int)$row['itemIndex'];
    $excellent=max(0,min(63,(int)($row['excellentMask']??0)));
    $ancient=max(0,(int)($row['ancientCode']??0));
    $harmony=max(0,(int)($row['harmonyCode']??0));
    $sockets=[];
    for($i=1;$i<=5;$i++) {
        $key='socket'.$i;
        $value=(int)($row[$key]??255);
        if($value===254) $sockets[]=['slot'=>$i,'state'=>'vacío'];
        elseif($value>=0 && $value<254) $sockets[]=['slot'=>$i,'state'=>'colocado','code'=>$value];
    }
    return [
        'skill'=>((int)$row['skillFlag']===1),
        'luck'=>((int)($row['luckFlag']??0)===1),
        'additional'=>max(0,min(28,(int)($row['additionalOption']??0))),
        'durability'=>max(0,min(255,(int)($row['durability']??0))),
        'excellent'=>panicMarketExcellentLines($itemIndex,$excellent),
        'excellentCount'=>count(panicMarketExcellentLines($itemIndex,$excellent)),
        'ancient'=>$ancient!==0,
        // An Ancient identifier is not a verified in-game set-name translation.
        'ancientType'=>$ancient!==0?'Set Ancient':null,
        'harmony'=>$harmony!==0,
        // No exact Harmony bonus is invented without the server's option table.
        'harmonyText'=>$harmony!==0?'Opción Harmony aplicada':null,
        'option380'=>((int)($row['option380Flag']??0)===1),
        'sockets'=>$sockets,
        'lot'=>max(1,min(5,(int)($row['lotCount']??0)))
    ];
}
