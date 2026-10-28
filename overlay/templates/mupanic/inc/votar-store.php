<?php
if(!defined('access') || !access) die();
/*
 * MU PANIC - Votar: premios por votar en listas de servidores.
 *
 * La web NUNCA escribe en la base del juego. Cuando una lista confirma un voto real (API de la lista),
 * el premio queda en un archivo privado (fuera de public_html) y el proceso del VPS lo acredita en WCoin C
 * con la cuenta desconectada, devolviendo un recibo (saldo antes / después). Mismo esquema que las recargas.
 *
 * Archivo privado: /home/mupanic/payments-private/votar.json
 *   config  -> premio por voto, encendido, y por lista: encendida + token (nunca va a git ni se muestra)
 *   premios -> cada voto cobrado: pending (esperando al VPS) o done (acreditado)
 *   ultimos -> último cobro y último intento por cuenta+lista (para los tiempos de espera)
 * Clave del puente con el VPS: HMAC('votar', token del worker de recargas).
 */

/** Listas conocidas. Para sumar una lista nueva: agregarla acá y, si tiene API propia, su adaptador en votar-sites.php. */
function panicVotarSites() {
    return [
        'mutop100'=>[
            'name'=>'MU Top 100',
            'vote_url'=>'https://www.mutop100.com/muonline/mu-panic-431',
            'hours'=>12,
            'api'=>'mutop100',
        ],
    ];
}

final class PanicVotarStore {
    private $directory;
    const MAX_PREMIOS=20000;
    const DEFAULT_REWARD=20;
    const ATTEMPT_SECONDS=20;

    public function __construct($directory='/home/mupanic/payments-private') {
        $real=realpath($directory); $public=realpath('/home/mupanic/public_html');
        if(!$real || !is_dir($real) || is_link($directory) || ($public && ($real===$public || strpos($real,$public.DIRECTORY_SEPARATOR)===0))) throw new RuntimeException('Private store unavailable');
        $this->directory=$real;
    }

    public static function validAccount($account) { return is_string($account) && preg_match('/^[A-Za-z0-9_]{1,10}$/D',$account)===1; }

    public function key() {
        $path=$this->directory.'/recharge-worker-token';
        if(is_link($path) || !is_file($path) || filesize($path)>256) throw new RuntimeException('Worker not installed');
        $token=trim(file_get_contents($path));
        if(!preg_match('/^[a-f0-9]{64}$/D',$token)) throw new RuntimeException('Worker token invalid');
        return hash_hmac('sha256','votar',$token);
    }

    private static function blank() { return ['config'=>['enabled'=>false,'reward'=>self::DEFAULT_REWARD,'sites'=>[],'updated_at'=>null,'last_api'=>null],'premios'=>[],'ultimos'=>[]]; }

    private function locked(callable $operation) {
        $lock=$this->directory.'/votar.lock';
        if(is_link($lock)) throw new RuntimeException('Invalid lock');
        $handle=fopen($lock,'c'); if(!$handle) throw new RuntimeException('Lock unavailable'); @chmod($lock,0600);
        try {
            if(!flock($handle,LOCK_EX)) throw new RuntimeException('Lock unavailable');
            $path=$this->directory.'/votar.json';
            if(is_link($path)) throw new RuntimeException('Invalid store');
            clearstatcache(true,$path);
            if(is_file($path) && filesize($path)>16777216) throw new RuntimeException('Store capacity exceeded');
            $data=is_file($path)?json_decode(file_get_contents($path),true,16,JSON_THROW_ON_ERROR):self::blank();
            if(!is_array($data)) throw new RuntimeException('Invalid store');
            $data=array_replace(self::blank(),$data);
            $data['config']=array_replace(self::blank()['config'],is_array($data['config'])?$data['config']:[]);
            $result=$operation($data);
            if(is_array($result) && array_key_exists('__save',$result)) { $this->save($result['__save']); return $result['__return'] ?? null; }
            return $result;
        } finally { flock($handle,LOCK_UN); fclose($handle); }
    }

    private function save(array $data) {
        if(count($data['premios'])>self::MAX_PREMIOS) throw new RuntimeException('Store full');
        $json=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        $tmp=tempnam($this->directory,'vot-'); if(!$tmp) throw new RuntimeException('Write failed');
        @chmod($tmp,0600);
        if(file_put_contents($tmp,$json,LOCK_EX)!==strlen($json) || !rename($tmp,$this->directory.'/votar.json')) { @unlink($tmp); throw new RuntimeException('Write failed'); }
    }

    /** Configuración pública (sin tokens): para la página y el panel. */
    public function config() {
        return $this->locked(function($d) {
            $c=$d['config']; $sites=[];
            foreach(panicVotarSites() as $id=>$site) {
                $s=is_array($c['sites'][$id] ?? null)?$c['sites'][$id]:[];
                $token=is_string($s['token'] ?? null)?$s['token']:'';
                $sites[$id]=$site+['enabled'=>!empty($s['enabled']),'has_token'=>$token!=='','token_tail'=>$token!==''?substr($token,-4):''];
            }
            return ['enabled'=>!empty($c['enabled']),'reward'=>(int)$c['reward'],'sites'=>$sites,'updated_at'=>$c['updated_at'],'sites_updated_at'=>$c['sites_updated_at'] ?? null,'last_api'=>$c['last_api']];
        });
    }

    /** Panel Votos: premio por voto y encendido general. */
    public function saveReward($enabled,$reward,$admin) {
        if(!is_int($reward) || $reward<1 || $reward>1000) throw new InvalidArgumentException('El premio tiene que estar entre 1 y 1.000 Eryns.');
        return $this->locked(function($d) use ($enabled,$reward,$admin) {
            $d['config']['enabled']=(bool)$enabled; $d['config']['reward']=$reward;
            $d['config']['updated_at']=gmdate('c'); $d['config']['updated_by']=$admin;
            return ['__save'=>$d,'__return'=>true];
        });
    }

    /** Panel Configuración: listas de votación. Un token vacío conserva el actual. */
    public function saveSites(array $sites,$admin) {
        return $this->locked(function($d) use ($sites,$admin) {
            foreach(panicVotarSites() as $id=>$site) {
                $in=is_array($sites[$id] ?? null)?$sites[$id]:[];
                $cur=is_array($d['config']['sites'][$id] ?? null)?$d['config']['sites'][$id]:[];
                $token=is_string($in['token'] ?? null)?trim($in['token']):'';
                if($token!=='' && !preg_match('/^[A-Za-z0-9._\-]{16,256}$/D',$token)) throw new InvalidArgumentException('El token de '.$site['name'].' tiene un formato inválido.');
                $d['config']['sites'][$id]=['enabled'=>!empty($in['enabled']),'token'=>$token!==''?$token:(string)($cur['token'] ?? '')];
            }
            $d['config']['sites_updated_at']=gmdate('c'); $d['config']['updated_by']=$admin;
            return ['__save'=>$d,'__return'=>true];
        });
    }

    /** Token para la llamada del servidor a la lista (solo uso interno). */
    public function token($site) {
        return $this->locked(function($d) use ($site) { $t=$d['config']['sites'][$site]['token'] ?? ''; return is_string($t)?$t:''; });
    }

    /**
     * Antes de llamar a la lista: valida tiempos y registra el intento.
     * Devuelve ['ok'=>true] o ['ok'=>false,'reason'=>'disabled|cooldown|too_fast','until'=>ts].
     */
    public function beginClaim($account,$site,$now) {
        if(!self::validAccount($account) || !isset(panicVotarSites()[$site])) throw new InvalidArgumentException('Datos inválidos');
        $hours=panicVotarSites()[$site]['hours'];
        return $this->locked(function($d) use ($account,$site,$now,$hours) {
            $c=$d['config'];
            if(empty($c['enabled']) || empty($c['sites'][$site]['enabled']) || ($c['sites'][$site]['token'] ?? '')==='') return ['ok'=>false,'reason'=>'disabled'];
            $k=strtolower($account).'|'.$site; $u=is_array($d['ultimos'][$k] ?? null)?$d['ultimos'][$k]:[];
            $next=(int)($u['cobro'] ?? 0)+$hours*3600;
            if($next>$now) return ['ok'=>false,'reason'=>'cooldown','until'=>$next];
            $again=(int)($u['intento'] ?? 0)+self::ATTEMPT_SECONDS;
            if($again>$now) return ['ok'=>false,'reason'=>'too_fast','until'=>$again];
            $u['intento']=$now; $d['ultimos'][$k]=$u;
            return ['__save'=>$d,'__return'=>['ok'=>true]];
        });
    }

    /** La lista confirmó el voto: se deja el premio en la cola del VPS. Un mismo comprobante nunca se paga dos veces. */
    public function addPremio($account,$site,$claimRef,$now) {
        if(!self::validAccount($account) || !isset(panicVotarSites()[$site]) || !is_string($claimRef) || !preg_match('/^[A-Za-z0-9._:\-]{1,120}$/D',$claimRef)) throw new InvalidArgumentException('Datos inválidos');
        return $this->locked(function($d) use ($account,$site,$claimRef,$now) {
            foreach($d['premios'] as $p) if(($p['site'] ?? '')===$site && ($p['claim_ref'] ?? '')===$claimRef) return ['__save'=>$d,'__return'=>$p];
            $id='V'.bin2hex(random_bytes(16));
            $p=['id'=>$id,'site'=>$site,'account'=>$account,'coins'=>max(1,(int)$d['config']['reward']),'claim_ref'=>$claimRef,'created'=>$now,'state'=>'pending'];
            $d['premios'][$id]=$p;
            $k=strtolower($account).'|'.$site; $u=is_array($d['ultimos'][$k] ?? null)?$d['ultimos'][$k]:[];
            $u['cobro']=$now; $d['ultimos'][$k]=$u;
            return ['__save'=>$d,'__return'=>$p];
        });
    }

    /** Guarda la última respuesta de una lista (recortada, sin datos sensibles) para diagnosticar desde el panel. */
    public function noteApi($site,$summary) {
        $summary=substr(preg_replace('/[^\x20-\x7E]/','',(string)$summary),0,600);
        $this->locked(function($d) use ($site,$summary) { $d['config']['last_api']=['site'=>$site,'at'=>gmdate('c'),'summary'=>$summary]; return ['__save'=>$d]; });
    }

    /** Estado de una cuenta: próximos votos y últimos premios. */
    public function account($account) {
        if(!self::validAccount($account)) return ['next'=>[],'premios'=>[]];
        return $this->locked(function($d) use ($account) {
            $next=[];
            foreach(panicVotarSites() as $id=>$site) { $u=$d['ultimos'][strtolower($account).'|'.$id] ?? []; $next[$id]=(int)($u['cobro'] ?? 0)+$site['hours']*3600; }
            $mine=array_values(array_filter($d['premios'],function($p) use ($account){ return strcasecmp($p['account'] ?? '',$account)===0; }));
            usort($mine,function($a,$b){ return $b['created']<=>$a['created']; });
            return ['next'=>$next,'premios'=>array_slice($mine,0,10)];
        });
    }

    /**
     * Página pública: números del mes, ranking del mes, últimos votos y total de la cuenta.
     * Mes según la hora de Argentina (UTC-3).
     */
    public function publicStats($account,$now,$rankLimit=10,$feedLimit=8) {
        return $this->locked(function($d) use ($account,$now,$rankLimit,$feedLimit) {
            $month=gmdate('Y-m',$now-3*3600);
            $votesMonth=0; $coinsAll=0; $accountsMonth=[]; $counts=[]; $names=[]; $mine=0; $feed=[];
            foreach($d['premios'] as $p) {
                $coins=(int)($p['coins'] ?? 0); $coinsAll+=$coins;
                $key=strtolower($p['account']);
                if($account!=='' && $key===strtolower($account)) $mine+=$coins;
                if(gmdate('Y-m',(int)$p['created']-3*3600)===$month) {
                    $votesMonth++; $accountsMonth[$key]=true;
                    $counts[$key]=($counts[$key] ?? 0)+1; $names[$key]=$p['account'];
                    $last[$key]=max($last[$key] ?? 0,(int)$p['created']);
                }
                $feed[]=['account'=>$p['account'],'coins'=>$coins,'created'=>(int)$p['created']];
            }
            usort($feed,function($a,$b){ return $b['created']<=>$a['created']; });
            $order=array_keys($counts);
            usort($order,function($a,$b) use ($counts,$last){ return [$counts[$b],$last[$a] ?? 0]<=>[$counts[$a],$last[$b] ?? 0]; });
            $ranking=[]; $me=null;
            foreach($order as $i=>$key) {
                $row=['pos'=>$i+1,'account'=>$names[$key],'votes'=>$counts[$key]];
                if($i<$rankLimit) $ranking[]=$row;
                if($account!=='' && $key===strtolower($account)) $me=$row;
            }
            return ['votes_month'=>$votesMonth,'coins_all'=>$coinsAll,'voters_month'=>count($accountsMonth),
                'ranking'=>$ranking,'me'=>$me,'mine_total'=>$mine,'feed'=>array_slice($feed,0,$feedLimit)];
        });
    }

    /** Panel admin: totales y últimos premios. */
    public function summary($limit=150) {
        return $this->locked(function($d) use ($limit) {
            $t=['pending'=>0,'pending_coins'=>0,'done'=>0,'done_coins'=>0,'month_coins'=>0,'accounts'=>[]];
            $month=gmdate('Y-m');
            foreach($d['premios'] as $p) {
                $coins=(int)($p['coins'] ?? 0);
                if(($p['state'] ?? '')==='done') { $t['done']++; $t['done_coins']+=$coins; } else { $t['pending']++; $t['pending_coins']+=$coins; }
                if(gmdate('Y-m',(int)$p['created'])===$month) $t['month_coins']+=$coins;
                $t['accounts'][strtolower($p['account'])]=true;
            }
            $t['accounts']=count($t['accounts']);
            $rows=array_values($d['premios']); usort($rows,function($a,$b){ return $b['created']<=>$a['created']; });
            return ['totals'=>$t,'rows'=>array_slice($rows,0,$limit)];
        });
    }

    /** Para el proceso del VPS: hasta 20 premios pendientes. */
    public function poll() {
        return $this->locked(function($d) {
            $out=[];
            foreach($d['premios'] as $p) {
                if(($p['state'] ?? '')!=='pending') continue;
                $out[]=['id'=>$p['id'],'account'=>$p['account'],'coins'=>(int)$p['coins'],'site'=>$p['site']];
                if(count($out)>=20) break;
            }
            return ['items'=>$out];
        });
    }

    /** Recibos del VPS: solo se marca acreditado si el saldo subió exactamente lo del premio. */
    public function acknowledge($results) {
        if(!is_array($results) || count($results)>20) throw new InvalidArgumentException('Invalid results');
        return $this->locked(function($d) use ($results) {
            $n=0;
            foreach($results as $r) {
                $id=$r['id'] ?? '';
                if(!is_string($id) || !preg_match('/^V[a-f0-9]{32}$/D',$id) || !isset($d['premios'][$id])) continue;
                $p=$d['premios'][$id];
                if(($r['state'] ?? '')!=='credited' || strcasecmp((string)($r['account'] ?? ''),$p['account'])!==0 || ($r['coins'] ?? null)!==(int)$p['coins']) continue;
                $before=$r['before_coin'] ?? null; $after=$r['after_coin'] ?? null;
                if(!is_int($before) || !is_int($after) || $before<0 || $after>2147483647 || $after-$before!==(int)$p['coins']) continue;
                if($p['state']==='pending') { $p['state']='done'; $p['done']=time(); $p['before_coin']=$before; $p['after_coin']=$after; $d['premios'][$id]=$p; }
                $n++;
            }
            return ['__save'=>$d,'__return'=>['acknowledged'=>$n]];
        });
    }
}

/** Verifica un pedido firmado del VPS: HMAC(timestamp \n body) con +-300 s y nonce de 32 hex. */
function panicVotarWorkerRequest($body,$stamp,$signature,$key,$now) {
    if(!is_string($body) || strlen($body)>8192 || !is_string($stamp) || !preg_match('/^[0-9]{10}$/D',$stamp) ||
        abs($now-(int)$stamp)>300 || !is_string($signature) || !hash_equals(hash_hmac('sha256',$stamp."\n".$body,$key),$signature)) return null;
    try { $request=json_decode($body,true,8,JSON_THROW_ON_ERROR); } catch(Throwable $e) { return null; }
    if(!is_array($request) || !is_string($request['nonce'] ?? null) || !preg_match('/^[a-f0-9]{32}$/D',$request['nonce'])) return null;
    return $request;
}
