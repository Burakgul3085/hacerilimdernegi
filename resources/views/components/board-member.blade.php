@props([
    'name' => '',
    'title' => '',
    'photo' => null,
    'bio' => null,
    'initials' => '',
    'featured' => false,
])

@php
    $photoUrl = filled($photo) ? \Illuminate\Support\Facades\Storage::disk('public')->url($photo) : null;
@endphp

<article {{ $attributes->merge(['class' => 'board-card card card-hover overflow-hidden'.($featured ? ' board-card-featured' : '')]) }}>
    <div @class([
        'board-photo relative overflow-hidden bg-cream-deep',
        'aspect-[4/5]' => $featured,
        'aspect-square' => ! $featured,
    ])>
        @if ($photoUrl)
            <img src="{{ $photoUrl }}" alt="{{ $name }}" loading="lazy" decoding="async"
                 class="h-full w-full object-cover">
        @else
            <div class="flex h-full w-full items-center justify-center">
                <span class="font-display text-4xl tracking-wide text-gold/80 {{ $featured ? 'sm:text-5xl' : '' }}">{{ $initials }}</span>
            </div>
        @endif
    </div>

    <div class="px-5 py-5 text-center {{ $featured ? 'sm:px-8 sm:py-6' : '' }}">
        @if (filled($title))
            <p class="eyebrow">{{ $title }}</p>
        @endif
        <h3 class="mt-2 font-display leading-snug text-forest {{ $featured ? 'text-3xl' : 'text-2xl' }}">{{ $name }}</h3>
        @if (filled($bio))
            <p class="mt-3 text-sm leading-relaxed text-muted">{{ $bio }}</p>
        @endif
    </div>
</article>
