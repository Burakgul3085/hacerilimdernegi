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
    <div {{ $attributes->class('activity-marquee') }}
         x-data="{
            photos: {{ \Illuminate\Support\Js::from(array_column($photos, 'url')) }},
            index: null,
            open(photoIndex) { this.index = Number(photoIndex) },
            close() { this.index = null },
            step(direction) {
                if (this.index === null || this.photos.length < 2) return
                const count = this.photos.length
                this.index = (this.index + direction + count) % count
            },
         }"
         @keydown.escape.window="close()"
         @keydown.left.window="step(-1)"
         @keydown.right.window="step(1)">
        <div class="activity-marquee-viewport" aria-label="{{ $alt }} galerisi">
            <div class="activity-marquee-track">
                @foreach ($strip as $index => $item)
                    @php
                        $isClone = $index >= count($photos);
                        $photoIndex = $index % count($photos);
                    @endphp
                    <button type="button"
                            class="activity-marquee-item"
                            x-on:click="open({{ $photoIndex }})"
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
             x-show="index !== null"
             x-on:click.self="close()"
             class="post-lightbox post-lightbox-navable"
             role="dialog"
             aria-modal="true"
             aria-label="Görsel">
            <button type="button"
                    class="post-lightbox-nav post-lightbox-prev"
                    x-show="photos.length > 1"
                    x-on:click="step(-1)"
                    aria-label="Önceki görsel">
                <x-ui.icon name="chevron-right" class="h-5 w-5 rotate-180" />
            </button>
            <img :src="index === null ? '' : photos[index]" alt="{{ $alt }}">
            <button type="button"
                    class="post-lightbox-nav post-lightbox-next"
                    x-show="photos.length > 1"
                    x-on:click="step(1)"
                    aria-label="Sonraki görsel">
                <x-ui.icon name="chevron-right" class="h-5 w-5" />
            </button>
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
