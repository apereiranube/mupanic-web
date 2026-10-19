<?php
if (!defined('PANIC_ECO_ADMIN') || PANIC_ECO_ADMIN !== true || !function_exists('panicRechargeAdminAllowed') || !panicRechargeAdminAllowed()) { http_response_code(403); exit('Acceso no autorizado.'); }
function panicEcoEscape($value) { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function panicEcoNumber($value, $decimals=0) { return $value === null ? '—' : number_format((float)$value, $decimals, ',', '.'); }
function panicEcoDate($value) { if (!$value) return '—'; if ($value instanceof DateTimeInterface) return $value->format('d/m/Y H:i'); try { return (new DateTimeImmutable((string)$value))->format('d/m/Y H:i'); } catch (Throwable $e) { return '—'; } }
function panicEcoUrl(array $query, array $changes=[]) { $args=array_merge($query, $changes); return __BASE_URL__.'usercp/shopadmin/?'.http_build_query($args, '', '&', PHP_QUERY_RFC3986).'#economia'; }
function panicEcoChange($value, $previous) { if ($value===null || $previous===null || (float)$previous==0.0) return '— · sin base comparable'; $pct=((float)$value-(float)$previous)/abs((float)$previous)*100; return ($pct>0?'+':'').panicEcoNumber($pct,1).'% vs. período anterior'; }
function panicEcoCards($cards) { echo '<div class="eco-cards">'; foreach ($cards as $card) { echo '<article class="eco-card"><strong>'.panicEcoEscape(($card[3]??'').panicEcoNumber($card[1], $card[4]??0)).'</strong><span>'.panicEcoEscape($card[0]).'</span>'; if (array_key_exists(2,$card)) echo '<small>'.panicEcoEscape(is_string($card[2]) && !is_numeric($card[2])?$card[2]:panicEcoChange($card[1],$card[2])).'</small>'; echo '</article>'; } echo '</div>'; }
function panicEcoTable($title, $columns, $rows, $options=[]) {
    echo '<section class="eco-panel"><h3>'.panicEcoEscape($title).'</h3>';
    if (!$rows) { echo '<p class="eco-empty">Sin datos en este período</p></section>'; return; }
    echo '<div class="eco-table-scroll"><table class="eco-table" data-eco-table><thead><tr>'; foreach ($columns as $label) echo '<th>'.panicEcoEscape($label).'</th>'; echo '</tr></thead><tbody>';
    foreach ($rows as $row) { $tone=isset($options['tone'])?call_user_func($options['tone'],$row):''; echo '<tr'.($tone?' class="'.panicEcoEscape($tone).'"':'').'>'; foreach ($columns as $key=>$label) { $v=$row[$key]??null; $numeric=in_array($key,$options['numbers']??[],true); echo '<td'.(($numeric || in_array($key,$options['dates']??[],true)) && $v!==null?' data-order="'.panicEcoEscape($v).'"':'').'>'; if (isset($options['links'][$key])) echo '<a href="'.panicEcoEscape(call_user_func($options['links'][$key],$row)).'">'.panicEcoEscape($v).'</a>'; elseif ($numeric) echo panicEcoNumber($v,in_array($key,$options['decimals']??[],true)?2:0); elseif (in_array($key,$options['dates']??[],true)) echo panicEcoEscape(panicEcoDate($v)); else echo panicEcoEscape($v===null?'—':$v); echo '</td>'; } echo '</tr>'; }
    echo '</tbody></table></div>'; if (!empty($options['note'])) echo '<p class="eco-note">'.panicEcoEscape($options['note']).'</p>'; echo '</section>';
}
function panicEcoChart($title, $type, $rows, $series, $options=[]) {
    echo '<section class="eco-panel"><h3>'.panicEcoEscape($title).'</h3>';
    if (!$rows) { echo '<p class="eco-empty">Sin datos en este período</p></section>'; return; }
    $payload=['type'=>$type,'rows'=>$rows,'series'=>$series,'options'=>$options];
    echo '<div class="eco-chart"><canvas aria-label="'.panicEcoEscape($title).'" role="img"></canvas></div><script type="application/json" class="eco-chart-data">'.json_encode($payload,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE).'</script>';
    echo '<details class="eco-chart-values"><summary>Ver valores del gráfico</summary><div class="eco-table-scroll"><table class="eco-table"><thead><tr><th>Período</th>'; foreach($series as $key=>$label) echo '<th>'.panicEcoEscape($label).'</th>'; echo '</tr></thead><tbody>'; foreach($rows as $r) { echo '<tr><td>'.panicEcoEscape($r['Periodo']).'</td>'; foreach($series as $key=>$label) echo '<td>'.panicEcoNumber($r[$key]??null).'</td>'; echo '</tr>'; } echo '</tbody></table></div></details></section>';
}
