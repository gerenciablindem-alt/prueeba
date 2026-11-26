@extends('layouts.app')
@section('titulo', 'Planes y tarifas')
@section('contenido')
<header class="top"><div><h1>Planes y tarifas</h1><p class="sub">Lo que cotiza al mes un independiente por plan y nivel de riesgo, sin administración.</p></div>
@if($tarifarios->count() > 1)<form method="get" class="filters"><select name="t" aria-label="Tarifario" onchange="this.form.submit()">@foreach($tarifarios as $t)<option value="{{ $t->id }}" @selected($actual?->id === $t->id)>Tarifario {{ $t->anio }} · {{ \App\Models\Tarifario::ESTADOS[$t->estado][0] }}</option>@endforeach</select></form>@endif
</header>
<section class="card">
  @if(!$actual)<div class="empty">Todavía no hay tarifarios.</div>@else
  <h2>Tarifario {{ $actual->anio }} {{ pill($actual->estado, \App\Models\Tarifario::ESTADOS) }}</h2>
  <p class="note">IBC {{ pesos($actual->smlv) }} · vigente desde {{ $actual->vigente_desde->format('d/m/Y') }}{{ $actual->publicado_at ? ' · publicado el '.$actual->publicado_at->format('d/m/Y') : '' }}</p>
  <div class="tbl"><table>
    <thead><tr><th>Plan</th><th>Incluye</th>@foreach(\App\Models\Venta::NIVELES as $t)<th class="num">Riesgo {{ $t }}</th>@endforeach</tr></thead>
    <tbody>@foreach($planes as $p)
      <tr><td><b>{{ $p->nombre }}</b>@unless($p->activo) <span class="pill p-mute">inactivo</span>@endunless<br><small>{{ $p->descripcion }}</small></td><td>{{ implode(' + ', $p->subsistemas()) }}</td>
      @foreach(array_keys(\App\Models\Venta::NIVELES) as $n)@php($c = $celdas[$p->id.'-'.$n] ?? null)<td class="num">{{ $c ? pesos($c->total) : '—' }}</td>@endforeach</tr>
    @endforeach</tbody></table></div>
  @if($actual->estado === 'borrador' && auth()->user()->esAdmin())
    <form method="post" action="{{ route('tarifarios.publicar', $actual) }}" style="margin-top:12px">@csrf<button class="btn pri" type="submit">Publicar tarifario {{ $actual->anio }}</button> <span class="note">Las ventas nuevas usarán estos valores. Las ventas existentes conservan su precio.</span></form>
  @endif
  @endif
  <p class="note">Porcentajes: salud {{ config('aportes.porcentajes.eps') }}%, pensión {{ config('aportes.porcentajes.afp') }}%, caja {{ config('aportes.porcentajes.ccf') }}%, ARL {{ collect(config('aportes.tarifas_arl'))->map(fn ($v, $k) => \App\Models\Venta::NIVELES[$k].' '.$v.'%')->implode(', ') }}. Cada aporte se redondea al múltiplo de 100 superior.</p>
</section>
@if(auth()->user()->esAdmin())
<div class="grid2">
  <form class="card" method="post" action="{{ route('tarifarios.store') }}">@csrf
    <h2>Nuevo tarifario</h2>
    <p class="note">Se calcula con el salario mínimo que indiques y queda en borrador hasta que lo publiques.</p>
    <div class="form">
      <label for="anio">Año<input id="anio" type="number" name="anio" value="{{ old('anio', now()->year + 1) }}" required></label>
      <label for="smlv">Salario mínimo (IBC)<input id="smlv" type="number" name="smlv" value="{{ old('smlv', \App\Models\Configuracion::salarioMinimo()) }}" required></label>
      <label for="vigente_desde">Vigente desde<input id="vigente_desde" type="date" name="vigente_desde" value="{{ old('vigente_desde', (now()->year + 1).'-01-01') }}" required></label>
    </div>
    <div style="margin-top:12px"><button class="btn pri" type="submit">Crear tarifario</button></div>
  </form>
  <form class="card" method="post" action="{{ route('planes.store') }}">@csrf
    <h2>Nuevo plan</h2>
    <div class="form">
      <label for="codigo">Código<input id="codigo" name="codigo" value="{{ old('codigo') }}" required></label>
      <label for="nombre">Nombre<input id="nombre" name="nombre" value="{{ old('nombre') }}" required></label>
      <label for="descripcion" class="full">Descripción<input id="descripcion" name="descripcion" value="{{ old('descripcion') }}"></label>
      <fieldset class="full" style="border:0;padding:0;margin:0;display:flex;flex-wrap:wrap;gap:8px 18px"><legend class="note" style="padding:0;margin-bottom:6px">Incluye</legend>@foreach(['incluye_eps' => 'Salud (EPS)', 'incluye_afp' => 'Pensión (AFP)', 'incluye_arl' => 'Riesgos (ARL)', 'incluye_ccf' => 'Caja (CCF)'] as $k => $t)<label class="check" style="display:inline-flex;width:auto"><input type="checkbox" name="{{ $k }}" value="1" style="width:auto" @checked(old($k))> {{ $t }}</label>@endforeach</fieldset>
    </div>
    <div style="margin-top:12px"><button class="btn pri" type="submit">Crear plan</button></div>
  </form>
</div>
@endif
@endsection
