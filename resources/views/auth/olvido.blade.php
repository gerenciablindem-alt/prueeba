@extends('layouts.auth')
@section('titulo', 'Recuperar contraseña')
@section('contenido')
<h1>Recuperar contraseña</h1>
<p class="sub">Escribe tu correo y te enviaremos un enlace para crear una nueva.</p>
<form method="post" action="{{ route('password.email') }}" style="display:flex;flex-direction:column;gap:12px">
  @csrf
  <label for="email">Correo electrónico<input id="email" name="email" type="email" value="{{ old('email') }}" required></label>
  @error('email')<span class="err">{{ $message }}</span>@enderror
  <button class="btn pri" type="submit" style="justify-content:center">Enviar enlace</button>
  <a href="{{ route('login') }}" style="font-size:13px">Volver a iniciar sesión</a>
</form>
@endsection
