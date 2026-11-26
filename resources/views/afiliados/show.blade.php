@extends('layouts.app')
@section('titulo', $a->nombre_completo)
@section('contenido')
<header class="top"><div><h1>{{ $a->nombre_completo }}</h1><p class="sub mono">{{ $a->tipo_documento }} {{ $a->documento }} · {{ $a->empresa->nombre }}</p></div>
<div class="filters"><a class="btn" href="{{ route('incapacidades.create', ['afiliado_id' => $a->id]) }}">Registrar incapacidad</a><a class="btn pri" href="{{ route('preafiliaciones.create') }}">Nueva afiliación</a></div></header>
<section class="card"><h2>Afiliaciones</h2>@include('preafiliaciones._tabla', ['items' => $a->preafiliaciones->each->setRelation('afiliado', $a)->each->setRelation('empresa', $a->empresa)])</section>
<div class="grid2">
<section class="card"><h2>Incapacidades</h2>
  @forelse($a->incapacidades as $i)<div><a class="mono" href="{{ route('incapacidades.show', $i) }}">{{ $i->codigo }}</a> · {{ $i->tipo_nombre }} · {{ $i->dias }} días desde {{ $i->fecha_inicio->format('d/m/Y') }} {{ pill($i->estado, \App\Models\Incapacidad::ESTADOS) }}</div>@empty<p class="note">Sin incapacidades.</p>@endforelse
</section>
<section class="card"><h2>Traslados</h2>
  @forelse($a->traslados as $t)<div><span class="ent">{{ $t->tipo }}</span>{{ $t->origen->nombre }} → {{ $t->destino->nombre }} {{ pill($t->estado, \App\Models\Traslado::ESTADOS) }}</div>@empty<p class="note">Sin traslados.</p>@endforelse
</section>
</div>
@endsection
