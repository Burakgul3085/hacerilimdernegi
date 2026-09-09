@props(['name' => 'arrow-right'])

@php
    $brandPath = \App\Support\Icons::BRAND[$name] ?? null;
    $outlinePaths = \App\Support\Icons::OUTLINE[$name] ?? \App\Support\Icons::OUTLINE['arrow-right'];
@endphp

@if ($brandPath)
    <svg {{ $attributes->merge(['class' => 'h-5 w-5', 'aria-hidden' => 'true']) }} viewBox="0 0 24 24" fill="currentColor">
        <path d="{{ $brandPath }}" />
    </svg>
@else
    <svg {{ $attributes->merge(['class' => 'h-5 w-5', 'aria-hidden' => 'true']) }} viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        @foreach (explode('|', $outlinePaths) as $path)
            <path d="{{ $path }}" />
        @endforeach
    </svg>
@endif
