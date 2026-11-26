@extends('layouts.app')
@section('titulo', 'Mi perfil')
@section('contenido')
<header class="top"><div><h1>Mi perfil</h1><p class="sub">{{ $u->email }} · {{ \App\Models\User::ROLES[$u->rol] }}</p></div></header>
<form class="card" method="post" action="{{ route('perfil.update') }}">
  @csrf @method('put')
  <div class="form">
    <label for="name">Nombre<input id="name" name="name" value="{{ old('name', $u->name) }}" required></label>
    <label for="password_actual">Contraseña actual<input id="password_actual" name="password_actual" type="password" autocomplete="current-password"></label>
    <span></span>
    <label for="password">Nueva contraseña<input id="password" name="password" type="password" autocomplete="new-password"></label>
    <label for="password_confirmation">Repite la nueva contraseña<input id="password_confirmation" name="password_confirmation" type="password"></label>
  </div>
  <p class="note">Deja los campos de contraseña vacíos si no la quieres cambiar.</p>
  <div><button class="btn pri" type="submit">Guardar</button></div>
</form>
@endsection
