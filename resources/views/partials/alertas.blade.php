<div class="alerts">
@forelse($lista as $a)
  <div class="al {{ $a['nivel'] }}"><span class="s"></span><div><p><b>{{ $a['titulo'] }}</b></p><small>{{ $a['detalle'] }}</small></div><a class="btn sm" href="{{ $a['url'] }}">Ver</a></div>
@empty
  <div class="empty">Todo al día. No hay nada pendiente.</div>
@endforelse
</div>
