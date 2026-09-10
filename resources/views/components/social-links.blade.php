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

    $whatsappChatUrl = \App\Support\SiteSettings::whatsappChatUrl();

    $available = collect($channels)->filter(function (string $label, string $key) use ($settings, $whatsappChatUrl): bool {
        if ($key === 'whatsapp') {
            return filled($whatsappChatUrl) || filled($settings[$key] ?? null);
        }

        return filled($settings[$key] ?? null);
    });

    $style = $tone === 'dark'
        ? 'border-white/15 text-cream/70 hover:border-gold hover:bg-gold hover:text-white'
        : 'border-line text-forest hover:border-gold hover:bg-gold hover:text-white';
@endphp

@if ($available->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
        @foreach ($available as $key => $label)
            @php
                $href = $key === 'whatsapp' && filled($whatsappChatUrl)
                    ? $whatsappChatUrl
                    : ($settings[$key] ?? '#');
            @endphp
            <a href="{{ $href }}" target="_blank" rel="noopener noreferrer" title="{{ $label }}"
               class="social-icon flex h-10 w-10 items-center justify-center rounded-full border {{ $style }}">
                <span class="sr-only">{{ $label }}</span>
                <x-ui.icon :name="$key" class="h-[18px] w-[18px]" />
            </a>
        @endforeach
    </div>
@endif
