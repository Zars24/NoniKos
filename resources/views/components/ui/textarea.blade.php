@props(['label' => null, 'name', 'value' => null, 'rows' => 3, 'required' => false])

@if ($label)
    <label for="{{ $name }}" class="label">{{ $label }}@if($required)<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
@endif

<textarea
    id="{{ $name }}"
    name="{{ $name }}"
    rows="{{ $rows }}"
    @if($required) required @endif
    @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
    {{ $attributes->merge(['class' => 'input mt-1']) }}
>{{ old($name, $value) }}</textarea>

@error($name)
    <p id="{{ $name }}-error" class="mt-1 text-xs text-danger dark:text-dark-danger">{{ $message }}</p>
@enderror
