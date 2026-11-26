@extends('layouts.app')
@section('titulo', $e->exists ? $e->nombre : 'Nueva empresa')
@section('contenido')
<header class="top"><div><h1>{{ $e->exists ? $e->nombre : 'Nueva empresa' }}</h1>
@if($e->exists && ($f = $e->datosFaltantes()))<p class="sub">Faltan: {{ implode(', ', $f) }}</p>@endif</div>
<a class="btn" href="{{ route('empresas.index') }}">Volver</a></header>
<form class="card" method="post" action="{{ $e->exists ? route('empresas.update', $e) : route('empresas.store') }}">
  @csrf @if($e->exists) @method('put') @endif
  <div class="form">
    <label for="nombre" class="full">Nombre<input id="nombre" name="nombre" value="{{ old('nombre', $e->nombre) }}" required></label>
    <label for="tipo_documento">Tipo de identificación<select id="tipo_documento" name="tipo_documento"><option value="NIT" @selected(old('tipo_documento', $e->tipo_documento) === 'NIT')>NIT</option><option value="CC" @selected(old('tipo_documento', $e->tipo_documento) === 'CC')>Cédula de ciudadanía</option></select></label>
    <label for="documento">Número<input id="documento" name="documento" value="{{ old('documento', $e->documento) }}" required></label>
    <label for="sede_id">Sede a la que pertenece<select id="sede_id" name="sede_id" required><option value="">Selecciona</option>@foreach($sedes as $s)<option value="{{ $s->id }}" @selected(old('sede_id', $e->sede_id) == $s->id)>{{ $s->nombre }}</option>@endforeach</select></label>
    <label for="ciiu">Código CIIU<input id="ciiu" name="ciiu" value="{{ old('ciiu', $e->ciiu) }}"></label>
    <label for="actividad_economica" class="full">Actividad económica<input id="actividad_economica" name="actividad_economica" value="{{ old('actividad_economica', $e->actividad_economica) }}"></label>
    <label for="direccion">Dirección<input id="direccion" name="direccion" value="{{ old('direccion', $e->direccion) }}"></label>
    <label for="telefono">Teléfono<input id="telefono" name="telefono" value="{{ old('telefono', $e->telefono) }}"></label>
    <label for="email">Correo<input id="email" type="email" name="email" value="{{ old('email', $e->email) }}"></label>
    <div class="full"><b style="font-size:12px;color:var(--muted)">ENTIDADES HABITUALES (se preseleccionan al afiliar)</b>
      <div class="form" style="margin-top:6px">@foreach($entidades as $tipo => $lista)<label>{{ $tipo }}
        <select name="entidades[]" multiple size="4" aria-label="{{ $tipo }}">@foreach($lista as $en)<option value="{{ $en->id }}" @selected(in_array($en->id, old('entidades', $e->exists ? $e->entidades->pluck('id')->all() : [])))>{{ $en->nombre }}</option>@endforeach</select></label>@endforeach</div>
      <p class="note">Usa Ctrl o Cmd para elegir varias.</p></div>
    <label class="check"><input type="hidden" name="activa" value="0"><input type="checkbox" name="activa" value="1" @checked(old('activa', $e->activa))> Empresa activa</label>
  </div>
  <div><button class="btn pri" type="submit">{{ $e->exists ? 'Guardar cambios' : 'Crear empresa' }}</button></div>
</form>
@if($e->exists)
<section class="card"><h2>Cargos</h2>
  @if($e->cargos->isEmpty())<p class="note">Agrega los cargos para que el riesgo ARL se asigne solo al afiliar.</p>@else
  <div class="tbl"><table><thead><tr><th>Cargo</th><th>Riesgo ARL</th><th></th></tr></thead><tbody>
  @foreach($e->cargos as $c)<tr><td>{{ $c->nombre }}</td><td>Riesgo {{ $c->riesgo_arl }}</td><td class="num"><form method="post" action="{{ route('cargos.destroy', $c) }}">@csrf @method('delete')<button class="btn sm danger" type="submit">Quitar</button></form></td></tr>@endforeach
  </tbody></table></div>@endif
  <form method="post" action="{{ route('empresas.cargos.store', $e) }}" class="filters">@csrf
    <input name="nombre" id="cargo_nombre" placeholder="Nombre del cargo" required aria-label="Nombre del cargo">
    <select name="riesgo_arl" id="cargo_riesgo" aria-label="Riesgo ARL">@for($r = 1; $r <= 5; $r++)<option value="{{ $r }}">Riesgo {{ $r }}</option>@endfor</select>
    <button class="btn" type="submit">Agregar cargo</button>
  </form>
</section>
@endif
@endsection
