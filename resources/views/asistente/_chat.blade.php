@php($disp = \App\Services\Asistente\Asistente::disponible())
<div class="chat {{ !empty($compacto) ? 'compacto' : '' }}" data-chat>
  <div class="chat-log" data-log aria-live="polite">
    @forelse($historial as $m)
      <div class="msg {{ $m['role'] === 'user' ? 'yo' : 'ia' }}">@if($m['role'] === 'user'){{ $m['content'] }}@else{!! \Illuminate\Support\Str::markdown($m['content'], ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}@endif</div>
    @empty
      <div class="chat-vacio" data-vacio>
        <span class="burbuja">@include('partials.icono', ['i' => 'chispa', 'tam' => 22])</span>
        <p><b>Pregúntame sobre tu operación.</b><br>Consulto los datos de la app en tiempo real, solo con lo que tu usuario puede ver.</p>
        <div class="sugerencias">
          @foreach(array_filter([
            '¿Qué requiere atención hoy?',
            '¿Qué radicados están pendientes de corrección y por qué?',
            '¿Cuánto dinero tenemos por recobrar en incapacidades?',
            auth()->user()->esInterno() ? '¿Qué clientes deben cuotas este mes?' : null,
            'Calcula una incapacidad de enfermedad general de 20 días con IBC de 2.000.000',
          ]) as $s)<button type="button" class="chip" data-sugerencia>{{ $s }}</button>@endforeach
        </div>
      </div>
    @endforelse
  </div>
  @if($disp)
  <form class="chat-form" data-form>
    <textarea name="pregunta" rows="1" maxlength="2000" placeholder="Escribe tu pregunta…" aria-label="Pregunta para el asistente" required></textarea>
    <button class="btn pri" type="submit" aria-label="Enviar">@include('partials.icono', ['i' => 'enviar', 'tam' => 16])</button>
  </form>
  <div class="chat-pie"><button type="button" class="link" data-reiniciar>Nueva conversación</button><span>La IA puede equivocarse; verifica los valores antes de radicar.</span></div>
  @else
  <div class="flash info">El asistente no está activado. @if(auth()->user()->esAdmin())<a href="{{ route('configuracion.edit') }}#ia">Pon la clave de API en Configuración</a>.@else Pide a un administrador que lo active.@endif</div>
  @endif
</div>
