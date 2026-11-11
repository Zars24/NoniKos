@props(['label' => null, 'name', 'options' => [], 'selected' => null, 'placeholder' => null, 'required' => false])

@if ($label)
    <label for="{{ $name }}" class="label">{{ $label }}@if($required)<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
@endif

<select
    id="{{ $name }}"
    name="{{ $name }}"
    @if($required) required @endif
    @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
    {{ $attributes->merge(['class' => 'input mt-1']) }}
>
    @if ($placeholder)
        <option value="">{{ $placeholder }}</option>
    @endif
    @foreach ($options as $optionValue => $optionLabel)
        <option value="{{ $optionValue }}" @selected((string) old($name, $selected) === (string) $optionValue)>{{ $optionLabel }}</option>
    @endforeach
</select>

@error($name)
    <p id="{{ $name }}-error" class="mt-1 text-xs text-danger dark:text-dark-danger">{{ $message }}</p>
@enderror
