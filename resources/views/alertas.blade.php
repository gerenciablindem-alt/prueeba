@extends('layouts.app')
@section('titulo', 'Alertas')
@section('contenido')
<header class="top"><div><h1>Alertas</h1><p class="sub">{{ $alertas->count() }} cosas por resolver, de la más urgente a la menos urgente.</p></div></header>
@include('partials.alertas', ['lista' => $alertas])
@endsection
