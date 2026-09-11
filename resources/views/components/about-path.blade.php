@props([
    'title',
    'text',
    'href',
    'icon',
    'delay' => '0ms',
    'index' => '01',
])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'about-path cinematic-panel reveal', 'aria-label' => $title]) }} style="--reveal-delay: {{ $delay }}">
    <span class="about-path-index" aria-hidden="true">{{ $index }}</span>
    <span class="about-path-icon">
        <x-ui.icon :name="$icon" class="h-5 w-5" />
    </span>
    <span class="about-path-copy">
        <span class="about-path-title">{{ $title }}</span>
        <span class="about-path-text">{{ $text }}</span>
    </span>
    <span class="about-path-arrow" aria-hidden="true">
        <x-ui.icon name="arrow-right" class="h-4 w-4" />
    </span>
</a>
