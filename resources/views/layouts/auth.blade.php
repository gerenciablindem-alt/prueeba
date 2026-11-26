<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('titulo') · Aportes Simple</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v=2">
</head>
<body>
<div class="auth"><div class="card">
  <div class="logo"><b>AS</b><div>Aportes Simple<small style="color:var(--muted)">Gestión de afiliaciones e incapacidades</small></div></div>
  @if(config('app.demo'))<div class="flash" style="background:var(--info-bg)"><b>Demo con datos ficticios.</b> Entra con <b>demo@example.com</b> / <b>demo12345</b> (administrador) o <b>empresa@example.com</b> / <b>demo12345</b> (empresa cliente).</div>@endif
  @if(session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
  @yield('contenido')
</div></div>
</body>
</html>
