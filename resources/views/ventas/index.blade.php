@extends('layouts.app')
@section('titulo', 'Ventas')
@section('contenido')
<header class="top"><div><h1>Ventas</h1><p class="sub">Planes de seguridad social vendidos a independientes.</p></div>
<a class="btn pri" href="{{ route('ventas.create') }}">+ Nueva venta</a></header>
<div class="kpis">
  <a class="kpi ok" href="?estado=activa"><span class="l">Ventas activas</span><span class="v">{{ $activas }}</span></a>
  <div class="kpi"><span class="l">Facturación mensual</span><span class="v" style="font-size:21px">{{ pesos($ingresoMensual) }}</span><span class="d">aportes + administración</span></div>
  <div class="kpi"><span class="l">Administración mensual</span><span class="v" style="font-size:21px">{{ pesos($administracionMensual) }}</span><span class="d">ingreso propio</span></div>
</div>
<section class="card">
  <form class="filters" method="get">
    <input name="q" id="q" value="{{ request('q') }}" placeholder="Cliente o documento">
    <select name="estado" id="estado"><option value="">Todos los estados</option>@foreach(\App\Models\Venta::ESTADOS as $k => [$t])<option value="{{ $k }}" @selected(request('estado') === $k)>{{ $t }}</option>@endforeach</select>
    <select name="plan" id="plan"><option value="">Todos los planes</option>@foreach($planes as $p)<option value="{{ $p->id }}" @selected(request('plan') == $p->id)>{{ $p->nombre }}</option>@endforeach</select>
    <button class="btn" type="submit">Filtrar</button>
  </form>
  @if($items->isEmpty())<div class="empty">No hay ventas con estos filtros.</div>@else
  <div class="tbl"><table>
    <thead><tr><th>#</th><th>Cliente</th><th>Plan</th><th>Riesgo</th><th>Sede</th><th>Desde</th><th class="num">Valor mensual</th><th class="num">Cuotas pendientes</th><th>Estado</th></tr></thead>
    <tbody>@foreach($items as $v)
      <tr><td><a class="mono" href="{{ route('ventas.show', $v) }}">#{{ $v->id }}</a></td>
      <td><a href="{{ route('ventas.show', $v) }}">{{ $v->nombre_cliente }}</a><br><small class="mono">{{ $v->tipo_documento }} {{ $v->documento }}</small></td>
      <td>{{ $v->plan->nombre }}</td><td>{{ \App\Models\Venta::NIVELES[$v->nivel_riesgo] }}</td><td>{{ $v->sede->nombre }}</td>
      <td class="nowrap">{{ ucfirst($v->periodo_inicial->translatedFormat('M Y')) }}</td>
      <td class="num">{{ pesos($v->valor_total) }}</td>
      <td class="num">{{ $v->cuotas_pendientes ?: '—' }}</td>
      <td>{{ pill($v->estado, \App\Models\Venta::ESTADOS) }}</td></tr>
    @endforeach</tbody></table></div>@endif
  @include('partials.paginacion', ['p' => $items])
</section>
@endsection
