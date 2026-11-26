@extends('layouts.auth')
@section('titulo', 'Nueva contraseña')
@section('contenido')
<h1>Nueva contraseña</h1>
<form method="post" action="{{ route('password.update') }}" style="display:flex;flex-direction:column;gap:12px">
  @csrf
  <input type="hidden" name="token" value="{{ $token }}">
  <label for="email">Correo electrónico<input id="email" name="email" type="email" value="{{ old('email', $email) }}" required></label>
  <label for="password">Nueva contraseña (mínimo 8 caracteres)<input id="password" name="password" type="password" required autocomplete="new-password"></label>
  <label for="password_confirmation">Repite la contraseña<input id="password_confirmation" name="password_confirmation" type="password" required></label>
  @if($errors->any())<span class="err">{{ $errors->first() }}</span>@endif
  <button class="btn pri" type="submit" style="justify-content:center">Guardar contraseña</button>
</form>
@endsection
