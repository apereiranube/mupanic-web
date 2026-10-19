<?php
if (!defined('PANIC_ECO_ADMIN') || PANIC_ECO_ADMIN !== true) { http_response_code(403); exit; }
final class PanicEconomyRepository {
    private $db; private $f;
    private const VIEWS=['JuegosDia','Movimientos','ComprasDia','Vessara','VessaraDia','Recargas','RecargasDia','SaldoActual','SaldoDia','Cuentas','CuadreDia','Alertas','Estado','Excluidas'];
    public function __construct($filters, $db=null) { $this->f=$filters; $this->db=$db?:Connection::Database('MuOnline'); }
    private function read($sql,$args=[]) {
        if (!preg_match('/^SELECT\s/i',$sql) || preg_match('/;|--|\/\*|\b(?:INSERT|UPDATE|DELETE|EXEC|MERGE|INTO)\b/i',$sql)) throw new LogicException('Consulta no permitida.');
        preg_match_all('/\bdbo\.vMUPanicEco([A-Za-z]+)/',$sql,$views); if(!$views[1]) throw new LogicException('Vista requerida.'); foreach($views[1] as $v) if(!in_array($v,self::VIEWS,true)) throw new LogicException('Vista no permitida.');
        $rows=$this->db->query_fetch($sql,$args); if($rows===false) throw new RuntimeException('No se pudo consultar Economía.'); return is_array($rows)?$rows:[];
    }
    private function where($previous=false,$withTests=true) { return ['DiaAR >= ? AND DiaAR < ?'.($withTests && !$this->f['tests']?' AND Prueba = 0':''),[($previous?$this->f['previous']:$this->f['start'])->format('Ymd'),($previous?$this->f['start']:$this->f['end'])->format('Ymd')]]; }
    private function period() {
        if($this->f['group']==='mes') return "CONVERT(char(7), DiaAR, 120)";
        if($this->f['group']==='semana') return "CONVERT(varchar(4), YEAR(DATEADD(day, 3, DATEADD(day, -(DATEDIFF(day, '19000101', DiaAR) % 7), DiaAR)))) + '-S' + RIGHT('0' + CONVERT(varchar(2), DATEPART(iso_week, DiaAR)), 2)";
        return 'CONVERT(char(10), DiaAR, 120)';
    }
    private function sum($column,$alias=null) { return 'COALESCE(SUM(CAST('.$column.' AS decimal(28,2))),0) AS '.($alias?:$column); }
    public function aggregate($view,$select,$previous=false,$extra='',$params=[]) { if(!in_array($view,self::VIEWS,true)) throw new LogicException('Vista no permitida.'); [$w,$p]=$this->where($previous); return $this->read('SELECT '.$select.' FROM dbo.vMUPanicEco'.$view.' WHERE '.$w.($extra?' AND '.$extra:''),array_merge($p,$params))[0]??[]; }
    public function state() { return $this->read('SELECT UltimaCargaAR,Resultado,Lineas,Nuevos,Alertas,Segundos,Mensaje,MinutosDesdeUltima,DatosDesde FROM dbo.vMUPanicEcoEstado')[0]??null; }
    public function balances() { return $this->read('SELECT MomentoAR,Total,TotalComercial,TotalPrueba,Cuentas,CuentasConSaldo,Top10 FROM dbo.vMUPanicEcoSaldoActual')[0]??null; }
    public function openingBalance() { return $this->read('SELECT TOP 1 DiaAR,TotalComercial FROM dbo.vMUPanicEcoSaldoDia WHERE DiaAR < ? ORDER BY DiaAR DESC',[$this->f['start']->format('Ymd')])[0]??null; }
    public function summary($previous=false) {
        $r=$this->aggregate('RecargasDia',$this->sum('Pesos').','.$this->sum('Eryns'),$previous);
        $g=$this->aggregate('JuegosDia',$this->sum('NetoRetirado'),$previous);
        $c=$this->aggregate('ComprasDia',"COALESCE(SUM(CASE WHEN Fuente='TIENDA' AND Evento='COMPRA' THEN CAST(Eryns AS decimal(28,2)) ELSE 0 END),0) AS Tienda, COALESCE(SUM(CASE WHEN Fuente='VIP' AND Evento='VIP_COMPRA' THEN CAST(Eryns AS decimal(28,2)) ELSE 0 END),0) AS Vip",$previous);
        $v=$this->aggregate('VessaraDia',$this->sum('Comisiones'),$previous,'Moneda = ?',['Eryns']);
        return array_merge($r,$g,$c,$v);
    }
    public function series($view,$select,$extra='',$params=[],$groups='',$withTests=true) { [$w,$p]=$this->where(false,$withTests); $period=$this->period(); return $this->read('SELECT '.$period.' AS Periodo,'.$select.' FROM dbo.vMUPanicEco'.$view.' WHERE '.$w.($extra?' AND '.$extra:'').' GROUP BY '.$period.($groups?','.$groups:'').' ORDER BY Periodo',array_merge($p,$params)); }
    public function flows() {
        // Each economic amount is aggregated in SQL; PHP only aligns periods for presentation.
        $sets=[$this->series('RecargasDia',$this->sum('Eryns','Recargas')),$this->series('JuegosDia','-COALESCE(SUM(CAST(NetoRetirado AS decimal(28,2))),0) AS Juegos'),$this->series('ComprasDia',"-COALESCE(SUM(CASE WHEN Fuente='TIENDA' AND Evento='COMPRA' THEN CAST(Eryns AS decimal(28,2)) ELSE 0 END),0) AS Tienda,-COALESCE(SUM(CASE WHEN Fuente='VIP' AND Evento='VIP_COMPRA' THEN CAST(Eryns AS decimal(28,2)) ELSE 0 END),0) AS Vip"),$this->series('VessaraDia','-COALESCE(SUM(CAST(Comisiones AS decimal(28,2))),0) AS Vessara','Moneda = ?',['Eryns'])];
        $rows=[]; foreach($sets as $set) foreach($set as $r) { $key=$r['Periodo']; $rows[$key]=array_merge($rows[$key]??['Periodo'=>$key,'Recargas'=>0,'Juegos'=>0,'Tienda'=>0,'Vip'=>0,'Vessara'=>0],$r); } ksort($rows); return array_values($rows);
    }
    public function balanceSeries() {
        // Last snapshot in each displayed period, never SUM daily balances.
        [$w,$p]=$this->where(false,false); $period=$this->period();
        return $this->read('SELECT Periodo,TotalComercial FROM (SELECT '.$period.' AS Periodo,TotalComercial,ROW_NUMBER() OVER(PARTITION BY '.$period.' ORDER BY DiaAR DESC,MomentoAR DESC) AS rn FROM dbo.vMUPanicEcoSaldoDia WHERE '.$w.') AS s WHERE rn = 1 ORDER BY Periodo',$p);
    }
    public function games() { [$w,$p]=$this->where(); return $this->read('SELECT Juego,'.$this->sum('Jugadas').','.$this->sum('Apostado').','.$this->sum('Pagado').','.$this->sum('NetoRetirado').',CASE WHEN SUM(CAST(Apostado AS decimal(28,2))) > 0 THEN SUM(CAST(Pagado AS decimal(28,2)))*100.0/SUM(CAST(Apostado AS decimal(28,2))) ELSE NULL END AS DevueltoPct,MAX(PremioMayor) AS PremioMayor FROM dbo.vMUPanicEcoJuegosDia WHERE '.$w.' GROUP BY Juego ORDER BY NetoRetirado DESC',$p); }
    public function gameSeries() { return $this->series('JuegosDia','Juego,'.$this->sum('NetoRetirado'),'',[],'Juego'); }
    public function players($losses=false) { [$w,$p]=$this->where(); return $this->read("SELECT TOP 20 Cuenta,".$this->sum('Gastado','Apostado').','.$this->sum('Recibido','Pagado').','.$this->sum('NetoJugador')." FROM dbo.vMUPanicEcoMovimientos WHERE $w AND Tipo = ? GROUP BY Cuenta ORDER BY NetoJugador ".($losses?'ASC':'DESC').',Cuenta',array_merge($p,['JUEGO'])); }
    public function gameDetail($game) { [$w,$p]=$this->where(); return $this->read('SELECT TOP 200 FechaAR,Cuenta,Personaje,Tx,Evento,Gastado,Recibido,Detalle FROM dbo.vMUPanicEcoMovimientos WHERE '.$w.' AND Tipo = ? AND Fuente = ? ORDER BY FechaAR DESC,Id DESC',array_merge($p,['JUEGO',$game])); }
    public function marketTotals($currency,$previous=false) { return $this->aggregate('VessaraDia',$this->sum('Ventas').','.$this->sum('Volumen').','.$this->sum('Comisiones').','.$this->sum('Destacadas').','.$this->sum('ComisionesDestacadas').','.$this->sum('SinLiquidar').',CASE WHEN SUM(CAST(Ventas AS decimal(28,2)))>0 THEN SUM(CAST(Volumen AS decimal(28,2)))/SUM(CAST(Ventas AS decimal(28,2))) ELSE NULL END AS PrecioPromedio',$previous,'Moneda = ?',[$currency]); }
    public function marketSeries($currency) { return $this->series('VessaraDia',$this->sum('Volumen').','.$this->sum('Comisiones'),'Moneda = ?',[$currency]); }
    public function marketRanking($currency,$buyers=false) { [$w,$p]=$this->where(); $account=$buyers?'BuyerAccount':'SellerAccount'; return $this->read('SELECT TOP 20 '.$account.' AS Cuenta,COUNT(*) AS Ventas,'.$this->sum('Precio','Volumen').' FROM dbo.vMUPanicEcoVessara WHERE '.$w.' AND Moneda = ? GROUP BY '.$account.' ORDER BY Volumen DESC,Cuenta',array_merge($p,[$currency])); }
    public function marketDetail($currency) { [$w,$p]=$this->where(); return $this->read('SELECT TOP 500 EntregadaAR,RequestID,BuyerAccount,SellerAccount,Moneda,Precio,Comision,NetoVendedor,ComisionPct,Destacada,Liquidada FROM dbo.vMUPanicEcoVessara WHERE '.$w.' AND Moneda = ? ORDER BY EntregadaAR DESC,RequestID DESC',array_merge($p,[$currency])); }
    public function purchaseTotals($source,$previous=false) { return $this->aggregate('ComprasDia',$this->sum('Compras').','.$this->sum('Eryns'),$previous,'Fuente = ? AND Evento = ?',[$source,$source==='VIP'?'VIP_COMPRA':'COMPRA']); }
    public function storeBuyers($previous=false) { return $this->aggregate('Movimientos','COUNT(DISTINCT Cuenta) AS Compradores',$previous,'Tipo = ? AND Fuente = ? AND Gastado > 0',['TIENDA','TIENDA']); }
    public function purchaseSeries() { return $this->series('ComprasDia','Fuente,'.$this->sum('Compras').','.$this->sum('Eryns'),"Evento IN ('COMPRA','VIP_COMPRA')",[],'Fuente'); }
    public function storeProducts() {
        [$w,$p]=$this->where(); $d="CAST(Detalle AS nvarchar(4000))";
        $field=function($name) use($d) { $token=$name.'='; $len=strlen($token); return "CASE WHEN CHARINDEX('$token',$d)>0 THEN SUBSTRING($d,CHARINDEX('$token',$d)+$len,CHARINDEX('|',$d+'|',CHARINDEX('$token',$d)+$len)-CHARINDEX('$token',$d)-$len) ELSE NULL END"; };
        $product=$field('producto'); $index=$field('indice');
        return $this->read("SELECT TOP 30 $product AS Producto,$index AS Indice,COUNT(DISTINCT Tx) AS Compras,".$this->sum('Gastado','Eryns')." FROM dbo.vMUPanicEcoMovimientos WHERE $w AND Tipo = ? AND Fuente = ? AND Gastado > 0 GROUP BY $product,$index ORDER BY Compras DESC",array_merge($p,['TIENDA','TIENDA']));
    }
    public function freeVip() { [$w,$p]=$this->where(); return $this->read('SELECT TOP 500 FechaAR,Cuenta,Personaje,Evento,Detalle FROM dbo.vMUPanicEcoMovimientos WHERE '.$w.' AND Evento = ? ORDER BY FechaAR DESC,Id DESC',array_merge($p,['VIP_SIN_COBRO'])); }
    public function activeVip() { return $this->read('SELECT Cuenta,NivelVip,VipVence,Eryns,Prueba FROM dbo.vMUPanicEcoCuentas WHERE NivelVip > 0 AND VipVence > ?'.(!$this->f['tests']?' AND Prueba = 0':'').' ORDER BY VipVence,Cuenta',[$this->f['now']]); }
    public function topupTotals($previous=false) { return $this->aggregate('Recargas',$this->sum('Pesos').','.$this->sum('Eryns').',COUNT(*) AS Recargas,COUNT(DISTINCT Cuenta) AS Cuentas,AVG(CAST(Pesos AS decimal(28,2))) AS Ticket',$previous); }
    public function topupSeries() { return $this->series('RecargasDia','Proveedor,'.$this->sum('Pesos'),'',[],'Proveedor'); }
    public function topupDetail($account='') { [$w,$p]=$this->where(); return $this->read('SELECT TOP 1000 OrderID,Proveedor,Ambiente,Cuenta,Eryns,Pesos,FechaAR,Prueba FROM dbo.vMUPanicEcoRecargas WHERE '.$w.($account!==''?' AND Cuenta = ?':'').' ORDER BY FechaAR DESC,OrderID DESC',array_merge($p,$account!==''?[$account]:[])); }
    public function reconciliation() { [$w,$p]=$this->where(false,false); return $this->read('SELECT DiaAR,SaldoInicio,SaldoFin,Variacion,Recargas,Juegos,Tienda,Vip,MercadoNpc,Vessara,Explicado,NoIdentificado FROM dbo.vMUPanicEcoCuadreDia WHERE '.$w.' ORDER BY DiaAR DESC',$p); }
    public function alerts($level='') { [$w,$p]=$this->where(); return $this->read("SELECT TOP 500 FechaAR,Cuenta,Personaje,Fuente,Evento,Nivel,Detalle FROM dbo.vMUPanicEcoAlertas WHERE $w".($level!==''?' AND Nivel = ?':'')." ORDER BY CASE Nivel WHEN 'ERROR' THEN 0 WHEN 'REVISAR' THEN 1 ELSE 2 END,FechaAR DESC,Id DESC",array_merge($p,$level!==''?[$level]:[])); }
    public function richest() { return $this->read('SELECT TOP 20 Cuenta,Eryns,NivelVip,VipVence FROM dbo.vMUPanicEcoCuentas WHERE Prueba = 0 ORDER BY Eryns DESC,Cuenta'); }
    public function excluded() { return $this->read('SELECT Cuenta,Motivo,AgregadaAR FROM dbo.vMUPanicEcoExcluidas ORDER BY Cuenta'); }
}
