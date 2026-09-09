@props([
    'name',
    'label' => null,
    'type' => 'text',
    'placeholder' => null,
    'required' => false,
    'value' => null,
    'rows' => 5,
    'options' => null,
    'autocomplete' => null,
])

@php
    $id = 'field-'.$name;
    $current = old($name, $value);
    $hasError = $errors->has($name);
    $ring = $hasError ? 'border-red-400 focus:border-red-500 focus:ring-red-200' : '';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-2']) }}>
    @if ($label)
        <label for="{{ $id }}" class="text-[13px] font-semibold text-forest">
            {{ $label }}@if ($required)<span class="text-gold"> *</span>@endif
        </label>
    @endif

    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}"
                  @if ($required) required @endif
                  class="field {{ $ring }}">{{ $current }}</textarea>
    @elseif ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" @if ($required) required @endif class="field {{ $ring }}">
            <option value="">{{ $placeholder ?? 'Seçiniz' }}</option>
            @foreach ($options ?? [] as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @else
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ $current }}"
               placeholder="{{ $placeholder }}" @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
               @if ($required) required @endif
               class="field {{ $ring }}">
    @endif

    @error($name)
        <p class="text-[13px] text-red-600">{{ $message }}</p>
    @enderror
</div>
