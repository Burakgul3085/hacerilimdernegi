@props([
    'settings' => [],
    'tone' => 'light',
])

@php
    $channels = [
        'telegram' => 'Telegram',
        'whatsapp' => 'WhatsApp',
        'twitter' => 'X',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
        'facebook' => 'Facebook',
    ];

    $available = collect($channels)->filter(fn ($label, $key) => filled($settings[$key] ?? null));

    $style = $tone === 'dark'
        ? 'border-white/15 text-cream/70 hover:border-gold hover:bg-gold hover:text-white'
        : 'border-line text-forest hover:border-gold hover:bg-gold hover:text-white';
@endphp

@if ($available->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
        @foreach ($available as $key => $label)
            <a href="{{ $settings[$key] }}" target="_blank" rel="noopener noreferrer" title="{{ $label }}"
               class="flex h-10 w-10 items-center justify-center rounded-full border transition duration-300 {{ $style }}">
                <span class="sr-only">{{ $label }}</span>
                <x-ui.icon :name="$key" class="h-[18px] w-[18px]" />
            </a>
        @endforeach
    </div>
@endif
