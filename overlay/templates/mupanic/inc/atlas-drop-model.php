<?php
if(!defined('access') or !access) die();
// Group numbered entry materials, while preserving distinct item variants and every rule index.
function panicAtlasDropGroups($drops) {
    $numbered = [6672,6673,6705,6706,7185,7186,7197];
    $groups = [];
    foreach($drops as $index=>$drop) {
        $levels = in_array($drop['id'], $numbered, true);
        $key = (string)$drop['id'].($levels ? '' : '-'.($drop['variant'] ?? 0));
        if(!isset($groups[$key])) {
            $name = $levels ? preg_replace('/\s+\d+$/', '', $drop['name']) : $drop['name'];
            $category = in_array($drop['id'], [6159,7181,7182,7184,7190,7209], true) ? 'joyas' : (in_array($drop['id'], [6670,6708], true) ? 'alas' : (in_array($drop['id'], [6214,6215,6216], true) ? 'sockets' : (in_array($drop['id'], array_merge($numbered,[7269,7278]), true) ? 'entradas' : (in_array($drop['id'], [7289,7290], true) ? 'cajas' : 'materiales'))));
            $groups[$key] = ['key'=>$key,'name'=>$name,'category'=>$category,'numbered'=>$levels,'rules'=>[]];
        }
        $groups[$key]['rules'][$index] = $drop;
    }
    return $groups;
}
