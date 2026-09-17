@props([
    'src' => null,
    'alt' => '',
    'ratio' => 'aspect-[4/3]',
    'rounded' => '',
    'fit' => 'cover',
])

@php
    $url = filled($src) ? \Illuminate\Support\Facades\Storage::disk('public')->url($src) : null;
    $natural = $fit === 'natural';
    $contain = $fit === 'contain';
    $frame = $fit === 'frame';
@endphp

@if ($frame)
    <div {{ $attributes->merge(['class' => trim('media-frame '.$ratio.' '.$rounded)]) }}>
        @if ($url)
            <img src="{{ $url }}" alt="" aria-hidden="true" loading="lazy" decoding="async" class="media-frame-blur">
            <img src="{{ $url }}" alt="{{ $alt }}" loading="lazy" decoding="async" class="media-frame-img">
        @else
            <div class="media-frame-empty">
                <img src="{{ \App\Support\SiteSettings::logoUrl() }}" alt="" aria-hidden="true"
                     class="h-12 w-auto max-w-[46%] object-contain opacity-25">
            </div>
        @endif
    </div>
@elseif ($natural)
    <div {{ $attributes->merge(['class' => trim('activity-cover '.$rounded)]) }}>
        @if ($url)
            <img src="{{ $url }}" alt="{{ $alt }}" loading="lazy" decoding="async" class="activity-cover-img">
        @else
            <div class="activity-cover-empty">
                <img src="{{ \App\Support\SiteSettings::logoUrl() }}" alt="" aria-hidden="true"
                     class="h-12 w-auto max-w-[46%] object-contain opacity-25">
            </div>
        @endif
    </div>
@elseif ($contain && $url)
    <div {{ $attributes->merge(['class' => trim($rounded.' flex justify-center overflow-hidden')]) }}>
        <img src="{{ $url }}" alt="{{ $alt }}" loading="lazy" decoding="async"
             class="post-media-img">
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
