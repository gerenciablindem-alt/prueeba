@extends('layouts.app')
@section('titulo', 'Registrar incapacidad')
@section('contenido')
<header class="top"><div><h1>Registrar incapacidad</h1><p class="sub">El valor a recobrar se calcula solo al guardar.</p></div></header>
<form class="card" method="post" action="{{ route('incapacidades.store') }}">
  @csrf
  <div class="form">
    <label for="afiliado_id" class="full">Empleado
      <select id="afiliado_id" name="afiliado_id" required><option value="">Selecciona</option>
        @foreach($afiliados as $a)<option value="{{ $a->id }}" data-salario="{{ $a->salario }}" @selected(old('afiliado_id', request('afiliado_id')) == $a->id)>{{ $a->nombre_completo }} · {{ $a->documento }} · {{ $a->empresa->nombre }}</option>@endforeach
      </select></label>
    <label for="tipo">Tipo<select id="tipo" name="tipo">@foreach(\App\Models\Incapacidad::TIPOS as $k => $t)<option value="{{ $k }}" @selected(old('tipo') === $k)>{{ $t }}</option>@endforeach</select></label>
    <label for="fecha_inicio">Fecha de inicio<input id="fecha_inicio" type="date" name="fecha_inicio" value="{{ old('fecha_inicio', now()->toDateString()) }}" required></label>
    <label for="dias">Días<input id="dias" type="number" name="dias" min="1" max="540" value="{{ old('dias', 3) }}" required></label>
    <label for="ibc">IBC mensual<input id="ibc" type="number" name="ibc" min="0" step="1000" value="{{ old('ibc') }}" required></label>
    <label for="diagnostico">Diagnóstico (CIE-10)<input id="diagnostico" name="diagnostico" value="{{ old('diagnostico') }}" placeholder="Ej. J06.9"></label>
    <label for="entidad_id">Entidad a la que se cobra<select id="entidad_id" name="entidad_id"><option value="">Selecciona</option>@foreach($entidades as $en)<option value="{{ $en->id }}" @selected(old('entidad_id') == $en->id)>{{ $en->tipo }} · {{ $en->nombre }}</option>@endforeach</select></label>
    <label for="prorroga_de_id" class="full">¿Es prórroga de otra incapacidad?
      <select id="prorroga_de_id" name="prorroga_de_id"><option value="">No, es una incapacidad nueva</option>
        @foreach($previas as $p)<option value="{{ $p->id }}" data-afiliado="{{ $p->afiliado_id }}" @selected(old('prorroga_de_id', $prorrogaDe) == $p->id)>{{ $p->codigo }} · {{ $p->afiliado->nombre_completo }} · {{ $p->dias_acumulados }} días acumulados</option>@endforeach
      </select></label>
    <label for="observaciones" class="full">Observaciones<textarea id="observaciones" name="observaciones">{{ old('observaciones') }}</textarea></label>
  </div>
  <p class="note">Salario mínimo usado en el cálculo: {{ pesos($salarioMinimo) }}. Lo cambia un administrador en Configuración.</p>
  <div><button class="btn pri" type="submit">Guardar incapacidad</button></div>
</form>
@endsection
@push('scripts')
<script>
const af=document.getElementById('afiliado_id'),ibc=document.getElementById('ibc'),pr=document.getElementById('prorroga_de_id');
function upd(){const o=af.selectedOptions[0];if(o&&o.dataset.salario&&!ibc.dataset.touched)ibc.value=o.dataset.salario;[...pr.options].forEach(op=>{if(op.value)op.hidden=op.dataset.afiliado!==af.value});if(pr.selectedOptions[0]?.hidden)pr.value='';}
ibc.addEventListener('input',()=>ibc.dataset.touched=1);af.onchange=upd;upd();
</script>
@endpush
