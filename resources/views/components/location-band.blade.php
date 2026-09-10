@props([
    'settings' => [],
])

@php
    $address = $settings['address'] ?? '';
    $mapEmbed = $settings['map_embed'] ?? '';
    $eyebrow = $settings['home_location_eyebrow'] ?? 'Bizi ziyaret edin';
    $title = $settings['home_location_title'] ?: 'Dernek konumu';
    $button = $settings['home_location_button'] ?: 'Haritayı aç';
    $mapsUrl = filled($address)
        ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($address)
        : null;
@endphp

@if (filled($mapEmbed) || filled($address))
    <section class="border-t border-line bg-paper" aria-labelledby="home-location-heading">
        <div class="shell flex flex-col gap-6 py-10 sm:py-12 lg:flex-row lg:items-end lg:justify-between lg:gap-10 lg:py-14">
            <div class="reveal max-w-2xl">
                @if (filled($eyebrow))
                    <p class="eyebrow">{{ $eyebrow }}</p>
                @endif
                <h2 id="home-location-heading" class="display-2 mt-3 text-balance">{{ $title }}</h2>
                @if (filled($address))
                    <p class="mt-3 text-base leading-relaxed text-muted sm:text-lg">{{ $address }}</p>
                @endif
            </div>

            @if ($mapsUrl)
                <div class="reveal shrink-0">
                    <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline">
                        <x-ui.icon name="pin" class="h-4 w-4" />
                        {{ $button }}
                    </a>
                </div>
            @endif
        </div>

        @if (filled($mapEmbed))
            <div class="reveal relative w-full overflow-hidden border-t border-line bg-cream-deep [&_iframe]:block [&_iframe]:h-[min(26rem,70vh)] [&_iframe]:min-h-[18rem] [&_iframe]:w-full sm:[&_iframe]:h-[min(28rem,65vh)]">
                {!! $mapEmbed !!}
            </div>
        @endif
    </section>
@endif
