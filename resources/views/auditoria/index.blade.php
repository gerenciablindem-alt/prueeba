@extends('layouts.app')
@section('titulo', 'Auditoría')
@section('contenido')
<header class="top"><div><h1>Auditoría</h1><p class="sub">Quién cambió qué y cuándo, con el valor anterior y el nuevo.</p></div></header>
<section class="card">
  <form class="filters" method="get">
    <select name="modulo" id="modulo"><option value="">Todos los módulos</option>@foreach($modulos as $m)<option @selected(request('modulo') === $m)>{{ $m }}</option>@endforeach</select>
    <select name="empresa_id" id="empresa_id"><option value="">Todas las empresas</option>@foreach($empresas as $e)<option value="{{ $e->id }}" @selected(request('empresa_id') == $e->id)>{{ $e->nombre }}</option>@endforeach</select>
    <select name="user_id" id="user_id"><option value="">Todos los usuarios</option>@foreach($usuarios as $us)<option value="{{ $us->id }}" @selected(request('user_id') == $us->id)>{{ $us->name }}</option>@endforeach</select>
    <select name="evento" id="evento"><option value="">Todos los eventos</option>@foreach(['creado','actualizado','eliminado','importado'] as $ev)<option value="{{ $ev }}" @selected(request('evento') === $ev)>{{ ucfirst($ev) }}</option>@endforeach</select>
    <label class="check">Desde <input type="date" name="desde" id="desde" value="{{ request('desde') }}"></label>
    <label class="check">Hasta <input type="date" name="hasta" id="hasta" value="{{ request('hasta') }}"></label>
    <button class="btn" type="submit">Filtrar</button> <a href="{{ route('auditoria.index') }}">Limpiar</a>
  </form>
  @if($items->isEmpty())<div class="empty">Sin movimientos.</div>@else
  <div class="tbl"><table>
    <thead><tr><th>Fecha</th><th>Usuario</th><th>Módulo</th><th>Empresa</th><th>Evento</th><th>Registro</th><th>Cambios</th></tr></thead>
    <tbody>@foreach($items as $h)
      <tr><td class="nowrap">{{ $h->created_at->format('d/m/Y H:i') }}</td><td>{{ $h->user?->name ?? 'Sistema' }}</td><td>{{ $h->modulo }}</td><td>{{ $h->empresa?->nombre ?? '—' }}</td>
      <td>{{ pill($h->evento, ['creado' => ['Creado','ok'], 'actualizado' => ['Actualizado','info'], 'eliminado' => ['Eliminado','bad'], 'importado' => ['Importado','mute']]) }}</td>
      <td>{{ $h->descripcion }}</td>
      <td style="min-width:260px">@if($h->evento === 'actualizado' && $h->cambios)@foreach($h->cambios as $campo => $c)<div><small><b>{{ str_replace('_', ' ', $campo) }}:</b> {{ is_scalar($c['antes']) ? $c['antes'] : '—' }} → {{ is_scalar($c['despues']) ? $c['despues'] : '—' }}</small></div>@endforeach @else <small>—</small> @endif</td></tr>
    @endforeach</tbody></table></div>@endif
  @include('partials.paginacion', ['p' => $items])
</section>
@endsection
