@props(['variant' => 'neutral', 'label' => null, 'icon' => null])

@php
    $variants = [
        'neutral' => 'bg-ivory text-ink border-stone dark:bg-dark-elevated dark:text-dark-sage dark:border-dark-border',
        'brand' => 'bg-brand/10 text-brand border-brand/20 dark:bg-dark-champagne/10 dark:text-dark-champagne dark:border-dark-champagne/20',
        'success' => 'bg-success-soft text-success border-success/20 dark:bg-dark-success/10 dark:text-dark-success dark:border-dark-success/20',
        'warning' => 'bg-warning-soft text-warning border-warning/20 dark:bg-dark-warning/10 dark:text-dark-warning dark:border-dark-warning/20',
        'danger' => 'bg-danger-soft text-danger border-danger/20 dark:bg-dark-danger/10 dark:text-dark-danger dark:border-dark-danger/20',
        'info' => 'bg-info-soft text-info border-info/20 dark:bg-info/10 dark:text-[#9DC3DA] dark:border-info/30',
        'champagne' => 'bg-champagne/15 text-champagne-deep border-champagne/30 dark:bg-dark-champagne/10 dark:text-dark-champagne dark:border-dark-champagne/30',
        'muted' => 'bg-stone/40 text-sage border-stone dark:bg-dark-border/40 dark:text-dark-sage dark:border-dark-border',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium '.($variants[$variant] ?? $variants['neutral'])]) }}>
    @if ($icon)<span aria-hidden="true">{{ $icon }}</span>@endif
    {{ $label ?? $slot }}
</span>
