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
    $placeholderUrl = asset('images/board-hijab-placeholder.png');
@endphp

<article {{ $attributes->merge(['class' => 'board-card card card-hover min-w-0 overflow-hidden'.($featured ? ' board-card-featured' : '')]) }}>
    <div @class([
        'board-photo relative w-full overflow-hidden',
        'bg-cream-deep' => blank($photoUrl),
        'aspect-[4/5]' => blank($photoUrl) && $featured,
        'aspect-square' => blank($photoUrl) && ! $featured,
    ])>
        @if ($photoUrl)
            <img src="{{ $photoUrl }}" alt="{{ $name }}" loading="lazy" decoding="async"
                 class="board-photo-fit">
        @else
            <img src="{{ $placeholderUrl }}" alt="" aria-hidden="true" loading="lazy" decoding="async"
                 class="board-photo-placeholder">
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
