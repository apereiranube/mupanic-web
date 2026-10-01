<?php
define('access',true);
require __DIR__.'/../overlay/templates/mupanic/inc/recharge-orders.php';
function checkShop($condition,$why) { if(!$condition) throw new RuntimeException($why); }
function rejectShop(callable $operation,$why) { try { $operation(); } catch(Throwable $exception) { return; } throw new RuntimeException($why); }
$catalogue=(require __DIR__.'/../overlay/templates/mupanic/inc/recharge-config.php')['packages'];
$cart=PanicRecharge::cart($catalogue,['wcoin-20000'=>'5']);
checkShop($cart['coins']===100000 && $cart['price_cents']===10000000,'Five 20k packages');
$mixed=PanicRecharge::cart($catalogue,['wcoin-20000'=>2,'wcoin-5000'=>3]);
checkShop($mixed['coins']===55000 && count($mixed['lines'])===2,'Mixed packages');
foreach([[],['missing'=>1],['wcoin-1000'=>-1],['wcoin-1000'=>'1.5'],['wcoin-1000'=>100],['wcoin-1000'=>[]],['wcoin-20000'=>99]] as $bad) rejectShop(function() use ($catalogue,$bad) { PanicRecharge::cart($catalogue,$bad); },'Invalid or excessive cart');
$root=sys_get_temp_dir().'/panic-orders-'.bin2hex(random_bytes(5)); mkdir($root,0700); file_put_contents($root.'/recharge-worker-token',str_repeat('a',64));
$created=[];$posts=0;$fail=false;$remoteState='PENDING';$wrongAmount=false;
$api=new PanicUalaBisApi(['username'=>'fixture','client_id'=>'merchant-a','client_secret_id'=>'fixture'],'test',function($method,$url,$headers,$body) use (&$created,&$posts,&$fail,&$remoteState,&$wrongAmount) {
    if(strpos($url,'/auth/token')!==false) return ['access_token'=>'fixture','expires_in'=>3600,'token_type'=>'Bearer'];
    if($fail) throw new PanicRechargeApiFailure('CHECKOUT_HTTP_503');
    if($method==='POST') {
        $posts++; $id='payment-'.$posts;
        $created[$id]=['uuid'=>$id,'amount'=>$body['amount'],'external_reference'=>$body['external_reference']];
        return $created[$id]+['links'=>['checkout_link'=>'https://stage-uala-arg-bis-link-de-pago-web.vercel.app/'.$id]];
    }
    $id=basename($url); $payment=$created[$id]; $payment['status']=$remoteState;
    if($wrongAmount) $payment['amount']='999.99';
    return $payment;
});
try {
    $store=new PanicRechargeOrders($root);
    $first=$store->begin('anotheracc',$cart,str_repeat('b',32),false,$api,'https://example.test/');
    checkShop($first['account']==='anotheracc' && $first['coins']===100000 && $created[$first['payment_id']]['amount']==='100000.00','Any account and exact peso wire amount');
    $again=$store->begin('anotheracc',$cart,str_repeat('b',32),false,$api,'https://example.test/');
    checkShop($again['id']===$first['id'] && $posts===1,'Repeated POST nonce creates once');
    rejectShop(function() use ($store,$mixed,$api) { $store->begin('anotheracc',$mixed,str_repeat('b',32),false,$api,'https://example.test/'); },'Changed cart cannot reuse nonce');
    rejectShop(function() use ($store,$first) { $store->owned($first['id'],'outsider'); },'Account ownership');
    checkShop($store->history('outsider')['total']===0,'History does not leak other accounts');
    $public=$store->history('anotheracc')['orders'][0];
    foreach(['callback_token','request_key','checkout_response','cart_hash','merchant_id'] as $secret) checkShop(!isset($public[$secret]),'Private field redacted');
    $remoteState='APPROVED'; $ready=$store->poll($api,'merchant-a',false);
    checkShop(count($ready['jobs'])===1 && $ready['jobs'][0]['coins']===100000,'Canonical verification produces variable amount job');
    checkShop($store->poll($api,'merchant-a',true)['jobs']===[],'No cross environment jobs');
    $receipt=['id'=>$first['id'],'payment_id'=>$first['payment_id'],'account'=>'anotheracc','coins'=>100000,'environment'=>'test','state'=>'credited','before_coin'=>11,'after_coin'=>100011];
    $forged=$receipt;$forged['coins']=999;
    rejectShop(function() use ($store,$forged) { $store->acknowledge($forged); },'Wrong SQL receipt rejected');
    $forged=$receipt;$forged['after_coin']=12;
    rejectShop(function() use ($store,$forged) { $store->acknowledge($forged); },'Wrong delta rejected');
    $store->acknowledge($receipt);$store->acknowledge($receipt);
    checkShop($store->get($first['id'])['delivery_state']==='credited','Repeated receipt is idempotent');
    checkShop($store->poll($api,'merchant-a',false)['jobs']===[],'Credited never dispatches again');
    $second=$store->begin('otheruser',$mixed,str_repeat('c',32),false,$api,'https://example.test/');
    $wrongAmount=true;$held=$store->refresh($second['id'],$api,'merchant-a');
    checkShop($held['payment_state']==='review','Wrong amount holds coins');
    $wrongAmount=false;checkShop($store->refresh($second['id'],$api,'merchant-a')['payment_state']==='review','Review is sticky');
    $remoteState='PENDING';$third=$store->begin('thirduser',$mixed,str_repeat('d',32),false,$api,'https://example.test/');
    $remoteState='APPROVED';$fail=true;
    checkShop($store->poll($api,'merchant-a',false)['jobs']===[],'Fresh gateway outage never dispatches cached job');
    $fail=false;$store->refresh($third['id'],$api,'merchant-a');
    $disk=json_decode(file_get_contents($root.'/recharge-orders.json'),true);$disk[$third['id']]['next_check']=0;
    file_put_contents($root.'/recharge-orders.json',json_encode($disk));$fail=true;
    checkShop($store->poll($api,'merchant-a',false)['jobs']===[],'Previously approved cache cannot bypass fresh outage');
    $fail=false;
    $remoteState='REFUNDED';checkShop($store->refresh($third['id'],$api,'merchant-a')['payment_state']==='review','Refund holds delivery');
    $fail=true;
    rejectShop(function() use ($store,$api,$mixed) { $store->begin('timeoutusr',$mixed,str_repeat('e',32),false,$api,'https://example.test/'); },'Timeout reservation');
    $fail=false;$before=$posts;$saved=$store->begin('timeoutusr',$mixed,str_repeat('e',32),false,$api,'https://example.test/');
    checkShop($saved['payment_state']==='creating' && $posts===$before,'Timeout replay never creates second checkout');
    $changedMerchant=new PanicUalaBisApi(['username'=>'fixture','client_id'=>'merchant-b','client_secret_id'=>'fixture'],'test',function() { throw new LogicException('Do not call changed merchant'); });
    rejectShop(function() use ($store,$first,$changedMerchant) { $store->refresh($first['id'],$changedMerchant,'merchant-b'); },'Merchant snapshot preserved');
    checkShop(panicRechargeCanBuy(['environment'=>'test'],'anyaccount',true),'All accounts can simulate');
    checkShop(!panicRechargeCanBuy(['environment'=>'production','sales_enabled'=>false],'anyaccount',true),'Production closed by default');
    checkShop(!panicRechargeCanBuy(['environment'=>'production','sales_enabled'=>true],'anyaccount',true),'Production verification required');
    checkShop(panicRechargeCanBuy(['environment'=>'production','sales_enabled'=>true,'recharges'=>['production_verified'=>true,'amount_unit'=>'ARS']],'anyaccount',true),'Explicit verified production gate');
    checkShop(!panicRechargeCanBuy(['environment'=>'test'],'anyaccount',false),'Worker token required before checkout');
    checkShop((new PanicRechargeOrders($root))->history('',1,true)['total']===4,'Durable admin history');
    $wire=json_encode(['action'=>'poll','environment'=>'test','nonce'=>str_repeat('a',32)]);$stamp=(string)time();$token=str_repeat('a',64);
    $signature=hash_hmac('sha256',$stamp."\n".$wire,$token);
    checkShop(panicRechargeWorkerRequest($wire,$stamp,$signature,$token,time())['action']==='poll','Valid signed bridge request');
    checkShop(panicRechargeWorkerRequest($wire.' ',$stamp,$signature,$token,time())===null,'Body tampering rejected');
    checkShop(panicRechargeWorkerRequest($wire,$stamp,$signature,str_repeat('b',64),time())===null,'Wrong worker key rejected');
    checkShop(panicRechargeWorkerRequest($wire,$stamp,$signature,$token,time()+301)===null,'Stale worker request rejected');
    $badNonce=json_encode(['action'=>'poll','nonce'=>['array']]);
    checkShop(panicRechargeWorkerRequest($badNonce,$stamp,hash_hmac('sha256',$stamp."\n".$badNonce,$token),$token,time())===null,'Malformed nonce rejected');
    // Independent processes race on the same nonce. Reservation is committed before API.
    if(function_exists('pcntl_fork')) {
        $raceNonce=str_repeat('f',32);$children=[];
        for($i=0;$i<3;$i++) {
            $pid=pcntl_fork(); if($pid===0) {
                $raceApi=new PanicUalaBisApi(['username'=>'fixture','client_id'=>'merchant-a','client_secret_id'=>'fixture'],'test',function($method,$url,$headers,$body) use ($root) {
                    if(strpos($url,'/auth/token')!==false) return ['access_token'=>'fixture','expires_in'=>3600,'token_type'=>'Bearer'];
                    file_put_contents($root.'/race-posts','POST\n',FILE_APPEND|LOCK_EX);
                    return ['uuid'=>'race-payment','amount'=>$body['amount'],'external_reference'=>$body['external_reference'],'links'=>['checkout_link'=>'https://stage.uala-checkout.com/race']];
                });
                (new PanicRechargeOrders($root))->begin('raceuser',$mixed,$raceNonce,false,$raceApi,'https://example.test/');exit(0);
            } $children[]=$pid;
        }
        foreach($children as $pid) { pcntl_waitpid($pid,$status); checkShop(pcntl_wexitstatus($status)===0,'Race child succeeded'); }
        checkShop(substr_count(file_get_contents($root.'/race-posts'),'POST')===1,'Concurrent reservation creates once');
    }
    echo "Recharge orders passed: quantities, multiple accounts, canonical amounts, ownership, history, replay, concurrent reservation, refunds and outages.\n";
} finally { foreach(glob($root.'/*') as $file) unlink($file); rmdir($root); }
