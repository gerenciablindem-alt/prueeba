@extends('layouts.app')
@section('titulo', 'Sedes y empresas')
@section('contenido')
<header class="top"><div><h1>Sedes y empresas</h1><p class="sub">{{ $empresas->count() }} empresas en {{ $sedes->count() }} sedes.</p></div>
<div class="filters"><a class="btn pri" href="{{ route('empresas.create') }}">+ Nueva empresa</a></div></header>
<section class="card">
  <form class="filters" method="get">
    <input name="q" id="q" value="{{ request('q') }}" placeholder="Nombre o NIT">
    <select name="sede_id" id="sede_id"><option value="">Todas las sedes</option>@foreach($sedes as $s)<option value="{{ $s->id }}" @selected(request('sede_id') == $s->id)>{{ $s->nombre }}</option>@endforeach</select>
    <select name="estado" id="estado"><option value="">Todos los estados</option><option value="activas" @selected(request('estado') === 'activas')>Activas</option><option value="inactivas" @selected(request('estado') === 'inactivas')>Inactivas</option></select>
    <select name="orden" id="orden"><option value="">Más recientes</option><option value="nombre" @selected(request('orden') === 'nombre')>Nombre (A-Z)</option></select>
    <label class="check"><input type="checkbox" name="faltantes" value="1" @checked(request('faltantes'))> Solo con datos faltantes</label>
    <button class="btn" type="submit">Filtrar</button>
  </form>
  <div class="tbl"><table>
    <thead><tr><th>Empresa</th><th>NIT</th><th>Sede</th><th class="num">Afiliados</th><th class="num">Cargos</th><th class="num">Entidades</th><th>Datos</th><th>Estado</th></tr></thead>
    <tbody>@forelse($empresas as $e)@php($f = $e->datosFaltantes())
      <tr><td><a href="{{ route('empresas.edit', $e) }}"><b>{{ $e->nombre }}</b></a><br><small>{{ $e->actividad_economica }}</small></td><td class="mono">{{ $e->tipo_documento }} {{ $e->documento }}</td><td>{{ $e->sede->nombre }}</td>
      <td class="num">{{ $e->afiliados_count }}</td><td class="num">{{ $e->cargos_count }}</td><td class="num">{{ $e->entidades_count }}</td>
      <td>@if($f)<span class="pill p-warn" title="{{ implode(', ', $f) }}">Faltan {{ count($f) }}</span>@else<span class="pill p-ok">Completos</span>@endif</td>
      <td>{!! $e->activa ? '<span class="pill p-ok">Activa</span>' : '<span class="pill p-mute">Inactiva</span>' !!}</td></tr>
    @empty<tr><td colspan="8" class="empty">No hay empresas con estos filtros.</td></tr>@endforelse</tbody>
  </table></div>
</section>
<details class="card dd" @if($errors->hasAny(['departamento','ciudad'])) open @endif>
  <summary><h2 style="display:inline">Sedes</h2> <span class="note">· toca para crear una sede</span></summary>
  <div class="tbl" style="margin-top:12px"><table><thead><tr><th>Sede</th><th>Documento</th><th>Ciudad</th><th class="num">Empresas</th></tr></thead>
  <tbody>@foreach($sedes as $s)<tr><td>{{ $s->nombre }}</td><td class="mono">{{ $s->tipo_documento }} {{ $s->documento }}</td><td>{{ $s->ciudad }}, {{ $s->departamento }}</td><td class="num">{{ $s->empresas_count }}</td></tr>@endforeach</tbody></table></div>
  <form method="post" action="{{ route('sedes.store') }}" class="form" style="margin-top:12px">@csrf
    <label for="s_nombre">Nombre<input id="s_nombre" name="nombre" required></label>
    <label for="s_tipo">Tipo de documento<select id="s_tipo" name="tipo_documento"><option>NIT</option><option value="CC">Cédula de ciudadanía</option></select></label>
    <label for="s_doc">Documento<input id="s_doc" name="documento" required></label>
    <label for="s_dep">Departamento<select id="s_dep" name="departamento" required><option value="">Selecciona</option>@foreach($departamentos as $d)<option>{{ $d }}</option>@endforeach</select></label>
    <label for="s_ciudad">Ciudad<input id="s_ciudad" name="ciudad" required></label>
    <div style="align-self:end"><button class="btn pri" type="submit">Crear sede</button></div>
  </form>
</details>
@endsection
