<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('titulo', 'Inicio') · Aportes Simple</title>
<script>(function(){try{var t=localStorage.getItem('tema');if(t)document.documentElement.dataset.theme=t}catch(e){}})();</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v=3">
</head>
<body>
@php
  $u = auth()->user();
  $nAlertas = app(\App\Services\Alertas::class)->para($u)->count();
  $menu = [
    ['Operación', true, [
      ['dashboard', 'Panel', 'panel', 'dashboard'],
      ['alertas', 'Alertas', 'campana', 'alertas', $nAlertas],
      ['asistente.index', 'Asistente IA', 'chispa', 'asistente.*'],
    ]],
    ['Afiliaciones', true, [
      ['preafiliaciones.create', 'Nueva afiliación (FUA)', 'nuevo', 'preafiliaciones.create'],
      ['preafiliaciones.index', 'Radicados', 'lista', ['preafiliaciones.index', 'preafiliaciones.show']],
      ['afiliados.index', 'Afiliados', 'personas', 'afiliados.*'],
      ['traslados.index', 'Traslados', 'traslado', 'traslados.*'],
      ['reportes.index', 'Reportes', 'grafica', 'reportes.*'],
    ]],
    ['Incapacidades', true, [
      ['incapacidades.index', 'Seguimiento y cobro', 'pulso', ['incapacidades.index', 'incapacidades.show', 'incapacidades.create']],
      ['incapacidades.calculadora', 'Calculadora', 'calculadora', 'incapacidades.calculadora'],
    ]],
    ['Ventas y cobro', $u->esInterno(), [
      ['ventas.index', 'Ventas', 'venta', ['ventas.index', 'ventas.show', 'ventas.create']],
      ['cuotas.index', 'Cuotas del mes', 'calendario', 'cuotas.*'],
      ['recibos.index', 'Recibos', 'recibo', 'recibos.*'],
      ['tarifarios.index', 'Planes y tarifas', 'etiqueta', 'tarifarios.*'],
    ]],
    ['Clientes', $u->esInterno(), [
      ['empresas.index', 'Sedes y empresas', 'empresa', 'empresas.*'],
    ]],
    ['Administración', $u->esAdmin(), [
      ['usuarios.index', 'Usuarios', 'usuario', 'usuarios.*'],
      ['auditoria.index', 'Auditoría', 'escudo', 'auditoria.*'],
      ['configuracion.edit', 'Configuración', 'ajustes', 'configuracion.*'],
    ]],
  ];
  $iniciales = collect(explode(' ', $u->name))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
@endphp
<div class="shell">
  <aside class="rail" id="rail" aria-label="Menú principal">
    <a class="logo" href="{{ route('dashboard') }}"><b>AS</b><div>Aportes Simple<small>{{ $u->esEmpresa() ? $u->empresa?->nombre : 'Blindem' }}</small></div></a>
    <nav class="nav">
      @foreach($menu as [$grupo, $ver, $items])
        @continue(!$ver)
        <div class="grp">{{ $grupo }}</div>
        @foreach($items as $it)
          <a href="{{ route($it[0]) }}" @class(['on' => request()->routeIs(...(array) $it[3])])>@include('partials.icono', ['i' => $it[2]])<span>{{ $it[1] }}</span>@if(!empty($it[4]))<em class="n">{{ $it[4] }}</em>@endif</a>
        @endforeach
      @endforeach
    </nav>
    <div class="user">
      <span class="avatar">{{ $iniciales }}</span>
      <div><b>{{ $u->name }}</b><span>{{ \App\Models\User::ROLES[$u->rol] ?? $u->rol }}</span></div>
      <a href="{{ route('perfil') }}" class="icon-btn" title="Mi perfil" aria-label="Mi perfil">@include('partials.icono', ['i' => 'ajustes', 'tam' => 16])</a>
      <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="icon-btn" title="Cerrar sesión" aria-label="Cerrar sesión">@include('partials.icono', ['i' => 'salir', 'tam' => 16])</button></form>
    </div>
  </aside>
  <div class="velo" data-cerrar-menu></div>
  <main>
    <div class="topbar">
      <button class="icon-btn menu-btn" type="button" data-abrir-menu aria-label="Abrir menú">@include('partials.icono', ['i' => 'menu'])</button>
      <button class="buscador" type="button" data-abrir-paleta>@include('partials.icono', ['i' => 'buscar', 'tam' => 16])<span>Buscar personas, radicados, empresas… o preguntar a la IA</span><kbd>Ctrl K</kbd></button>
      <button class="icon-btn" type="button" data-tema aria-label="Cambiar tema claro u oscuro">@include('partials.icono', ['i' => 'luna', 'icoClase' => 'si-claro'])@include('partials.icono', ['i' => 'sol', 'icoClase' => 'si-oscuro'])</button>
      <button class="btn ia" type="button" data-abrir-ia>@include('partials.icono', ['i' => 'chispa', 'tam' => 16])<span>Asistente</span></button>
    </div>
    <div class="wrap">
      @if(config('app.demo'))<div class="flash info"><b>Demo:</b> todos los datos son ficticios y se pueden borrar en cualquier momento.</div>@endif
      @if(session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
      @if($errors->any())<div class="flash bad">Revisa los datos: {{ $errors->first() }}</div>@endif
      @yield('contenido')
    </div>
  </main>
</div>

{{-- Paleta de comandos: búsqueda global y accesos rápidos --}}
<dialog class="paleta" id="paleta" aria-label="Buscar">
  <div class="paleta-in">
    <label class="paleta-q">@include('partials.icono', ['i' => 'buscar'])<input id="paleta-q" type="text" autocomplete="off" placeholder="Escribe un nombre, documento, radicado o pregunta" aria-label="Buscar"></label>
    <div id="paleta-res" class="paleta-res" role="listbox"></div>
    <div class="paleta-pie"><span><kbd>↑</kbd><kbd>↓</kbd> moverse</span><span><kbd>Enter</kbd> abrir</span><span><kbd>Esc</kbd> cerrar</span></div>
  </div>
</dialog>

{{-- Asistente de IA en panel lateral --}}
<aside class="ia-panel" id="ia-panel" aria-label="Asistente de IA" hidden>
  <header><b>@include('partials.icono', ['i' => 'chispa', 'tam' => 16]) Asistente IA</b>
    <div><a class="icon-btn" href="{{ route('asistente.index') }}" title="Abrir en pantalla completa" aria-label="Pantalla completa">@include('partials.icono', ['i' => 'flecha', 'tam' => 16])</a><button class="icon-btn" type="button" data-cerrar-ia aria-label="Cerrar asistente">@include('partials.icono', ['i' => 'cerrar', 'tam' => 16])</button></div>
  </header>
  @include('asistente._chat', ['historial' => session('asistente', []), 'compacto' => true])
</aside>

@php
  $acciones = collect([
    ['Nueva afiliación (FUA)', route('preafiliaciones.create'), true],
    ['Registrar incapacidad', route('incapacidades.create'), true],
    ['Calculadora de incapacidades', route('incapacidades.calculadora'), true],
    ['Nueva venta', route('ventas.create'), $u->esInterno()],
    ['Cuotas del mes', route('cuotas.index'), $u->esInterno()],
    ['Ver alertas', route('alertas'), true],
    ['Reportes y exportar', route('reportes.index'), true],
  ])->filter(fn ($a) => $a[2])->map(fn ($a) => ['t' => $a[0], 'u' => $a[1]])->values();
  $cfgJs = ['buscar' => route('buscar'), 'ia' => route('asistente.mensaje'), 'iaReiniciar' => route('asistente.reiniciar'), 'csrf' => csrf_token(), 'acciones' => $acciones];
@endphp
<script>window.AS=@json($cfgJs);</script>
<script src="{{ asset('js/app.js') }}?v=3" defer></script>
@stack('scripts')
</body>
</html>
