@extends('layouts.app')
@section('titulo', 'Radicados')
@section('contenido')
<header class="top">
  <div><h1>Radicados</h1><p class="sub">Pre-afiliaciones con su estado y los días que llevan en él.</p></div>
  <div class="filters">
    <a class="btn" href="{{ route('reportes.exportar', request()->only('estado', 'empresa_id', 'q')) }}">Exportar a Excel</a>
    <a class="btn pri" href="{{ route('preafiliaciones.create') }}">+ Nueva</a>
  </div>
</header>
<section class="card">
  <div class="filters">
    <a @class(['chip', 'on' => !request('estado')]) href="{{ request()->fullUrlWithQuery(['estado' => null, 'page' => null]) }}">Todos<span class="c">{{ $conteoEstado->sum() }}</span></a>
    @foreach(\App\Models\Preafiliacion::ESTADOS as $k => [$txt])
      <a @class(['chip', 'on' => request('estado') === $k]) href="{{ request()->fullUrlWithQuery(['estado' => $k, 'page' => null]) }}">{{ $txt }}<span class="c">{{ $conteoEstado[$k] ?? 0 }}</span></a>
    @endforeach
  </div>
  <form class="filters" method="get">
    <input type="hidden" name="estado" value="{{ request('estado') }}">
    <input name="q" id="q" value="{{ request('q') }}" placeholder="Nombre, documento o radicado">
    <select name="tipo" id="tipo"><option value="">Todas las entidades</option>@foreach(['EPS','ARL','CCF','AFP'] as $t)<option value="{{ $t }}" @selected(request('tipo') === $t)>{{ $t }} ({{ $conteoTipo[$t] ?? 0 }})</option>@endforeach</select>
    @if(!auth()->user()->esEmpresa())
    <select name="empresa_id" id="empresa_id"><option value="">Todas las empresas</option>@foreach($empresas as $em)<option value="{{ $em->id }}" @selected(request('empresa_id') == $em->id)>{{ $em->nombre }}</option>@endforeach</select>
    @endif
    <button class="btn" type="submit">Filtrar</button>
    @if(request()->hasAny(['q','tipo','empresa_id','estado']))<a href="{{ route('preafiliaciones.index') }}">Limpiar</a>@endif
  </form>
  @include('preafiliaciones._tabla', ['items' => $items])
  @include('partials.paginacion', ['p' => $items])
</section>
@endsection
