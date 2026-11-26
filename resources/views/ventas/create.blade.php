@extends('layouts.app')
@section('titulo', 'Nueva venta')
@section('contenido')
<header class="top"><div><h1>Nueva venta</h1><p class="sub">Registra el plan que compra un independiente. Se genera su primera cuota.</p></div>
<a class="btn" href="{{ route('ventas.index') }}">Volver</a></header>
@if(!$tarifario)
  <div class="flash bad">No hay un tarifario publicado. @if(auth()->user()->esAdmin())<a href="{{ route('tarifarios.index') }}">Crea y publica uno</a> antes de vender.@else Pide a un administrador que publique uno.@endif</div>
@else
<div class="grid2">
<form class="card" method="post" action="{{ route('ventas.store') }}">
  @csrf
  <h2>Cliente</h2>
  <div class="form">
    <label for="tipo_documento">Tipo de documento<select id="tipo_documento" name="tipo_documento">@foreach(['CC' => 'Cédula de ciudadanía', 'CE' => 'Cédula de extranjería', 'PPT' => 'Permiso por protección temporal', 'PA' => 'Pasaporte', 'TI' => 'Tarjeta de identidad'] as $k => $t)<option value="{{ $k }}" @selected(old('tipo_documento') === $k)>{{ $t }}</option>@endforeach</select></label>
    <label for="documento">Número<input id="documento" name="documento" value="{{ old('documento') }}" required inputmode="numeric"></label>
    <label for="nombre_cliente" class="full">Nombre completo<input id="nombre_cliente" name="nombre_cliente" value="{{ old('nombre_cliente') }}" required></label>
    <label for="celular">Celular<input id="celular" name="celular" value="{{ old('celular') }}" inputmode="tel"></label>
    <label for="email">Correo<input id="email" type="email" name="email" value="{{ old('email') }}"></label>
  </div>
  <h2 style="margin-top:18px">Plan</h2>
  <div class="form">
    <label for="plan_id">Plan<select id="plan_id" name="plan_id" required>@foreach($planes as $p)<option value="{{ $p->id }}" @selected(old('plan_id') == $p->id)>{{ $p->nombre }} ({{ implode(' + ', $p->subsistemas()) }})</option>@endforeach</select></label>
    <label for="nivel_riesgo">Nivel de riesgo ARL<select id="nivel_riesgo" name="nivel_riesgo">@foreach(\App\Models\Venta::NIVELES as $k => $t)<option value="{{ $k }}" @selected(old('nivel_riesgo', 1) == $k)>Riesgo {{ $t }}</option>@endforeach</select></label>
    <label for="sede_id">Sede<select id="sede_id" name="sede_id" required>@foreach($sedes as $s)<option value="{{ $s->id }}" @selected(old('sede_id', auth()->user()->sede_id) == $s->id)>{{ $s->nombre }}</option>@endforeach</select></label>
    <label for="periodo_inicial">Primer mes a cotizar<input id="periodo_inicial" type="month" name="periodo_inicial" value="{{ old('periodo_inicial', now()->format('Y-m')) }}" required></label>
    <label for="administracion">Administración mensual<input id="administracion" type="number" min="0" step="100" name="administracion" value="{{ old('administracion', config('aportes.administracion')) }}" required></label>
  </div>
  <div style="margin-top:16px"><button class="btn pri" type="submit">Registrar venta</button></div>
</form>
<section class="card">
  <h2>Valor mensual</h2>
  <p class="note">Tarifario {{ $tarifario->anio }} · IBC {{ pesos($tarifario->smlv) }}</p>
  <div class="tbl"><table>
    <tbody>
      <tr><td>Salud (EPS)</td><td class="num" id="v_eps">—</td></tr>
      <tr><td>Pensión (AFP)</td><td class="num" id="v_afp">—</td></tr>
      <tr><td>Riesgos (ARL)</td><td class="num" id="v_arl">—</td></tr>
      <tr><td>Caja (CCF)</td><td class="num" id="v_ccf">—</td></tr>
      <tr><td>Administración</td><td class="num" id="v_adm">—</td></tr>
      <tr><td><b>Total a pagar cada mes</b></td><td class="num"><b id="v_total">—</b></td></tr>
    </tbody>
  </table></div>
  <p class="note" id="v_aviso" hidden>Este plan no tiene precio para ese nivel de riesgo en el tarifario vigente.</p>
</section>
</div>
@endif
@endsection
@push('scripts')
@if($tarifario)
<script>
(function(){const c=@json($celdasJs);const $=id=>document.getElementById(id);
const fmt=n=>'$ '+Number(n).toLocaleString('es-CO');
const f=()=>{const k=$('plan_id').value+'-'+$('nivel_riesgo').value,x=c[k],adm=parseInt($('administracion').value||0,10);
$('v_aviso').hidden=!!x;['eps','afp','arl','ccf'].forEach(s=>$('v_'+s).textContent=x?(x[s]?fmt(x[s]):'—'):'—');
$('v_adm').textContent=fmt(adm);$('v_total').textContent=x?fmt(x.total+adm):'—'};
['plan_id','nivel_riesgo','administracion'].forEach(id=>$(id).addEventListener('input',f));f();})();
</script>
@endif
@endpush
