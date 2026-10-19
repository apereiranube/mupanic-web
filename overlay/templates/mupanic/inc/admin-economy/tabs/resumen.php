<?php
if(!defined('PANIC_ECO_ADMIN') || PANIC_ECO_ADMIN!==true) { http_response_code(403); exit; }
$current=$ecoRepository->summary(); $previous=$ecoRepository->summary(true); $balance=$ecoRepository->balances(); $opening=$ecoRepository->openingBalance();
$flows=$ecoRepository->flows(); $balances=$ecoRepository->balanceSeries();
$circulation=$balance['TotalComercial']??null; $delta=($circulation!==null && $opening && $opening['TotalComercial']!==null)?(float)$circulation-(float)$opening['TotalComercial']:null;
$cards=[['Pesos cobrados · ARS',$current['Pesos'],$previous['Pesos'],'$ '],['Eryns emitidos por recargas',$current['Eryns'],$previous['Eryns']],['Eryns retirados por juegos',$current['NetoRetirado'],$previous['NetoRetirado']],['Eryns gastados en tienda',$current['Tienda'],$previous['Tienda']],['Eryns gastados en VIP',$current['Vip'],$previous['Vip']],['Comisiones Vessara · Eryns',$current['Comisiones'],$previous['Comisiones']],['Eryns en circulación ahora · reales',$circulation,$delta===null?'— · sin foto previa al rango':($delta>0?'+':'').panicEcoNumber($delta).' Eryns desde el inicio del rango']];
panicEcoCards($cards);
?>
<p class="eco-note">Circulación comercial: cuentas reales, incluso al incluir operaciones de prueba. Foto actual: <?php echo panicEcoEscape(panicEcoDate($balance['MomentoAR']??null)); ?>. <?php if($delta!==null) echo 'Base: foto del '.panicEcoEscape(panicEcoDate($opening['DiaAR'])).'. '; ?>Saldo de prueba actual: <?php echo panicEcoNumber($balance['TotalPrueba']??null); ?> Eryns.</p>
<?php
panicEcoChart('Entradas y salidas de Eryns','bar',$flows,['Recargas'=>'Recargas','Juegos'=>'Juegos neto','Tienda'=>'Tienda','Vip'=>'VIP','Vessara'=>'Comisiones Vessara'],['stacked'=>true]);
echo '<p class="eco-note">Entradas arriba y salidas abajo. Si los juegos pagan más de lo apostado, su neto aparece como entrada. El volumen de Vessara no es ingreso del servidor.</p>';
panicEcoChart('Eryns en circulación · cuentas reales','line',$balances,['TotalComercial'=>'Saldo comercial']);
