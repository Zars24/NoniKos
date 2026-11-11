@props(['title' => 'Belum ada data', 'description' => null, 'icon' => '◇'])

<div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-stone bg-white/60 px-6 py-12 text-center dark:border-dark-border dark:bg-dark-surface/60">
    <span class="text-3xl text-champagne" aria-hidden="true">{{ $icon }}</span>
    <p class="mt-3 text-sm font-medium text-ink dark:text-dark-ink">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-sage dark:text-dark-sage">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
