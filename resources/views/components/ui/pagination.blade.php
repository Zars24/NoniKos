@props(['name' => 'page', 'paginator'])

@if ($paginator->hasPages())
    <nav class="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row" aria-label="Navigasi halaman">
        <p class="text-xs text-sage dark:text-dark-sage">
            Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data
        </p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn-secondary cursor-not-allowed opacity-50" aria-disabled="true">‹ Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn-secondary" rel="prev">‹ Sebelumnya</a>
            @endif

            <span class="px-3 text-xs text-sage dark:text-dark-sage">Hal. {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn-secondary" rel="next">Berikutnya ›</a>
            @else
                <span class="btn-secondary cursor-not-allowed opacity-50" aria-disabled="true">Berikutnya ›</span>
            @endif
        </div>
    </nav>
@endif
