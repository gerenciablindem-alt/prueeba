@extends('layouts.app')
@section('titulo', $p->radicado)
@section('contenido')
@php($a = $p->afiliado)
<header class="top">
  <div><h1><span class="mono" style="font-size:20px">{{ $p->radicado }}</span> {{ pill($p->estado, \App\Models\Preafiliacion::ESTADOS) }}</h1>
  <p class="sub">{{ $p->tipo }} · {{ $p->entidad->nombre }} · creado el {{ $p->created_at->format('d/m/Y') }}{{ $p->creador ? ' por '.$p->creador->name : '' }}</p></div>
  <a class="btn" href="{{ route('preafiliaciones.index') }}">Volver</a>
</header>
<div class="grid2">
  <section class="card">
    <h2>Afiliado</h2>
    <dl class="datos">
      <div><dt>Nombre</dt><dd><a href="{{ route('afiliados.show', $a) }}">{{ $a->nombre_completo }}</a></dd></div>
      <div><dt>Documento</dt><dd class="mono">{{ $a->tipo_documento }} {{ $a->documento }}</dd></div>
      <div><dt>Empresa</dt><dd>{{ $p->empresa->nombre }}</dd></div>
      <div><dt>Cargo</dt><dd>{{ $a->cargo?->nombre ?? '—' }}{{ $a->cargo ? ' · riesgo '.$a->cargo->riesgo_arl : '' }}</dd></div>
      <div><dt>Salario</dt><dd>{{ pesos($a->salario) }}</dd></div>
      <div><dt>Nacimiento</dt><dd>{{ $a->fecha_nacimiento?->format('d/m/Y') ?? '—' }}</dd></div>
      <div><dt>Teléfono</dt><dd>{{ $a->telefono ?? '—' }}</dd></div>
      <div><dt>Correo</dt><dd>{{ $a->email ?? '—' }}</dd></div>
      <div><dt>Dirección</dt><dd>{{ $a->direccion ?? '—' }} {{ $a->ciudad }}</dd></div>
    </dl>
    @if($p->observaciones)<div class="flash {{ in_array($p->estado, ['correccion','rechazado']) ? 'bad' : '' }}"><b>Observaciones:</b> {{ $p->observaciones }}</div>@endif
    <h2>Documentos</h2>
    @forelse($p->documentos as $d)
      <a href="{{ route('documentos.show', $d) }}">{{ $d->nombre }}</a>
    @empty <p class="note">Sin documentos cargados.</p> @endforelse
    <form method="post" enctype="multipart/form-data" action="{{ route('preafiliaciones.documentos', $p) }}" class="filters">
      @csrf <input type="file" name="archivo" id="archivo" accept=".pdf,.jpg,.jpeg,.png" required><button class="btn" type="submit">Cargar documento</button>
    </form>
  </section>
  <div style="display:flex;flex-direction:column;gap:16px;min-width:0">
    @if(auth()->user()->esInterno())
    <form class="card" method="post" action="{{ route('preafiliaciones.update', $p) }}">
      @csrf @method('put')
      <h2>Actualizar estado</h2>
      <label for="estado">Estado<select id="estado" name="estado">@foreach(\App\Models\Preafiliacion::ESTADOS as $k => [$t])<option value="{{ $k }}" @selected(old('estado', $p->estado) === $k)>{{ $t }}</option>@endforeach</select></label>
      <label for="fecha_afiliacion">Fecha de afiliación efectiva<input id="fecha_afiliacion" type="date" name="fecha_afiliacion" value="{{ old('fecha_afiliacion', $p->fecha_afiliacion?->format('Y-m-d')) }}"></label>
      <label for="numero_entidad">Número o radicado de la entidad<input id="numero_entidad" name="numero_entidad" value="{{ old('numero_entidad', $p->numero_entidad) }}"></label>
      <label for="observaciones">Observaciones (obligatorio si se devuelve o rechaza)<textarea id="observaciones" name="observaciones">{{ old('observaciones', $p->observaciones) }}</textarea></label>
      <div><button class="btn pri" type="submit">Guardar</button></div>
    </form>
    @endif
    <section class="card">
      <h2>Historial</h2>
      <div class="timeline">
        @foreach($historial as $h)
          <div><b>{{ ucfirst($h->evento) }}</b> · {{ $h->created_at->format('d/m/Y H:i') }} · {{ $h->user?->name ?? 'Sistema' }}
            @if($h->evento === 'actualizado' && $h->cambios)<br><small class="note">@foreach($h->cambios as $campo => $c){{ str_replace('_', ' ', $campo) }}: {{ is_scalar($c['antes']) ? $c['antes'] : '—' }} → {{ is_scalar($c['despues']) ? $c['despues'] : '—' }}@if(!$loop->last) · @endif @endforeach</small>@endif
          </div>
        @endforeach
      </div>
    </section>
  </div>
</div>
@endsection
