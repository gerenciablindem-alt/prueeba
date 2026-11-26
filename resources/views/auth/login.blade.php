@extends('layouts.auth')
@section('titulo', 'Iniciar sesión')
@section('contenido')
<h1>Iniciar sesión</h1>
<form method="post" action="{{ url('/login') }}" style="display:flex;flex-direction:column;gap:12px">
  @csrf
  <label for="email">Correo electrónico<input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"></label>
  <label for="password">Contraseña<input id="password" name="password" type="password" required autocomplete="current-password"></label>
  @error('email')<span class="err">{{ $message }}</span>@enderror
  <label class="check"><input type="checkbox" name="recordar" value="1"> Recordarme en este equipo</label>
  <button class="btn pri" type="submit" style="justify-content:center">Entrar</button>
  <a href="{{ route('password.request') }}" style="font-size:13px">¿Olvidaste tu contraseña?</a>
</form>
@endsection
