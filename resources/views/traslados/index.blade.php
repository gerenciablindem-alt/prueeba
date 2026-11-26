@extends('layouts.app')
@section('titulo', 'Traslados')
@section('contenido')
<header class="top"><div><h1>Traslados</h1><p class="sub">Cambios de EPS, ARL, CCF o fondo, con su seguimiento.</p></div></header>
@if(auth()->user()->esInterno())
<details class="card dd" @if($errors->any()) open @endif>
  <summary class="btn pri" style="align-self:flex-start">+ Registrar traslado</summary>
  <form method="post" action="{{ route('traslados.store') }}" class="form" style="margin-top:12px">
    @csrf
    <label for="t_afiliado" class="full">Empleado<select id="t_afiliado" name="afiliado_id" required><option value="">Selecciona</option>@foreach($afiliados as $af)<option value="{{ $af->id }}" @selected(old('afiliado_id') == $af->id)>{{ $af->nombre_completo }} · {{ $af->documento }} · {{ $af->empresa->nombre }}</option>@endforeach</select></label>
    <label for="t_tipo">Tipo<select id="t_tipo" name="tipo" required>@foreach(['EPS','ARL','CCF','AFP'] as $t)<option @selected(old('tipo') === $t)>{{ $t }}</option>@endforeach</select></label>
    <label for="t_origen">Entidad origen<select id="t_origen" name="entidad_origen_id" required>@foreach($entidades as $en)<option value="{{ $en->id }}" data-tipo="{{ $en->tipo }}" @selected(old('entidad_origen_id') == $en->id)>{{ $en->nombre }}</option>@endforeach</select></label>
    <label for="t_destino">Entidad destino<select id="t_destino" name="entidad_destino_id" required>@foreach($entidades as $en)<option value="{{ $en->id }}" data-tipo="{{ $en->tipo }}" @selected(old('entidad_destino_id') == $en->id)>{{ $en->nombre }}</option>@endforeach</select></label>
    <label for="t_fecha">Fecha de solicitud<input id="t_fecha" type="date" name="fecha_solicitud" value="{{ old('fecha_solicitud', now()->toDateString()) }}" required></label>
    <label for="t_seg" class="full">Nota inicial<input id="t_seg" name="seguimiento" value="{{ old('seguimiento') }}"></label>
    <div><button class="btn pri" type="submit">Guardar traslado</button></div>
  </form>
</details>
@endif
<section class="card">
  <div class="filters">
    <a @class(['chip','on' => !request('tipo')]) href="{{ request()->fullUrlWithQuery(['tipo' => null]) }}">Todos<span class="c">{{ $conteoTipo->sum() }}</span></a>
    @foreach(['EPS','ARL','CCF','AFP'] as $t)<a @class(['chip','on' => request('tipo') === $t]) href="{{ request()->fullUrlWithQuery(['tipo' => $t]) }}">{{ $t }}<span class="c">{{ $conteoTipo[$t] ?? 0 }}</span></a>@endforeach
    <form method="get" class="filters" style="margin-left:auto"><input type="hidden" name="tipo" value="{{ request('tipo') }}"><input name="q" id="q" value="{{ request('q') }}" placeholder="Buscar empleado"><select name="estado" id="estado"><option value="">Todos los estados</option>@foreach(\App\Models\Traslado::ESTADOS as $k => [$tx])<option value="{{ $k }}" @selected(request('estado') === $k)>{{ $tx }}</option>@endforeach</select><button class="btn" type="submit">Filtrar</button></form>
  </div>
  @if($items->isEmpty())<div class="empty">No hay traslados.</div>@else
  <div class="tbl"><table>
    <thead><tr><th>Empleado</th><th>Empresa</th><th>Tipo</th><th>Origen → destino</th><th>Estado</th><th>Seguimiento</th>@if(auth()->user()->esInterno())<th></th>@endif</tr></thead>
    <tbody>@foreach($items as $t)
      <tr><td>{{ $t->afiliado->nombre_completo }}<br><small class="mono">{{ $t->afiliado->documento }}</small></td><td>{{ $t->empresa->nombre }}</td><td><span class="ent">{{ $t->tipo }}</span></td>
      <td>{{ $t->origen->nombre }} → {{ $t->destino->nombre }}<br><small>Solicitado {{ $t->fecha_solicitud->format('d/m/Y') }}{{ $t->fecha_efectiva ? ' · efectivo '.$t->fecha_efectiva->format('d/m/Y') : '' }}</small></td>
      <td>{{ pill($t->estado, \App\Models\Traslado::ESTADOS) }}</td>
      <td style="max-width:280px"><small style="white-space:pre-line">{{ \Illuminate\Support\Str::limit($t->seguimiento, 160) ?: '—' }}</small></td>
      @if(auth()->user()->esInterno())<td><details class="dd"><summary class="btn sm">Actualizar</summary>
        <form method="post" action="{{ route('traslados.update', $t) }}" style="display:flex;flex-direction:column;gap:8px;margin-top:8px;min-width:220px">@csrf @method('put')
          <select name="estado" aria-label="Estado">@foreach(\App\Models\Traslado::ESTADOS as $k => [$tx])<option value="{{ $k }}" @selected($t->estado === $k)>{{ $tx }}</option>@endforeach</select>
          <input type="date" name="fecha_efectiva" aria-label="Fecha efectiva" value="{{ $t->fecha_efectiva?->format('Y-m-d') }}">
          <input name="nota" placeholder="Nota de seguimiento" aria-label="Nota">
          <button class="btn pri sm" type="submit">Guardar</button></form></details></td>@endif
      </tr>
    @endforeach</tbody></table></div>@endif
  @include('partials.paginacion', ['p' => $items])
</section>
@endsection
@push('scripts')
<script>
(function(){const tipo=document.getElementById('t_tipo');if(!tipo)return;
const f=()=>['t_origen','t_destino'].forEach(id=>{const s=document.getElementById(id);[...s.options].forEach(o=>o.hidden=o.dataset.tipo!==tipo.value);if(s.selectedOptions[0]?.hidden){const v=[...s.options].find(o=>!o.hidden);if(v)s.value=v.value}});
tipo.onchange=f;f();})();
</script>
@endpush
