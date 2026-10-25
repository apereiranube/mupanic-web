<?php
define('access',true);$_SESSION=['username'=>'panic'];function isLoggedIn(){return true;}function config($key,$return){return ['panic'=>true];}
require __DIR__.'/../overlay/templates/mupanic/inc/recharge-orders.php';
require __DIR__.'/../overlay/templates/mupanic/inc/recharge-admin-data.php';
function overviewAssert($v,$why){if(!$v)throw new RuntimeException($why);}
$catalogue=(require __DIR__.'/../overlay/templates/mupanic/inc/recharge-config.php')['packages'];$cart=PanicRecharge::cart($catalogue,['wcoin-1000'=>1]);
$uala=[];$bank=[];
for($i=0;$i<45;$i++) {
 $o=PanicRecharge::order('panic',$cart,$i%2?'bank_transfer':'uala_bis',$i%3===0);
 $o['ticket_channel']=null;
 $o['id']='PANIC-'.str_pad(dechex($i),32,'0',STR_PAD_LEFT);$o['created_at']=gmdate('c',strtotime('2026-10-01')+$i*3600);$o['payment_id']='OP_'.$i;
 if($i%4===0){$o['payment_state']='approved';$o['delivery_state']='credited';}
 elseif($i%4===1)$o['payment_state']='approved';
 elseif($i%4===2)$o['payment_state']='rejected';
 if($i%2)$bank[]=$o;else $uala[]=$o;
}
$all=PanicRechargeAdminOverview::build($uala,$bank);
overviewAssert($all['total']===45 && $all['pages']===3 && count($all['orders'])===20,'Both ledgers participate in pagination');
overviewAssert($all['orders'][0]['id']==='PANIC-'.str_pad(dechex(44),32,'0',STR_PAD_LEFT),'Joint chronological sorting');
$approved=PanicRechargeAdminOverview::build($uala,$bank,['provider'=>'bank_transfer','state'=>'approved']);
overviewAssert($approved['total']===11 && $approved['summary']['approved_cents']===1100000,'Approved bank transfers remain visible after review');
$credited=PanicRechargeAdminOverview::build($uala,$bank,['state'=>'credited']);overviewAssert($credited['total']===12 && $credited['summary']['delivered_coins']===12000,'Credited purchases aggregate accurately');
$test=PanicRechargeAdminOverview::build($uala,$bank,['environment'=>'test']);$production=PanicRechargeAdminOverview::build($uala,$bank,['environment'=>'production']);
overviewAssert($test['total']===30 && $production['total']===15,'Production and test filters stay separate');
$query=PanicRechargeAdminOverview::build($uala,$bank,['query'=>'OP_41']);overviewAssert($query['total']===1 && $query['orders'][0]['provider']==='bank_transfer','Bank operation reference is searchable');
$last=PanicRechargeAdminOverview::build($uala,$bank,[],99);overviewAssert($last['page']===3 && count($last['orders'])===5,'Out-of-range pages clamp without losing records');
// Reading the raw rows is still administrator-only for both stores.
$root=sys_get_temp_dir().'/panic-overview-'.bin2hex(random_bytes(5));mkdir($root,0700);
$u=new PanicRechargeOrders($root);$b=new PanicBankTransfers($root);$_SESSION['username']='other';
foreach([$u,$b]as $store){$blocked=false;try{$store->administrationRows();}catch(Throwable $e){$blocked=true;}overviewAssert($blocked,'Private ledgers require admin');}
foreach(glob($root.'/*')as $p)unlink($p);rmdir($root);
echo "Unified admin overview: providers, approved/credited history, filters, totals, pagination and access checks passed.\n";
