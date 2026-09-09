@props([
    'eyebrow' => null,
    'title' => '',
    'text' => null,
    'linkLabel' => null,
    'linkUrl' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="max-w-2xl">
        @if ($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        <h2 class="display-2 mt-3 text-balance">{{ $title }}</h2>
        @if ($text)
            <p class="lead mt-3">{{ $text }}</p>
        @endif
    </div>

    @if ($linkLabel && $linkUrl)
        <a href="{{ $linkUrl }}" class="group inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-forest">
            {{ $linkLabel }}
            <span class="arrow-btn h-8 w-8"><x-ui.icon name="arrow-right" class="h-4 w-4" /></span>
        </a>
    @endif
</div>
