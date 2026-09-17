@props([
    'items' => [],
    'alt' => '',
])

@php
    $photos = array_values(array_filter(
        is_array($items) ? $items : [],
        fn (mixed $item): bool => is_array($item)
            && filled($item['url'] ?? null)
            && (($item['kind'] ?? 'image') === 'image')
    ));
    $others = array_values(array_filter(
        is_array($items) ? $items : [],
        fn (mixed $item): bool => is_array($item)
            && filled($item['url'] ?? null)
            && (($item['kind'] ?? 'image') !== 'image')
    ));
    $strip = $photos === [] ? [] : [...$photos, ...$photos];
@endphp

@if ($photos !== [])
    <div {{ $attributes->class('activity-marquee') }} x-data="{ lightbox: null }" @keydown.escape.window="lightbox = null">
        <div class="activity-marquee-viewport" aria-label="{{ $alt }} galerisi">
            <div class="activity-marquee-track">
                @foreach ($strip as $index => $item)
                    @php
                        $isClone = $index >= count($photos);
                        $photoIndex = $index % count($photos);
                    @endphp
                    <button type="button"
                            class="activity-marquee-item"
                            data-src="{{ $item['url'] }}"
                            x-on:click="lightbox = $el.dataset.src"
                            @if ($isClone) tabindex="-1" aria-hidden="true" @endif>
                        <img src="{{ $item['url'] }}"
                             alt="{{ $isClone ? '' : $alt.' görseli '.($photoIndex + 1) }}"
                             loading="lazy"
                             decoding="async"
                             draggable="false">
                    </button>
                @endforeach
            </div>
        </div>

        <div x-cloak
             x-show="lightbox"
             x-on:click.self="lightbox = null"
             class="post-lightbox"
             role="dialog"
             aria-modal="true"
             aria-label="Görsel">
            <img :src="lightbox" alt="{{ $alt }}">
        </div>
    </div>
@endif

@if ($others !== [])
    <div class="activity-gallery-extras mt-6">
        @foreach ($others as $item)
            @if (($item['kind'] ?? '') === 'video')
                <div class="activity-gallery-tile activity-gallery-tile-video">
                    <div class="activity-gallery-frame">
                        <video src="{{ $item['url'] }}" controls playsinline preload="metadata"></video>
                    </div>
                </div>
            @elseif (($item['kind'] ?? '') === 'audio')
                <div class="post-gallery-item post-gallery-item-audio">
                    <audio src="{{ $item['url'] }}" controls preload="metadata"></audio>
                </div>
            @endif
        @endforeach
    </div>
@endif
