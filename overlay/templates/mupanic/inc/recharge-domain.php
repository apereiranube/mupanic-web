<?php
if(!defined('access') || !access) die();

/** Payment core. Delivery uses the private signed worker and SQL ledger. */
final class PanicRecharge {
    const PROVIDERS = ['uala_bis', 'mobbex', 'mercadopago'];

    public static function cents($value) {
        // Amounts are stored as integer centavos; API decimals are converted once.
        if(is_float($value)) {
            if(!is_finite($value)) throw new InvalidArgumentException('Invalid amount');
            $value = (string)$value;
        }
        if(!is_string($value) && !is_int($value)) throw new InvalidArgumentException('Invalid amount');
        if(!preg_match('/^(0|[1-9][0-9]{0,8})(?:\.([0-9]{1,2}))?$/D', (string)$value, $match)) {
            throw new InvalidArgumentException('Invalid amount');
        }
        return (int)$match[1]*100 + (int)str_pad($match[2] ?? '', 2, '0');
    }

    public static function package(array $package) {
        if(!preg_match('/^[a-z0-9_-]{1,40}$/D', $package['id'] ?? '')) throw new InvalidArgumentException('Invalid package');
        foreach(['price_cents','coins','bonus'] as $key) {
            if(!isset($package[$key]) || !is_int($package[$key]) || $package[$key]<0 || $package[$key]>100000000) {
                throw new InvalidArgumentException('Invalid package amount');
            }
        }
        if($package['price_cents']<1 || $package['coins']<1 || $package['coins']+$package['bonus']>100000000) {
            throw new InvalidArgumentException('Invalid package amount');
        }
        if(!is_string($package['title'] ?? null) || trim($package['title'])==='' || strlen($package['title'])>120) {
            throw new InvalidArgumentException('Invalid package title');
        }
        return $package;
    }

    public static function cart(array $catalogue,array $quantities) {
        $known=[]; foreach($catalogue as $offer) { $offer=self::package($offer); if(isset($known[$offer['id']])) throw new InvalidArgumentException('Duplicate package'); $known[$offer['id']]=$offer; }
        $lines=[]; $cents=0; $coins=0;
        foreach($quantities as $id=>$quantity) {
            if(!isset($known[$id]) || (!is_string($quantity) && !is_int($quantity)) ||
               !preg_match('/^(0|[1-9][0-9]?)$/D',(string)$quantity)) throw new InvalidArgumentException('Invalid cart quantity');
            $quantity=(int)$quantity; if($quantity===0) continue;
            $offer=$known[$id];
            if($offer['price_cents']!==$offer['coins']*100) throw new InvalidArgumentException('Invalid exchange rate');
            $lines[]=array_merge($offer,['quantity'=>$quantity]);
            $cents+=$offer['price_cents']*$quantity; $coins+=($offer['coins']+$offer['bonus'])*$quantity;
        }
        if(!$lines || $cents>100000000 || $coins>1000000) throw new InvalidArgumentException('Cart empty or outside limits');
        return ['id'=>'cart','title'=>'Recarga de '.number_format($coins,0,',','.').' WCoin C',
            'price_cents'=>$cents,'coins'=>$coins,'bonus'=>0,'lines'=>$lines];
    }

    public static function order($account, array $package, $provider, $live) {
        $package=self::package($package);
        if(!is_string($account) || !preg_match('/^[A-Za-z0-9_]{1,10}$/D', $account)) throw new InvalidArgumentException('Invalid account');
        if(!in_array($provider, self::PROVIDERS, true) || !is_bool($live)) throw new InvalidArgumentException('Invalid provider');
        // Snapshot: subsequent catalogue edits cannot change a purchased package.
        return [
            'id'=>'PANIC-'.bin2hex(random_bytes(16)), 'account'=>$account,
            'package_id'=>$package['id'], 'title'=>$package['title'],
            'price_cents'=>$package['price_cents'], 'coins'=>$package['coins'], 'bonus'=>$package['bonus'],
            'currency'=>'ARS', 'wallet'=>'WCoin C', 'provider'=>$provider, 'live'=>$live,
            'payment_id'=>null, 'payment_state'=>'pending', 'delivery_state'=>'pending',
            'created_at'=>gmdate('c'),
        ];
    }

    public static function decision(array $order, array $payment, $merchant) {
        // Only a server-side provider GET may supply this canonical payment.
        foreach(['provider','id','reference','merchant','currency','amount_cents','live','state'] as $key) {
            if(!array_key_exists($key, $payment)) throw new InvalidArgumentException('Incomplete payment');
        }
        if(!is_string($payment['id']) || !preg_match('/^[A-Za-z0-9:_-]{1,120}$/D', $payment['id'])) throw new InvalidArgumentException('Invalid payment id');
        if(!is_string($merchant) || $merchant==='') throw new InvalidArgumentException('Merchant not configured');
        if($payment['provider']!==$order['provider'] || $payment['reference']!==$order['id'] ||
           $payment['merchant']!==$merchant || $payment['currency']!==$order['currency'] ||
           !is_int($payment['amount_cents']) || $payment['amount_cents']!==$order['price_cents'] ||
           !is_bool($payment['live']) || $payment['live']!==$order['live']) return 'review';
        if($order['payment_id']!==null && $order['payment_id']!==$payment['id']) return 'review';
        if(in_array($payment['state'], ['refunded','chargeback'], true)) return 'review';
        if($order['delivery_state']==='credited') return 'already_credited';
        if($order['payment_state']==='review') return 'review';
        if($payment['state']==='approved') return $order['live'] ? 'ready' : 'test_approved';
        if(in_array($payment['state'], ['pending','rejected','cancelled'], true)) return $payment['state'];
        return 'review';
    }
}

interface PanicRechargeGateway {
    public function fetchPayment($paymentId);
}

interface PanicRechargeLedger {
    public function findOrder($reference);
    /**
     * MUST lock and re-read the order, enforce UNIQUE(provider,payment_id),
     * re-run PanicRecharge::decision(), and write payment state atomically.
     * When ready: store an approved delivery job, not a blind SQL increment.
     * Never regress a credited order or erase a review hold on a stale event.
     */
    public function reconcileAtomically($orderId, array $payment, $merchant);
}

final class PanicRechargeReconciler {
    private $gateway;
    private $ledger;
    private $merchant;
    public function __construct(PanicRechargeGateway $gateway, PanicRechargeLedger $ledger, $merchant) {
        $this->gateway=$gateway; $this->ledger=$ledger; $this->merchant=$merchant;
    }
    public function reconcile($paymentId) {
        // Browser returns and webhook bodies are hints, never payment evidence.
        $payment=$this->gateway->fetchPayment($paymentId);
        $order=$this->ledger->findOrder($payment['reference'] ?? '');
        if(!$order) return 'unknown_order';
        PanicRecharge::decision($order,$payment,$this->merchant);
        // The durable implementation must repeat validation after taking locks.
        return $this->ledger->reconcileAtomically($order['id'],$payment,$this->merchant);
    }
}
