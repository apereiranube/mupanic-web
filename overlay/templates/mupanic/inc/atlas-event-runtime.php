<?php
require_once(__DIR__.'/atlas-runtime.php');
function panicAtlasEventsValid($data) {
    if(!is_array($data) || array_keys($data) !== ['schemaVersion','generatedAt','sourceHash','timezone','events'] || $data['schemaVersion'] !== 1 || !is_string($data['generatedAt']) || strtotime($data['generatedAt']) === false || !is_string($data['sourceHash']) || !preg_match('/^[a-f0-9]{64}$/D',$data['sourceHash']) || $data['timezone'] !== 'America/Argentina/Buenos_Aires' || !is_array($data['events']) || count($data['events'])>150) return false;
    $ids=[];
    foreach($data['events'] as $e) {
        if(!is_array($e) || array_diff(['id','name','enabled','group','mode','schedule','durationMinutes','coins','bags','items','itemCount','maps','monsters'],array_keys($e)) || array_diff(array_keys($e),['id','name','enabled','group','mode','schedule','durationMinutes','coins','bags','items','itemCount','maps','monsters'])) return false;
        if(!is_string($e['id']) || !preg_match('/^[a-z][a-z0-9-]{0,60}$/D',$e['id']) || isset($ids[$e['id']]) || !is_string($e['name']) || strlen($e['name'])>100 || preg_match('/[<>]/',$e['name']) || !is_bool($e['enabled']) || !in_array($e['group'],['classic','custom','boss','staff','invasion'],true) || !in_array($e['mode'],['scheduled','access','manual'],true) || !is_numeric($e['durationMinutes']) || $e['durationMinutes']<0 || $e['durationMinutes']>1440 || !is_int($e['itemCount']) || $e['itemCount']<0) return false;
        $ids[$e['id']]=true;
        foreach(['schedule','coins','bags','items','maps','monsters'] as $k) if(!is_array($e[$k]) || count($e[$k])>500) return false;
        foreach($e['schedule'] as $row) {
            if(!is_array($row) || count($row)!==7) return false;
            foreach([[2000,2100],[1,12],[1,31],[1,7],[0,23],[0,59],[0,59]] as $i=>$range) if(!is_int($row[$i]) || ($row[$i]!==-1 && ($row[$i]<$range[0] || $row[$i]>$range[1]))) return false;
        }
        foreach($e['coins'] as $row) {if(!is_array($row) || count($row)!==3) return false;foreach($row as $n) if(!is_int($n) || $n<0 || $n>1000000000) return false;}
        foreach(['bags','maps','monsters'] as $k) foreach($e[$k] as $n) if(!is_int($n) || $n<0 || $n>1000000) return false;
        foreach($e['items'] as $s) if(!is_string($s) || strlen($s)>100 || preg_match('/[<>]/',$s)) return false;
    }
    return true;
}
function panicAtlasEvents() {
    $path=panicAtlasRuntimeDirectory().'/public-events.json';
    if(is_file($path) && !is_link($path) && filesize($path)<=1000000) {
        $data=json_decode(file_get_contents($path),true);
        if(panicAtlasEventsValid($data)) return $data;
    }
    return json_decode(file_get_contents(__DIR__.'/public-events.json'),true);
}
