<?php
// No network, SQL connection, account mutations or real credits.
define('access',true);
require __DIR__.'/../overlay/templates/mupanic/inc/recharge-providers.php';
function expect($value,$message) { if(!$value) throw new RuntimeException($message); }
function rejects(callable $action) {
    try { $action(); } catch(InvalidArgumentException $e) { return; }
    throw new RuntimeException('Expected validation rejection');
}
$package=['id'=>'test','title'=>'Fixture only','price_cents'=>12345,'coins'=>1000,'bonus'=>100];
$order=PanicRecharge::order('TestAcct',$package,'mobbex',true);
expect(PanicRecharge::cents('123.45')===12345,'Decimal amount');
expect(PanicRecharge::cents(123.45)===12345,'JSON numeric amount');
expect(PanicRecharge::cents('0.1')===10,'Centavo padding');
foreach(['-1','1.001','1e3','NaN',true,[],null] as $bad) rejects(function() use ($bad) { PanicRecharge::cents($bad); });
foreach(['price_cents'=>0,'coins'=>0,'bonus'=>-1,'title'=>'','id'=>'../foo'] as $key=>$bad) {
    $p=$package; $p[$key]=$bad; rejects(function() use ($p) { PanicRecharge::package($p); });
}
$package['coins']=2000; expect($order['coins']===1000,'Snapshot immutable after catalogue edit');
expect($order['id']!==PanicRecharge::order('TestAcct',$package,'mobbex',true)['id'],'Unique references');
rejects(function() use ($package) { PanicRecharge::order('bad<script>',$package,'mobbex',true); });
$payment=['provider'=>'mobbex','id'=>'PAY-123','reference'=>$order['id'],'merchant'=>'SHOP-1','currency'=>'ARS','amount_cents'=>12345,'live'=>true,'state'=>'approved'];
expect(PanicRecharge::decision($order,$payment,'SHOP-1')==='ready','Verified approval');
foreach(['merchant'=>'SHOP-2','provider'=>'mercadopago','reference'=>'other','currency'=>'USD','amount_cents'=>12346,'live'=>false,'state'=>'authorized'] as $key=>$bad) {
    $p=$payment; $p[$key]=$bad; expect(PanicRecharge::decision($order,$p,'SHOP-1')==='review','Reject mismatch '.$key);
}
foreach(['pending','rejected','cancelled'] as $state) {
    $p=$payment; $p['state']=$state; expect(PanicRecharge::decision($order,$p,'SHOP-1')===$state,'Non-approved '.$state);
}
$testOrder=$order; $testOrder['live']=false; $testPayment=$payment; $testPayment['live']=false;
expect(PanicRecharge::decision($testOrder,$testPayment,'SHOP-1')==='test_approved','Sandbox cannot deliver real coins');
$delivered=$order; $delivered['payment_id']='PAY-123'; $delivered['delivery_state']='credited';
expect(PanicRecharge::decision($delivered,$payment,'SHOP-1')==='already_credited','Duplicate event');
$stale=$payment; $stale['state']='pending'; expect(PanicRecharge::decision($delivered,$stale,'SHOP-1')==='already_credited','No stale-event regression');
$second=$payment; $second['id']='PAY-SECOND'; expect(PanicRecharge::decision($delivered,$second,'SHOP-1')==='review','Different second payment needs review');
foreach(['refunded','chargeback'] as $state) {
    $p=$payment; $p['state']=$state; expect(PanicRecharge::decision($delivered,$p,'SHOP-1')==='review','After-delivery dispute');
}
$hold=$order; $hold['payment_state']='review'; expect(PanicRecharge::decision($hold,$payment,'SHOP-1')==='review','Review hold persists');
$customer=['email'=>'fixture@example.test','name'=>'Fixture','identification'=>'12345678'];
$payload=PanicMobbexApi::checkoutPayload($order,$customer,'https://beta.mupanic.com.ar/usercp/recharge/','https://beta.mupanic.com.ar/hook');
expect($payload['reference']===$order['id'] && $payload['total']===123.45 && $payload['test']===false,'Mobbex checkout snapshot');
rejects(function() use ($order,$customer) { PanicMobbexApi::checkoutPayload($order,$customer,'http://example.test','https://example.test'); });
$seen=[];
$mobbex=new PanicMobbexApi(['api_key'=>'test-key','access_token'=>'test-token'],function($method,$url,$headers,$body) use (&$seen,$order) {
    $seen=[$method,$url,$headers,$body];
    return ['result'=>true,'data'=>['transaction'=>['entity'=>['uid'=>'SHOP-1'],
        'payment'=>['id'=>'PAY-123','reference'=>$order['id'],'currency'=>['code'=>'ARS'],'total'=>123.45,'status'=>['code'=>'200']]]]];
});
$canonical=$mobbex->fetchPayment('PAY-123');
expect(PanicRecharge::decision($order,$canonical,'SHOP-1')==='ready','Mobbex authenticated normalization');
expect($seen[0]==='GET' && $seen[1]==='https://api.mobbex.com/p/operations/PAY-123','Only provider GET');
expect(in_array('x-api-key: test-key',$seen[2],true),'Private authentication');
$incomplete=new PanicMobbexApi(['api_key'=>'test-key','access_token'=>'test-token'],function() { return ['result'=>true,'data'=>['transaction'=>['payment'=>['id'=>'PAY-123','currency'=>['code'=>'ARS']]]]]; });
try { $incomplete->fetchPayment('PAY-123'); throw new LogicException('Incomplete merchant accepted'); } catch(RuntimeException $e) { expect(!($e instanceof LogicException),'Fail closed'); }
$mpOrder=$order; $mpOrder['provider']='mercadopago';
$mpPayload=PanicMercadoPagoApi::checkoutPayload($mpOrder,'https://beta.mupanic.com.ar/usercp/recharge/','https://beta.mupanic.com.ar/hook');
expect($mpPayload['items'][0]['unit_price']===123.45 && $mpPayload['external_reference']===$order['id'],'MP checkout');
$mp=new PanicMercadoPagoApi(['access_token'=>'test-token'],function() use ($order) {
    return ['id'=>123,'collector_id'=>456,'external_reference'=>$order['id'],'currency_id'=>'ARS','transaction_amount'=>123.45,'live_mode'=>true,'status'=>'approved'];
});
expect(PanicRecharge::decision($mpOrder,$mp->fetchPayment('123'),'456')==='ready','MP normalize');
$mpPartial=new PanicMercadoPagoApi(['access_token'=>'test-token'],function() use ($order) {
    return ['id'=>123,'collector_id'=>456,'external_reference'=>$order['id'],'currency_id'=>'ARS','transaction_amount'=>123.45,'transaction_amount_refunded'=>1,'live_mode'=>true,'status'=>'approved'];
});
expect(PanicRecharge::decision($mpOrder,$mpPartial->fetchPayment('123'),'456')==='review','Partial refund blocks grant');
// This in-memory fixture verifies workflow replay only, not SQL atomicity.
class FixtureLedger implements PanicRechargeLedger {
    public $order; public $jobs=0;
    public function __construct($order) { $this->order=$order; }
    public function findOrder($reference) { return $reference===$this->order['id']?$this->order:null; }
    public function reconcileAtomically($id,array $payment,$merchant) {
        $decision=PanicRecharge::decision($this->order,$payment,$merchant);
        if($decision==='ready') {
            if($this->order['delivery_state']==='pending') $this->jobs++;
            $this->order['payment_id']=$payment['id']; $this->order['payment_state']='approved'; $this->order['delivery_state']='queued';
        }
        return $decision;
    }
}
$ledger=new FixtureLedger($order); $reconciler=new PanicRechargeReconciler($mobbex,$ledger,'SHOP-1');
expect($reconciler->reconcile('PAY-123')==='ready','Reconciler');
$reconciler->reconcile('PAY-123'); expect($ledger->jobs===1,'Replay produces one fixture job');
// Entire public catalogue is closed and has no secrets or made-up offers.
$config=require __DIR__.'/../overlay/templates/mupanic/inc/recharge-config.php';
expect($config['packages']===[] && $config['providers']['mercadopago']['state']==='disabled','Prelaunch configuration');
echo "Recharge domain and mock API tests passed. No live payments or game writes.\n";
