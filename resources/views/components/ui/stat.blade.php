@props(['label', 'value', 'hint' => null, 'href' => null, 'tone' => 'neutral', 'icon' => null])

@php
    $tones = [
        'neutral' => 'text-ink dark:text-dark-ink',
        'brand' => 'text-brand dark:text-dark-champagne',
        'success' => 'text-success dark:text-dark-success',
        'warning' => 'text-warning dark:text-dark-warning',
        'danger' => 'text-danger dark:text-dark-danger',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif class="card block p-4 transition hover:shadow-lift focus-visible:shadow-lift">
    <div class="flex items-start justify-between gap-2">
        <p class="text-xs font-medium uppercase tracking-wide text-sage dark:text-dark-sage">{{ $label }}</p>
        @if ($icon)<span class="text-lg text-champagne" aria-hidden="true">{{ $icon }}</span>@endif
    </div>
    <p class="mt-2 text-2xl font-semibold tabular {{ $tones[$tone] ?? $tones['neutral'] }}">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-sage dark:text-dark-sage">{{ $hint }}</p>
    @endif
</{{ $tag }}>
