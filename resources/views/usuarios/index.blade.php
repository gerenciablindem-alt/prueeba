@extends('layouts.app')
@section('titulo', 'Usuarios')
@section('contenido')
<header class="top"><div><h1>Usuarios</h1><p class="sub">Equipo interno y usuarios de las empresas cliente.</p></div><a class="btn pri" href="{{ route('usuarios.create') }}">+ Nuevo usuario</a></header>
<section class="card"><div class="tbl"><table>
  <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Sede / empresa</th><th>Estado</th></tr></thead>
  <tbody>@foreach($usuarios as $us)
    <tr><td><a href="{{ route('usuarios.edit', $us) }}">{{ $us->name }}</a></td><td>{{ $us->email }}</td><td>{{ \App\Models\User::ROLES[$us->rol] ?? $us->rol }}</td>
    <td>{{ $us->empresa?->nombre ?? $us->sede?->nombre ?? '—' }}</td><td>{!! $us->activo ? '<span class="pill p-ok">Activo</span>' : '<span class="pill p-mute">Inactivo</span>' !!}</td></tr>
  @endforeach</tbody>
</table></div></section>
@endsection
