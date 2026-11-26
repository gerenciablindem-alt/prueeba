@if($items->isEmpty())
  <div class="empty">No hay radicados con estos filtros.</div>
@else
<div class="tbl"><table>
  <thead><tr><th>Radicado</th><th>Afiliado</th><th>Empresa</th><th>Entidad</th><th>Estado</th><th class="num">Días</th><th>Fecha</th></tr></thead>
  <tbody>
  @foreach($items as $p)
    <tr>
      <td><a class="mono" href="{{ route('preafiliaciones.show', $p) }}">{{ $p->radicado }}</a></td>
      <td>{{ $p->afiliado->nombre_completo }}<br><small class="mono">{{ $p->afiliado->tipo_documento }} {{ $p->afiliado->documento }}</small></td>
      <td>{{ $p->empresa->nombre }}</td>
      <td class="nowrap"><span class="ent">{{ $p->tipo }}</span>{{ $p->entidad->nombre }}</td>
      <td>{{ pill($p->estado, \App\Models\Preafiliacion::ESTADOS) }}</td>
      <td class="num">{{ in_array($p->estado, ['aprobado', 'rechazado']) ? '—' : $p->diasEnEstado() }}</td>
      <td class="nowrap">{{ $p->created_at->format('d/m/Y') }}</td>
    </tr>
  @endforeach
  </tbody>
</table></div>
@endif
