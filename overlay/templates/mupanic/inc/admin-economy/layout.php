<?php
if (!defined('PANIC_ECO_ADMIN') || PANIC_ECO_ADMIN !== true) { http_response_code(403); exit; }
$ecoTabs=['resumen'=>'Resumen','juegos'=>'Juegos','vessara'=>'Mercado Vessara','tienda-vip'=>'Tienda y VIP','recargas'=>'Recargas','control'=>'Revisiones'];
?>
<section class="eco" id="economia" aria-labelledby="eco-title">
<header class="eco-hero"><div><span class="eco-eyebrow">MU PANIC / ADMINISTRACIÓN</span><h2 id="eco-title">Tu economía, en claro.</h2><p>Qué entró, qué se gastó y qué conviene revisar.</p></div><a class="eco-back" href="<?php echo panicEcoEscape(__BASE_URL__.'usercp/shopadmin/'); ?>">← Inicio del panel</a><div class="eco-hero-meta"><span>Solo lectura</span><span>Hora Argentina</span><span>Acceso exclusivo: panic</span></div></header>
<nav class="eco-tabs" aria-label="Panel de Economía"><?php foreach($ecoTabs as $key=>$label) { ?><a href="<?php echo panicEcoEscape(panicEcoUrl($ecoFilters['query'], ['eco_tab'=>$key])); ?>" <?php if($ecoFilters['tab']===$key) echo 'aria-current="page"'; ?>><?php echo $label; ?></a><?php } ?></nav>
<form class="eco-filters" method="get" action="<?php echo panicEcoEscape(__BASE_URL__.'usercp/shopadmin/'); ?>#economia">
<input type="hidden" name="eco" value="1"><input type="hidden" name="eco_tab" value="<?php echo panicEcoEscape($ecoFilters['tab']); ?>">
<label>Rango<select name="rango"><?php foreach(['hoy'=>'Hoy','ayer'=>'Ayer','7'=>'Últimos 7 días','30'=>'Últimos 30 días','mes'=>'Este mes','personalizado'=>'Personalizado'] as $key=>$label) { ?><option value="<?php echo $key; ?>" <?php if((string)$key===$ecoFilters['query']['rango']) echo 'selected'; ?>><?php echo $label; ?></option><?php } ?></select></label>
<label>Agrupar por<select name="agrupar"><?php foreach(['dia'=>'Día','semana'=>'Semana','mes'=>'Mes'] as $key=>$label) { ?><option value="<?php echo $key; ?>" <?php if($key===$ecoFilters['group']) echo 'selected'; ?>><?php echo $label; ?></option><?php } ?></select></label>
<label>Desde<input type="date" name="desde" value="<?php echo $ecoFilters['query']['desde']; ?>" data-eco-date></label><label>Hasta<input type="date" name="hasta" value="<?php echo $ecoFilters['query']['hasta']; ?>" data-eco-date></label>
<label class="eco-switch"><input type="checkbox" name="pruebas" value="1" <?php if($ecoFilters['tests']) echo 'checked'; ?>> Ver también mis pruebas <?php echo panicEcoHelp('Cuentas de prueba o admin: louismk, louismk2, panic, pruebabaul y pruebacoin, entre otras que estén marcadas.'); ?></label><button type="submit">Aplicar filtros</button></form>
<p class="eco-note"><?php echo $ecoFilters['start']->format('d/m/Y'); ?> al <?php echo $ecoFilters['end']->modify('-1 day')->format('d/m/Y'); ?> · Comparación: <?php echo $ecoFilters['previous']->format('d/m/Y'); ?> al <?php echo $ecoFilters['start']->modify('-1 day')->format('d/m/Y'); ?> · <?php echo $ecoFilters['tests']?'Estás viendo jugadores reales y mis pruebas.':'Estás viendo solo jugadores reales.'; ?></p>
<?php if(!$ecoState || $ecoState['MinutosDesdeUltima']===null || (float)$ecoState['MinutosDesdeUltima']>15 || ($ecoState['Resultado']??'')!=='OK') { ?><div class="eco-alert eco-alert--error" role="alert"><strong>La carga de datos está atrasada</strong><span><?php echo $ecoState?'Última carga: '.panicEcoEscape(panicEcoDate($ecoState['UltimaCargaAR'])).' · Resultado: '.panicEcoEscape($ecoState['Resultado']):'Todavía no hay un estado de carga disponible.'; ?></span></div><?php } else { ?><p class="eco-status"><?php echo panicEcoIcon('check'); ?>Datos actualizados · Última carga: <?php echo panicEcoEscape(panicEcoDate($ecoState['UltimaCargaAR'])); ?></p><?php } ?>
<?php
try { require __DIR__.'/tabs/'.$ecoFilters['tab'].'.php'; }
catch(Throwable $e) { error_log('[MU PANIC Economia] Error de lectura en '.$ecoFilters['tab']); echo '<div class="eco-alert eco-alert--error" role="alert"><strong>No se pudieron consultar los datos de esta pestaña.</strong><span>Revisá la conexión y la disponibilidad de las vistas. Un error de lectura no significa que los valores sean cero.</span></div>'; }
?>
<p class="eco-footer">Los datos se actualizan cada 5 minutos. Este panel es para mirar: no cambia saldos ni compras.</p>
</section>
