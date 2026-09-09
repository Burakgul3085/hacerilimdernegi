@props([
    'icon' => 'users',
    'title' => '',
    'text' => null,
    'buttonLabel' => null,
    'buttonUrl' => null,
])

@if (filled($title))
    <div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-2xl bg-forest px-6 py-8 text-cream sm:px-10']) }}>
        <div class="grain pointer-events-none absolute inset-0 opacity-30"></div>

        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-5">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-gold/40 text-gold-light">
                    <x-ui.icon :name="$icon" class="h-6 w-6" />
                </span>
                <div>
                    <p class="font-display text-2xl leading-snug sm:text-3xl">{{ $title }}</p>
                    @if ($text)
                        <p class="mt-2 max-w-xl text-sm leading-relaxed text-cream/70">{{ $text }}</p>
                    @endif
                </div>
            </div>

            @if ($buttonLabel && $buttonUrl)
                <a href="{{ $buttonUrl }}" class="btn btn-cream shrink-0">
                    {{ $buttonLabel }}
                    <x-ui.icon name="arrow-right" class="h-4 w-4" />
                </a>
            @endif
        </div>
    </div>
@endif
