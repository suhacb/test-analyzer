@if ($paginator->hasPages())
@php
    $btn    = 'display:inline-flex;align-items:center;justify-content:center;height:2rem;padding:0 .65rem;border-radius:5px;font-size:.82rem;text-decoration:none;font-family:inherit;line-height:1;';
    $normal = $btn . 'background:#f1f5f9;color:#475569;';
    $muted  = $btn . 'background:#f1f5f9;color:#cbd5e1;cursor:default;';
@endphp
<nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
     style="display:flex;align-items:center;gap:.5rem;margin-top:1rem">

    @if ($paginator->onFirstPage())
        <span style="{{ $muted }}" aria-disabled="true">‹ {!! __('pagination.previous') !!}</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" style="{{ $normal }}">‹ {!! __('pagination.previous') !!}</a>
    @endif

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" style="{{ $normal }}">{!! __('pagination.next') !!} ›</a>
    @else
        <span style="{{ $muted }}" aria-disabled="true">{!! __('pagination.next') !!} ›</span>
    @endif

</nav>
@endif
