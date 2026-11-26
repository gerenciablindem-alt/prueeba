@php($colores = ['Empleador' => 'var(--accent)', 'EPS' => 'var(--brand)', 'ARL' => 'var(--info)', 'Fondo de pensiones' => 'var(--warn)'])
<div><span class="note">Se puede recobrar</span><div class="total">{{ pesos($calc['recobro']) }}</div><span class="note">El empleador asume {{ pesos($calc['empleador']) }} · total {{ pesos($calc['total']) }}</span></div>
<div class="split">@foreach($calc['tramos'] as $t)<i style="width:{{ $calc['total'] ? $t['valor'] / $calc['total'] * 100 : 0 }}%;background:{{ $colores[$t['paga']] }}"></i>@endforeach</div>
<div class="tbl"><table>
  <thead><tr><th>Paga</th><th>Días</th><th class="num">% del IBC</th><th class="num">Valor</th></tr></thead>
  <tbody>@foreach($calc['tramos'] as $t)<tr><td><span class="legend"><span style="--c:{{ $colores[$t['paga']] }}">{{ $t['paga'] }}</span></span></td><td>{{ $t['desde'] }}–{{ $t['hasta'] }} ({{ $t['dias'] }})</td><td class="num">{{ round($t['porcentaje'] * 100, 2) }}%</td><td class="num">{{ pesos($t['valor']) }}</td></tr>@endforeach</tbody>
</table></div>
