@extends('layouts.app')
@section('titulo', 'Venta #'.$v->id)
@section('contenido')
<header class="top">
  <div><h1>{{ $v->nombre_cliente }} {{ pill($v->estado, \App\Models\Venta::ESTADOS) }}</h1>
  <p class="sub">Venta #{{ $v->id }} · {{ $v->plan->nombre }} · {{ $v->tipo_documento }} {{ $v->documento }}</p></div>
  <a class="btn" href="{{ route('ventas.index') }}">Volver</a>
</header>
<div class="grid2">
  <div style="display:flex;flex-direction:column;gap:16px;min-width:0">
    <section class="card">
      <dl class="datos">
        <div><dt>Plan</dt><dd>{{ $v->plan->nombre }}<br><small>{{ implode(' + ', $v->plan->subsistemas()) }}</small></dd></div>
        <div><dt>Riesgo ARL</dt><dd>{{ \App\Models\Venta::NIVELES[$v->nivel_riesgo] }}</dd></div>
        <div><dt>Sede</dt><dd>{{ $v->sede->nombre }}</dd></div>
        <div><dt>Cotiza desde</dt><dd>{{ ucfirst($v->periodo_inicial->translatedFormat('F Y')) }}</dd></div>
        <div><dt>Celular</dt><dd>{{ $v->celular ?? '—' }}</dd></div>
        <div><dt>Correo</dt><dd>{{ $v->email ?? '—' }}</dd></div>
        <div><dt>Tarifario</dt><dd>{{ $v->tarifario->anio }}</dd></div>
        @if($v->estado === 'retirada')<div><dt>Retiro</dt><dd>{{ $v->fecha_retiro->format('d/m/Y') }}<br><small>{{ $v->motivo_retiro }}</small></dd></div>@endif
      </dl>
    </section>
    <section class="card"><h2>Cuotas</h2>
      @if($v->cuotas->isEmpty())<div class="empty">Sin cuotas todavía.</div>@else
      <div class="tbl"><table>
        <thead><tr><th>Periodo</th><th>Vence</th><th class="num">Valor</th><th>Estado</th><th>Recibo</th></tr></thead>
        <tbody>@foreach($v->cuotas as $c)
          <tr><td>{{ $c->periodo_nombre }}</td><td class="nowrap">{{ $c->fecha_limite->format('d/m/Y') }}</td><td class="num">{{ pesos($c->valor) }}</td>
          <td>{{ pill($c->estado_visible, \App\Models\Cuota::ESTADOS) }}</td>
          <td>@if($c->reciboVigente)<a class="mono" href="{{ route('recibos.show', $c->reciboVigente) }}">{{ $c->reciboVigente->numero }}</a>@elseif($c->estado === 'pendiente')<form method="post" action="{{ route('cuotas.recibo', $c) }}">@csrf<button class="btn sm" type="submit">Generar recibo</button></form>@else — @endif</td></tr>
        @endforeach</tbody></table></div>@endif
    </section>
  </div>
  <div style="display:flex;flex-direction:column;gap:16px;min-width:0">
    <section class="card"><h2>Valor mensual</h2>
      <div class="tbl"><table><tbody>
        @foreach(['eps' => 'Salud (EPS)', 'afp' => 'Pensión (AFP)', 'arl' => 'Riesgos (ARL)', 'ccf' => 'Caja (CCF)'] as $k => $t)@if($v->$k)<tr><td>{{ $t }}</td><td class="num">{{ pesos($v->$k) }}</td></tr>@endif @endforeach
        <tr><td>Administración</td><td class="num">{{ pesos($v->administracion) }}</td></tr>
        <tr><td><b>Total</b></td><td class="num"><b>{{ pesos($v->valor_total) }}</b></td></tr>
      </tbody></table></div>
    </section>
    @if($v->estado === 'activa')
    <details class="card dd">
      <summary class="btn" style="align-self:flex-start">Registrar retiro</summary>
      <form method="post" action="{{ route('ventas.retirar', $v) }}" style="display:flex;flex-direction:column;gap:10px;margin-top:12px">@csrf
        <label for="fecha_retiro">Fecha de retiro<input id="fecha_retiro" type="date" name="fecha_retiro" value="{{ now()->toDateString() }}" required></label>
        <label for="motivo_retiro">Motivo<input id="motivo_retiro" name="motivo_retiro" required></label>
        <p class="note">Se anulan las cuotas pendientes de los meses siguientes al retiro.</p>
        <div><button class="btn danger" type="submit">Retirar</button></div>
      </form>
    </details>
    @endif
  </div>
</div>
@endsection
