@props(['name', 'show' => false, 'maxWidth' => 'md', 'title' => null])

@php
    $widths = ['sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-md', 'lg' => 'sm:max-w-lg', 'xl' => 'sm:max-w-xl', '2xl' => 'sm:max-w-2xl'];
@endphp

<div
    x-data="{ open: @js($show) }"
    x-on:open-modal.window="$event.detail === '{{ $name }}' && (open = true)"
    x-on:close-modal.window="$event.detail === '{{ $name }}' && (open = false)"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
    role="dialog"
    aria-modal="true"
    @if($title) aria-label="{{ $title }}" @endif
>
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-brand-deep/50" x-on:click="open = false" aria-hidden="true"></div>

    <div x-show="open" x-transition class="relative mx-auto mt-10 w-full {{ $widths[$maxWidth] ?? $widths['md'] }}">
        <div class="card overflow-hidden p-0">
            @if ($title)
                <div class="flex items-center justify-between border-b border-stone px-5 py-4 dark:border-dark-border">
                    <h2 class="text-base font-semibold text-ink dark:text-dark-ink">{{ $title }}</h2>
                    <button type="button" class="btn-ghost px-2 py-1" x-on:click="open = false" aria-label="Tutup dialog">✕</button>
                </div>
            @endif
            <div class="px-5 py-4">{{ $slot }}</div>
        </div>
    </div>
</div>
