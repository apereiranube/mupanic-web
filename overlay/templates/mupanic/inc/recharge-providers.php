<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/recharge-domain.php';

final class PanicRechargeApiFailure extends RuntimeException {
    public $safeCode;
    public function __construct($safeCode) { parent::__construct('Payment API unavailable'); $this->safeCode=$safeCode; }
}

/** Server-side adapters. Instantiated only by future private payment bootstrap. */
abstract class PanicRechargeApi implements PanicRechargeGateway {
    protected $credentials;
    private $transport;
    public function __construct(array $credentials, callable $transport=null) {
        $this->credentials=$credentials; $this->transport=$transport;
    }
    protected function credential($name) {
        $value=$this->credentials[$name] ?? '';
        if(!is_string($value) || $value==='' || preg_match('/[\r\n]/', $value)) throw new RuntimeException('Payment credentials unavailable');
        return $value;
    }
    protected function request($method, $url, array $headers, array $body=null) {
        if($this->transport) return ($this->transport)($method,$url,$headers,$body);
        if(!function_exists('curl_init')) throw new RuntimeException('Payment transport unavailable');
        $curl=curl_init($url);
        curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>$method,
            CURLOPT_HTTPHEADER=>array_merge(['Content-Type: application/json','Accept: application/json'],$headers),
            CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>12,CURLOPT_FOLLOWLOCATION=>false,
            CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
        if($body!==null) curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($body,JSON_THROW_ON_ERROR));
        $raw=curl_exec($curl); $status=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE); $curlError=curl_errno($curl); curl_close($curl);
        // Never expose provider response bodies or headers to the browser/logs.
        $service=strpos($url,'/auth/token')!==false?'AUTH':'CHECKOUT';
        if($raw===false) throw new PanicRechargeApiFailure($service.'_NETWORK_'.(int)$curlError);
        if($status<200 || $status>=300) throw new PanicRechargeApiFailure($service.'_HTTP_'.$status);
        if(strlen($raw)>1048576) throw new PanicRechargeApiFailure($service.'_RESPONSE_SIZE');
        $result=json_decode($raw,true,32,JSON_THROW_ON_ERROR);
        if(!is_array($result)) throw new RuntimeException('Invalid payment response');
        return $result;
    }
    protected static function id($id) {
        if(!is_string($id) || !preg_match('/^[A-Za-z0-9:_-]{1,120}$/D',$id)) throw new InvalidArgumentException('Invalid payment id');
        return rawurlencode($id);
    }
    protected static function callback($url) {
        $parts=parse_url($url);
        if(!$parts || ($parts['scheme'] ?? '')!=='https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) throw new InvalidArgumentException('HTTPS callback required');
        return $url;
    }
    protected static function checkOrder(array $order, $provider) {
        if(($order['provider'] ?? '')!==$provider || !is_bool($order['live'] ?? null) ||
           !is_int($order['price_cents'] ?? null) || $order['price_cents']<1 ||
           ($order['currency'] ?? '')!=='ARS' || !preg_match('/^PANIC-[a-f0-9]{32}$/D',$order['id'] ?? '')) {
            throw new InvalidArgumentException('Invalid order');
        }
    }
}

final class PanicMobbexApi extends PanicRechargeApi {
    private function headers() { return ['x-api-key: '.$this->credential('api_key'),'x-access-token: '.$this->credential('access_token')]; }
    public static function checkoutPayload(array $order, array $customer, $returnUrl, $webhookUrl) {
        self::checkOrder($order,'mobbex');
        if(!filter_var($customer['email'] ?? '',FILTER_VALIDATE_EMAIL) || empty($customer['name']) ||
           !preg_match('/^[0-9]{7,11}$/D',$customer['identification'] ?? '')) throw new InvalidArgumentException('Customer information required');
        return ['total'=>$order['price_cents']/100,'currency'=>'ARS','reference'=>$order['id'],
            'description'=>'MU PANIC · '.$order['title'],'test'=>!$order['live'],
            'return_url'=>self::callback($returnUrl),'webhook'=>self::callback($webhookUrl),
            'customer'=>['name'=>$customer['name'],'email'=>$customer['email'],'identification'=>$customer['identification']]];
    }
    public function createCheckout(array $payload) {
        return $this->request('POST','https://api.mobbex.com/p/checkout',$this->headers(),$payload);
    }
    public function fetchPayment($paymentId) {
        $result=$this->request('GET','https://api.mobbex.com/p/operations/'.self::id($paymentId),$this->headers());
        if(($result['result'] ?? null)!==true) throw new RuntimeException('Payment lookup failed');
        $transaction=$result['data']['transaction'] ?? [];
        $payment=$transaction['payment'] ?? [];
        // Require merchant identity in the authenticated response, not the webhook.
        $merchant=$transaction['entity']['uid'] ?? '';
        $currency=$payment['currency']['code'] ?? '';
        if(($payment['id'] ?? null)!==$paymentId || $merchant==='' || !in_array($currency,['ARS','TEST'],true)) {
            throw new RuntimeException('Mobbex response requires integration review');
        }
        // Fail closed for held, authorized-only, refunded and unfamiliar states.
        $states=['1'=>'pending','2'=>'pending','200'=>'approved','201'=>'pending','400'=>'rejected','401'=>'cancelled','402'=>'cancelled','601'=>'cancelled','602'=>'refunded','603'=>'chargeback'];
        return ['provider'=>'mobbex','id'=>$paymentId,'reference'=>$payment['reference'] ?? '',
            'merchant'=>(string)$merchant,'currency'=>$currency==='TEST'?'ARS':$currency,
            'amount_cents'=>PanicRecharge::cents($payment['requestedTotal'] ?? $payment['total'] ?? ''),
            'live'=>$currency!=='TEST','state'=>$states[(string)($payment['status']['code'] ?? '')] ?? 'review'];
    }
}

final class PanicMercadoPagoApi extends PanicRechargeApi {
    private function headers() { return ['Authorization: Bearer '.$this->credential('access_token')]; }
    public static function checkoutPayload(array $order, $returnUrl, $webhookUrl) {
        self::checkOrder($order,'mercadopago'); $returnUrl=self::callback($returnUrl);
        return ['external_reference'=>$order['id'],
            'items'=>[['id'=>$order['package_id'],'title'=>'MU PANIC · '.$order['title'],'quantity'=>1,'currency_id'=>'ARS','unit_price'=>$order['price_cents']/100]],
            'back_urls'=>['success'=>$returnUrl,'pending'=>$returnUrl,'failure'=>$returnUrl],
            'notification_url'=>self::callback($webhookUrl)];
    }
    public function createCheckout(array $payload) {
        return $this->request('POST','https://api.mercadopago.com/checkout/preferences',$this->headers(),$payload);
    }
    public function fetchPayment($paymentId) {
        $payment=$this->request('GET','https://api.mercadopago.com/v1/payments/'.self::id($paymentId),$this->headers());
        if((string)($payment['id'] ?? '')!==$paymentId || !isset($payment['collector_id']) || !is_bool($payment['live_mode'] ?? null)) throw new RuntimeException('Invalid payment response');
        $state=$payment['status'] ?? 'review';
        if(($payment['transaction_amount_refunded'] ?? 0)>0 || $state==='charged_back') $state='review';
        $states=['approved'=>'approved','pending'=>'pending','in_process'=>'pending','rejected'=>'rejected','cancelled'=>'cancelled','refunded'=>'refunded','charged_back'=>'chargeback'];
        return ['provider'=>'mercadopago','id'=>$paymentId,'reference'=>$payment['external_reference'] ?? '',
            'merchant'=>(string)$payment['collector_id'],'currency'=>$payment['currency_id'] ?? '',
            'amount_cents'=>PanicRecharge::cents($payment['transaction_amount'] ?? ''),'live'=>$payment['live_mode'],
            'state'=>$states[$state] ?? 'review'];
    }
}
