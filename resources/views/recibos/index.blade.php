@extends('layouts.app')
@section('titulo', 'Recibos')
@section('contenido')
<header class="top"><div><h1>Recibos de caja</h1><p class="sub">Recibos emitidos por cuota, con su pago.</p></div></header>
<section class="card">
  <form class="filters" method="get">
    <input name="q" id="q" value="{{ request('q') }}" placeholder="Número, cliente o documento">
    <select name="estado" id="estado"><option value="">Todos los estados</option>@foreach(\App\Models\Recibo::ESTADOS as $k => [$t])<option value="{{ $k }}" @selected(request('estado') === $k)>{{ $t }}</option>@endforeach</select>
    <button class="btn" type="submit">Filtrar</button>
  </form>
  @if($items->isEmpty())<div class="empty">No hay recibos. Se generan desde las cuotas del mes.</div>@else
  <div class="tbl"><table>
    <thead><tr><th>Número</th><th>Cliente</th><th>Periodo</th><th class="num">Valor</th><th>Pago</th><th>Estado</th></tr></thead>
    <tbody>@foreach($items as $r)
      <tr><td><a class="mono" href="{{ route('recibos.show', $r) }}">{{ $r->numero }}</a><br><small>{{ $r->created_at->format('d/m/Y') }}</small></td>
      <td>{{ $r->venta->nombre_cliente }}<br><small class="mono">{{ $r->venta->documento }}</small></td>
      <td>{{ $r->cuota->periodo_nombre }}</td><td class="num">{{ pesos($r->valor) }}</td>
      <td>@if($r->fecha_pago){{ $r->fecha_pago->format('d/m/Y') }}<br><small>{{ \App\Models\Recibo::MEDIOS[$r->medio_pago] ?? $r->medio_pago }}</small>@else — @endif</td>
      <td>{{ pill($r->estado, \App\Models\Recibo::ESTADOS) }}</td></tr>
    @endforeach</tbody></table></div>@endif
  @include('partials.paginacion', ['p' => $items])
</section>
@endsection
