@extends('layouts.app')
@section('titulo', $u->exists ? $u->name : 'Nuevo usuario')
@section('contenido')
<header class="top"><div><h1>{{ $u->exists ? $u->name : 'Nuevo usuario' }}</h1></div><a class="btn" href="{{ route('usuarios.index') }}">Volver</a></header>
<form class="card" method="post" action="{{ $u->exists ? route('usuarios.update', $u) : route('usuarios.store') }}">
  @csrf @if($u->exists) @method('put') @endif
  <div class="form">
    <label for="name">Nombre<input id="name" name="name" value="{{ old('name', $u->name) }}" required></label>
    <label for="email">Correo<input id="email" type="email" name="email" value="{{ old('email', $u->email) }}" required></label>
    <label for="rol">Rol<select id="rol" name="rol">@foreach(\App\Models\User::ROLES as $k => $v)<option value="{{ $k }}" @selected(old('rol', $u->rol) === $k)>{{ $v }}</option>@endforeach</select></label>
    <label for="sede_id">Sede<select id="sede_id" name="sede_id"><option value="">—</option>@foreach($sedes as $s)<option value="{{ $s->id }}" @selected(old('sede_id', $u->sede_id) == $s->id)>{{ $s->nombre }}</option>@endforeach</select></label>
    <label for="empresa_id">Empresa (solo para usuarios de empresa cliente)<select id="empresa_id" name="empresa_id"><option value="">—</option>@foreach($empresas as $e)<option value="{{ $e->id }}" @selected(old('empresa_id', $u->empresa_id) == $e->id)>{{ $e->nombre }}</option>@endforeach</select></label>
    <label for="password">{{ $u->exists ? 'Nueva contraseña (opcional)' : 'Contraseña inicial' }}<input id="password" type="password" name="password" autocomplete="new-password" {{ $u->exists ? '' : 'required' }}></label>
    <label class="check"><input type="checkbox" name="activo" value="1" @checked(old('activo', $u->activo))> Usuario activo</label>
  </div>
  <p class="note">Administrador: todo, incluida auditoría y usuarios. Operador: afiliaciones, traslados, incapacidades y empresas. Empresa cliente: solo ve y radica lo de su empresa.</p>
  <div><button class="btn pri" type="submit">Guardar</button></div>
</form>
@endsection
