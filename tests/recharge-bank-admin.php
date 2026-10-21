<?php
function bankAdminRender($view,$post=null) {
    putenv('RECHARGE_ADMIN_VIEW='.$view);
    putenv('RECHARGE_ADMIN_POST='.($post===null?'':json_encode($post)));
    $output=[];$code=0;
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/fixtures/recharge-admin.php'),$output,$code);
    if($code!==0)throw new RuntimeException('Fixture failed');return implode("\n",$output);
}
function bankAdminAssert($condition,$message){if(!$condition)throw new RuntimeException($message);}
$html=bankAdminRender('bank');
bankAdminAssert(substr_count($html,'<article class="recharge-order"')===1,'Only pending transfers rendered');
bankAdminAssert(strpos($html,'name="bank[holder]"')===false && strpos($html,'id="shop-catalogue"')===false,'Review is separate from bank/store settings');
bankAdminAssert(strpos($html,'No es el código de Discord.')!==false,'Explain where operation number comes from');
$post=['admin_csrf'=>str_repeat('a',64),'admin_action'=>'bank_approve','bank_order'=>'FIXTURE_BANK_ORDER','bank_confirmed'=>'on','bank_reference'=>''];
$error=bankAdminRender('bank',$post);
bankAdminAssert(strpos($error,'No es el código de Discord.')!==false && substr_count($error,'<article class="recharge-order"')===1,'Failed approval retains order');
$post['bank_reference']='12';$post['admin_csrf']=str_repeat('b',64);$denied=bankAdminRender('bank',$post);
bankAdminAssert(strpos($denied,'La sesión venció')!==false && strpos($denied,'Transferencia confirmada.')===false,'Bank approval CSRF rejected');
$post['admin_csrf']=str_repeat('a',64);$approved=bankAdminRender('bank',$post);
bankAdminAssert(strpos($approved,'Transferencia confirmada.')!==false && strpos($approved,'No hay transferencias pendientes')!==false,'Successful review leaves pending screen');
$rootError=bankAdminRender('',['admin_csrf'=>str_repeat('b',64),'admin_action'=>'bank_config','bank_revision'=>'1','bank'=>[]]);
bankAdminAssert(strpos($rootError,'La sesión venció')!==false && strpos($rootError,'value="Titular de prueba"')!==false && strpos($rootError,'value="mu.panic.prueba"')!==false,'POST error must not blank saved recipient details');
putenv('RECHARGE_ADMIN_VIEW');putenv('RECHARGE_ADMIN_POST');
echo "Bank review: separate pending queue, approval, CSRF and recipient retention passed.\n";
