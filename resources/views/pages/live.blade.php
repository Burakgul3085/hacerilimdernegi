@extends('layouts.app')

@section('title', 'Canlı yayın')
@section('description', $settings['live_intro'])

@php
    $youtubeId = null;

    if (preg_match('~(?:v=|youtu\.be/|embed/|live/)([A-Za-z0-9_-]{6,})~', (string) ($settings['live_youtube_url'] ?? ''), $matches)) {
        $youtubeId = $matches[1];
    }

    $isLive = ($settings['live_is_active'] ?? '0') === '1' && $youtubeId;

    $channels = array_filter([
        'live_instagram_url' => ['label' => 'Instagram yayını', 'icon' => 'instagram', 'url' => $settings['live_instagram_url'] ?? ''],
        'youtube' => ['label' => 'YouTube kanalı', 'icon' => 'youtube', 'url' => $settings['youtube'] ?? ''],
        'telegram' => ['label' => 'Telegram kanalı', 'icon' => 'telegram', 'url' => $settings['telegram'] ?? ''],
        'whatsapp' => ['label' => 'WhatsApp kanalı', 'icon' => 'whatsapp', 'url' => $settings['whatsapp'] ?? ''],
    ], fn (array $channel) => filled($channel['url']));
@endphp

@section('content')

<x-page-header
    eyebrow="Yayın"
    title="Canlı yayın"
    :lead="$settings['live_intro']"
    :breadcrumbs="[['label' => 'Canlı']]">
    <span class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold {{ $isLive ? 'border-red-200 bg-red-50 text-red-700' : 'border-line bg-paper text-muted' }}">
        <span class="relative flex h-2 w-2">
            @if ($isLive)
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-75"></span>
            @endif
            <span class="relative inline-flex h-2 w-2 rounded-full {{ $isLive ? 'bg-red-600' : 'bg-muted/50' }}"></span>
        </span>
        {{ $isLive ? 'Şu anda yayında' : 'Yayın kapalı' }}
    </span>
</x-page-header>

<section class="shell py-14 lg:py-20">
    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem] lg:gap-14">
        <div class="reveal">
            @if ($isLive)
                <div class="aspect-video overflow-hidden rounded-2xl bg-forest-deep">
                    <iframe class="h-full w-full" src="https://www.youtube.com/embed/{{ $youtubeId }}" title="Canlı yayın"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            @else
                <div class="grain flex aspect-video flex-col items-center justify-center rounded-2xl border border-line bg-paper text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full border border-line text-gold">
                        <x-ui.icon name="video" class="h-6 w-6" />
                    </span>
                    <p class="mt-5 font-display text-3xl text-forest">Şu anda aktif yayın yok</p>
                    <p class="mt-2 max-w-md text-sm text-muted">Yayın başladığında bu sayfada canlı olarak izleyebilirsiniz.</p>
                </div>
            @endif
        </div>

        <aside class="reveal space-y-4">
            @if (filled($channels))
                <div class="card p-6">
                    <p class="eyebrow">Yayın kanalları</p>
                    <ul class="mt-4 divide-y divide-line">
                        @foreach ($channels as $channel)
                            <li>
                                <a href="{{ $channel['url'] }}" target="_blank" rel="noopener noreferrer" class="group flex items-center gap-4 py-3.5">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-line text-gold transition group-hover:border-gold group-hover:bg-gold group-hover:text-white">
                                        <x-ui.icon :name="$channel['icon']" class="h-[18px] w-[18px]" />
                                    </span>
                                    <span class="flex-1 font-medium text-forest">{{ $channel['label'] }}</span>
                                    <x-ui.icon name="arrow-up-right" class="h-4 w-4 text-line transition group-hover:text-gold" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card grain p-6">
                <p class="eyebrow">Duyurular</p>
                <p class="mt-3 text-sm leading-relaxed text-muted">
                    Yayın saatleri ve program duyuruları sosyal medya kanallarımızdan paylaşılır.
                </p>
                <a href="{{ route('events.index') }}" class="btn btn-outline btn-sm mt-5 w-full">Etkinlik takvimi</a>
            </div>
        </aside>
    </div>
</section>

@endsection
