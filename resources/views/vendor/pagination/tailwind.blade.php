@if ($paginator->hasPages())
@php
    $btn     = 'display:inline-flex;align-items:center;justify-content:center;min-width:2rem;height:2rem;padding:0 .5rem;border-radius:5px;font-size:.82rem;text-decoration:none;font-family:inherit;line-height:1;';
    $active  = $btn . 'background:#6366f1;color:#fff;font-weight:600;cursor:default;';
    $normal  = $btn . 'background:#f1f5f9;color:#475569;';
    $muted   = $btn . 'background:#f1f5f9;color:#cbd5e1;cursor:default;';
    $dots    = $btn . 'background:transparent;color:#94a3b8;cursor:default;';
@endphp
<nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
     style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-top:1rem">

    <p style="font-size:.8rem;color:#94a3b8;margin:0">
        @if ($paginator->firstItem())
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        @else
            {{ $paginator->count() }} results
        @endif
    </p>

    <div style="display:flex;align-items:center;gap:.25rem;flex-wrap:wrap">

        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span style="{{ $muted }}" aria-disabled="true">‹</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" style="{{ $normal }}" aria-label="{{ __('pagination.previous') }}">‹</a>
        @endif

        {{-- Page links --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span style="{{ $dots }}">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span style="{{ $active }}" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" style="{{ $normal }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" style="{{ $normal }}" aria-label="{{ __('pagination.next') }}">›</a>
        @else
            <span style="{{ $muted }}" aria-disabled="true">›</span>
        @endif

    </div>
</nav>
@endif
