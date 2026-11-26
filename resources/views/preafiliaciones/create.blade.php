@extends('layouts.app')
@section('titulo', 'Nueva pre-afiliación')
@section('contenido')
<header class="top"><div><h1>FUA / Pre-afiliación</h1><p class="sub">Un radicado por cada entidad que elijas. Si el documento ya existe, los datos se llenan solos.</p></div></header>
<div class="steps" id="pasos">
  @foreach(['Tipo','Empresa','Afiliado','Afiliación','Confirmar'] as $i => $s)
    <button type="button" class="step" data-ir="{{ $i }}"><b>{{ $i + 1 }}</b>{{ $s }}</button>
  @endforeach
</div>
<form method="post" action="{{ route('preafiliaciones.store') }}" id="fua" class="card" novalidate>
  @csrf
  {{-- Paso 1 --}}
  <section data-paso="0">
    <div class="hd"><h2>Selecciona el tipo de afiliación</h2><span class="note">Puedes elegir varios</span></div>
    <div class="opts" style="margin-top:12px">
      @foreach(\App\Models\Entidad::TIPOS as $t => $desc)
        <label class="opt"><input type="checkbox" name="tipos[]" value="{{ $t }}" @checked(in_array($t, old('tipos', [])))><div><b>{{ $t }}</b><span>{{ $desc }}</span></div></label>
      @endforeach
    </div>
    @error('tipos')<p class="err">{{ $message }}</p>@enderror
  </section>
  {{-- Paso 2 --}}
  <section data-paso="1" hidden>
    <h2>Empresa</h2>
    <div class="form" style="margin-top:12px">
      <label for="empresa_id" class="full">Empresa a la que pertenece el afiliado
        <select id="empresa_id" name="empresa_id" required>
          <option value="">Selecciona la empresa</option>
          @foreach($empresas as $em)<option value="{{ $em->id }}" @selected(old('empresa_id', $empresas->count() === 1 ? $em->id : null) == $em->id)>{{ $em->nombre }} · {{ $em->tipo_documento }} {{ $em->documento }}</option>@endforeach
        </select>
      </label>
    </div>
    <p class="note" id="empresaInfo"></p>
  </section>
  {{-- Paso 3 --}}
  <section data-paso="2" hidden>
    <h2>Datos del afiliado</h2>
    <div class="form" style="margin-top:12px">
      <label for="tipo_documento">Tipo de documento
        <select id="tipo_documento" name="tipo_documento">@foreach(['CC'=>'Cédula de ciudadanía','CE'=>'Cédula de extranjería','PPT'=>'Permiso por protección temporal','TI'=>'Tarjeta de identidad','PA'=>'Pasaporte'] as $k=>$v)<option value="{{ $k }}" @selected(old('tipo_documento')===$k)>{{ $v }}</option>@endforeach</select></label>
      <label for="documento">Número de documento<input id="documento" name="documento" value="{{ old('documento') }}" inputmode="numeric" required></label>
      <label for="fecha_expedicion">Fecha de expedición<input id="fecha_expedicion" name="fecha_expedicion" type="date" value="{{ old('fecha_expedicion') }}"></label>
      <div class="full" id="existente" hidden></div>
      <label for="nombres">Nombres<input id="nombres" name="nombres" value="{{ old('nombres') }}" required></label>
      <label for="apellidos">Apellidos<input id="apellidos" name="apellidos" value="{{ old('apellidos') }}" required></label>
      <label for="fecha_nacimiento">Fecha de nacimiento<input id="fecha_nacimiento" name="fecha_nacimiento" type="date" value="{{ old('fecha_nacimiento') }}"></label>
      <label for="sexo">Sexo<select id="sexo" name="sexo"><option value="">—</option><option value="F" @selected(old('sexo')==='F')>Femenino</option><option value="M" @selected(old('sexo')==='M')>Masculino</option></select></label>
      <label for="telefono">Teléfono<input id="telefono" name="telefono" value="{{ old('telefono') }}" inputmode="tel"></label>
      <label for="email">Correo<input id="email" name="email" type="email" value="{{ old('email') }}"></label>
      <label for="direccion">Dirección<input id="direccion" name="direccion" value="{{ old('direccion') }}"></label>
      <label for="ciudad">Ciudad<input id="ciudad" name="ciudad" value="{{ old('ciudad') }}"></label>
    </div>
  </section>
  {{-- Paso 4 --}}
  <section data-paso="3" hidden>
    <h2>Afiliación</h2>
    <div class="form" style="margin-top:12px">
      @foreach(\App\Models\Entidad::TIPOS as $t => $desc)
        <label for="entidad_{{ $t }}" data-tipo="{{ $t }}">{{ $t }} a la que se afilia
          <select id="entidad_{{ $t }}" name="entidades[{{ $t }}]">
            <option value="">Selecciona</option>
            @foreach($entidades[$t] ?? [] as $en)<option value="{{ $en->id }}" @selected(old("entidades.$t") == $en->id)>{{ $en->nombre }}</option>@endforeach
          </select>
          @error("entidades.$t")<span class="err">{{ $message }}</span>@enderror
        </label>
      @endforeach
      <label for="cargo_id">Cargo<select id="cargo_id" name="cargo_id"><option value="">Sin cargo</option></select></label>
      <label for="salario">Salario mensual (IBC)<input id="salario" name="salario" type="number" min="0" step="1000" value="{{ old('salario') }}" required></label>
      <label for="fecha_ingreso">Fecha de ingreso<input id="fecha_ingreso" name="fecha_ingreso" type="date" value="{{ old('fecha_ingreso', now()->toDateString()) }}"></label>
      <label for="observaciones" class="full">Observaciones<textarea id="observaciones" name="observaciones">{{ old('observaciones') }}</textarea></label>
    </div>
    <p class="note" id="riesgoInfo"></p>
  </section>
  {{-- Paso 5 --}}
  <section data-paso="4" hidden>
    <h2>Confirma antes de enviar</h2>
    <dl class="datos" id="resumen" style="margin-top:12px"></dl>
  </section>
  <p class="err" id="errPaso" hidden></p>
  <div class="filters">
    <button type="button" class="btn" id="atras">Atrás</button>
    <button type="button" class="btn pri" id="siguiente">Siguiente</button>
    <button type="submit" class="btn pri" id="enviar" hidden>Crear radicados</button>
  </div>
</form>
@endsection
@push('scripts')
<script>
const EMPRESAS = @json($empresasJs);
const OLD_CARGO = @json(old('cargo_id'));
const $ = s => document.querySelector(s), $$ = s => [...document.querySelectorAll(s)];
let paso = {{ $errors->any() ? ($errors->has('tipos') ? 0 : ($errors->has('empresa_id') ? 1 : ($errors->hasAny(['documento','nombres','apellidos']) ? 2 : 3))) : 0 }};
const tipos = () => $$('input[name="tipos[]"]:checked').map(i => i.value);
function validar(n) {
  if (n === 0 && !tipos().length) return 'Elige al menos un tipo de afiliación.';
  if (n === 1 && !$('#empresa_id').value) return 'Elige la empresa.';
  if (n === 2 && (!$('#documento').value.trim() || !$('#nombres').value.trim() || !$('#apellidos').value.trim())) return 'Completa documento, nombres y apellidos.';
  if (n === 3) { for (const t of tipos()) if (!$('#entidad_' + t).value) return 'Elige la ' + t + '.'; if ($('#salario').value === '') return 'Escribe el salario.'; }
  return '';
}
function mostrar() {
  $$('[data-paso]').forEach(s => s.hidden = +s.dataset.paso !== paso);
  $$('#pasos .step').forEach((b, i) => { b.classList.toggle('on', i === paso); b.classList.toggle('done', i < paso); b.querySelector('b').textContent = i < paso ? '✓' : i + 1; });
  $('#atras').disabled = paso === 0; $('#siguiente').hidden = paso === 4; $('#enviar').hidden = paso !== 4;
  $$('[data-tipo]').forEach(l => l.hidden = !tipos().includes(l.dataset.tipo));
  if (paso === 4) resumen();
  $('#errPaso').hidden = true;
}
function ir(n) { for (let i = 0; i < Math.min(n, 5); i++) { const e = validar(i); if (e && n > i) { paso = i; mostrar(); $('#errPaso').textContent = e; $('#errPaso').hidden = false; return; } } paso = n; mostrar(); window.scrollTo(0, 0); }
$('#siguiente').onclick = () => ir(paso + 1);
$('#atras').onclick = () => { paso = Math.max(0, paso - 1); mostrar(); };
$$('#pasos .step').forEach(b => b.onclick = () => ir(+b.dataset.ir));
function cargarEmpresa() {
  const e = EMPRESAS[$('#empresa_id').value]; const sel = $('#cargo_id');
  sel.innerHTML = '<option value="">Sin cargo</option>';
  if (!e) { $('#empresaInfo').textContent = ''; return; }
  e.cargos.forEach(c => sel.add(new Option(c.nombre + ' · riesgo ' + c.riesgo_arl, c.id, false, String(c.id) === String(OLD_CARGO))));
  e.entidades.forEach(en => { const s = $('#entidad_' + en.tipo); if (s && !s.value) s.value = en.id; });
  $('#empresaInfo').textContent = e.faltan.length ? 'A esta empresa le faltan datos: ' + e.faltan.join(', ') + '. Puedes seguir, pero conviene completarlos.' : 'Las entidades habituales de la empresa se preseleccionan en el paso 4.';
}
$('#empresa_id').onchange = cargarEmpresa;
$('#cargo_id').onchange = () => { const e = EMPRESAS[$('#empresa_id').value]; const c = e?.cargos.find(c => String(c.id) === $('#cargo_id').value); $('#riesgoInfo').textContent = c ? 'Nivel de riesgo ARL según el cargo: ' + c.riesgo_arl : ''; };
let t;
$('#documento').addEventListener('input', () => { clearTimeout(t); t = setTimeout(buscar, 400); });
async function buscar() {
  const box = $('#existente'); const r = await fetch('{{ route('afiliados.buscar') }}?documento=' + encodeURIComponent($('#documento').value), {headers: {Accept: 'application/json'}});
  const d = await r.json(); if (!d) { box.hidden = true; return; }
  ['nombres','apellidos','fecha_nacimiento','fecha_expedicion','sexo','telefono','email','direccion','ciudad'].forEach(k => { if (!$('#' + k).value && d[k]) $('#' + k).value = d[k]; });
  const dup = d.afiliaciones.filter(a => tipos().includes(a.tipo));
  box.innerHTML = '<div class="al ' + (dup.length ? 'warn' : 'info') + '"><span class="s"></span><div><p><b>Este documento ya está registrado (' + d.empresa + ').</b> Completamos los datos que faltaban.</p><small>' +
    (d.afiliaciones.length ? d.afiliaciones.map(a => a.tipo + ' ' + a.entidad + ': ' + a.estado + ' (' + a.radicado + ', ' + a.fecha + ')').join(' · ') : 'Sin radicados previos.') + '</small></div><span></span></div>';
  box.hidden = false;
}
function resumen() {
  const e = EMPRESAS[$('#empresa_id').value]; const txt = s => s.options[s.selectedIndex]?.text || '—';
  const filas = [['Tipos', tipos().join(', ')], ['Empresa', e?.nombre], ['Afiliado', $('#nombres').value + ' ' + $('#apellidos').value], ['Documento', $('#tipo_documento').value + ' ' + $('#documento').value],
    ...tipos().map(t => [t, txt($('#entidad_' + t))]), ['Cargo', txt($('#cargo_id'))], ['Salario', '$ ' + Number($('#salario').value || 0).toLocaleString('es-CO')], ['Fecha de ingreso', $('#fecha_ingreso').value || '—']];
  $('#resumen').innerHTML = filas.map(([k, v]) => '<div><dt>' + k + '</dt><dd>' + (v || '—').toString().replace(/</g, '&lt;') + '</dd></div>').join('');
}
$$('input[name="tipos[]"]').forEach(i => i.onchange = mostrar);
cargarEmpresa(); mostrar();
</script>
@endpush
