@props(['name' => 'arrow-right'])

@php
    $brandPath = \App\Support\Icons::BRAND[$name] ?? null;
    $outlinePaths = \App\Support\Icons::OUTLINE[$name] ?? \App\Support\Icons::OUTLINE['arrow-right'];

    /**
     * Varsayılan boyut yalnızca çağıran bir boyut vermediğinde uygulanır.
     * Aksi halde "h-5 w-5 h-4 w-4" gibi çakışan sınıflar oluşur ve
     * Tailwind çıktısında h-5 daha sonra geldiği için istenen boyut ezilir.
     */
    $hasCustomSize = preg_match('/(^|\s)(size-|[hw]-)/', (string) $attributes->get('class')) === 1;
    $sizeClass = $hasCustomSize ? [] : ['h-5 w-5'];
@endphp

@if ($brandPath)
    <svg {{ $attributes->class($sizeClass)->merge(['aria-hidden' => 'true']) }} viewBox="0 0 24 24" fill="currentColor">
        <path d="{{ $brandPath }}" />
    </svg>
@else
    <svg {{ $attributes->class($sizeClass)->merge(['aria-hidden' => 'true']) }} viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        @foreach (explode('|', $outlinePaths) as $path)
            <path d="{{ $path }}" />
        @endforeach
    </svg>
@endif
