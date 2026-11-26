<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('titulo', 'Inicio') · Aportes Simple</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v=2">
</head>
<body>
@php($u = auth()->user())
<div class="shell">
  <aside class="rail" id="rail">
    <div class="rail-top">
      <a class="logo" href="{{ route('dashboard') }}"><b>AS</b><div>Aportes Simple<small>{{ $u->esEmpresa() ? $u->empresa?->nombre : 'Blindem' }}</small></div></a>
      <button class="btn menu-btn" type="button" onclick="document.getElementById('rail').classList.toggle('open')">Menú</button>
    </div>
    <nav class="nav">
      <div class="grp">Operación</div>
      <a href="{{ route('dashboard') }}" @class(['on' => request()->routeIs('dashboard')])>Panel</a>
      <a href="{{ route('alertas') }}" @class(['on' => request()->routeIs('alertas')])>Alertas @if(($nAlertas = app(\App\Services\Alertas::class)->para($u)->count()) > 0)<span class="n">{{ $nAlertas }}</span>@endif</a>
      <div class="grp">Afiliaciones</div>
      <a href="{{ route('preafiliaciones.create') }}" @class(['on' => request()->routeIs('preafiliaciones.create')])>FUA / Pre-afiliación</a>
      <a href="{{ route('preafiliaciones.index') }}" @class(['on' => request()->routeIs('preafiliaciones.index', 'preafiliaciones.show')])>Radicados</a>
      <a href="{{ route('afiliados.index') }}" @class(['on' => request()->routeIs('afiliados.*')])>Consulta de afiliados</a>
      <a href="{{ route('traslados.index') }}" @class(['on' => request()->routeIs('traslados.*')])>Traslados</a>
      <a href="{{ route('reportes.index') }}" @class(['on' => request()->routeIs('reportes.*')])>Reportes</a>
      <div class="grp">Incapacidades</div>
      <a href="{{ route('incapacidades.index') }}" @class(['on' => request()->routeIs('incapacidades.index', 'incapacidades.show', 'incapacidades.create')])>Seguimiento y cobro</a>
      <a href="{{ route('incapacidades.calculadora') }}" @class(['on' => request()->routeIs('incapacidades.calculadora')])>Calculadora</a>
      @if($u->esInterno())
      <div class="grp">Clientes</div>
      <a href="{{ route('empresas.index') }}" @class(['on' => request()->routeIs('empresas.*')])>Sedes y empresas</a>
      <div class="grp">Ventas y cobro</div>
      <a href="{{ route('ventas.create') }}" @class(['on' => request()->routeIs('ventas.create')])>Nueva venta</a>
      <a href="{{ route('ventas.index') }}" @class(['on' => request()->routeIs('ventas.index', 'ventas.show')])>Ventas</a>
      <a href="{{ route('cuotas.index') }}" @class(['on' => request()->routeIs('cuotas.*')])>Cuotas del mes</a>
      <a href="{{ route('recibos.index') }}" @class(['on' => request()->routeIs('recibos.*')])>Recibos</a>
      <a href="{{ route('tarifarios.index') }}" @class(['on' => request()->routeIs('tarifarios.*')])>Planes y tarifas</a>
      @endif
      @if($u->esAdmin())
      <div class="grp">Administración</div>
      <a href="{{ route('usuarios.index') }}" @class(['on' => request()->routeIs('usuarios.*')])>Usuarios</a>
      <a href="{{ route('auditoria.index') }}" @class(['on' => request()->routeIs('auditoria.*')])>Auditoría</a>
      <a href="{{ route('configuracion.edit') }}" @class(['on' => request()->routeIs('configuracion.*')])>Configuración</a>
      @endif
    </nav>
    <div class="user">
      <b>{{ $u->name }}</b>
      <span>{{ \App\Models\User::ROLES[$u->rol] ?? $u->rol }}</span>
      <a href="{{ route('perfil') }}" style="color:var(--rail-fg)">Mi perfil</a>
      <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">Cerrar sesión</button></form>
    </div>
  </aside>
  <main><div class="wrap">
    @if(config('app.demo'))<div class="flash" style="background:var(--info-bg)"><b>Demo:</b> todos los datos son ficticios y se pueden borrar en cualquier momento.</div>@endif
    @if(session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
    @if($errors->any())<div class="flash bad">Revisa los datos: {{ $errors->first() }}</div>@endif
    @yield('contenido')
  </div></main>
</div>
@stack('scripts')
</body>
</html>
