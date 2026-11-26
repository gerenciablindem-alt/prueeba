@extends('layouts.app')
@section('titulo', 'Recibo '.$r->numero)
@section('contenido')
<style>@media print{.rail,.top .filters,.no-print,.flash{display:none!important}.shell{display:block}main{padding:0}.card{box-shadow:none;border:1px solid #999}}</style>
<header class="top">
  <div><h1><span class="mono" style="font-size:20px">{{ $r->numero }}</span> {{ pill($r->estado, \App\Models\Recibo::ESTADOS) }}</h1>
  <p class="sub">{{ $r->venta->nombre_cliente }} · {{ $r->cuota->periodo_nombre }}</p></div>
  <div class="filters"><button class="btn" type="button" onclick="window.print()">Imprimir / PDF</button><a class="btn" href="{{ route('ventas.show', $r->venta) }}">Ver venta</a></div>
</header>
<div class="grid2">
  <section class="card">
    <h2>Recibo de caja {{ $r->numero }}</h2>
    <dl class="datos">
      <div><dt>Sede</dt><dd>{{ $r->sede->nombre }}<br><small>{{ $r->sede->tipo_documento }} {{ $r->sede->documento }} · {{ $r->sede->ciudad }}</small></dd></div>
      <div><dt>Cliente</dt><dd>{{ $r->venta->nombre_cliente }}<br><small>{{ $r->venta->tipo_documento }} {{ $r->venta->documento }}</small></dd></div>
      <div><dt>Plan</dt><dd>{{ $r->venta->plan->nombre }}</dd></div>
      <div><dt>Periodo</dt><dd>{{ $r->cuota->periodo_nombre }}</dd></div>
      <div><dt>Fecha límite</dt><dd>{{ $r->cuota->fecha_limite->format('d/m/Y') }}</dd></div>
      <div><dt>Emitido</dt><dd>{{ $r->created_at->format('d/m/Y') }}{{ $r->generador ? ' · '.$r->generador->name : '' }}</dd></div>
    </dl>
    <div class="tbl" style="margin-top:14px"><table><tbody>
      <tr><td>Aportes a seguridad social</td><td class="num">{{ pesos($r->cuota->aportes) }}</td></tr>
      <tr><td>Administración</td><td class="num">{{ pesos($r->cuota->administracion) }}</td></tr>
      <tr><td><b>Total</b></td><td class="num"><b>{{ pesos($r->valor) }}</b></td></tr>
    </tbody></table></div>
    @if($r->estado === 'pagado')<div class="flash ok" style="display:block!important">Pagado el {{ $r->fecha_pago->format('d/m/Y') }} por {{ \App\Models\Recibo::MEDIOS[$r->medio_pago] ?? $r->medio_pago }}{{ $r->referencia ? ' · ref. '.$r->referencia : '' }} · {{ pesos($r->valor_pagado) }}</div>@endif
    @if($r->estado === 'anulado')<div class="flash bad" style="display:block!important">Anulado el {{ $r->anulado_at->format('d/m/Y') }}: {{ $r->motivo_anulacion }}</div>@endif
  </section>
  <div class="no-print" style="display:flex;flex-direction:column;gap:16px;min-width:0">
    @if($r->estado === 'emitido')
    <form class="card" method="post" action="{{ route('recibos.pagar', $r) }}">@csrf
      <h2>Registrar pago</h2>
      <label for="fecha_pago">Fecha de pago<input id="fecha_pago" type="date" name="fecha_pago" value="{{ old('fecha_pago', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required></label>
      <label for="medio_pago">Medio de pago<select id="medio_pago" name="medio_pago">@foreach(\App\Models\Recibo::MEDIOS as $k => $t)<option value="{{ $k }}" @selected(old('medio_pago') === $k)>{{ $t }}</option>@endforeach</select></label>
      <label for="valor_pagado">Valor recibido<input id="valor_pagado" type="number" min="1" name="valor_pagado" value="{{ old('valor_pagado', $r->valor) }}" required></label>
      <label for="referencia">Referencia o número de comprobante<input id="referencia" name="referencia" value="{{ old('referencia') }}"></label>
      <div><button class="btn pri" type="submit">Registrar pago</button></div>
    </form>
    @endif
    @if($r->estado !== 'anulado' && auth()->user()->esAdmin())
    <details class="card dd"><summary class="btn" style="align-self:flex-start">Anular recibo</summary>
      <form method="post" action="{{ route('recibos.anular', $r) }}" style="display:flex;flex-direction:column;gap:10px;margin-top:12px">@csrf
        <label for="motivo_anulacion">Motivo<input id="motivo_anulacion" name="motivo_anulacion" required></label>
        <p class="note">La cuota vuelve a quedar pendiente{{ $r->estado === 'pagado' ? ' y el pago registrado queda sin efecto' : '' }}.</p>
        <div><button class="btn danger" type="submit">Anular</button></div>
      </form></details>
    @endif
  </div>
</div>
@endsection
