@extends('layouts.app')
@section('titulo', 'Consulta de afiliados')
@section('contenido')
<header class="top"><div><h1>Consulta de afiliados</h1><p class="sub">Busca a una persona y mira en qué entidades está afiliada.</p></div></header>
<section class="card">
  <form class="filters" method="get"><input name="q" id="q" value="{{ $s }}" placeholder="Documento o nombre" autofocus><button class="btn" type="submit">Buscar</button></form>
  @if($items->isEmpty())<div class="empty">No encontramos afiliados{{ $s ? ' con "'.$s.'"' : '' }}.</div>@else
  <div class="tbl"><table>
    <thead><tr><th>Afiliado</th><th>Empresa</th>@foreach(['EPS','ARL','CCF','AFP'] as $t)<th>{{ $t }}</th>@endforeach</tr></thead>
    <tbody>@foreach($items as $a)@php($r = \App\Http\Controllers\ReporteController::resumen($a))
      <tr><td><a href="{{ route('afiliados.show', $a) }}">{{ $a->nombre_completo }}</a><br><small class="mono">{{ $a->tipo_documento }} {{ $a->documento }}</small></td><td>{{ $a->empresa->nombre }}</td>
      @foreach($r as $x)<td>@if($x){{ $x['entidad'] }}<br>{{ pill($x['estado'], \App\Models\Preafiliacion::ESTADOS) }}@else<span class="note">—</span>@endif</td>@endforeach</tr>
    @endforeach</tbody>
  </table></div>@endif
  @include('partials.paginacion', ['p' => $items])
</section>
@endsection
