<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/recharge-bank.php';
/** One read-only list for both payment providers; filtering precedes pagination. */
final class PanicRechargeAdminOverview {
    public static function build(array $uala,array $bank,array $filters=[],$page=1) {
        $query=trim((string)($filters['query'] ?? ''));$environment=$filters['environment'] ?? 'all';$state=$filters['state'] ?? '';$provider=$filters['provider'] ?? '';
        $rows=[];$summary=['total'=>0,'approved_cents'=>0,'delivered_coins'=>0,'approved_count'=>0,'credited'=>0,'waiting'=>0,'bank_pending'=>0];
        $providers=['uala_bis'=>['count'=>0,'cents'=>0],'bank_transfer'=>['count'=>0,'cents'=>0]];
        foreach(['uala_bis'=>$uala,'bank_transfer'=>$bank] as $method=>$orders)foreach($orders as $o) {
            if(($environment==='test' && $o['live']) || ($environment==='production' && !$o['live']) || ($provider!=='' && $provider!==$method))continue;
            if($query!=='' && stripos($o['account'],$query)===false && stripos($o['id'],$query)===false && stripos((string)($o['payment_id'] ?? ''),$query)===false)continue;
            [$tone,$label,$copy]=$method==='bank_transfer'?panicBankStatus($o):panicRechargeStatus($o);
            if($state!=='' && $tone!==$state)continue;
            $o['provider']=$method;$o['status']=[$tone,$label,$copy];$rows[]=$o;$summary['total']++;
            if($o['payment_state']==='approved') {
                $summary['approved_cents']+=$o['price_cents'];$summary['approved_count']++;
                $providers[$method]['count']++;$providers[$method]['cents']+=$o['price_cents'];
                if($o['delivery_state']!=='credited')$summary['waiting']++;
            }
            if($o['delivery_state']==='credited'){$summary['credited']++;$summary['delivered_coins']+=$o['coins'];}
            if($method==='bank_transfer' && $o['payment_state']==='pending')$summary['bank_pending']++;
        }
        usort($rows,function($a,$b){return strcmp($b['created_at'],$a['created_at']) ?: strcmp($b['id'],$a['id']);});
        $pages=max(1,(int)ceil(count($rows)/20));$page=max(1,min($pages,(int)$page));
        return ['summary'=>$summary,'providers'=>$providers,'total'=>count($rows),'pages'=>$pages,'page'=>$page,'orders'=>array_slice($rows,($page-1)*20,20),'recent'=>array_slice($rows,0,5)];
    }
}
