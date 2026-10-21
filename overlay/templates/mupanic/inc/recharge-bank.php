<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/recharge-management.php';

/** Separate private transfer ledger; Uala orders and credentials remain untouched. */
final class PanicBankTransfers {
    private $directory;
    public function __construct($directory='/home/mupanic/payments-private') {
        $real=realpath($directory); $public=realpath('/home/mupanic/public_html');
        if(!$real || !is_dir($real) || is_link($directory) || ($public && ($real===$public || strpos($real,$public.DIRECTORY_SEPARATOR)===0))) throw new RuntimeException('Transferencias no disponibles.');
        $this->directory=$real;
    }
    private function file(callable $operation) {
        $path=$this->directory.'/recharge-bank.json'; $lock=$this->directory.'/recharge-bank.lock';
        if(is_link($path) || is_link($lock)) throw new RuntimeException('Transferencias no disponibles.');
        $handle=fopen($lock,'c'); if(!$handle) throw new RuntimeException('Transferencias no disponibles.'); chmod($lock,0600);
        try {
            if(!flock($handle,LOCK_EX)) throw new RuntimeException('Transferencias no disponibles.');
            clearstatcache(true,$path);
            if(is_file($path) && filesize($path)>8388608) throw new RuntimeException('Registro completo. Contactá al equipo.');
            $data=is_file($path)?json_decode(file_get_contents($path),true,32,JSON_THROW_ON_ERROR):['config'=>self::defaults(),'orders'=>[]];
            if(!is_array($data) || !is_array($data['config'] ?? null) || !is_array($data['orders'] ?? null) || count($data['orders'])>5000) throw new RuntimeException('Registro inválido.');
            return $operation($data,function($next) use($path) {
                $json=json_encode($next,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
                if(strlen($json)>8388608) throw new RuntimeException('Registro completo.');
                $tmp=tempnam($this->directory,'bank-'); if(!$tmp) throw new RuntimeException('No se pudo guardar.');
                try {
                    chmod($tmp,0600);$h=fopen($tmp,'wb');
                    if(!$h) throw new RuntimeException('No se pudo guardar.');
                    try { if(fwrite($h,$json)!==strlen($json) || !fflush($h) || (function_exists('fsync') && !fsync($h))) throw new RuntimeException('No se pudo guardar.'); } finally { fclose($h); }
                    if(!rename($tmp,$path)) throw new RuntimeException('No se pudo guardar.');
                } finally { if(is_file($tmp)) unlink($tmp); }
            });
        } finally { flock($handle,LOCK_UN);fclose($handle); }
    }
    public static function defaults() {
        return ['revision'=>0,'enabled'=>false,'holder'=>'','bank'=>'','recipient_cuit'=>'','alias'=>'','cbu'=>'',
            'guild_id'=>'1555229554920915057','support_channel_id'=>''];
    }
    public function config() { return $this->file(function($d){return $d['config'];}); }
    public function environment() {
        $path=$this->directory.'/settings.json';
        if(is_link($path) || !is_file($path) || filesize($path)>131072) throw new RuntimeException('Ambiente no disponible.');
        $data=json_decode(file_get_contents($path),true,16,JSON_THROW_ON_ERROR);
        if(!in_array($data['environment'] ?? '',['test','production'],true)) throw new RuntimeException('Ambiente no disponible.');
        return $data['environment'];
    }
    public static function cuit($value) {
        if(!is_string($value) || !preg_match('/^(?:\d{11}|\d{2}-\d{8}-\d)$/D',$value)) throw new InvalidArgumentException('Ingresá un CUIT válido de 11 dígitos.');
        $value=str_replace('-','',$value);$sum=0;
        foreach([5,4,3,2,7,6,5,4,3,2] as $i=>$weight) $sum+=(int)$value[$i]*$weight;
        $digit=11-($sum%11); if($digit===11)$digit=0;
        if($digit===10 || $digit!==(int)$value[10] || $value===str_repeat('0',11)) throw new InvalidArgumentException('El dígito verificador del CUIT no es válido.');
        return $value;
    }
    public function saveConfig(array $input,$revision) {
        if(!panicRechargeAdminAllowed()) throw new RuntimeException('Acceso no autorizado.');
        return $this->file(function($d,$save)use($input,$revision){
            $old=$d['config'];if($revision!==$old['revision']) throw new InvalidArgumentException('La configuración cambió en otra ventana. Recargá el panel.');
            $next=self::defaults();$next['revision']=$old['revision']+1;$next['enabled']=!empty($input['enabled']);
            foreach(['holder'=>120,'bank'=>80,'recipient_cuit'=>13,'alias'=>64,'cbu'=>22,'guild_id'=>20,'support_channel_id'=>20] as $k=>$max) {
                $v=$input[$k] ?? '';if(!is_string($v) || strlen($v)>$max || preg_match('/[\x00-\x1f\x7f]/',$v)) throw new InvalidArgumentException('Revisá los datos de transferencia y Discord.');
                $next[$k]=trim($v);
            }
            if($next['recipient_cuit']!=='')$next['recipient_cuit']=self::cuit($next['recipient_cuit']);
            if($next['cbu']!=='' && !preg_match('/^\d{22}$/D',$next['cbu'])) throw new InvalidArgumentException('El CBU/CVU debe tener 22 dígitos.');
            foreach(['guild_id','support_channel_id']as $k)if($next[$k]!=='' && !preg_match('/^[1-9]\d{16,19}$/D',$next[$k]))throw new InvalidArgumentException('Los ID de Discord deben ser numéricos.');
            if($next['enabled'] && !self::configured($next))throw new InvalidArgumentException('Completá la cuenta bancaria y la integración de Discord antes de habilitar.');
            $d['config']=$next;$save($d);return $next;
        });
    }
    public static function configured(array $c) {
        foreach(['holder','bank','guild_id','support_channel_id']as $k)if(empty($c[$k]))return false;
        return !empty($c['alias']) || !empty($c['cbu']);
    }
    public static function workerReady(array $w,$environment,$now=null) {
        $now=$now ?? time();return ($w['version'] ?? 0)>=3 && ($w['environment'] ?? '')===$environment && ($w['last_success'] ?? 0)>=$now-180 && ($w['last_success'] ?? 0)<=$now+30;
    }
    public function begin($account,array $cart,$nonce,$cuit,$environment,array $worker) {
        if(!self::workerReady($worker,$environment) || !$this->botReady($environment))throw new RuntimeException('La entrega de transferencias todavía no está disponible.');
        if(!in_array($environment,['test','production'],true) || $environment!==$this->environment())throw new RuntimeException('Ambiente inválido.');
        $cuit=self::cuit($cuit);
        if(!is_string($nonce) || !preg_match('/^[a-f0-9]{32}$/D',$nonce))throw new RuntimeException('La sesión venció.');
        $snapshot=PanicRecharge::cart($cart['lines'] ?? [],array_column($cart['lines'] ?? [],'quantity','id'));
        if($snapshot!==$cart)throw new RuntimeException('Carrito inválido.');
        $fingerprint=hash('sha256',json_encode([$cart,$cuit,$environment]));$key=hash('sha256',$account."\n".$nonce);
        return $this->file(function($d,$save)use($account,$cart,$cuit,$environment,$fingerprint,$key){
            foreach($d['orders']as $o)if($o['request_key']===$key){if($o['fingerprint']!==$fingerprint)throw new RuntimeException('La solicitud cambió.');return $o;}
            $c=$d['config'];if(empty($c['enabled']) || !self::configured($c))throw new RuntimeException('Las transferencias no están habilitadas.');
            if(count($d['orders'])>=5000)throw new RuntimeException('Registro completo.');
            $open=0;foreach($d['orders']as $o)if($o['account']===$account && in_array($o['payment_state'],['pending','approved'],true) && $o['delivery_state']==='pending')$open++;
            if($open>=5)throw new RuntimeException('Tenés 5 transferencias pendientes. Revisá las anteriores.');
            $o=PanicRecharge::order($account,$cart,'bank_transfer',$environment==='production');
            $o+=['lines'=>$cart['lines'],'cuit'=>$cuit,'request_key'=>$key,'fingerprint'=>$fingerprint,'ticket_state'=>'none','ticket_channel'=>null,'discord_id'=>null];
            $o['bank']=array_intersect_key($c,array_flip(['holder','bank','recipient_cuit','alias','cbu']));
            $o['discord']=array_intersect_key($c,array_flip(['guild_id','support_channel_id']));
            $d['orders'][$o['id']]=$o;$save($d);return $o;
        });
    }
    public function get($id,$account=null) {
        return $this->file(function($d)use($id,$account){$o=$d['orders'][$id] ?? null;if(!$o || ($account!==null && !hash_equals($o['account'],$account)))throw new RuntimeException('Compra no disponible.');return $o;});
    }
    public function history($account,$page=1,$admin=false) {
        if($admin && !panicRechargeAdminAllowed())throw new RuntimeException('Acceso no autorizado.');
        return $this->file(function($d)use($account,$page,$admin){
            $rows=array_values(array_filter($d['orders'],function($o)use($admin,$account){return $admin || $o['account']===$account;}));
            usort($rows,function($a,$b){return strcmp($b['created_at'],$a['created_at']);});
            return ['total'=>count($rows),'orders'=>array_slice($rows,(max(1,$page)-1)*10,10)];
        });
    }
    private function update($id,callable $fn) {
        return $this->file(function($d,$save)use($id,$fn){$o=$d['orders'][$id] ?? null;if(!$o)throw new RuntimeException('Compra no disponible.');$o=$fn($o,$d['orders']);$d['orders'][$id]=$o;$save($d);return $o;});
    }
    public function ready($id,$account) {
        $code=bin2hex(random_bytes(12));
        $o=$this->update($id,function($o)use($account,$code){
            if($o['account']!==$account || $o['payment_state']!=='pending')throw new RuntimeException('Compra no disponible.');
            $o['claim_hash']=hash('sha256',$code);$o['claim_expires']=time()+1800;return $o;
        });
        return ['order'=>$o,'code'=>$code];
    }
    public function bot(array $r) {
        $action=$r['action'] ?? ''; $environment=$r['environment'] ?? '';
        if(!in_array($environment,['test','production'],true) || $environment!==$this->environment())throw new RuntimeException('Ambiente inválido.');
        if($action==='heartbeat') {
            if(!preg_match('/^[1-9]\d{16,19}$/D',$r['guild_id'] ?? '') || !preg_match('/^[1-9]\d{16,19}$/D',$r['support_channel_id'] ?? ''))throw new RuntimeException('Bot inválido.');
            return $this->file(function($d,$save)use($r){$d['bot']=['last_seen'=>time(),'environment'=>$r['environment'],'guild_id'=>$r['guild_id'],'support_channel_id'=>$r['support_channel_id']];$save($d);return ['state'=>'ok'];});
        }
        if($action==='claim') {
            if(!is_string($r['code'] ?? null) || !preg_match('/^[a-f0-9]{24}$/D',$r['code']) || !preg_match('/^[1-9]\d{16,19}$/D',$r['discord_id'] ?? ''))throw new RuntimeException('Código inválido.');
            return $this->file(function($d,$save)use($r,$environment){
                foreach($d['orders']as $id=>$o) {
                    if(!isset($o['claim_hash']) || !hash_equals($o['claim_hash'],hash('sha256',$r['code'])) || ($o['claim_expires'] ?? 0)<time())continue;
                    if($o['payment_state']!=='pending' || $o['live']!==($environment==='production') || $o['discord']['guild_id']!==($r['guild_id'] ?? '') || ($o['discord_id']!==null && $o['discord_id']!==$r['discord_id']))throw new RuntimeException('Código no disponible.');
                    $create=$o['ticket_state']==='none';
                    if($create){$o['discord_id']=$r['discord_id'];$o['ticket_state']='creating';$d['orders'][$id]=$o;$save($d);}
                    return ['order'=>self::botOrder($o),'can_create'=>$create];
                }
                throw new RuntimeException('Código vencido o no disponible.');
            });
        }
        $o=$this->get($r['id'] ?? '');
        if($o['live']!==($environment==='production') || $o['discord']['guild_id']!==($r['guild_id'] ?? '') || $o['discord_id']!==($r['discord_id'] ?? '') || !preg_match('/^[1-9]\d{16,19}$/D',$r['channel_id'] ?? ''))throw new RuntimeException('Ticket inválido.');
        if($action==='ticket')return $this->update($o['id'],function($o)use($r){
            if($o['ticket_state']!=='creating' && $o['ticket_state']!=='open')throw new RuntimeException('Ticket inválido.');
            if($o['ticket_channel']!==null && $o['ticket_channel']!==$r['channel_id'])throw new RuntimeException('El ticket ya existe.');
            $o['ticket_channel']=$r['channel_id'];$o['ticket_state']='open';return ['id'=>$o['id']]+$o;
        });
        if($action==='receipt')return $this->update($o['id'],function($o)use($r){
            if($o['payment_state']!=='pending' || $o['ticket_channel']!==$r['channel_id'] || !preg_match('/^[1-9]\d{16,19}$/D',$r['message_id'] ?? '') || ($r['has_attachment'] ?? null)!==true)throw new RuntimeException('Comprobante inválido.');
            $o['receipt_message']=$r['message_id'];$o['receipt_url']='https://discord.com/channels/'.$o['discord']['guild_id'].'/'.$o['ticket_channel'].'/'.$r['message_id'];$o['receipt_at']=gmdate('c');return $o;
        });
        throw new RuntimeException('Acción inválida.');
    }
    private static function botOrder(array $o) {
        return array_intersect_key($o,array_flip(['id','account','coins','price_cents','cuit','ticket_channel','discord_id','discord']));
    }
    public function botInfo() { return $this->file(function($d){return $d['bot'] ?? [];}); }
    public function botReady($environment) {
        return $this->file(function($d)use($environment){$b=$d['bot'] ?? [];return ($b['last_seen'] ?? 0)>=time()-180 && ($b['environment'] ?? '')===$environment && ($b['guild_id'] ?? '')===$d['config']['guild_id'] && ($b['support_channel_id'] ?? '')===$d['config']['support_channel_id'];});
    }
    public function approve($id,$reference,$confirmed) {
        if(!panicRechargeAdminAllowed())throw new RuntimeException('Acceso no autorizado.');
        if($confirmed!==true)throw new InvalidArgumentException('Confirmá que verificaste el ingreso bancario y el comprobante.');
        if(!is_string($reference) || !preg_match('/^[A-Za-z0-9_-]{6,80}$/D',$reference))throw new InvalidArgumentException('Ingresá la referencia bancaria de la operación, sin espacios.');
        $o=$this->get($id);if($o['payment_state']!=='pending' || !$o['ticket_channel'])throw new InvalidArgumentException('La compra no está pendiente con un ticket abierto.');
        if(($o['live']?'production':'test')!==$this->environment())throw new InvalidArgumentException('La compra pertenece a otro ambiente.');
        if(empty($o['receipt_message']) || empty($o['receipt_url']))throw new InvalidArgumentException('Falta que el bot registre el comprobante adjunto en Discord.');
        $payment='BANK_'.strtoupper($reference);
        return $this->update($id,function($current,$orders)use($payment){
            if($current['payment_state']!=='pending')throw new InvalidArgumentException('La compra ya fue revisada.');
            foreach($orders as $other)if($other['live']===$current['live'] && ($other['payment_id'] ?? '')===$payment)throw new InvalidArgumentException('Esta operación bancaria ya está registrada.');
            $current['payment_id']=$payment;$current['payment_state']='approved';$current['approved_by']=(string)$_SESSION['username'];$current['approved_at']=gmdate('c');
            return $current;
        });
    }
    public function reject($id) {
        if(!panicRechargeAdminAllowed())throw new RuntimeException('Acceso no autorizado.');
        return $this->update($id,function($o){if($o['payment_state']!=='pending')throw new InvalidArgumentException('La compra ya fue revisada.');$o['payment_state']='rejected';$o['reviewed_by']='panic';$o['reviewed_at']=gmdate('c');return $o;});
    }
    public function jobs($environment,$limit=3) {
        if(!in_array($environment,['test','production'],true) || !is_int($limit) || $limit<1 || $limit>3)throw new RuntimeException('Cola inválida.');
        return $this->file(function($d,$save)use($environment,$limit){
            $rows=array_filter($d['orders'],function($o)use($environment){return $o['payment_state']==='approved' && $o['delivery_state']==='pending' && $o['live']===($environment==='production');});
            uasort($rows,function($a,$b){return ($a['last_poll'] ?? 0)<=>($b['last_poll'] ?? 0);});
            $jobs=[];foreach(array_slice($rows,0,$limit,true)as $id=>$o){
                $jobs[]=array_intersect_key($o,array_flip(['id','payment_id','account','coins','price_cents','provider']))+['environment'=>$environment];
                $d['orders'][$id]['last_poll']=microtime(true);
            }
            if($jobs)$save($d);return $jobs;
        });
    }
    public function acknowledge(array $r) {
        return $this->update($r['id'] ?? '',function($o)use($r){
            if($o['payment_state']!=='approved' || ($r['provider'] ?? '')!=='bank_transfer' || ($r['state'] ?? '')!=='credited' || ($r['account'] ?? '')!==$o['account'] || ($r['payment_id'] ?? '')!==$o['payment_id'] || ($r['coins'] ?? null)!==$o['coins'] || ($r['environment'] ?? '')!==($o['live']?'production':'test') || !is_int($r['before_coin'] ?? null) || !is_int($r['after_coin'] ?? null) || $r['before_coin']<0 || $r['after_coin']>2147483647 || $r['after_coin']-$r['before_coin']!==$o['coins'])throw new RuntimeException('Recibo inválido.');
            if($o['delivery_state']!=='credited'){$o['delivery_state']='credited';$o['credited_at']=gmdate('c');$o['before_coin']=$r['before_coin'];$o['after_coin']=$r['after_coin'];}return $o;
        });
    }
}
function panicBankStatus(array $o) {
    if($o['delivery_state']==='credited')return ['credited','Eryns acreditados','La recarga ya está en tu cuenta.'];
    if($o['payment_state']==='approved')return ['approved','Transferencia confirmada','Desconectate del juego para recibir los Eryns.'];
    if($o['payment_state']==='rejected')return ['rejected','Transferencia no aprobada','Consultá el motivo con el equipo en tu ticket.'];
    return ['pending',$o['ticket_channel']?'En revisión':'Esperando tu comprobante',$o['ticket_channel']?'Adjuntá el comprobante en tu ticket de Discord. El equipo verificará el ingreso.':'Transferí el importe exacto y abrí el ticket para adjuntar el comprobante.'];
}
