@extends('layouts.app')
@section('titulo', 'Incapacidades')
@section('contenido')
<header class="top"><div><h1>Incapacidades</h1><p class="sub">Quién paga, cuánto se recobra y en qué va cada trámite.</p></div>
<a class="btn pri" href="{{ route('incapacidades.create') }}">+ Registrar incapacidad</a></header>
<div class="kpis">
  <a class="kpi warn" href="?estado=por_radicar"><span class="l">Por radicar</span><span class="v">{{ $conteo['por_radicar'] ?? 0 }}</span></a>
  <a class="kpi" href="?estado=radicada"><span class="l">Por recobrar</span><span class="v" style="font-size:21px">{{ pesos($porRecobrar) }}</span><span class="d">radicadas y aprobadas</span></a>
  <a class="kpi bad" href="?estado=rechazada"><span class="l">Rechazadas</span><span class="v">{{ $conteo['rechazada'] ?? 0 }}</span><span class="d">{{ pesos($rechazado) }}</span></a>
  <a class="kpi ok" href="?estado=pagada"><span class="l">Pagadas</span><span class="v">{{ $conteo['pagada'] ?? 0 }}</span><span class="d">{{ pesos($pagado) }} recibidos</span></a>
</div>
<section class="card">
  <form class="filters" method="get">
    <input name="q" id="q" value="{{ request('q') }}" placeholder="Empleado, documento o código">
    <select name="estado" id="estado"><option value="">Todos los estados</option>@foreach(\App\Models\Incapacidad::ESTADOS as $k => [$t])<option value="{{ $k }}" @selected(request('estado') === $k)>{{ $t }}</option>@endforeach</select>
    <select name="tipo" id="tipo"><option value="">Todos los tipos</option>@foreach(\App\Models\Incapacidad::TIPOS as $k => $t)<option value="{{ $k }}" @selected(request('tipo') === $k)>{{ $t }}</option>@endforeach</select>
    <button class="btn" type="submit">Filtrar</button>
  </form>
  @if($items->isEmpty())<div class="empty">No hay incapacidades registradas con estos filtros.</div>@else
  <div class="tbl"><table>
    <thead><tr><th>Caso</th><th>Empleado</th><th>Empresa</th><th>Tipo</th><th>Fechas</th><th class="num">Días</th><th>Cobrar a</th><th class="num">A recobrar</th><th>Estado</th></tr></thead>
    <tbody>@foreach($items as $i)
      <tr><td><a class="mono" href="{{ route('incapacidades.show', $i) }}">{{ $i->codigo }}</a></td>
      <td>{{ $i->afiliado->nombre_completo }}@if($i->prorroga_de_id) <span class="pill p-info">prórroga</span>@endif<br><small class="mono">{{ $i->afiliado->documento }}</small></td>
      <td>{{ $i->empresa->nombre }}</td><td>{{ $i->tipo_nombre }}</td>
      <td class="nowrap">{{ $i->fecha_inicio->format('d/m/Y') }}<br><small>al {{ $i->fecha_fin->format('d/m/Y') }}</small></td>
      <td class="num">{{ $i->dias }}@if($i->dias_acumulados > $i->dias)<br><small>{{ $i->dias_acumulados }} acum.</small>@endif</td>
      <td>{{ $i->entidad?->nombre ?? '—' }}</td><td class="num">{{ pesos($i->valor_recobro) }}</td>
      <td>{{ pill($i->estado, \App\Models\Incapacidad::ESTADOS) }}</td></tr>
    @endforeach</tbody></table></div>@endif
  @include('partials.paginacion', ['p' => $items])
</section>
@endsection
