@props(['variant' => 'info', 'title' => null])

@php
    $variants = [
        'info' => 'border-info/20 bg-info-soft text-info dark:border-info/30 dark:bg-info/10 dark:text-[#9DC3DA]',
        'success' => 'border-success/20 bg-success-soft text-success dark:border-dark-success/30 dark:bg-dark-success/10 dark:text-dark-success',
        'warning' => 'border-warning/20 bg-warning-soft text-warning dark:border-dark-warning/30 dark:bg-dark-warning/10 dark:text-dark-warning',
        'danger' => 'border-danger/20 bg-danger-soft text-danger dark:border-dark-danger/30 dark:bg-dark-danger/10 dark:text-dark-danger',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex gap-3 rounded-lg border px-4 py-3 text-sm '.($variants[$variant] ?? $variants['info'])]) }} role="{{ $variant === 'danger' ? 'alert' : 'status' }}">
    <div class="min-w-0">
        @if ($title)<p class="font-semibold">{{ $title }}</p>@endif
        <div class="{{ $title ? 'mt-0.5' : '' }}">{{ $slot }}</div>
    </div>
</div>
