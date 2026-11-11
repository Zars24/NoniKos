@props(['label' => null, 'name', 'value' => null, 'required' => false, 'help' => null])

@if ($label)
    <label for="{{ $name }}" class="label">{{ $label }}@if($required)<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
@endif

<input
    id="{{ $name }}"
    name="{{ $name }}"
    value="{{ old($name, $value) }}"
    @if($required) required aria-required="true" @endif
    @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
    {{ $attributes->merge(['class' => 'input mt-1']) }}
>

@if ($help)
    <p class="hint mt-1">{{ $help }}</p>
@endif
@error($name)
    <p id="{{ $name }}-error" class="mt-1 text-xs text-danger dark:text-dark-danger">{{ $message }}</p>
@enderror
