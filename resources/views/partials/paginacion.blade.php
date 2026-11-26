@if($p->hasPages())
<div class="pager">
  <span>{{ $p->firstItem() }}–{{ $p->lastItem() }} de {{ $p->total() }}</span>
  @if(!$p->onFirstPage())<a href="{{ $p->previousPageUrl() }}">Anterior</a>@endif
  <span class="cur">{{ $p->currentPage() }}</span>
  @if($p->hasMorePages())<a href="{{ $p->nextPageUrl() }}">Siguiente</a>@endif
</div>
@endif
