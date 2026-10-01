<?php
define('access',true);
require __DIR__.'/../overlay/templates/mupanic/inc/recharge-uala.php';
function verify($condition,$message) { if(!$condition) throw new RuntimeException($message); }
$package=['id'=>'test','title'=>'Fixture only','price_cents'=>12345,'coins'=>1000,'bonus'=>0];
$order=PanicRecharge::order('pruebacoin',$package,'uala_bis',false);
$payload=PanicUalaBisApi::checkoutPayload($order,'https://example.test/return','https://example.test/hook');
verify($payload['amount']==='12345','Uala amount is integer centavos, not pesos');
verify($payload['external_reference']===$order['id'],'Snapshot reference');
$calls=[]; $state='APPROVED'; $amount=12345;
$api=new PanicUalaBisApi(['username'=>'fixture','client_id'=>'fixture-client','client_secret_id'=>'fixture-secret'],'test',function($method,$url,$headers,$body) use (&$calls,&$state,&$amount,$order) {
    $calls[]=[$method,$url,$headers,$body];
    if(strpos($url,'/auth/token')!==false) {
        verify($body['username']==='fixture' && $body['grant_type']==='client_credentials','Authentication payload');
        return ['access_token'=>'fixture-token','expires_in'=>86400,'token_type'=>'Bearer'];
    }
    verify(in_array('Authorization: Bearer fixture-token',$headers,true),'Bearer required');
    return ['uuid'=>'fixture-order','amount'=>$amount,'status'=>$state,'external_reference'=>$order['id']];
});
$payment=$api->fetchPayment('fixture-order');
verify(PanicRecharge::decision($order,$payment,'fixture-client')==='test_approved','Sandbox cannot grant real coins');
verify($calls[1][1]==='https://checkout.stage.developers.ar.ua.la/v2/api/orders/fixture-order','Test host');
$api->fetchPayment('fixture-order');verify(count($calls)===3,'Token reused, not exposed');
foreach(['PENDING','PROCESSED','PROCCESED','REJECTED','REFUNDED','UNKNOWN'] as $value) {
    $state=$value; $payment=$api->fetchPayment('fixture-order');
    verify(PanicRecharge::decision($order,$payment,'fixture-client')!=='ready','Only approved can deliver');
}
$state='APPROVED';$amount='12345';verify($api->fetchPayment('fixture-order')['amount_cents']===12345,'String centavos');
$amount='123.45';
try { $api->fetchPayment('fixture-order'); throw new LogicException('Decimal centavos accepted'); } catch(RuntimeException $e) {}
$small=$order;$small['price_cents']=2499;
try { PanicUalaBisApi::checkoutPayload($small,'https://example.test/return','https://example.test/hook');throw new LogicException('Below minimum accepted'); } catch(InvalidArgumentException $e) {}
try { $api->createCheckout(array_replace($order,['live'=>true]),'https://example.test/return','https://example.test/hook');throw new LogicException('Environment mismatch accepted'); } catch(InvalidArgumentException $e) {}
$file=tempnam(sys_get_temp_dir(),'uala-test-');
try {
    file_put_contents($file,json_encode(['environment'=>'test','sales_enabled'=>false,'uala_bis'=>['username'=>'fixture','client_id'=>'fixture-client','client_secret_id'=>'fixture-secret']]));
    verify(panicUalaPrivateSettings($file)['sales_enabled']===false,'Private config remains closed');
    file_put_contents($file,file_get_contents(__DIR__.'/../examples/uala-settings.example.json'));
    try { panicUalaPrivateSettings($file);throw new LogicException('Placeholder accepted'); } catch(RuntimeException $e) {}
} finally { unlink($file); }
echo "Uala v2 mock tests passed: authentication, centavos, test environment, status and private settings.\n";
