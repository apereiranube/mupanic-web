<?php
if(!defined('access') || !access) die();
/*
 * MU PANIC - Votar: adaptadores de cada lista.
 * Cada adaptador recibe la cuenta y devuelve si la lista confirmó un voto real sin cobrar.
 * Respuesta: ['ok'=>bool, 'ref'=>comprobante de la lista, 'summary'=>texto corto para el panel, 'error'=>bool].
 */

/** Llamada servidor a servidor. El token va solo en el encabezado y nunca se guarda en el resumen. */
function panicVotarHttpJson($url,$token,array $payload) {
    if(!function_exists('curl_init')) return ['http'=>0,'data'=>null,'error'=>'curl_unavailable'];
    $ch=curl_init($url);
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_POST=>true,
        CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Content-Type: application/json','Accept: application/json'],
        CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT=>12,
        CURLOPT_CONNECTTIMEOUT=>6,
        CURLOPT_FOLLOWLOCATION=>false,
        CURLOPT_SSL_VERIFYPEER=>true,
        CURLOPT_SSL_VERIFYHOST=>2,
    ]);
    $raw=curl_exec($ch); $http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch);
    if($raw===false) return ['http'=>$http,'data'=>null,'error'=>'curl_error','detail'=>$err];
    $data=json_decode((string)$raw,true);
    return ['http'=>$http,'data'=>is_array($data)?$data:null,'error'=>is_array($data)?null:'bad_json','raw'=>substr((string)$raw,0,400)];
}

/** Texto corto para diagnóstico: solo claves y valores simples de la respuesta, sin el token. */
function panicVotarSummarize(array $res) {
    $parts=['http='.$res['http']];
    if($res['error']) $parts[]='error='.$res['error'];
    if(is_array($res['data'])) foreach($res['data'] as $k=>$v) {
        if(is_scalar($v) || $v===null) $parts[]=$k.'='.(is_bool($v)?($v?'true':'false'):substr((string)$v,0,80));
        elseif(is_array($v)) $parts[]=$k.'={'.implode(',',array_slice(array_map('strval',array_keys($v)),0,8)).'}';
    } elseif(isset($res['raw'])) $parts[]='raw='.$res['raw'];
    return implode(' ',$parts);
}

/**
 * MU Top 100 (Vote Reward API): 'claim' consume un voto elegible de la cuenta y devuelve su comprobante.
 * Si no hay voto sin cobrar, la lista responde sin comprobante y no se acredita nada.
 */
function panicVotarClaimMutop100($account,$token) {
    $res=panicVotarHttpJson('https://www.mutop100.com/api/v1/vote-rewards.php',$token,[
        'action'=>'claim','username'=>$account,'client_ref'=>bin2hex(random_bytes(16)),
    ]);
    return panicVotarParseMutop100($res);
}

/** Interpreta la respuesta de 'claim': solo es premio si la lista confirma y devuelve un comprobante. */
function panicVotarParseMutop100(array $res) {
    $summary=panicVotarSummarize($res);
    $d=$res['data'];
    if(!is_array($d) || $res['http']<200 || $res['http']>=300) return ['ok'=>false,'ref'=>'','summary'=>$summary,'error'=>$res['http']===0 || $res['http']>=500 || $res['http']===401 || $res['http']===403];
    $flag=function($k) use ($d){ return array_key_exists($k,$d) && ($d[$k]===true || $d[$k]===1 || $d[$k]==='1' || $d[$k]==='true'); };
    $ref='';
    foreach(['claim_ref','vote_id','claim_id','id'] as $k) {
        $v=$d[$k] ?? ((is_array($d['data'] ?? null) ? ($d['data'][$k] ?? null) : null) ?? (is_array($d['vote'] ?? null) ? ($d['vote'][$k] ?? null) : null));
        if((is_string($v) || is_int($v)) && preg_match('/^[A-Za-z0-9._:\-]{1,120}$/D',(string)$v)) { $ref=(string)$v; break; }
    }
    $okFlag=$flag('ok') || $flag('success');
    $claimed=$flag('claimed') || in_array(strtolower((string)($d['status'] ?? '')),['claimed','ok','success'],true);
    $denied=(array_key_exists('claimed',$d) && !$flag('claimed')) || isset($d['error']);
    return ['ok'=>$okFlag && !$denied && $ref!=='' && ($claimed || !array_key_exists('claimed',$d)),'ref'=>$ref,'summary'=>$summary,'error'=>false];
}

/** Despacha al adaptador de la lista. */
function panicVotarClaim($site,$account,$token) {
    $sites=panicVotarSites();
    if(!isset($sites[$site])) return ['ok'=>false,'ref'=>'','summary'=>'unknown site','error'=>true];
    if($sites[$site]['api']==='mutop100') return panicVotarClaimMutop100($account,$token);
    return ['ok'=>false,'ref'=>'','summary'=>'no adapter','error'=>true];
}
