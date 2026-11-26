@extends('layouts.app')
@section('titulo', 'Asistente IA')
@section('contenido')
<header class="top"><div><h1>Asistente IA</h1><p class="sub">Haz preguntas en lenguaje natural sobre afiliaciones, incapacidades, ventas y cuotas.</p></div></header>
<section class="card chat-card">
  @include('asistente._chat', ['historial' => $historial, 'compacto' => false])
</section>
@endsection
