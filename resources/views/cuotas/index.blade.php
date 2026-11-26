@extends('layouts.app')
@section('titulo', 'Cuotas del mes')
@section('contenido')
<header class="top"><div><h1>Cuotas de {{ $periodo->translatedFormat('F Y') }}</h1><p class="sub">Lo que debe pagar cada cliente este mes y si ya pagó.</p></div>
<form method="post" action="{{ route('cuotas.generar') }}" class="filters">@csrf
  <input type="hidden" name="periodo" value="{{ $periodo->format('Y-m') }}">
  <button class="btn pri" type="submit">Generar cuotas del mes</button>
</form></header>
<div class="kpis">
  <div class="kpi"><span class="l">Total del mes</span><span class="v" style="font-size:21px">{{ pesos($resumen['total']) }}</span></div>
  <a class="kpi ok" href="{{ request()->fullUrlWithQuery(['estado' => 'pagada']) }}"><span class="l">Recaudado</span><span class="v" style="font-size:21px">{{ pesos($resumen['recaudado']) }}</span><span class="d">{{ $resumen['pagadas'] }} pagadas</span></a>
  <a class="kpi warn" href="{{ request()->fullUrlWithQuery(['estado' => 'pendiente']) }}"><span class="l">Pendientes</span><span class="v">{{ $resumen['pendientes'] }}</span></a>
  <a class="kpi bad" href="{{ request()->fullUrlWithQuery(['estado' => 'vencida']) }}"><span class="l">Vencidas</span><span class="v">{{ $resumen['vencidas'] }}</span></a>
</div>
<section class="card">
  <form class="filters" method="get">
    <input type="month" name="periodo" id="periodo" value="{{ $periodo->format('Y-m') }}" aria-label="Periodo">
    <input name="q" id="q" value="{{ request('q') }}" placeholder="Cliente o documento">
    <select name="estado" id="estado"><option value="">Todos los estados</option>@foreach(\App\Models\Cuota::ESTADOS as $k => [$t])<option value="{{ $k }}" @selected(request('estado') === $k)>{{ $t }}</option>@endforeach</select>
    <select name="sede" id="sede"><option value="">Todas las sedes</option>@foreach($sedes as $s)<option value="{{ $s->id }}" @selected(request('sede') == $s->id)>{{ $s->nombre }}</option>@endforeach</select>
    <button class="btn" type="submit">Filtrar</button>
  </form>
  @if($items->isEmpty())<div class="empty">No hay cuotas para este mes. Usa «Generar cuotas del mes» para crearlas a partir de las ventas activas.</div>@else
  <div class="tbl"><table>
    <thead><tr><th>Cliente</th><th>Plan</th><th>Vence</th><th class="num">Aportes</th><th class="num">Administración</th><th class="num">Total</th><th>Estado</th><th>Recibo</th></tr></thead>
    <tbody>@foreach($items as $c)
      <tr><td><a href="{{ route('ventas.show', $c->venta_id) }}">{{ $c->venta->nombre_cliente }}</a><br><small class="mono">{{ $c->venta->documento }}</small></td>
      <td>{{ $c->venta->plan->nombre }}</td><td class="nowrap">{{ $c->fecha_limite->format('d/m/Y') }}</td>
      <td class="num">{{ pesos($c->aportes) }}</td><td class="num">{{ pesos($c->administracion) }}</td><td class="num"><b>{{ pesos($c->valor) }}</b></td>
      <td>{{ pill($c->estado_visible, \App\Models\Cuota::ESTADOS) }}</td>
      <td>@if($c->reciboVigente)<a class="mono" href="{{ route('recibos.show', $c->reciboVigente) }}">{{ $c->reciboVigente->numero }}</a>@elseif($c->estado === 'pendiente')<form method="post" action="{{ route('cuotas.recibo', $c) }}">@csrf<button class="btn sm" type="submit">Generar recibo</button></form>@else — @endif</td></tr>
    @endforeach</tbody></table></div>@endif
  @include('partials.paginacion', ['p' => $items])
</section>
@endsection
