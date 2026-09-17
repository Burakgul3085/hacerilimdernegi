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
        'aspect-[4/5]' => blank($photoUrl),
    ])>
        @if ($photoUrl)
            <img src="{{ $photoUrl }}" alt="{{ $name }}" loading="lazy" decoding="async"
                 class="board-photo-fit">
        @else
            <img src="{{ $placeholderUrl }}" alt="" aria-hidden="true" loading="lazy" decoding="async"
                 class="board-photo-placeholder">
        @endif
    </div>

    <div class="px-4 py-4 text-center {{ $featured ? 'sm:px-5 sm:py-5' : '' }}">
        @if (filled($title))
            <p class="eyebrow">{{ $title }}</p>
        @endif
        <h3 class="mt-2 font-display leading-snug text-forest {{ $featured ? 'text-[1.55rem] sm:text-[1.7rem]' : 'text-[1.2rem]' }}">{{ $name }}</h3>
        @if (filled($bio))
            <p class="mt-2 text-[13px] leading-relaxed text-muted">{{ $bio }}</p>
        @endif
    </div>
</article>
