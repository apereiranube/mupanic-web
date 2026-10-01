<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/recharge-providers.php';

/** Uala Bis API v2. Server-side only; no public payment endpoint yet. */
final class PanicUalaBisApi extends PanicRechargeApi {
    private $environment;
    private $token=null;
    private $expiresAt=0;
    public function __construct(array $credentials, $environment, callable $transport=null) {
        if(!in_array($environment,['test','production'],true)) throw new InvalidArgumentException('Invalid Uala environment');
        parent::__construct($credentials,$transport); $this->environment=$environment;
    }
    private function base($service) {
        return 'https://'.$service.($this->environment==='test'?'.stage':'').'.developers.ar.ua.la/v2/api';
    }
    public function authenticate() {
        if($this->token!==null && $this->expiresAt>time()+60) return;
        $result=$this->request('POST',$this->base('auth').'/auth/token',[],[
            'username'=>$this->credential('username'),'client_id'=>$this->credential('client_id'),
            'client_secret_id'=>$this->credential('client_secret_id'),'grant_type'=>'client_credentials']);
        $token=$result['access_token'] ?? null; $expiry=$result['expires_in'] ?? null;
        if(!is_string($token) || $token==='' || strlen($token)>16384 || preg_match('/[\r\n]/',$token) ||
           !is_int($expiry) || $expiry<1 || ($result['token_type'] ?? '')!=='Bearer') {
            throw new RuntimeException('Invalid Uala authentication response');
        }
        $this->token=$token; $this->expiresAt=time()+min($expiry,86400);
    }
    private function headers() {
        $this->authenticate(); return ['Authorization: Bearer '.$this->token];
    }
    public static function checkoutPayload(array $order, $returnUrl, $webhookUrl) {
        self::checkOrder($order,'uala_bis');
        if($order['price_cents']<2500 || $order['price_cents']>999999900) throw new InvalidArgumentException('Amount outside Uala limits');
        return ['amount'=>(string)$order['price_cents'],'description'=>'MU PANIC · '.$order['title'],
            'callback_fail'=>self::callback($returnUrl),'callback_success'=>self::callback($returnUrl),
            'notification_url'=>self::callback($webhookUrl),'external_reference'=>$order['id']];
    }
    public function createCheckout(array $order, $returnUrl, $webhookUrl) {
        if(($order['live'] ?? null)!==($this->environment==='production')) throw new InvalidArgumentException('Uala environment mismatch');
        $payload=self::checkoutPayload($order,$returnUrl,$webhookUrl);
        return $this->request('POST',$this->base('checkout').'/checkout',$this->headers(),$payload);
    }
    public function pilotOrders($cursor=null) {
        if($this->environment!=='test') throw new RuntimeException('Sandbox only');
        $query=['limit'=>20];
        if($cursor!==null) {
            if(!is_string($cursor) || $cursor==='' || strlen($cursor)>4096) throw new RuntimeException('Invalid recovery cursor');
            $query['last_search_key']=$cursor;
        }
        return $this->request('GET',$this->base('checkout').'/orders?'.http_build_query($query,'','&',PHP_QUERY_RFC3986),$this->headers());
    }
    public function fetchPayment($paymentId) {
        $result=$this->request('GET',$this->base('checkout').'/orders/'.self::id($paymentId),$this->headers());
        $amount=$result['amount'] ?? null;
        if(is_string($amount) && preg_match('/^[0-9]{1,9}$/D',$amount)) $amount=(int)$amount;
        if(($result['uuid'] ?? null)!==$paymentId || !is_int($amount) || $amount<0 || $amount>999999900 ||
           !is_string($result['external_reference'] ?? null)) throw new RuntimeException('Invalid Uala order response');
        $states=['PENDING'=>'pending','PROCESSED'=>'pending','PROCCESED'=>'pending','APPROVED'=>'approved','REJECTED'=>'rejected','REFUNDED'=>'refunded'];
        // GET is authorized for the configured merchant. The response omits
        // merchant/currency/environment; these derive from authenticated scope
        // and fixed Argentina v2 hosts, never from webhook/browser parameters.
        return ['provider'=>'uala_bis','id'=>$paymentId,'reference'=>$result['external_reference'],
            'merchant'=>$this->credential('client_id'),'currency'=>'ARS','amount_cents'=>$amount,
            'live'=>$this->environment==='production','state'=>$states[$result['status'] ?? ''] ?? 'review'];
    }
}

function panicUalaPrivateSettings($path='/home/mupanic/payments-private/settings.json') {
    $real=realpath($path); $public=realpath('/home/mupanic/public_html');
    if(!$real || !is_file($real) || ($public && ($real===$public || strpos($real,$public.DIRECTORY_SEPARATOR)===0)) || filesize($real)>65536) {
        throw new RuntimeException('Private payment settings unavailable');
    }
    $raw=file_get_contents($real);
    if($raw===false) throw new RuntimeException('Private payment settings unavailable');
    $settings=json_decode($raw,true,16,JSON_THROW_ON_ERROR);
    if(!is_array($settings) || !in_array($settings['environment'] ?? '',['test','production'],true) ||
       ($settings['sales_enabled'] ?? null)!==false || !is_array($settings['uala_bis'] ?? null)) {
        throw new RuntimeException('Invalid private settings: sales must remain disabled');
    }
    foreach(['username','client_id','client_secret_id'] as $key) {
        $value=$settings['uala_bis'][$key] ?? null;
        if(!is_string($value) || trim($value)==='' || strpos($value,'REEMPLAZAR_')===0 || preg_match('/[\r\n]/',$value)) throw new RuntimeException('Uala credential missing');
    }
    return $settings;
}
