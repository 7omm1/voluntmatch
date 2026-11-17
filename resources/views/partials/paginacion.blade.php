@if ($paginator->hasPages())
    <nav class="admin-pagination" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span class="disabled">Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
        @endif

        <span class="admin-pagination-info">
            Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}
            · {{ $paginator->total() }} registros
        </span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
        @else
            <span class="disabled">Siguiente</span>
        @endif
    </nav>
@endif
