<?php
if(!defined('access') || !access) die();
/*
 * MU PANIC - Creadores: postulaciones de streamers y su puente firmado con el bot de Discord y el VPS.
 * La web NUNCA escribe en la base del juego: las altas y los pagos quedan en un archivo privado
 * (fuera de public_html) y el worker de Socios del VPS los aplica en SQL.
 * Clave del puente: HMAC('creadores', token del worker de recargas). La usan el bot y el VPS.
 *
 * Postulacion: esperando_discord -> pendiente -> aprobado | rechazado
 *   - esperando_discord: se lleno el formulario; falta que la persona confirme el codigo en Discord.
 *   - pendiente: el bot la publico (o la va a publicar) en el canal del staff.
 * Pedidos para el VPS (kind): alta | baja | pagado. Estado pending -> done.
 */
require_once __DIR__.'/referidos-cola.php'; // panicReferidosWorkerRequest (mismo formato de firma)

final class PanicCreadores {
    private $directory;
    const MAX_ITEMS=2000;
    const PLATAFORMAS=['twitch'=>'Twitch','kick'=>'Kick','youtube'=>'YouTube','tiktok'=>'TikTok','facebook'=>'Facebook','otra'=>'Otra'];
    public function __construct($directory='/home/mupanic/payments-private') {
        $real=realpath($directory); $public=realpath('/home/mupanic/public_html');
        if(!$real || !is_dir($real) || is_link($directory) || ($public && ($real===$public || strpos($real,$public.DIRECTORY_SEPARATOR)===0))) throw new RuntimeException('Private store unavailable');
        $this->directory=$real;
    }
    public function key() {
        $path=$this->directory.'/recharge-worker-token';
        if(is_link($path) || !is_file($path) || filesize($path)>256) throw new RuntimeException('Worker not installed');
        $token=trim(file_get_contents($path));
        if(!preg_match('/^[a-f0-9]{64}$/D',$token)) throw new RuntimeException('Worker token invalid');
        return hash_hmac('sha256','creadores',$token);
    }
    private function locked(callable $operation) {
        $lock=$this->directory.'/creadores.lock';
        if(is_link($lock)) throw new RuntimeException('Invalid lock');
        $handle=fopen($lock,'c'); if(!$handle) throw new RuntimeException('Lock unavailable'); @chmod($lock,0600);
        try {
            if(!flock($handle,LOCK_EX)) throw new RuntimeException('Lock unavailable');
            $path=$this->directory.'/creadores.json';
            if(is_link($path)) throw new RuntimeException('Invalid store');
            clearstatcache(true,$path);
            if(is_file($path) && filesize($path)>4194304) throw new RuntimeException('Store capacity exceeded');
            $data=is_file($path)?json_decode(file_get_contents($path),true,8,JSON_THROW_ON_ERROR):[];
            if(!is_array($data)) throw new RuntimeException('Invalid store');
            $data+=['apps'=>[],'jobs'=>[],'snapshot'=>['at'=>0,'codes'=>[],'liq'=>[]]];
            $result=$operation($data);
            if(is_array($result) && array_key_exists('__save',$result)) { $this->save($result['__save']); return $result['__return'] ?? null; }
            return $result;
        } finally { flock($handle,LOCK_UN); fclose($handle); }
    }
    private function save(array $data) {
        $limit=time()-60*86400;
        foreach($data['jobs'] as $id=>$j) if(($j['state'] ?? '')==='done' && ($j['done'] ?? 0)<$limit) unset($data['jobs'][$id]);
        foreach($data['apps'] as $id=>$a) if(($a['state'] ?? '')==='esperando_discord' && ($a['created'] ?? 0)<time()-14*86400) unset($data['apps'][$id]);
        if(count($data['apps'])+count($data['jobs'])>self::MAX_ITEMS) throw new RuntimeException('Store full');
        $json=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        $tmp=tempnam($this->directory,'cre-'); if(!$tmp) throw new RuntimeException('Write failed');
        @chmod($tmp,0600);
        if(file_put_contents($tmp,$json,LOCK_EX)!==strlen($json) || !rename($tmp,$this->directory.'/creadores.json')) { @unlink($tmp); throw new RuntimeException('Write failed'); }
    }
    private static function hex($n) { return bin2hex(random_bytes($n)); }
    private static function cuentaOk($a) { return is_string($a) && preg_match('/^[A-Za-z0-9_]{1,10}$/D',$a); }
    private static function discordOk($d) { return is_string($d) && preg_match('/^[1-9][0-9]{16,19}$/D',$d); }

    /** Datos publicos de una postulacion (para la web de la propia cuenta y para el bot). */
    private static function vista(array $a, $conCodigo=false) {
        $v=['id'=>$a['id'],'account'=>$a['account'],'platform'=>$a['platform'],'channel_url'=>$a['channel_url'],'audience'=>$a['audience'],'schedule'=>$a['schedule'],
            'note'=>$a['note'],'state'=>$a['state'],'created'=>$a['created'],'discord_id'=>$a['discord_id'] ?? null,'decided'=>$a['decided'] ?? null];
        if($conCodigo) $v['code']=$a['code'];
        return $v;
    }

    /** Formulario de la web: una postulacion activa por cuenta. Devuelve la postulacion (con codigo). */
    public function apply($account,array $f) {
        if(!self::cuentaOk($account)) throw new InvalidArgumentException('Cuenta inválida');
        $platform=(string)($f['platform'] ?? '');
        $url=trim((string)($f['channel_url'] ?? ''));
        $audience=(int)($f['audience'] ?? 0);
        $schedule=trim((string)($f['schedule'] ?? ''));
        $note=trim((string)($f['note'] ?? ''));
        if(!isset(self::PLATAFORMAS[$platform])) throw new InvalidArgumentException('Elegí la plataforma.');
        if(strlen($url)>200 || !preg_match('~^https://[A-Za-z0-9.-]+\.[A-Za-z]{2,}(/[^\s<>"]*)?$~D',$url)) throw new InvalidArgumentException('Pegá el link completo de tu canal (empieza con https://).');
        if($audience<0 || $audience>1000000) throw new InvalidArgumentException('Revisá la cantidad de espectadores.');
        if(mb_strlen($schedule)>120 || mb_strlen($note)>400) throw new InvalidArgumentException('El texto es demasiado largo.');
        $clean=function($s){ return preg_replace('/[\x00-\x1F\x7F]+/u',' ',$s); };
        return $this->locked(function($d) use ($account,$platform,$url,$audience,$schedule,$note,$clean) {
            foreach($d['apps'] as $a) if(strcasecmp($a['account'],$account)===0 && in_array($a['state'],['esperando_discord','pendiente','aprobado'],true))
                return ['__save'=>$d,'__return'=>self::vista($a,true)];
            $recent=0; foreach($d['apps'] as $a) if(strcasecmp($a['account'],$account)===0 && $a['created']>time()-7*86400) $recent++;
            if($recent>=3) throw new RuntimeException('Hiciste muchas postulaciones esta semana. Escribinos por Discord.');
            $id='C'.self::hex(16);
            $d['apps'][$id]=['id'=>$id,'account'=>$account,'platform'=>$platform,'channel_url'=>$url,'audience'=>$audience,'schedule'=>$clean($schedule),'note'=>$clean($note),
                'code'=>self::hex(12),'state'=>'esperando_discord','created'=>time(),'discord_id'=>null,'guild_id'=>null,'staff_message'=>null,'decided'=>null,'decided_by'=>null,'dm'=>false];
            return ['__save'=>$d,'__return'=>self::vista($d['apps'][$id],true)];
        });
    }
    /** Ultima postulacion de una cuenta (para mostrar el estado en la web). */
    public function mine($account) {
        return $this->locked(function($d) use ($account) {
            $last=null;
            foreach($d['apps'] as $a) if(strcasecmp($a['account'],$account)===0 && (!$last || $a['created']>$last['created'])) $last=$a;
            return $last?self::vista($last,$last['state']==='esperando_discord'):null;
        });
    }
    public function all() {
        return $this->locked(function($d) { $l=array_map(function($a){ return self::vista($a); },array_values($d['apps'])); usort($l,function($x,$y){ return $y['created']<=>$x['created']; }); return $l; });
    }

    /* ---------- Bot de Discord ---------- */
    public function bot(array $r) {
        $action=$r['action'] ?? '';
        $guild=(string)($r['guild_id'] ?? '');
        if(!self::discordOk($guild)) throw new InvalidArgumentException('guild');
        switch($action) {
            case 'claim': return $this->claim((string)($r['code'] ?? ''),(string)($r['discord_id'] ?? ''),$guild);
            case 'to_post': return $this->locked(function($d) {
                $out=[]; foreach($d['apps'] as $a) if($a['state']==='pendiente' && !$a['staff_message']) { $out[]=self::vista($a); if(count($out)>=5) break; }
                return ['apps'=>$out]; });
            case 'posted': return $this->mark((string)($r['id'] ?? ''),function(&$a) use ($r) {
                if(!self::discordOk((string)($r['message_id'] ?? ''))) throw new InvalidArgumentException('message');
                $a['staff_message']=(string)$r['message_id']; });
            case 'decide': return $this->decide((string)($r['id'] ?? ''),(string)($r['decision'] ?? ''),(string)($r['staff_id'] ?? ''));
            case 'to_dm': return $this->locked(function($d) {
                // Solo cuando el VPS ya dio de alta al creador y mando su codigo de referido.
                $out=[]; foreach($d['apps'] as $a) {
                    $code=$d['snapshot']['codes'][strtolower($a['account'])] ?? null;
                    if($a['state']==='aprobado' && !$a['dm'] && $a['discord_id'] && $code) { $v=self::vista($a); $v['link']=__BASE_URL__.'register/?ref='.$code; $out[]=$v; if(count($out)>=5) break; }
                }
                return ['apps'=>$out]; });
            case 'dm_done': return $this->mark((string)($r['id'] ?? ''),function(&$a){ $a['dm']=true; });
            case 'liquidacion':
                $mes=(string)($r['mes'] ?? '');
                if(!preg_match('/^20[0-9]{2}-(0[1-9]|1[0-2])$/D',$mes)) throw new InvalidArgumentException('mes');
                return $this->locked(function($d) use ($mes) {
                    $ids=[]; foreach($d['apps'] as $a) if($a['state']==='aprobado' && $a['discord_id']) $ids[strtolower($a['account'])]=$a['discord_id'];
                    $rows=$d['snapshot']['liq'][$mes] ?? null;
                    if(!is_array($rows)) return ['mes'=>$mes,'rows'=>null,'at'=>$d['snapshot']['at']];
                    foreach($rows as &$x) $x['discord_id']=$ids[strtolower($x['account'])] ?? null;
                    return ['mes'=>$mes,'rows'=>$rows,'at'=>$d['snapshot']['at']];
                });
            case 'pagado':
                $account=(string)($r['account'] ?? ''); $mes=(string)($r['mes'] ?? ''); $staff=(string)($r['staff_id'] ?? '');
                if(!self::cuentaOk($account) || !preg_match('/^20[0-9]{2}-(0[1-9]|1[0-2])$/D',$mes) || !self::discordOk($staff)) throw new InvalidArgumentException('pagado');
                return $this->job('pagado',$account,['mes'=>$mes,'by'=>$staff]);
        }
        throw new InvalidArgumentException('action');
    }
    private function claim($code,$discord,$guild) {
        if(!preg_match('/^[a-f0-9]{24}$/D',$code) || !self::discordOk($discord)) throw new InvalidArgumentException('Código inválido');
        return $this->locked(function($d) use ($code,$discord,$guild) {
            foreach($d['apps'] as $id=>$a) {
                if(!hash_equals($a['code'],$code)) continue;
                if($a['state']==='esperando_discord') { $d['apps'][$id]['state']='pendiente'; $d['apps'][$id]['discord_id']=$discord; $d['apps'][$id]['guild_id']=$guild; }
                elseif(($a['discord_id'] ?? '')!==$discord) return ['__save'=>$d,'__return'=>['state'=>'otro_usuario']];
                return ['__save'=>$d,'__return'=>['state'=>'ok','app'=>self::vista($d['apps'][$id])]];
            }
            return ['__save'=>$d,'__return'=>['state'=>'no_existe']];
        });
    }
    private function decide($id,$decision,$staff) {
        if(!in_array($decision,['aprobar','rechazar'],true) || !self::discordOk($staff)) throw new InvalidArgumentException('decide');
        $res=$this->locked(function($d) use ($id,$decision,$staff) {
            if(!isset($d['apps'][$id])) return ['__save'=>$d,'__return'=>['state'=>'no_existe']];
            $a=&$d['apps'][$id];
            if($a['state']!=='pendiente') return ['__save'=>$d,'__return'=>['state'=>'ya_decidida','app'=>self::vista($a)]];
            $a['state']=$decision==='aprobar'?'aprobado':'rechazado'; $a['decided']=time(); $a['decided_by']=$staff;
            if($decision==='aprobar') {
                $jid='J'.self::hex(16);
                $d['jobs'][$jid]=['id'=>$jid,'kind'=>'alta','account'=>$a['account'],'created'=>time(),'state'=>'pending','result'=>'','by'=>$staff];
            }
            return ['__save'=>$d,'__return'=>['state'=>'ok','app'=>self::vista($a)]];
        });
        return $res;
    }
    private function mark($id,callable $change) {
        if(!preg_match('/^C[a-f0-9]{32}$/D',$id)) throw new InvalidArgumentException('id');
        return $this->locked(function($d) use ($id,$change) {
            if(!isset($d['apps'][$id])) return ['__save'=>$d,'__return'=>['state'=>'no_existe']];
            $change($d['apps'][$id]);
            return ['__save'=>$d,'__return'=>['state'=>'ok']];
        });
    }
    private function job($kind,$account,array $extra) {
        return $this->locked(function($d) use ($kind,$account,$extra) {
            foreach($d['jobs'] as $j) if($j['kind']===$kind && strcasecmp($j['account'],$account)===0 && $j['state']==='pending' && ($j['mes'] ?? '')===($extra['mes'] ?? ''))
                return ['__save'=>$d,'__return'=>['state'=>'ok','job'=>$j['id']]];
            $jid='J'.self::hex(16);
            $d['jobs'][$jid]=['id'=>$jid,'kind'=>$kind,'account'=>$account,'created'=>time(),'state'=>'pending','result'=>'']+$extra;
            return ['__save'=>$d,'__return'=>['state'=>'ok','job'=>$jid]];
        });
    }

    /* ---------- Worker de Socios en el VPS ---------- */
    /**
     * El worker manda en cada consulta una foto chica sacada de SQL:
     *   codes: { cuenta: codigoReferido } de los creadores activos
     *   liq:   { 'AAAA-MM': [ {account, invitados, recargas, a_pagar, pagado} ] } (mes actual y anterior)
     */
    public function poll($snapshot=null) {
        $snap=null;
        if(is_array($snapshot)) {
            $codes=[]; foreach((array)($snapshot['codes'] ?? []) as $acc=>$code) if(self::cuentaOk((string)$acc) && is_string($code) && preg_match('/^[A-Z0-9]{8}$/D',$code)) $codes[strtolower($acc)]=$code;
            $liq=[]; foreach((array)($snapshot['liq'] ?? []) as $mes=>$rows) {
                if(!preg_match('/^20[0-9]{2}-(0[1-9]|1[0-2])$/D',(string)$mes) || !is_array($rows)) continue;
                $liq[$mes]=[]; foreach(array_slice($rows,0,50) as $x) {
                    if(!is_array($x) || !self::cuentaOk((string)($x['account'] ?? ''))) continue;
                    $liq[$mes][]=['account'=>(string)$x['account'],'invitados'=>max(0,(int)($x['invitados'] ?? 0)),'recargas'=>max(0,(int)($x['recargas'] ?? 0)),'a_pagar'=>max(0,(int)($x['a_pagar'] ?? 0)),'pagado'=>max(0,(int)($x['pagado'] ?? 0))];
                }
            }
            $snap=['at'=>time(),'codes'=>$codes,'liq'=>$liq];
        }
        return $this->locked(function($d) use ($snap) {
            if($snap) $d['snapshot']=$snap;
            $out=[];
            foreach($d['jobs'] as $j) {
                if($j['state']!=='pending') continue;
                $row=['id'=>$j['id'],'kind'=>$j['kind'],'account'=>$j['account']];
                if($j['kind']==='pagado') $row['mes']=$j['mes'];
                $out[]=$row; if(count($out)>=20) break;
            }
            return ['__save'=>$d,'__return'=>['items'=>$out]];
        });
    }
    public function acknowledge($results) {
        if(!is_array($results) || count($results)>20) throw new InvalidArgumentException('Invalid results');
        return $this->locked(function($d) use ($results) {
            $n=0;
            foreach($results as $r) {
                $id=$r['id'] ?? ''; $res=$r['result'] ?? '';
                if(!is_string($id) || !preg_match('/^J[a-f0-9]{32}$/D',$id) || !is_string($res) || !preg_match('/^[A-Z_0-9 ()]{1,40}$/D',$res) || !isset($d['jobs'][$id])) continue;
                if($d['jobs'][$id]['state']==='pending') { $d['jobs'][$id]['state']='done'; $d['jobs'][$id]['result']=$res; $d['jobs'][$id]['done']=time(); $n++; }
            }
            return ['__save'=>$d,'__return'=>['acknowledged'=>$n]];
        });
    }
}
