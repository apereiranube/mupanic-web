<?php
if(!defined('access') || !access) die();
/*
 * MU PANIC - Referidos: cola de pedidos web -> VPS.
 * La web NUNCA escribe en la base del juego. Las confirmaciones de invitados y las acciones del admin
 * quedan en un archivo privado (fuera de public_html) y el proceso del VPS las toma con un pedido firmado.
 * La clave del puente se deriva del token del worker de recargas: HMAC('referidos', token).
 */
final class PanicReferidosCola {
    private $directory;
    const MAX_ITEMS=3000;
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
        return hash_hmac('sha256','referidos',$token);
    }
    private function locked(callable $operation) {
        $lock=$this->directory.'/referidos-cola.lock';
        if(is_link($lock)) throw new RuntimeException('Invalid lock');
        $handle=fopen($lock,'c'); if(!$handle) throw new RuntimeException('Lock unavailable'); @chmod($lock,0600);
        try {
            if(!flock($handle,LOCK_EX)) throw new RuntimeException('Lock unavailable');
            $path=$this->directory.'/referidos-cola.json';
            if(is_link($path)) throw new RuntimeException('Invalid store');
            clearstatcache(true,$path);
            if(is_file($path) && filesize($path)>4194304) throw new RuntimeException('Store capacity exceeded');
            $items=is_file($path)?json_decode(file_get_contents($path),true,8,JSON_THROW_ON_ERROR):[];
            if(!is_array($items)) throw new RuntimeException('Invalid store');
            $result=$operation($items);
            if(is_array($result) && array_key_exists('__save',$result)) { $this->save($result['__save']); return $result['__return'] ?? null; }
            return $result;
        } finally { flock($handle,LOCK_UN); fclose($handle); }
    }
    private function save(array $items) {
        // limpieza: lo resuelto hace mas de 30 dias se descarta
        $limit=time()-30*86400;
        foreach($items as $id=>$item) if(($item['state'] ?? '')==='done' && ($item['done'] ?? 0)<$limit) unset($items[$id]);
        if(count($items)>self::MAX_ITEMS) throw new RuntimeException('Store full');
        $data=json_encode($items,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        $tmp=tempnam($this->directory,'ref-'); if(!$tmp) throw new RuntimeException('Write failed');
        @chmod($tmp,0600);
        if(file_put_contents($tmp,$data,LOCK_EX)!==strlen($data) || !rename($tmp,$this->directory.'/referidos-cola.json')) { @unlink($tmp); throw new RuntimeException('Write failed'); }
    }
    private static function newId() { return 'R'.bin2hex(random_bytes(16)); }
    /** El invitado confirma el codigo de quien lo invito. Un solo pedido pendiente por cuenta. */
    public function requestLink($account,$code) {
        if(!preg_match('/^[A-Za-z0-9_]{1,10}$/D',$account) || !preg_match('/^[A-Z0-9]{8}$/D',$code)) throw new InvalidArgumentException('Datos inválidos');
        return $this->locked(function($items) use ($account,$code) {
            foreach($items as $item) if(($item['kind'] ?? '')==='vincular' && strcasecmp($item['account'] ?? '',$account)===0 && ($item['state'] ?? '')==='pending') return ['__save'=>$items,'__return'=>$item['id']];
            $recent=0; foreach($items as $item) if(($item['kind'] ?? '')==='vincular' && strcasecmp($item['account'] ?? '',$account)===0 && ($item['created'] ?? 0)>time()-86400) $recent++;
            if($recent>=5) throw new RuntimeException('Demasiados intentos');
            $id=self::newId();
            $items[$id]=['id'=>$id,'kind'=>'vincular','account'=>$account,'code'=>$code,'created'=>time(),'state'=>'pending','result'=>''];
            return ['__save'=>$items,'__return'=>$id];
        });
    }
    /** Accion del administrador sobre un vinculo. */
    public function requestAdmin($vinculo,$action,$admin) {
        if(!is_int($vinculo) || $vinculo<1 || !in_array($action,['ANULAR','APROBAR'],true) || !preg_match('/^[A-Za-z0-9_]{1,10}$/D',$admin)) throw new InvalidArgumentException('Datos inválidos');
        return $this->locked(function($items) use ($vinculo,$action,$admin) {
            foreach($items as $item) if(($item['kind'] ?? '')==='admin' && ($item['vinculo'] ?? 0)===$vinculo && ($item['state'] ?? '')==='pending') return ['__save'=>$items,'__return'=>$item['id']];
            $id=self::newId();
            $items[$id]=['id'=>$id,'kind'=>'admin','vinculo'=>$vinculo,'action'=>$action,'account'=>$admin,'created'=>time(),'state'=>'pending','result'=>''];
            return ['__save'=>$items,'__return'=>$id];
        });
    }
    /** Ultimo pedido de vinculo de una cuenta (para mostrar el resultado). */
    public function lastLink($account) {
        return $this->locked(function($items) use ($account) {
            $last=null;
            foreach($items as $item) if(($item['kind'] ?? '')==='vincular' && strcasecmp($item['account'] ?? '',$account)===0 && (!$last || $item['created']>$last['created'])) $last=$item;
            return $last;
        });
    }
    public function pendingAdmin() {
        return $this->locked(function($items) { return array_values(array_filter($items,function($i){ return ($i['kind'] ?? '')==='admin' && ($i['state'] ?? '')==='pending'; })); });
    }
    /** Para el proceso del VPS: hasta 20 pedidos pendientes. */
    public function poll() {
        return $this->locked(function($items) {
            $out=[];
            foreach($items as $item) {
                if(($item['state'] ?? '')!=='pending') continue;
                $row=['id'=>$item['id'],'kind'=>$item['kind'],'account'=>$item['account']];
                if($item['kind']==='vincular') $row['code']=$item['code']; else { $row['vinculo']=$item['vinculo']; $row['action']=$item['action']; }
                $out[]=$row; if(count($out)>=20) break;
            }
            return ['items'=>$out];
        });
    }
    public function acknowledge($results) {
        if(!is_array($results) || count($results)>20) throw new InvalidArgumentException('Invalid results');
        return $this->locked(function($items) use ($results) {
            $n=0;
            foreach($results as $r) {
                $id=$r['id'] ?? ''; $res=$r['result'] ?? '';
                if(!is_string($id) || !preg_match('/^R[a-f0-9]{32}$/D',$id) || !is_string($res) || !preg_match('/^[A-Z_ ()]{1,40}$/D',$res) || !isset($items[$id])) continue;
                if($items[$id]['state']==='pending') { $items[$id]['state']='done'; $items[$id]['result']=$res; $items[$id]['done']=time(); $n++; }
            }
            return ['__save'=>$items,'__return'=>['acknowledged'=>$n]];
        });
    }
}

/** Verifica un pedido firmado del VPS: HMAC(timestamp \n body) con +-300 s y nonce de 32 hex. */
function panicReferidosWorkerRequest($body,$stamp,$signature,$key,$now) {
    if(!is_string($body) || strlen($body)>8192 || !is_string($stamp) || !preg_match('/^[0-9]{10}$/D',$stamp) ||
        abs($now-(int)$stamp)>300 || !is_string($signature) || !hash_equals(hash_hmac('sha256',$stamp."\n".$body,$key),$signature)) return null;
    try { $request=json_decode($body,true,8,JSON_THROW_ON_ERROR); } catch(Throwable $e) { return null; }
    if(!is_array($request) || !is_string($request['nonce'] ?? null) || !preg_match('/^[a-f0-9]{32}$/D',$request['nonce'])) return null;
    return $request;
}

/** Codigo de invitacion recibido por link (?ref=) guardado en la sesion o la cookie. */
function panicReferidosCodigoPendiente() {
    foreach([$_SESSION['mupanic_ref'] ?? null, $_COOKIE['mupanic_ref'] ?? null] as $c) if(is_string($c) && preg_match('/^[A-Z0-9]{8}$/D',$c)) return $c;
    return null;
}
function panicReferidosGuardarCodigo($code) {
    $code=strtoupper(trim((string)$code));
    if(!preg_match('/^[A-Z0-9]{8}$/D',$code)) return;
    $_SESSION['mupanic_ref']=$code;
    if(!headers_sent()) setcookie('mupanic_ref',$code,['expires'=>time()+7*86400,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
}

/** Lecturas de las vistas (solo SELECT). */
function panicReferidosDb() { return Connection::Database('MuOnline'); }
function panicReferidosMiCodigo($account) {
    $rows=panicReferidosDb()->query_fetch('SELECT Codigo FROM [MuOnline43].[dbo].[vMUPanicRefCodigo] WHERE Cuenta = ?',[$account]);
    $c=is_array($rows) && count($rows)===1 ? (string)$rows[0]['Codigo'] : '';
    return preg_match('/^[A-Z0-9]{8}$/D',$c)?$c:null;
}
function panicReferidosComoInvitado($account) {
    $rows=panicReferidosDb()->query_fetch('SELECT Id, Estado, Resets, Dias, DiasInvitado, PagoInvitadoAR FROM [MuOnline43].[dbo].[vMUPanicRefVinculo] WHERE Invitado = ?',[$account]);
    return is_array($rows) && count($rows)===1 ? $rows[0] : null;
}
function panicReferidosMisInvitados($account) {
    $rows=panicReferidosDb()->query_fetch('SELECT TOP 100 Id, Personaje, Estado, Motivo, Resets, Dias, DiasInvita, PagaInvita, PagoInvitaAR, CONVERT(varchar(10),CreadoAR,103) AS Creado FROM [MuOnline43].[dbo].[vMUPanicRefVinculo] WHERE Invita = ? ORDER BY Id DESC',[$account]);
    return is_array($rows) ? $rows : [];
}
/** Para el inicio de Mi cuenta: hay un codigo por confirmar y la cuenta todavia no esta vinculada. */
function panicReferidosHayPorConfirmar($account) {
    if(!panicReferidosCodigoPendiente() || !preg_match('/^[A-Za-z0-9_]{1,10}$/D',(string)$account)) return false;
    try { return panicReferidosComoInvitado($account)===null; } catch(Throwable $e) { return false; }
}
