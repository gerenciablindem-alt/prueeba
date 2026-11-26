@extends('layouts.app')
@section('titulo', 'Reportes')
@section('contenido')
<header class="top"><div><h1>Reportes</h1><p class="sub">Estado de cada afiliado por entidad, con filtros y descarga.</p></div>
<a class="btn pri" href="{{ route('reportes.exportar', request()->query()) }}">Descargar Excel (CSV)</a></header>
<div class="kpis" style="grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
  @foreach($stats as $titulo => $serie)@php($mx = max(1, $serie->max() ?? 1))
  <section class="card"><h2>{{ $titulo }}</h2><div class="bars">
    @forelse($serie as $k => $n)<div class="bar"><span class="lbl" title="{{ $k }}">{{ $k }}</span><span class="t"><i style="width:{{ $n / $mx * 100 }}%"></i></span><span class="num">{{ $n }}</span></div>@empty<p class="note">Sin datos.</p>@endforelse
  </div></section>
  @endforeach
</div>
<section class="card">
  <form class="filters" method="get">
    <input name="q" id="q" value="{{ request('q') }}" placeholder="Documento o nombre">
    @if(!auth()->user()->esEmpresa())<select name="empresa_id" id="empresa_id"><option value="">Todas las empresas</option>@foreach($empresas as $em)<option value="{{ $em->id }}" @selected(request('empresa_id') == $em->id)>{{ $em->nombre }}</option>@endforeach</select>@endif
    <select name="estado" id="estado"><option value="">Cualquier estado</option>@foreach(\App\Models\Preafiliacion::ESTADOS as $k => [$t])<option value="{{ $k }}" @selected(request('estado') === $k)>{{ $t }}</option>@endforeach</select>
    <label class="check">Desde <input type="date" name="desde" id="desde" value="{{ request('desde') }}"></label>
    <label class="check">Hasta <input type="date" name="hasta" id="hasta" value="{{ request('hasta') }}"></label>
    <button class="btn" type="submit">Filtrar</button>
  </form>
  @if($items->isEmpty())<div class="empty">Sin resultados.</div>@else
  <div class="tbl"><table>
    <thead><tr><th>Afiliado</th><th>Empresa</th>@foreach(['EPS','ARL','CCF','AFP'] as $t)<th>{{ $t }}</th>@endforeach<th>Última fecha</th></tr></thead>
    <tbody>@foreach($items as $a)@php($r = \App\Http\Controllers\ReporteController::resumen($a))
      <tr><td><a href="{{ route('afiliados.show', $a) }}">{{ $a->nombre_completo }}</a><br><small class="mono">{{ $a->documento }}</small></td><td>{{ $a->empresa->nombre }}</td>
      @foreach($r as $x)<td>@if($x){{ $x['entidad'] }}<br>{{ pill($x['estado'], \App\Models\Preafiliacion::ESTADOS) }}@else<span class="note">—</span>@endif</td>@endforeach
      <td class="nowrap">{{ optional($a->preafiliaciones->first())->created_at?->format('d/m/Y') }}</td></tr>
    @endforeach</tbody></table></div>@endif
  @include('partials.paginacion', ['p' => $items])
</section>
@endsection
