<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/recharge-uala.php';
require_once __DIR__.'/recharge-management.php';

/** Private durable order ledger. API calls never hold its global filesystem lock. */
final class PanicRechargeOrders {
    private $directory;
    public function __construct($directory='/home/mupanic/payments-private') {
        $real=realpath($directory); $public=realpath('/home/mupanic/public_html');
        if(!$real || !is_dir($real) || is_link($directory) || ($public && ($real===$public || strpos($real,$public.DIRECTORY_SEPARATOR)===0))) throw new RuntimeException('Private ledger unavailable');
        $this->directory=$real;
    }
    public function token() {
        $path=$this->directory.'/recharge-worker-token';
        if(is_link($path) || !is_file($path) || filesize($path)>256) throw new RuntimeException('Worker not installed');
        $token=trim(file_get_contents($path));
        if(!preg_match('/^[a-f0-9]{64}$/D',$token)) throw new RuntimeException('Worker token invalid');
        return $token;
    }
    private function locked(callable $operation) {
        $path=$this->directory.'/recharge-orders.lock';
        if(is_link($path)) throw new RuntimeException('Invalid ledger lock');
        $handle=fopen($path,'c'); if(!$handle) throw new RuntimeException('Ledger lock unavailable'); chmod($path,0600);
        try {
            if(!flock($handle,LOCK_EX)) throw new RuntimeException('Ledger lock unavailable');
            $path=$this->directory.'/recharge-orders.json';
            if(is_link($path)) throw new RuntimeException('Invalid ledger');
            clearstatcache(true,$path);
            if(is_file($path) && filesize($path)>8388608) throw new RuntimeException('Ledger capacity exceeded');
            $orders=is_file($path)?json_decode(file_get_contents($path),true,24,JSON_THROW_ON_ERROR):[];
            if(!is_array($orders) || count($orders)>5000) throw new RuntimeException('Invalid ledger');
            foreach($orders as $id=>$order) {
                if(!is_array($order) || ($order['id'] ?? '')!==$id || !preg_match('/^PANIC-[a-f0-9]{32}$/D',$id) ||
                   ($order['provider'] ?? '')!=='uala_bis' || !is_bool($order['live'] ?? null) ||
                   !is_int($order['coins'] ?? null) || $order['coins']<1 || $order['coins']>1000000 ||
                   !is_int($order['price_cents'] ?? null) || $order['price_cents']<100 || $order['price_cents']>100000000 || ($order['bonus'] ?? null)!==0 ||
                   !preg_match('/^[A-Za-z0-9_]{1,10}$/D',$order['account'] ?? '') ||
                   ($order['currency'] ?? '')!=='ARS' || ($order['wallet'] ?? '')!=='WCoin C' ||
                   !in_array($order['payment_state'] ?? '',['creating','pending','approved','rejected','cancelled','review'],true) ||
                   !in_array($order['delivery_state'] ?? '',['pending','credited'],true) ||
                   !is_string($order['merchant_id'] ?? null) || $order['merchant_id']==='' ||
                   !preg_match('/^[a-f0-9]{64}$/D',$order['request_key'] ?? '') ||
                   !preg_match('/^[a-f0-9]{64}$/D',$order['callback_token'] ?? '') ||
                   !in_array($order['amount_unit'] ?? '',['ARS','centavos'],true)) throw new RuntimeException('Invalid ledger order');
            }
            foreach($orders as $order) {
                try { $snapshot=PanicRecharge::cart($order['lines'] ?? [],array_column($order['lines'] ?? [],'quantity','id')); }
                catch(Throwable $exception) { throw new RuntimeException('Invalid order snapshot'); }
                if($snapshot['coins']!==$order['coins'] || $snapshot['price_cents']!==$order['price_cents']) throw new RuntimeException('Invalid order snapshot totals');
            }
            return $operation($orders,$this);
        } finally { flock($handle,LOCK_UN); fclose($handle); }
    }
    private function save(array $orders) {
        $data=json_encode($orders,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        if(strlen($data)>8388608) throw new RuntimeException('Ledger capacity exceeded');
        $path=tempnam($this->directory,'orders-'); if(!$path) throw new RuntimeException('Ledger write failed');
        try {
            chmod($path,0600); $handle=fopen($path,'wb');
            if(!$handle) throw new RuntimeException('Ledger write failed');
            try { if(fwrite($handle,$data)!==strlen($data) || !fflush($handle) || (function_exists('fsync') && !fsync($handle))) throw new RuntimeException('Ledger write failed'); }
            finally { fclose($handle); }
            if(!rename($path,$this->directory.'/recharge-orders.json')) throw new RuntimeException('Ledger commit failed');
        } finally { if(is_file($path)) unlink($path); }
    }
    public function get($id) { return $this->locked(function($orders) use ($id) { return $orders[$id] ?? null; }); }
    public function owned($id,$account) {
        $order=$this->get($id);
        if(!$order || !hash_equals($order['account'],$account)) throw new RuntimeException('Order unavailable');
        return $order;
    }
    public static function publicOrder(array $order) {
        return array_intersect_key($order,array_flip(['id','account','title','price_cents','coins','lines','live','payment_state','delivery_state','created_at','credited_at','before_coin','after_coin','checkout_url']));
    }
    public function history($account,$page=1,$admin=false) {
        return $this->locked(function($orders) use ($account,$page,$admin) {
            $rows=array_values(array_filter($orders,function($order) use ($account,$admin) { return $admin || $order['account']===$account; }));
            usort($rows,function($a,$b) { return strcmp($b['created_at'],$a['created_at']); });
            return ['total'=>count($rows),'orders'=>array_map([self::class,'publicOrder'],array_slice($rows,(max(1,$page)-1)*10,10))];
        });
    }
    public function administration(array $filter,$page=1) {
        if(!panicRechargeAdminAllowed()) throw new RuntimeException('Forbidden');
        return $this->locked(function($orders) use ($filter,$page) {
            $query=trim((string)($filter['query'] ?? '')); $state=$filter['state'] ?? ''; $environment=$filter['environment'] ?? '';
            $rows=[]; $summary=['total'=>0,'approved_cents'=>0,'delivered_coins'=>0,'pending'=>0,'review'=>0];
            foreach($orders as $order) {
                [$tone]=panicRechargeStatus($order);
                if(($environment==='test' && $order['live']) || ($environment==='production' && !$order['live'])) continue;
                if($query!=='' && stripos($order['account'],$query)===false && stripos($order['id'],$query)===false && stripos((string)$order['payment_id'],$query)===false) continue;
                if($state!=='' && $tone!==$state) continue;
                $summary['total']++;
                if($order['payment_state']==='approved') $summary['approved_cents']+=$order['price_cents'];
                if($order['delivery_state']==='credited') $summary['delivered_coins']+=$order['coins'];
                elseif($order['payment_state']==='approved') $summary['pending']++;
                if(in_array($order['payment_state'],['review','creating'],true) || isset($order['check_error'])) $summary['review']++;
                $public=self::publicOrder($order);
                $public+=['payment_id'=>$order['payment_id'],'checked_at'=>$order['checked_at'] ?? null,'error'=>$order['error'] ?? null,'check_error'=>$order['check_error'] ?? null];
                $rows[]=$public;
            }
            usort($rows,function($a,$b) { return strcmp($b['created_at'],$a['created_at']); });
            return ['summary'=>$summary,'total'=>count($rows),'orders'=>array_slice($rows,(max(1,$page)-1)*20,20)];
        });
    }
    public function begin($account,array $cart,$nonce,$live,PanicUalaBisApi $api,$baseUrl,$amountUnit=null) {
        $this->token();
        if(!is_string($nonce) || !preg_match('/^[a-f0-9]{32}$/D',$nonce)) throw new InvalidArgumentException('Invalid checkout nonce');
        $fingerprint=hash('sha256',json_encode($cart,JSON_THROW_ON_ERROR));
        $key=hash('sha256',$account."\n".$nonce); $created=false;
        $order=$this->locked(function($orders,$store) use ($account,$cart,$key,$fingerprint,$live,$amountUnit,$api,&$created) {
            foreach($orders as $order) if($order['request_key']===$key) {
                if($order['cart_hash']!==$fingerprint || $order['live']!==$live) throw new RuntimeException('Checkout request changed');
                return $order;
            }
            if(count($orders)>=5000) throw new RuntimeException('Ledger capacity exceeded');
            $open=0; foreach($orders as $order) if($order['account']===$account && $order['delivery_state']==='pending' && in_array($order['payment_state'],['creating','pending','approved'],true)) $open++;
            if($open>=5) throw new RuntimeException('Too many open orders');
            $order=PanicRecharge::order($account,$cart,'uala_bis',$live);
            $order['merchant_id']=$api->merchantId();
            $order['amount_unit']=$amountUnit ?? ($live?'centavos':'ARS');
            $order['lines']=$cart['lines']; $order['request_key']=$key; $order['cart_hash']=$fingerprint;
            $order['callback_token']=bin2hex(random_bytes(32)); $order['payment_state']='creating';
            $order['next_check']=0; $order['version']=1;
            $orders[$order['id']]=$order; $store->save($orders); $created=true; return $order;
        });
        if(!$created) return $order;
        $hook=rtrim($baseUrl,'/').'/templates/mupanic/api/recharge-hook.php?id='.$order['id'].'&key='.$order['callback_token'];
        try {
            $result=$api->createCheckout($order,rtrim($baseUrl,'/').'/usercp/recharge/#recharge-history',$hook);
            return $this->locked(function($orders,$store) use ($order,$result) {
                $state=$orders[$order['id']];
                // Preserve only useful private diagnostics, never full customer/card response.
                $state['checkout_response']=['uuid'=>is_string($result['uuid'] ?? null)?substr($result['uuid'],0,120):null,
                    'amount'=>is_scalar($result['amount'] ?? null)?substr((string)$result['amount'],0,32):null,
                    'reference'=>is_string($result['external_reference'] ?? null)?substr($result['external_reference'],0,120):null];
                $id=$result['uuid'] ?? null;
                $amount=($state['amount_unit'] ?? ($state['live']?'centavos':'ARS'))==='ARS'?PanicUalaBisApi::sandboxCents($result['amount'] ?? null):$result['amount'] ?? null;
                if(is_string($amount) && preg_match('/^[0-9]{1,9}$/D',$amount)) $amount=(int)$amount;
                $link=PanicUalaBisApi::checkoutLink($result['links']['checkout_link'] ?? null,!$state['live']);
                if(!is_string($id) || !preg_match('/^[A-Za-z0-9_-]{1,120}$/D',$id) || ($result['external_reference'] ?? '')!==$state['id'] || $amount!==$state['price_cents']) {
                    $state['payment_state']='review'; $state['error']='CHECKOUT_RESPONSE';
                } else {
                    foreach($orders as $other) if($other['id']!==$state['id'] && $other['live']===$state['live'] && $other['payment_id']===$id) throw new RuntimeException('Duplicate provider payment');
                    $state['payment_id']=$id; $state['payment_state']='pending';
                    if($link!==null) $state['checkout_url']=$link; else $state['error']='CHECKOUT_LINK_UNAVAILABLE';
                }
                $orders[$state['id']]=$state; $store->save($orders); return $state;
            });
        } catch(Throwable $exception) {
            $this->locked(function($orders,$store) use ($order) { $orders[$order['id']]['error']='CHECKOUT_UNCONFIRMED'; $store->save($orders); });
            // Reservation survives timeouts. Never blindly retry checkout creation.
            throw new RuntimeException('Checkout unconfirmed; existing order retained');
        }
    }
    public function refresh($id,PanicUalaBisApi $api,$merchant) {
        $order=$this->get($id); if(!$order || !is_string($order['payment_id'])) throw new RuntimeException('Order cannot be checked yet');
        if(($order['merchant_id'] ?? null)!==$merchant || $api->merchantId()!==$merchant) throw new RuntimeException('Merchant changed');
        $payment=$api->fetchPayment($order['payment_id']);
        return $this->locked(function($orders,$store) use ($id,$payment,$merchant) {
            $state=$orders[$id];
            if($state['merchant_id']!==$merchant) throw new RuntimeException('Merchant changed');
            $decision=PanicRecharge::decision($state,$payment,$merchant);
            if(in_array($decision,['ready','test_approved'],true)) $state['payment_state']='approved';
            elseif($decision==='review') $state['payment_state']='review';
            elseif($decision!=='already_credited') {
                // Preserve approval on delayed pending; hold contradictory rejection.
                if($state['payment_state']==='approved' && $decision!=='pending') $state['payment_state']='review';
                elseif($state['payment_state']!=='approved') $state['payment_state']=$decision;
            }
            if($state['payment_state']==='pending' && empty($state['checkout_url']) && !empty($payment['checkout_url'])) $state['checkout_url']=$payment['checkout_url'];
            $state['checked_at']=gmdate('c'); $state['next_check']=time()+30; unset($state['check_error']);
            $orders[$id]=$state; $store->save($orders); return $state;
        });
    }
    public function poll(PanicUalaBisApi $api,$merchant,$live,$supportsBonuses=false) {
        $candidates=$this->locked(function($orders) use ($live,$supportsBonuses) {
            $rows=array_filter($orders,function($order) use ($live,$supportsBonuses) {
                return ($supportsBonuses || $order['price_cents']===$order['coins']*100) && $order['live']===$live &&
                    $order['delivery_state']==='pending' && in_array($order['payment_state'],['pending','approved'],true) &&
                    is_string($order['payment_id']) && ($order['next_check'] ?? 0)<=time();
            });
            uasort($rows,function($a,$b) { return ($a['next_check'] ?? 0)<=>($b['next_check'] ?? 0); });
            return array_slice($rows,0,3,true);
        });
        $jobs=[]; $errors=0;
        foreach($candidates as $id=>$candidate) {
            try {
                $state=$this->refresh($id,$api,$merchant);
                if($state['payment_state']==='approved' && $state['delivery_state']==='pending') {
                    $jobs[]=['id'=>$id,'payment_id'=>$state['payment_id'],'provider'=>'uala_bis','account'=>$state['account'],
                        'coins'=>$state['coins'],'price_cents'=>$state['price_cents'],'environment'=>$live?'production':'test'];
                }
            } catch(Throwable $exception) {
                $errors++;
                $this->locked(function($orders,$store) use ($id) { $orders[$id]['check_error']='PAYMENT_CHECK_FAILED'; $orders[$id]['next_check']=time()+60; $store->save($orders); });
            }
        }
        return ['jobs'=>$jobs,'check_errors'=>$errors];
    }
    public function acknowledge(array $receipt) {
        return $this->locked(function($orders,$store) use ($receipt) {
            $id=$receipt['id'] ?? ''; $state=$orders[$id] ?? null;
            if(!$state || !is_string($state['payment_id']) || ($receipt['payment_id'] ?? null)!==$state['payment_id'] || ($receipt['account'] ?? null)!==$state['account'] ||
               ($receipt['coins'] ?? null)!==$state['coins'] || ($receipt['environment'] ?? null)!==($state['live']?'production':'test') ||
               ($receipt['state'] ?? '')!=='credited' || !is_int($receipt['before_coin'] ?? null) || !is_int($receipt['after_coin'] ?? null) ||
               $receipt['before_coin']<0 || $receipt['after_coin']>2147483647 || $receipt['after_coin']-$receipt['before_coin']!==$state['coins']) throw new RuntimeException('Receipt mismatch');
            // A committed SQL receipt is durable even if a refund/review arrived meanwhile.
            if($state['delivery_state']!=='credited') {
                $state['delivery_state']='credited'; $state['credited_at']=gmdate('c');
                $state['before_coin']=$receipt['before_coin']; $state['after_coin']=$receipt['after_coin'];
                $orders[$id]=$state; $store->save($orders);
            }
            return ['state'=>'credited','id'=>$id];
        });
    }
}

function panicRechargeShopGateway() {
    $settings=panicUalaPrivateSettings('/home/mupanic/payments-private/settings.json',true);
    $live=$settings['environment']==='production'; $options=$settings['recharges'] ?? [];
    $unit=$live?($options['amount_unit'] ?? null):'ARS';
    if($live && (($options['production_verified'] ?? false)!==true || !in_array($unit,['ARS','centavos'],true))) throw new RuntimeException('Production not verified');
    return [new PanicUalaBisApi($settings['uala_bis'],$settings['environment'],null,$unit),$settings['uala_bis']['client_id'],$settings];
}
function panicRechargeCanBuy(array $settings,$account,$workerReady) {
    if(!$workerReady) return false;
    if($settings['environment']==='test') return true;
    return $settings['environment']==='production' && ($settings['sales_enabled'] ?? false)===true &&
        ($settings['recharges']['production_verified'] ?? false)===true && in_array($settings['recharges']['amount_unit'] ?? null,['ARS','centavos'],true);
}

function panicRechargeStatus(array $order) {
    if($order['payment_state']==='review') return ['review','En revisión','El equipo necesita revisar esta compra.'];
    if($order['delivery_state']==='credited') return ['credited','Eryns acreditados','Ya podés usar tus Eryns en el juego.'];
    if($order['payment_state']==='approved') return ['approved','Pago aprobado · entrega pendiente','Desconectate del juego para recibir tus Eryns. La entrega se reintenta automáticamente.'];
    if($order['payment_state']==='creating') return ['creating','Compra pendiente de revisión','Conservá el número de compra y consultá al equipo antes de iniciar otra.'];
    if(in_array($order['payment_state'],['rejected','cancelled'],true)) return ['rejected','Pago no aprobado','Esta compra no acreditó Eryns.'];
    return ['pending','Esperando el pago','Completá el pago en Ualá.'];
}

function panicRechargeWorkerRequest($body,$stamp,$signature,$token,$now) {
    if(!is_string($body) || strlen($body)>8192 || !is_string($stamp) || !preg_match('/^[0-9]{10}$/D',$stamp) ||
        abs($now-(int)$stamp)>300 || !is_string($signature) || !hash_equals(hash_hmac('sha256',$stamp."\n".$body,$token),$signature)) return null;
    try { $request=json_decode($body,true,8,JSON_THROW_ON_ERROR); }
    catch(Throwable $exception) { return null; }
    if(!is_array($request) || !is_string($request['nonce'] ?? null) || !preg_match('/^[a-f0-9]{32}$/D',$request['nonce'])) return null;
    return $request;
}
