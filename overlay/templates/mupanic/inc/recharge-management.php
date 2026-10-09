<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/recharge-domain.php';

function panicRechargeAdminAllowed() {
    if(!function_exists('isLoggedIn') || !isLoggedIn() || ($_SESSION['username'] ?? '')!=='panic') return false;
    $admins=function_exists('config')?config('admins',true):null;
    return is_array($admins) && array_key_exists('panic',$admins);
}
function panicRechargeArgDate($value,$format='d/m/Y · H:i') {
    try { return (new DateTimeImmutable($value,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Argentina/Buenos_Aires'))->format($format); }
    catch(Throwable $ignored) { return 'Sin fecha'; }
}

final class PanicRechargeManagement {
    private $directory;
    public function __construct($directory='/home/mupanic/payments-private') {
        $real=realpath($directory); $public=realpath('/home/mupanic/public_html');
        if(!$real || !is_dir($real) || is_link($directory) || ($public && ($real===$public || strpos($real,$public.DIRECTORY_SEPARATOR)===0))) throw new RuntimeException('Private configuration unavailable');
        $this->directory=$real;
    }
    private function file($name,callable $operation) {
        $path=$this->directory.'/'.$name.'.json'; $lock=$this->directory.'/'.$name.'.lock';
        if(is_link($path) || is_link($lock)) throw new RuntimeException('Invalid private file');
        $handle=fopen($lock,'c'); if(!$handle) throw new RuntimeException('Private lock unavailable'); chmod($lock,0600);
        try {
            if(!flock($handle,LOCK_EX)) throw new RuntimeException('Private lock unavailable');
            clearstatcache(true,$path);
            if(is_file($path) && filesize($path)>131072) throw new RuntimeException('Private file too large');
            $data=is_file($path)?json_decode(file_get_contents($path),true,16,JSON_THROW_ON_ERROR):null;
            return $operation($data,function($data) use ($path) {
                $json=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR); $tmp=tempnam($this->directory,'shop-');
                if(!$tmp) throw new RuntimeException('Private write failed');
                try { chmod($tmp,0600); if(file_put_contents($tmp,$json)!==strlen($json) || !rename($tmp,$path)) throw new RuntimeException('Private write failed'); }
                finally { if(is_file($tmp)) unlink($tmp); }
            });
        } finally { flock($handle,LOCK_UN); fclose($handle); }
    }
    public function catalogue() {
        return $this->file('recharge-catalogue',function($data) {
            if($data===null) {
                $defaults=(require __DIR__.'/recharge-config.php')['packages'];
                foreach($defaults as &$row) { $row+=['enabled'=>true,'featured'=>$row['id']==='wcoin-20000','starts_at'=>'','ends_at'=>'']; } unset($row);
                return ['revision'=>0,'packages'=>$defaults,'updated_at'=>null];
            }
            if(!is_array($data) || !is_int($data['revision'] ?? null) || !is_array($data['packages'] ?? null)) throw new RuntimeException('Invalid catalogue');
            self::validate($data['packages']); return $data;
        });
    }
    private static function validate(array $rows) {
        if(!$rows || count($rows)>12) throw new InvalidArgumentException('Hasta 12 paquetes. Conservá al menos uno.');
        $ids=[];
        foreach($rows as $row) {
            PanicRecharge::package($row);
            if(isset($ids[$row['id']]) || $row['coins']>1000000 || $row['coins']+$row['bonus']>1000000 || $row['price_cents']!==$row['coins']*100 ||
                !is_bool($row['enabled'] ?? null) || !is_bool($row['featured'] ?? null)) throw new InvalidArgumentException('Revisá los valores de los paquetes.');
            $ids[$row['id']]=true;
            foreach(['starts_at','ends_at'] as $key) {
                if(!is_string($row[$key] ?? null)) throw new InvalidArgumentException('Fecha inválida.');
                if($row[$key]==='') continue;
                $date=DateTimeImmutable::createFromFormat('!Y-m-d\TH:iP',$row[$key]);
                if(!$date || $date->format('Y-m-d\TH:iP')!==$row[$key]) throw new InvalidArgumentException('Revisá las fechas de promoción.');
            }
            if($row['starts_at']!=='' && $row['ends_at']!=='' && strtotime($row['ends_at'])<=strtotime($row['starts_at'])) throw new InvalidArgumentException('La promoción debe terminar después de empezar.');
        }
    }
    public function saveCatalogue(array $rows,$revision) {
        if(!panicRechargeAdminAllowed()) throw new RuntimeException('Forbidden');
        self::validate($rows);
        return $this->file('recharge-catalogue',function($data,$write) use ($rows,$revision) {
            if(!is_int($revision) || $revision!==($data['revision'] ?? 0)) throw new RuntimeException('La configuración cambió en otra ventana. Recargá antes de guardar.');
            $next=['revision'=>$revision+1,'packages'=>array_values($rows),'updated_at'=>gmdate('c'),'updated_by'=>'panic'];
            $write($next); return $next;
        });
    }
    public function offers($now=null) {
        $now=$now ?? time(); $worker=$this->worker();
        $compatible=($worker['version'] ?? 0)>=2 && ($worker['last_success'] ?? 0)>=$now-180 && empty($worker['last_error']);
        $rows=[];
        foreach($this->catalogue()['packages'] as $row) {
            if(!$row['enabled']) continue;
            $active=$compatible && ($row['starts_at']==='' || strtotime($row['starts_at'])<=$now) && ($row['ends_at']==='' || strtotime($row['ends_at'])>$now);
            if(!$active) $row['bonus']=0;
            $rows[]=$row;
        }
        return $rows;
    }
    public static function storefrontDefaults() {
        $defaults=json_decode(file_get_contents(__DIR__.'/recharge-storefront.json'),true,16,JSON_THROW_ON_ERROR);
        foreach($defaults['products'] as &$product) $product['vip_price_coins']=null;
        unset($product);
        return ['revision'=>0,'updated_at'=>null,'products'=>$defaults['products'],'vip'=>[
            'name'=>'VIP PANIC','days'=>null,'price_coins'=>null,
            'benefits'=>[
                ['title'=>'10% en X','detail'=>'10% de descuento en los objetos de la tienda X mientras tu VIP esté activo.','enabled'=>true],
                ['title'=>'Más experiencia','detail'=>'','enabled'=>false],
                ['title'=>'Más drop','detail'=>'','enabled'=>false],
                ['title'=>'','detail'=>'','enabled'=>false],
                ['title'=>'','detail'=>'','enabled'=>false],
                ['title'=>'','detail'=>'','enabled'=>false]
            ]]];
    }
    private static function validateStorefront(array $data) {
        if(!is_array($data['products'] ?? null) || array_column($data['products'],'slug')!==['theryon','nerathys','vaeraxes']) throw new InvalidArgumentException('Revisá las tres monturas.');
        foreach($data['products'] as $product) {
            if(!is_bool($product['available'] ?? null)) throw new InvalidArgumentException('Disponibilidad inválida.');
            foreach(['price_coins','vip_price_coins'] as $key) {
                if(!array_key_exists($key,$product) || ($product[$key]!==null && (!is_int($product[$key]) || $product[$key]<1 || $product[$key]>1000000))) throw new InvalidArgumentException('Usá precios enteros de 1 a 1.000.000 Eryns o dejalos vacíos.');
            }
            if($product['available'] && $product['price_coins']===null) throw new InvalidArgumentException('Definí el precio normal antes de habilitar una montura.');
            if($product['vip_price_coins']!==null && ($product['price_coins']===null || $product['vip_price_coins']>$product['price_coins'])) throw new InvalidArgumentException('El precio VIP no puede superar el normal.');
        }
        $vip=$data['vip'] ?? null;
        if(!is_array($vip) || !is_string($vip['name'] ?? null) || trim($vip['name'])==='' || strlen($vip['name'])>80) throw new InvalidArgumentException('Revisá el nombre del único plan VIP.');
        foreach(['days'=>3650,'price_coins'=>1000000] as $key=>$max) if(!array_key_exists($key,$vip) || ($vip[$key]!==null && (!is_int($vip[$key]) || $vip[$key]<1 || $vip[$key]>$max))) throw new InvalidArgumentException('Revisá el precio y la duración VIP; pueden quedar pendientes.');
        if(!is_array($vip['benefits'] ?? null) || count($vip['benefits'])!==6) throw new InvalidArgumentException('Revisá los beneficios VIP.');
        foreach($vip['benefits'] as $benefit) if(!is_array($benefit) || !is_bool($benefit['enabled'] ?? null) || !is_string($benefit['title'] ?? null) || strlen($benefit['title'])>80 || !is_string($benefit['detail'] ?? null) || strlen($benefit['detail'])>1200 || ($benefit['enabled'] && (trim($benefit['title'])==='' || trim($benefit['detail'])===''))) throw new InvalidArgumentException('Completá título y detalle de cada beneficio publicado.');
    }
    public function storefront() {
        return $this->file('recharge-storefront',function($data) {
            if($data===null) return self::storefrontDefaults();
            if(!is_array($data) || !is_int($data['revision'] ?? null)) throw new RuntimeException('Invalid storefront');
            self::validateStorefront($data); return $data;
        });
    }
    public function saveStorefront(array $data,$revision) {
        if(!panicRechargeAdminAllowed()) throw new RuntimeException('Forbidden');
        self::validateStorefront($data);
        return $this->file('recharge-storefront',function($current,$write) use ($data,$revision) {
            if(!is_int($revision) || $revision!==($current['revision'] ?? 0)) throw new RuntimeException('La configuración cambió en otra ventana. Recargá antes de guardar.');
            $next=['revision'=>$revision+1,'updated_at'=>gmdate('c'),'updated_by'=>'panic','products'=>$data['products'],'vip'=>$data['vip']];
            $write($next);return $next;
        });
    }
    public function worker() { return $this->file('recharge-worker-status',function($data) { return is_array($data)?$data:[]; }); }
    // Only the already HMAC-authenticated worker endpoint calls this method.
    public function report(array $request,$phase,array $extra=[]) {
        if(!in_array($phase,['polling','ok','error'],true) || !in_array($request['environment'] ?? '',['test','production'],true)) throw new InvalidArgumentException('Invalid worker status');
        $version=$request['version'] ?? 1; if(!is_int($version) || $version<1 || $version>2) throw new InvalidArgumentException('Invalid worker version');
        return $this->file('recharge-worker-status',function($data,$write) use ($request,$phase,$extra,$version) {
            $state=is_array($data)?$data:[];
            $state['last_seen']=time(); $state['environment']=$request['environment']; $state['version']=$version; $state['phase']=$phase;
            foreach(['jobs','check_errors','waiting'] as $key) if(isset($extra[$key]) && is_int($extra[$key]) && $extra[$key]>=0 && $extra[$key]<=10) $state[$key]=$extra[$key];
            if(isset($extra['vip_config']) && is_array($extra['vip_config']) && count($extra['vip_config'])<=24) {
                $vip=[]; foreach($extra['vip_config'] as $key=>$value) {
                    if(!is_string($key) || !preg_match('/^(?:GS|CS)\.[A-Za-z0-9_]*Vip[A-Za-z0-9_]*$/iD',$key) || !is_string($value) || !preg_match('/^[A-Za-z0-9 _.-]{1,60}$/D',$value)) throw new InvalidArgumentException('Invalid VIP report');
                    $vip[$key]=$value;
                } $state['vip_config']=$vip;
            }
            if($phase==='ok') { $state['last_success']=time(); $state['last_error']=null; }
            if($phase==='error') { $state['last_error']='WORKER_FAILED'; $state['last_error_at']=time(); }
            $write($state); return ['state'=>'received'];
        });
    }
}
