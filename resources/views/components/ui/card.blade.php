@props(['title' => null, 'description' => null, 'padding' => 'p-5'])

<div {{ $attributes->merge(['class' => "card $padding"]) }}>
    @if ($title)
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold text-ink dark:text-dark-ink">{{ $title }}</h3>
                @if ($description)
                    <p class="mt-1 text-sm text-sage dark:text-dark-sage">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    {{ $slot }}
</div>
