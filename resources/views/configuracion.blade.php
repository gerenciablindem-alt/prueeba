@extends('layouts.app')
@section('titulo', 'Configuración')
@section('contenido')
<header class="top"><div><h1>Configuración</h1><p class="sub">Valores que usa la app en los cálculos y el catálogo de entidades.</p></div></header>
<form class="card" method="post" action="{{ route('configuracion.update') }}">
  @csrf @method('put')
  <div class="form">
    <label for="salario_minimo">Salario mínimo mensual vigente<input id="salario_minimo" type="number" name="salario_minimo" value="{{ old('salario_minimo', $salarioMinimo) }}"></label>
  </div>
  <h2>Agregar entidad</h2>
  <div class="form">
    <label for="nueva_entidad_tipo">Tipo<select id="nueva_entidad_tipo" name="nueva_entidad_tipo"><option value="">—</option>@foreach(\App\Models\Entidad::TIPOS as $k => $v)<option value="{{ $k }}">{{ $k }}</option>@endforeach</select></label>
    <label for="nueva_entidad_nombre">Nombre<input id="nueva_entidad_nombre" name="nueva_entidad_nombre"></label>
    <label for="nueva_entidad_codigo">Código<input id="nueva_entidad_codigo" name="nueva_entidad_codigo"></label>
  </div>
  <div><button class="btn pri" type="submit">Guardar</button></div>
</form>
<section class="card" id="ia">
  <div class="hd"><h2>Asistente de IA</h2>{!! \App\Services\Asistente\Asistente::disponible() ? '<span class="pill p-ok">Activo</span>' : '<span class="pill p-mute">Inactivo</span>' !!}</div>
  <p class="note">El asistente usa Claude, de Anthropic. Para activarlo, crea una clave de API en console.anthropic.com y pégala aquí. Se guarda cifrada y no se vuelve a mostrar. Cuando alguien pregunta, los datos que la IA consulta (nombres, documentos, valores) se envían a Anthropic para generar la respuesta. Límite actual: {{ config('aportes.ia_limite_diario') }} preguntas por día.</p>
  <form method="post" action="{{ route('configuracion.ia') }}" class="filters">@csrf
    <input type="password" name="anthropic_api_key" id="anthropic_api_key" autocomplete="off" placeholder="{{ \App\Services\Asistente\Asistente::disponible() ? 'Clave guardada · pega una nueva para cambiarla' : 'sk-ant-…' }}" aria-label="Clave de API de Anthropic" style="flex:1 1 280px">
    <button class="btn pri" type="submit">Guardar clave</button>
    @if(\App\Models\Configuracion::find('anthropic_api_key'))<button class="btn danger" type="submit" name="quitar" value="1" formnovalidate>Quitar clave</button>@endif
  </form>
</section>
<section class="card"><h2>Entidades ({{ $entidades->count() }})</h2><div class="tbl"><table><thead><tr><th>Tipo</th><th>Nombre</th><th>Código</th></tr></thead>
<tbody>@foreach($entidades as $en)<tr><td><span class="ent">{{ $en->tipo }}</span></td><td>{{ $en->nombre }}</td><td class="mono">{{ $en->codigo }}</td></tr>@endforeach</tbody></table></div></section>
@endsection
