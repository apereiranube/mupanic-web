<?php
define('access',true);
require __DIR__.'/../overlay/templates/mupanic/inc/recharge-pilot.php';
function expectPilot($condition,$message) { if(!$condition) throw new RuntimeException($message); }
function rejectPilot(callable $test,$message) { try { $test(); } catch(Throwable $exception) { return; } throw new RuntimeException($message); }
$root=sys_get_temp_dir().'/panic-pilot-'.bin2hex(random_bytes(6)); mkdir($root,0700);
$token=str_repeat('a',64); file_put_contents($root.'/sandbox-worker-token',$token);
$calls=[]; $paymentState='APPROVED'; $amount=100000; $external=null;
$api=new PanicUalaBisApi(['username'=>'fixture','client_id'=>'fixture-merchant','client_secret_id'=>'fixture-secret'],'test',
    function($method,$url,$headers,$body) use (&$calls,&$external,&$paymentState,&$amount) {
        $calls[]=$url;
        if(strpos($url,'/auth/token')!==false) return ['access_token'=>'fixture-token','expires_in'=>3600,'token_type'=>'Bearer'];
        if($method==='POST') {
            expectPilot(strpos($url,'.stage.')!==false,'Checkout must use sandbox');
            expectPilot($body['amount']==='100000','Exactly 1000 ARS in cents');
            $external=$body['external_reference'];
            return ['uuid'=>'fixture-payment','amount'=>100000,'external_reference'=>$external,'links'=>['checkout_link'=>'https://stage.uala-checkout.com/orders/fixture']];
        }
        return ['uuid'=>'fixture-payment','amount'=>$amount,'external_reference'=>$external,'status'=>$paymentState];
    });
try {
    $store=new PanicRechargePilot($root);
    $order=$store->begin($api,'https://preview.test/');
    expectPilot($order['account']==='pruebacoin' && $order['coins']===1000 && $order['live']===false,'Restricted real coin pilot');
    expectPilot(count($calls)===2,'One authentication and one checkout');
    $same=$store->begin($api,'https://preview.test/');
    expectPilot($same['id']===$order['id'] && count($calls)===2,'Replay never creates another checkout');
    rejectPilot(function() use ($store,$api) { $store->refresh($api,'fixture-merchant','forged'); },'Forged callback');
    expectPilot(count($calls)===2,'Forged callback does not call gateway');
    expectPilot(PanicRechargePilot::job($order)===null,'Pending has no job');
    $state=$store->refresh($api,'fixture-merchant',$order['callback_token']);
    expectPilot($state['payment_state']==='approved','Authenticated sandbox approval');
    $job=PanicRechargePilot::job($state);
    expectPilot($job['account']==='pruebacoin' && $job['coins']===1000,'Only approved fixed amount becomes job');
    $paymentState='PENDING'; $state=$store->refresh($api,'fixture-merchant');
    expectPilot($state['payment_state']==='approved','Delayed pending does not regress approval');
    $rejected=$api->fetchPayment('fixture-payment'); $rejected['state']='rejected';
    expectPilot(PanicRechargePilot::refreshed($state,$rejected,'fixture-merchant')['payment_state']==='review','Contradictory rejection holds delivery');
    $mismatch=$api->fetchPayment('fixture-payment'); $mismatch['amount_cents']=100001;
    $review=PanicRechargePilot::refreshed($state,$mismatch,'fixture-merchant');
    expectPilot($review['payment_state']==='review' && PanicRechargePilot::job($review)===null,'Wrong amount holds delivery');
    $mismatch['amount_cents']=100000; $mismatch['live']=true;
    expectPilot(PanicRechargePilot::refreshed($state,$mismatch,'fixture-merchant')['payment_state']==='review','Production response never delivers');
    $expired=$state; $expired['expires_at']=time()-1;
    expectPilot(PanicRechargePilot::job($expired)===null,'Expired pilot never delivers');
    $receipt=['id'=>$order['id'],'payment_id'=>'fixture-payment','account'=>'pruebacoin','coins'=>1000,'state'=>'credited'];
    $forged=$receipt; $forged['account']='panic';
    rejectPilot(function() use ($store,$forged) { $store->acknowledge($forged); },'Different account receipt');
    $state=$store->acknowledge($receipt);
    expectPilot($state['delivery_state']==='credited' && PanicRechargePilot::job($state)===null,'Acknowledged SQL commit stops job');
    $store->acknowledge($receipt);
    $receipt['state']='reverted'; $state=$store->acknowledge($receipt);
    expectPilot($state['delivery_state']==='reverted' && PanicRechargePilot::job($state)===null,'Reversal stops job permanently');
    $receipt['state']='credited'; $state=$store->acknowledge($receipt);
    expectPilot($state['delivery_state']==='reverted','Late credit acknowledgment cannot undo reversal');
    expectPilot((new PanicRechargePilot($root))->current()['delivery_state']==='reverted','State persists across instances');
    $state['delivery_state']='pending'; $state['payment_state']='approved'; $store->save($state);
    $receipt['state']='reverted';
    expectPilot($store->acknowledge($receipt)['delivery_state']==='reverted','Recover reversal if previous credit acknowledgment was lost');
    $bad=$state; $bad['coins']=2000; $store->save($bad);
    rejectPilot(function() use ($store) { $store->current(); },'Corrupt state cannot deliver');
    unlink($root.'/uala-pilot.json');
    $attempts=0;
    $uncertain=new PanicUalaBisApi(['username'=>'fixture','client_id'=>'fixture','client_secret_id'=>'fixture'],'test',function($method,$url) use (&$attempts) {
        if(strpos($url,'/auth/token')!==false) return ['access_token'=>'fixture','expires_in'=>3600,'token_type'=>'Bearer'];
        $attempts++; throw new RuntimeException('Ambiguous external timeout');
    });
    rejectPilot(function() use ($store,$uncertain) { $store->begin($uncertain,'https://preview.test/'); },'Timeout must propagate');
    expectPilot($store->current()['payment_state']==='creating','Ambiguous request remains reserved');
    $store->begin($uncertain,'https://preview.test/');
    expectPilot($attempts===1,'Timeout retry does not create another checkout');
    expectPilot($store->current()['last_error']==='PILOT_CHECK_FAILED','Raw exception is not persisted');
    expectPilot(PanicRechargePilot::diagnostic(new PanicRechargeApiFailure('CHECKOUT_HTTP_403'))==='CHECKOUT_HTTP_403','Safe numeric diagnostics');
    expectPilot(PanicRechargePilot::diagnostic(new PanicRechargeApiFailure('SECRET'))==='PILOT_CHECK_FAILED','Unrecognized diagnostic is not disclosed');
    $reserved=$store->current(); $postCount=0; $found=false;
    $recovery=new PanicUalaBisApi(['username'=>'fixture','client_id'=>'fixture','client_secret_id'=>'fixture'],'test',function($method,$url) use ($reserved,&$postCount,&$found) {
        if(strpos($url,'/auth/token')!==false) return ['access_token'=>'fixture','expires_in'=>3600,'token_type'=>'Bearer'];
        if($method==='POST') { $postCount++; throw new RuntimeException('Recovery must not POST checkout'); }
        if(strpos($url,'?limit=20')!==false) return ['orders'=>$found?[['uuid'=>'recovered-payment','external_reference'=>$reserved['id']]]:[]];
        return ['uuid'=>'recovered-payment','amount'=>100000,'external_reference'=>$reserved['id'],'status'=>'APPROVED'];
    });
    $inspected=$store->inspect($recovery,'fixture');
    expectPilot($inspected['last_error']==='CHECKOUT_NOT_FOUND' && $inspected['payment_state']==='creating','No match does not erase reservation');
    $found=true; $inspected=$store->inspect($recovery,'fixture');
    expectPilot($inspected['payment_state']==='approved' && $postCount===0,'Recovery uses canonical GET without new checkout');
    unlink($root.'/uala-pilot.json');
    if(function_exists('pcntl_fork')) {
        $children=[];
        for($i=0;$i<2;$i++) {
            $pid=pcntl_fork();
            if($pid===-1) throw new RuntimeException('Cannot fork pilot test');
            if($pid===0) {
                $childApi=new PanicUalaBisApi(['username'=>'fixture','client_id'=>'fixture','client_secret_id'=>'fixture'],'test',function($method,$url,$headers,$body) use ($root) {
                    if(strpos($url,'/auth/token')!==false) return ['access_token'=>'fixture','expires_in'=>3600,'token_type'=>'Bearer'];
                    file_put_contents($root.'/checkout-count',"one\n",FILE_APPEND);
                    return ['uuid'=>'race-payment','amount'=>100000,'external_reference'=>$body['external_reference'],'links'=>['checkout_link'=>'https://stage.uala-checkout.com/orders/race']];
                });
                try { (new PanicRechargePilot($root))->begin($childApi,'https://preview.test/'); exit(0); }
                catch(Throwable $exception) { exit(1); }
            }
            $children[]=$pid;
        }
        foreach($children as $pid) { pcntl_waitpid($pid,$status); expectPilot(pcntl_wexitstatus($status)===0,'Concurrent child succeeds'); }
        expectPilot(file_get_contents($root.'/checkout-count')==="one\n",'Concurrent creation makes exactly one checkout');
    }
    echo "Sandbox pilot tests passed: fixed account/amount, reservation, canonical verification, replay, state persistence and reversal.\n";
} finally { foreach(glob($root.'/*') as $path) unlink($path); rmdir($root); }
