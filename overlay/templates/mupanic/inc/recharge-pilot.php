<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/recharge-uala.php';

/** Single, administrator-authorized sandbox purchase. Never a public shop. */
final class PanicRechargePilot {
    private $directory;
    public function __construct($directory='/home/mupanic/payments-private') {
        $real=realpath($directory); $public=realpath('/home/mupanic/public_html');
        if(!$real || !is_dir($real) || is_link($directory) ||
           ($public && ($real===$public || strpos($real,$public.DIRECTORY_SEPARATOR)===0))) throw new RuntimeException('Private pilot directory unavailable');
        $this->directory=$real;
    }
    public function token() {
        $path=$this->directory.'/sandbox-worker-token';
        if(is_link($path) || !is_file($path) || filesize($path)>256) throw new RuntimeException('Worker not configured');
        $token=trim(file_get_contents($path));
        if(!preg_match('/^[a-f0-9]{64}$/D',$token)) throw new RuntimeException('Invalid worker token');
        return $token;
    }
    public function locked(callable $operation) {
        $path=$this->directory.'/uala-pilot.lock';
        if(is_link($path)) throw new RuntimeException('Invalid pilot lock');
        $lock=fopen($path,'c');
        if(!$lock) throw new RuntimeException('Pilot lock unavailable');
        chmod($path,0600);
        try {
            if(!flock($lock,LOCK_EX)) throw new RuntimeException('Pilot lock unavailable');
            $state=$this->read();
            return $operation($state,$this);
        } finally { flock($lock,LOCK_UN); fclose($lock); }
    }
    private function read() {
        $path=$this->directory.'/uala-pilot.json';
        if(is_link($path)) throw new RuntimeException('Invalid pilot state');
        if(!is_file($path)) return null;
        if(filesize($path)>65536) throw new RuntimeException('Invalid pilot state');
        $state=json_decode(file_get_contents($path),true,16,JSON_THROW_ON_ERROR);
        if(!is_array($state) || ($state['account'] ?? '')!=='pruebacoin' || ($state['coins'] ?? null)!==1000 ||
           ($state['bonus'] ?? null)!==0 || ($state['price_cents'] ?? null)!==100000 || ($state['live'] ?? null)!==false ||
           ($state['provider'] ?? '')!=='uala_bis' || !preg_match('/^PANIC-[a-f0-9]{32}$/D',$state['id'] ?? '')) throw new RuntimeException('Invalid pilot state');
        return $state;
    }
    public function save(array $state) {
        $path=tempnam($this->directory,'pilot-');
        if(!$path) throw new RuntimeException('Pilot storage unavailable');
        try {
            chmod($path,0600);
            $file=fopen($path,'wb');
            if(!$file) throw new RuntimeException('Pilot storage unavailable');
            try {
                $data=json_encode($state,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);
                if(fwrite($file,$data)!==strlen($data) || !fflush($file) || (function_exists('fsync') && !fsync($file))) throw new RuntimeException('Pilot storage unavailable');
            } finally { fclose($file); }
            if(!rename($path,$this->directory.'/uala-pilot.json')) throw new RuntimeException('Pilot commit failed');
        } finally { if(is_file($path)) unlink($path); }
    }
    public function current() { return $this->locked(function($state) { return $state; }); }
    public function begin(PanicUalaBisApi $api,$baseUrl) {
        $this->token();
        return $this->locked(function($state,$store) use ($api,$baseUrl) {
            if($state!==null) return $state; // One pilot: never create a second payment on retry.
            $package=['id'=>'wcoin-1000','title'=>'PRUEBA · 1.000 WCoin C','price_cents'=>100000,'coins'=>1000,'bonus'=>0];
            $state=PanicRecharge::order('pruebacoin',$package,'uala_bis',false);
            $state['payment_state']='creating';
            $state['callback_token']=bin2hex(random_bytes(32));
            $state['expires_at']=time()+7*86400;
            $store->save($state); // Reserve before external request; ambiguity never creates another order.
            $hook=rtrim($baseUrl,'/').'/templates/mupanic/api/uala-pilot-hook.php?key='.$state['callback_token'];
            $return=rtrim($baseUrl,'/').'/usercp/recharge/';
            try { $result=$api->createCheckout($state,$return,$hook); }
            catch(Throwable $exception) { $state['last_error']=self::diagnostic($exception); $store->save($state); throw $exception; }
            $id=$result['uuid'] ?? null; $link=$result['links']['checkout_link'] ?? null;
            $parts=is_string($link)?parse_url($link):false;
            if(!is_string($id) || !preg_match('/^[A-Za-z0-9_-]{1,120}$/D',$id) || ($result['external_reference'] ?? '')!==$state['id'] ||
               (string)($result['amount'] ?? '')!=='100000' || !$parts || ($parts['scheme'] ?? '')!=='https' || empty($parts['host']) ||
               isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment']) ||
               !preg_match('/(^|\.)(uala-checkout\.com|ua\.la|ualabis\.com\.ar)$/D',strtolower($parts['host']))) throw new RuntimeException('Invalid sandbox checkout');
            $state['payment_id']=$id; $state['checkout_url']=$link; $state['payment_state']='pending';
            $store->save($state); return $state;
        });
    }
    public static function refreshed(array $state,array $payment,$merchant) {
        $decision=PanicRecharge::decision($state,$payment,$merchant);
        if($decision==='test_approved') $state['payment_state']='approved';
        elseif($decision==='review') $state['payment_state']='review';
        elseif(in_array($decision,['rejected','cancelled'],true) && $state['payment_state']==='approved') $state['payment_state']='review';
        elseif($decision!=='already_credited' && $state['payment_state']!=='approved') $state['payment_state']=$decision;
        $state['checked_at']=gmdate('c');
        return $state;
    }
    public static function diagnostic(Throwable $exception) {
        if($exception instanceof PanicRechargeApiFailure && preg_match('/^(AUTH|CHECKOUT)_(HTTP_[0-9]{1,3}|NETWORK_[0-9]{1,3}|RESPONSE_SIZE)$/D',$exception->safeCode)) return $exception->safeCode;
        $codes=['HTTPS callback required'=>'CALLBACK_HTTPS','Private pilot directory unavailable'=>'PRIVATE_DIRECTORY',
            'Pilot lock unavailable'=>'PRIVATE_LOCK','Pilot storage unavailable'=>'PRIVATE_WRITE','Pilot commit failed'=>'PRIVATE_COMMIT',
            'Invalid pilot state'=>'PRIVATE_STATE','Invalid sandbox checkout'=>'CHECKOUT_RESPONSE','Sandbox only'=>'TEST_REQUIRED',
            'Private payment settings unavailable'=>'PRIVATE_SETTINGS','Invalid private settings: sales must remain disabled'=>'SETTINGS_DISABLED',
            'Uala credential missing'=>'CREDENTIALS','Payment transport unavailable'=>'PHP_CURL'];
        return $codes[$exception->getMessage()] ?? 'PILOT_CHECK_FAILED';
    }
    public function inspect(PanicUalaBisApi $api,$merchant) {
        return $this->locked(function($state,$store) use ($api,$merchant) {
            if(!$state) throw new RuntimeException('Pilot checkout unavailable');
            if(is_string($state['payment_id'] ?? null)) {
                $state=self::refreshed($state,$api->fetchPayment($state['payment_id']),$merchant);
            } else {
                // Recover an ambiguous creation using authenticated GET only.
                $result=$api->pilotOrders();
                if(!is_array($result['orders'] ?? null)) throw new RuntimeException('Invalid sandbox checkout');
                $matches=[];
                foreach($result['orders'] as $candidate) if(is_array($candidate) && ($candidate['external_reference'] ?? '')===$state['id']) $matches[]=$candidate;
                if(count($matches)>1) { $state['payment_state']='review'; $state['last_error']='MULTIPLE_CHECKOUTS'; }
                elseif(count($matches)===1 && is_string($matches[0]['uuid'] ?? null)) {
                    $state['payment_id']=$matches[0]['uuid'];
                    $payment=$api->fetchPayment($state['payment_id']);
                    $state=self::refreshed($state,$payment,$merchant);
                    $state['last_error']='CHECKOUT_RECOVERED';
                } else {
                    $state['last_error']=!empty($result['has_more_items'])?'RECOVERY_MORE_PAGES':'CHECKOUT_NOT_FOUND';
                }
            }
            $store->save($state); return $state;
        });
    }
    public function refresh(PanicUalaBisApi $api,$merchant,$callbackKey=null) {
        return $this->locked(function($state,$store) use ($api,$merchant,$callbackKey) {
            if(!$state || !is_string($state['payment_id'] ?? null)) throw new RuntimeException('Pilot checkout unavailable');
            if($callbackKey!==null && (!is_string($callbackKey) || !hash_equals($state['callback_token'],$callbackKey))) throw new RuntimeException('Invalid callback');
            $state=self::refreshed($state,$api->fetchPayment($state['payment_id']),$merchant);
            $store->save($state); return $state;
        });
    }
    public function acknowledge(array $receipt) {
        return $this->locked(function($state,$store) use ($receipt) {
            if(!$state || ($receipt['id'] ?? null)!==$state['id'] || ($receipt['payment_id'] ?? null)!==$state['payment_id'] ||
               ($receipt['account'] ?? '')!=='pruebacoin' || ($receipt['coins'] ?? null)!==1000 ||
               !in_array($receipt['state'] ?? '',['credited','reverted'],true)) throw new RuntimeException('Invalid delivery receipt');
            if(($state['delivery_state'] ?? '')==='reverted') return $state;
            // Signed worker reports committed SQL state, including a reversal
            // after an earlier acknowledgment was lost. Never issue a new job here.
            if(!in_array($state['payment_state'],['approved','review'],true) && $state['delivery_state']!=='credited') throw new RuntimeException('Payment not approved');
            $state['delivery_state']=$receipt['state']; $state['delivered_at']=gmdate('c');
            $store->save($state); return $state;
        });
    }
    public static function job(array $state) {
        if($state['payment_state']!=='approved' || ($state['delivery_state'] ?? '')!=='pending' || time()>$state['expires_at']) return null;
        return ['id'=>$state['id'],'payment_id'=>$state['payment_id'],'account'=>'pruebacoin','coins'=>1000,'price_cents'=>100000,'environment'=>'test'];
    }
}

function panicRechargePilotGateway() {
    $settings=panicUalaPrivateSettings();
    if($settings['environment']!=='test') throw new RuntimeException('Sandbox only');
    return [new PanicUalaBisApi($settings['uala_bis'],'test'),$settings['uala_bis']['client_id']];
}
