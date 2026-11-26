@extends('layouts.app')
@section('titulo', $i->codigo)
@section('contenido')
<header class="top">
  <div><h1><span class="mono" style="font-size:20px">{{ $i->codigo }}</span> {{ pill($i->estado, \App\Models\Incapacidad::ESTADOS) }}</h1>
  <p class="sub">{{ $i->tipo_nombre }} · {{ $i->afiliado->nombre_completo }} · {{ $i->empresa->nombre }}</p></div>
  <div class="filters"><a class="btn" href="{{ route('incapacidades.create', ['afiliado_id' => $i->afiliado_id, 'prorroga_de' => $i->id]) }}">Registrar prórroga</a><a class="btn" href="{{ route('incapacidades.index') }}">Volver</a></div>
</header>
<div class="grid2">
  <div style="display:flex;flex-direction:column;gap:16px;min-width:0">
    <section class="card">
      <dl class="datos">
        <div><dt>Desde</dt><dd>{{ $i->fecha_inicio->format('d/m/Y') }}</dd></div>
        <div><dt>Hasta</dt><dd>{{ $i->fecha_fin->format('d/m/Y') }}</dd></div>
        <div><dt>Días</dt><dd>{{ $i->dias }} (acumulados {{ $i->dias_acumulados }})</dd></div>
        <div><dt>IBC</dt><dd>{{ pesos($i->ibc) }}</dd></div>
        <div><dt>Diagnóstico</dt><dd>{{ $i->diagnostico ?? '—' }}</dd></div>
        <div><dt>Cobrar a</dt><dd>{{ $i->entidad?->nombre ?? '—' }}</dd></div>
        <div><dt>Radicado entidad</dt><dd>{{ $i->numero_radicado_entidad ?? '—' }}{{ $i->fecha_radicacion ? ' · '.$i->fecha_radicacion->format('d/m/Y') : '' }}</dd></div>
        <div><dt>Pagado</dt><dd>{{ $i->valor_pagado !== null ? pesos($i->valor_pagado) : '—' }}{{ $i->fecha_pago ? ' · '.$i->fecha_pago->format('d/m/Y') : '' }}</dd></div>
      </dl>
      @if($i->valor_pagado !== null && $i->valor_pagado < $i->valor_recobro)<div class="flash bad">La entidad pagó {{ pesos($i->valor_recobro - $i->valor_pagado) }} menos de lo calculado.</div>@endif
      @if($i->observaciones)<div class="flash"><b>Observaciones:</b> {{ $i->observaciones }}</div>@endif
      @if($i->prorrogaDe)<p class="note">Prórroga de <a href="{{ route('incapacidades.show', $i->prorrogaDe) }}">{{ $i->prorrogaDe->codigo }}</a>.</p>@endif
      @foreach($i->prorrogas as $pr)<p class="note">Prorrogada en <a href="{{ route('incapacidades.show', $pr) }}">{{ $pr->codigo }}</a>.</p>@endforeach
    </section>
    <section class="card"><h2>Cálculo</h2>@include('incapacidades._desglose', ['calc' => $calc])</section>
  </div>
  <div style="display:flex;flex-direction:column;gap:16px;min-width:0">
    @if(auth()->user()->esInterno())
    <form class="card" method="post" action="{{ route('incapacidades.update', $i) }}">
      @csrf @method('put')
      <h2>Seguimiento del cobro</h2>
      <label for="estado">Estado<select id="estado" name="estado">@foreach(\App\Models\Incapacidad::ESTADOS as $k => [$t])<option value="{{ $k }}" @selected(old('estado', $i->estado) === $k)>{{ $t }}</option>@endforeach</select></label>
      <label for="entidad_id">Entidad<select id="entidad_id" name="entidad_id"><option value="">—</option>@foreach(\App\Models\Entidad::whereIn('tipo', ['EPS','ARL'])->orderBy('nombre')->get() as $en)<option value="{{ $en->id }}" @selected(old('entidad_id', $i->entidad_id) == $en->id)>{{ $en->tipo }} · {{ $en->nombre }}</option>@endforeach</select></label>
      <label for="numero_radicado_entidad">Número de radicado en la entidad<input id="numero_radicado_entidad" name="numero_radicado_entidad" value="{{ old('numero_radicado_entidad', $i->numero_radicado_entidad) }}"></label>
      <label for="fecha_radicacion">Fecha de radicación<input id="fecha_radicacion" type="date" name="fecha_radicacion" value="{{ old('fecha_radicacion', $i->fecha_radicacion?->format('Y-m-d')) }}"></label>
      <label for="valor_pagado">Valor pagado por la entidad<input id="valor_pagado" type="number" min="0" name="valor_pagado" value="{{ old('valor_pagado', $i->valor_pagado) }}"></label>
      <label for="fecha_pago">Fecha de pago<input id="fecha_pago" type="date" name="fecha_pago" value="{{ old('fecha_pago', $i->fecha_pago?->format('Y-m-d')) }}"></label>
      <label for="observaciones">Observaciones<textarea id="observaciones" name="observaciones">{{ old('observaciones', $i->observaciones) }}</textarea></label>
      <div><button class="btn pri" type="submit">Guardar</button></div>
    </form>
    @endif
    <section class="card"><h2>Soportes</h2>
      @forelse($i->documentos as $d)<a href="{{ route('documentos.show', $d) }}">{{ $d->nombre }}</a>@empty<p class="note">Carga el certificado de incapacidad y la historia clínica si la EPS la pide.</p>@endforelse
      <form method="post" enctype="multipart/form-data" action="{{ route('incapacidades.documentos', $i) }}" class="filters">@csrf<input type="file" name="archivo" id="archivo" accept=".pdf,.jpg,.jpeg,.png" required><button class="btn" type="submit">Cargar</button></form>
    </section>
  </div>
</div>
@endsection
