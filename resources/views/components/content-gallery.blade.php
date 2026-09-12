@props([
    'items' => [],
    'alt' => '',
])

@php
    $items = array_values(array_filter(
        is_array($items) ? $items : [],
        fn (mixed $item): bool => is_array($item) && filled($item['url'] ?? null)
    ));
@endphp

@if ($items !== [])
    <div {{ $attributes->class('post-gallery') }} x-data="{ lightbox: null }" @keydown.escape.window="lightbox = null">
        @foreach ($items as $index => $item)
            @if (($item['kind'] ?? 'image') === 'video')
                <div class="post-gallery-item post-gallery-item-video">
                    <video src="{{ $item['url'] }}" controls playsinline preload="metadata"></video>
                </div>
            @elseif (($item['kind'] ?? 'image') === 'audio')
                <div class="post-gallery-item post-gallery-item-audio">
                    <audio src="{{ $item['url'] }}" controls preload="metadata"></audio>
                </div>
            @else
                <button type="button"
                        class="post-gallery-item group"
                        data-src="{{ $item['url'] }}"
                        x-on:click="lightbox = $el.dataset.src">
                    <img src="{{ $item['url'] }}" alt="{{ $alt }} görseli {{ $index + 1 }}" loading="lazy" decoding="async">
                </button>
            @endif
        @endforeach

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
