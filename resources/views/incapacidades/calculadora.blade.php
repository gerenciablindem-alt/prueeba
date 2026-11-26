@extends('layouts.app')
@section('titulo', 'Calculadora de incapacidades')
@section('contenido')
<header class="top"><div><h1>Calculadora de incapacidades</h1><p class="sub">Separa lo que paga el empleador de lo que se recobra a la EPS, ARL o fondo.</p></div></header>
<div class="grid2" style="grid-template-columns:minmax(0,1fr) minmax(0,1.3fr)">
  <form class="card" method="get" id="calc">
    <label for="tipo">Tipo<select id="tipo" name="tipo">@foreach(\App\Models\Incapacidad::TIPOS as $k => $t)<option value="{{ $k }}" @selected($tipo === $k)>{{ $t }}</option>@endforeach</select></label>
    <label for="dias">Días de esta incapacidad<input id="dias" type="number" name="dias" min="1" max="540" value="{{ $dias }}"></label>
    <label for="previos">Días previos (si es prórroga)<input id="previos" type="number" name="previos" min="0" max="539" value="{{ $previos }}"></label>
    <label for="ibc">IBC mensual<input id="ibc" type="number" name="ibc" min="0" step="1000" value="{{ $ibc }}"></label>
    <button class="btn pri" type="submit">Calcular</button>
    <p class="note">Salario mínimo usado: {{ pesos($salarioMinimo) }}. Reglas: enfermedad general al 66,67% (días 1-2 empleador, 3-90 EPS), 50% del día 91 al 180 (EPS) y del 181 al 540 (fondo de pensiones), nunca por debajo del mínimo diario. Laboral: día 1 empleador, desde el día 2 la ARL al 100%. Licencias: EPS al 100%. Valídalas con tu equipo.</p>
  </form>
  <section class="card">@include('incapacidades._desglose', ['calc' => $calc])</section>
</div>
@endsection
@push('scripts')
<script>document.querySelectorAll('#calc select,#calc input').forEach(e=>e.addEventListener('change',()=>document.getElementById('calc').submit()));</script>
@endpush
