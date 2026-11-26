@extends('layouts.app')
@section('titulo', 'Panel')
@section('contenido')
@php($e = $porEstado)
<header class="top">
  <div><h1>Hola, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}</h1><p class="sub">Esto es lo que necesita atención hoy, {{ now()->translatedFormat('l j \d\e F') }}.</p></div>
  <a class="btn pri" href="{{ route('preafiliaciones.create') }}">+ Nueva pre-afiliación</a>
</header>
<div class="kpis">
  <a class="kpi" href="{{ route('preafiliaciones.index') }}"><span class="l">Radicados</span><span class="v">{{ $total }}</span><span class="d">en total</span></a>
  <a class="kpi warn" href="{{ route('preafiliaciones.index', ['estado' => 'pendiente']) }}"><span class="l">Pendientes</span><span class="v">{{ ($e['pendiente'] ?? 0) + ($e['revision'] ?? 0) }}</span><span class="d">esperando entidad</span></a>
  <a class="kpi bad" href="{{ route('preafiliaciones.index', ['estado' => 'correccion']) }}"><span class="l">Por corregir</span><span class="v">{{ $e['correccion'] ?? 0 }}</span><span class="d">devueltos</span></a>
  <a class="kpi ok" href="{{ route('preafiliaciones.index', ['estado' => 'aprobado']) }}"><span class="l">Aprobadas</span><span class="v">{{ $e['aprobado'] ?? 0 }}</span><span class="d">{{ $total ? round(($e['aprobado'] ?? 0) / $total * 100) : 0 }}% del total</span></a>
  <a class="kpi" href="{{ route('incapacidades.index') }}"><span class="l">Incapacidades por cobrar</span><span class="v" style="font-size:21px">{{ pesos($porCobrar) }}</span><span class="d">{{ $incAbiertas }} casos abiertos</span></a>
</div>
<div class="grid2">
  <section class="card">
    <div class="hd"><h2>Atención hoy</h2><a class="btn sm" href="{{ route('alertas') }}">Ver las {{ $alertas->count() }}</a></div>
    @include('partials.alertas', ['lista' => $alertas->take(5)])
  </section>
  <section class="card">
    <div class="hd"><h2>Radicados por entidad</h2></div>
    @php($mx = max(1, $porTipo->max()))
    <div class="bars">
      @foreach($porTipo as $t => $n)
        <a class="bar" href="{{ route('preafiliaciones.index', ['tipo' => $t]) }}" style="color:inherit;text-decoration:none"><b class="lbl">{{ $t }}</b><span class="t"><i style="width:{{ $n / $mx * 100 }}%"></i></span><span class="num">{{ $n }}</span></a>
      @endforeach
    </div>
  </section>
</div>
<section class="card">
  <div class="hd"><h2>Radicados recientes</h2><a class="btn sm" href="{{ route('preafiliaciones.index') }}">Ver todos</a></div>
  @include('preafiliaciones._tabla', ['items' => $recientes])
</section>
@endsection
