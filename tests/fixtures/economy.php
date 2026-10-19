<?php
// Isolated renderer: all database rows are synthetic. Never connects to MuOnline.
define('access','index'); define('__BASE_URL__','https://preview.test/beta/'); define('__PATH_TEMPLATE__',__BASE_URL__.'templates/mupanic/');
function isLoggedIn() { return getenv('ECO_USER')!=='guest'; }
function config($key,$return=true) { return ['panic'=>1]; }
$_SESSION=['username'=>getenv('ECO_USER')?:'panic']; $_SERVER['REQUEST_METHOD']=getenv('ECO_METHOD')?:'GET';
$_GET=json_decode(getenv('ECO_QUERY')?:'{"eco":"1","pruebas":"1"}',true); $_REQUEST=['page'=>'usercp','subpage'=>'shopadmin'];
$inc=dirname(__DIR__,2).'/overlay/templates/mupanic/inc'; require $inc.'/recharge-management.php';
class Connection { public static $queries=[]; public static function Database($name) { if($name!=='MuOnline') throw new Exception('Unexpected DB'); return new self; }
    public function query_fetch($sql,$params=[]) {
        self::$queries[]=[$sql,$params];
        if(getenv('ECO_FAIL')==='1') return false;
        if(strpos($sql,'vMUPanicEcoEstado')!==false) return [['UltimaCargaAR'=>'2026-10-18 22:30:00','Resultado'=>'OK','Lineas'=>1000,'Nuevos'=>100,'Alertas'=>2,'Segundos'=>2,'Mensaje'=>'Carga correcta','MinutosDesdeUltima'=>getenv('ECO_STALE')==='1'?20:3,'DatosDesde'=>'2026-10-01']];
        if(getenv('ECO_EMPTY')==='1') {
            if(strpos($sql,'SUM(')!==false || strpos($sql,'COUNT(')!==false || strpos($sql,'AVG(')!==false) {
                if(strpos($sql,'GROUP BY')!==false) return null;
                preg_match_all('/\bAS ([A-Za-z]+)(?:,| FROM)/',$sql,$m); $row=[];foreach($m[1] as $key)$row[$key]=0;return [$row];
            } return null;
        }
        $base=['Periodo'=>'2026-10-18','Juego'=>'RULETA','Fuente'=>'TIENDA','Cuenta'=>'jugador<script>','Personaje'=>'Guerrero','Tx'=>'tx-100','Evento'=>'COMPRA','DiaAR'=>'2026-10-18','FechaAR'=>'2026-10-18 20:30:00','Gastado'=>1000,'Recibido'=>920,'NetoJugador'=>-80,'Detalle'=>'producto=Espada de prueba|indice=100','Prueba'=>0,'Jugadas'=>10,'Apostado'=>10000,'Pagado'=>9200,'NetoRetirado'=>800,'DevueltoPct'=>92,'PremioMayor'=>1400,'Compras'=>5,'Compradores'=>3,'Eryns'=>5000,'Pesos'=>5000,'Tienda'=>1000,'Vip'=>2000,'Comisiones'=>100,'Recargas'=>2,'Cuentas'=>3,'Ticket'=>2500,'Ventas'=>2,'Volumen'=>2000,'Destacadas'=>1,'ComisionesDestacadas'=>80,'SinLiquidar'=>1,'PrecioPromedio'=>1000,'Proveedor'=>'Ualá','OrderID'=>'P-001','Ambiente'=>'production','RequestID'=>1,'BuyerAccount'=>'comprador','SellerAccount'=>'vendedor','Precio'=>1000,'Comision'=>50,'NetoVendedor'=>950,'ComisionPct'=>5,'Liquidada'=>1,'Destacada'=>0,'EntregadaAR'=>'2026-10-18 20:00:00','Producto'=>'Espada de prueba','Indice'=>'100','NivelVip'=>1,'VipVence'=>'2026-12-01 20:00:00','MomentoAR'=>'2026-10-18 22:30:00','Total'=>120000,'TotalComercial'=>100000,'TotalPrueba'=>20000,'CuentasConSaldo'=>10,'Top10'=>60000,'SaldoInicio'=>95000,'SaldoFin'=>100000,'Variacion'=>5000,'Juegos'=>-800,'MercadoNpc'=>0,'Vessara'=>-100,'Explicado'=>3000,'NoIdentificado'=>2000,'Nivel'=>'REVISAR','Motivo'=>'Cuenta de prueba','AgregadaAR'=>'2026-10-01'];
        if(strpos($sql,'AS Periodo')!==false) $base['Periodo']=($_GET['agrupar']??'dia')==='semana'?'2026-S42':(($_GET['agrupar']??'dia')==='mes'?'2026-10':'2026-10-18');
        if(strpos($sql,'SELECT TOP 1 DiaAR')===0) {$base['DiaAR']='2026-10-11';$base['TotalComercial']=95000;}
        if(strpos($sql,'-COALESCE')!==false || strpos($sql,'-COALESCE')!==false) {$base['Juegos']=-800;$base['Tienda']=-1000;$base['Vip']=-2000;$base['Vessara']=-100;}
        if(strpos($sql,'AS Periodo')!==false && strpos($sql,'FROM dbo.vMUPanicEcoRecargasDia')!==false) return [['Periodo'=>$base['Periodo']]+(strpos($sql,'AS Recargas')!==false?['Recargas'=>5000]:['Proveedor'=>'Ualá','Pesos'=>5000])];
        if(strpos($sql,'AS Periodo')!==false && strpos($sql,'FROM dbo.vMUPanicEcoJuegosDia')!==false && strpos($sql,'AS Juegos')!==false) return [['Periodo'=>$base['Periodo'],'Juegos'=>-800]];
        if(strpos($sql,'AS Periodo')!==false && strpos($sql,'FROM dbo.vMUPanicEcoComprasDia')!==false && strpos($sql,'AS Tienda')!==false) return [['Periodo'=>$base['Periodo'],'Tienda'=>-1000,'Vip'=>-2000]];
        if(strpos($sql,'AS Periodo')!==false && strpos($sql,'FROM dbo.vMUPanicEcoVessaraDia')!==false && strpos($sql,'AS Vessara')!==false) return [['Periodo'=>$base['Periodo'],'Vessara'=>-100]];
        return [$base];
    }
}
if(!panicRechargeAdminAllowed()) { require $inc.'/recharge-admin.php'; exit; }
echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="https://preview.test/beta/templates/mupanic/css/style.css"><link rel="stylesheet" href="https://preview.test/beta/templates/mupanic/css/account.css"><link rel="stylesheet" href="https://preview.test/beta/templates/mupanic/css/recharge.css"><style>body{margin:0;background:#eae6df;font-family:Arial}main{max-width:1100px;padding:24px;margin:20px auto;background:#fffaf3;min-width:0}@media(max-width:650px){main{margin:0;padding:12px}}</style></head><body class="is-account"><main class="module-surface"><div class="account-module" data-account-module="shopadmin">';
require $inc.'/recharge-admin.php';
echo '</div></main><script src="https://preview.test/jquery.js"></script></body></html>';
if(getenv('ECO_QUERIES')) file_put_contents(getenv('ECO_QUERIES'),json_encode(Connection::$queries));
