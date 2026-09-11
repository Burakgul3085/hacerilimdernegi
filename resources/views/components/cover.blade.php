@props([
    'src' => null,
    'alt' => '',
    'ratio' => 'aspect-[4/3]',
    'rounded' => '',
    'fit' => 'cover',
])

@php
    $url = filled($src) ? \Illuminate\Support\Facades\Storage::disk('public')->url($src) : null;
    $contain = $fit === 'contain';
@endphp

@if ($contain && $url)
    <div {{ $attributes->merge(['class' => trim($rounded.' overflow-hidden')]) }}>
        <img src="{{ $url }}" alt="{{ $alt }}" loading="lazy" decoding="async"
             class="post-media-img block h-auto w-full">
    </div>
@else
    <div {{ $attributes->merge(['class' => trim($ratio.' '.$rounded.' relative overflow-hidden bg-cream-deep')]) }}>
        @if ($url)
            <img src="{{ $url }}" alt="{{ $alt }}" loading="lazy" decoding="async"
                 class="h-full w-full object-cover transition duration-[1100ms] ease-out group-hover:scale-[1.08]">
        @else
            <div class="grain flex h-full w-full items-center justify-center">
                <img src="{{ \App\Support\SiteSettings::logoUrl() }}" alt="" aria-hidden="true"
                     class="h-14 w-auto max-w-[60%] object-contain opacity-25">
            </div>
        @endif
    </div>
@endif
