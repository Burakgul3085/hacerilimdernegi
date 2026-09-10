@extends('layouts.app')

@section('title', $settings['tagline'])
@section('description', $settings['about_excerpt'])

@php
    $heroImage = \App\Support\SiteSettings::heroImageUrl();
    $aboutImage = \App\Support\SiteSettings::aboutImageUrl();
    $pillars = \App\Support\SiteSettings::list('value_pillars');
    $stats = \App\Support\SiteSettings::list('stats');
    $nextProgram = $programs->first();
@endphp

@section('content')

{{-- Hero --}}
<section class="hero-cinematic relative overflow-hidden border-b border-line bg-cream">
    <div class="hero-visual absolute inset-y-0 right-0 hidden w-[46%] overflow-hidden lg:block">
        <img src="{{ $heroImage }}" alt="" aria-hidden="true" data-parallax="0.16"
             class="hero-parallax h-full w-full object-cover object-top">
        <div class="absolute inset-0 bg-gradient-to-r from-cream via-cream/45 to-transparent"></div>

        @if ($nextProgram)
            <div class="hero-enter absolute bottom-10 left-10 max-w-xs rounded-2xl border border-line bg-paper/95 p-5 shadow-float backdrop-blur" style="--enter-delay: 0.72s">
                <p class="tag">Yaklaşan program</p>
                <p class="mt-2 font-display text-xl leading-snug text-forest">{{ $nextProgram->title }}</p>
                <div class="mt-3 flex flex-col gap-1.5">
                    <x-meta icon="calendar">{{ $nextProgram->starts_at?->translatedFormat('d F Y, H:i') ?? 'Tarih duyurulacak' }}</x-meta>
                    @if ($nextProgram->location)
                        <x-meta icon="pin">{{ $nextProgram->location }}</x-meta>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="shell relative">
        <div class="hero-copy pb-14 sm:pb-20 lg:max-w-[52%] lg:pb-32">
            @if (filled($settings['hero_eyebrow']))
                <p class="hero-enter eyebrow" style="--enter-delay: 0.04s">{{ $settings['hero_eyebrow'] }}</p>
            @endif

            <h1 class="hero-enter display-1 mt-5 text-balance" style="--enter-delay: 0.16s">{{ $settings['hero_title'] ?: $settings['site_name'] }}</h1>

            @if (filled($settings['hero_text']))
                <p class="hero-enter lead mt-6 max-w-xl" style="--enter-delay: 0.3s">{{ $settings['hero_text'] }}</p>
            @endif

            <div class="hero-enter mt-9 flex flex-wrap gap-3" style="--enter-delay: 0.44s">
                @if (filled($settings['hero_primary_label']))
                    <a href="{{ $settings['hero_primary_url'] ?: route('programs.index') }}" class="btn btn-solid">
                        {{ $settings['hero_primary_label'] }}
                        <x-ui.icon name="arrow-right" class="h-4 w-4" />
                    </a>
                @endif
                @if (filled($settings['hero_secondary_label']))
                    <a href="{{ $settings['hero_secondary_url'] ?: route('membership') }}" class="btn btn-outline">
                        {{ $settings['hero_secondary_label'] }}
                    </a>
                @endif
            </div>

            @if (filled($settings['hero_quote']))
                <figure class="hero-enter mt-12 max-w-md rounded-2xl border border-line bg-paper/70 p-6" style="--enter-delay: 0.58s">
                    <x-ui.icon name="quote" class="h-6 w-6 text-gold" />
                    <blockquote class="mt-3 font-display text-xl leading-snug text-forest">“{{ $settings['hero_quote'] }}”</blockquote>
                    @if (filled($settings['hero_quote_author']))
                        <figcaption class="mt-3 text-[13px] text-muted">{{ $settings['hero_quote_author'] }}</figcaption>
                    @endif
                </figure>
            @endif
        </div>
    </div>

    <div class="hero-visual overflow-hidden lg:hidden">
        <img src="{{ $heroImage }}" alt="" aria-hidden="true" class="hero-parallax h-64 w-full object-cover sm:h-80" data-parallax="0.1">
    </div>
</section>

{{-- Değerler --}}
@if (filled($pillars))
    <section class="border-b border-line bg-paper">
        <div class="shell">
            <div class="grid grid-cols-1 gap-px bg-line sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($pillars as $pillar)
                    <div class="value-pillar reveal bg-paper px-6 py-10 text-center" style="--reveal-delay: {{ $loop->index * 90 }}ms">
                        <span class="value-pillar-icon inline-flex h-12 w-12 items-center justify-center rounded-full border border-line text-gold">
                            <x-ui.icon :name="$pillar['icon'] ?? 'sparkles'" class="h-6 w-6" />
                        </span>
                        <p class="mt-4 font-display text-xl text-forest">{{ $pillar['title'] ?? '' }}</p>
                        @if (filled($pillar['text'] ?? null))
                            <p class="mx-auto mt-2 max-w-[15rem] text-[13px] leading-relaxed text-muted">{{ $pillar['text'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- Kurum --}}
<section class="shell py-20 lg:py-28">
    <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
        <div class="reveal reveal-left">
            <p class="eyebrow">Kurum</p>
            <h2 class="display-2 mt-3 text-balance">Hakkımızda</h2>
            <p class="lead mt-5">{{ $settings['about_excerpt'] }}</p>

            @if (filled($stats))
                <div class="mt-10 grid grid-cols-1 gap-px bg-line sm:grid-cols-3">
                    @foreach ($stats as $stat)
                        <div class="stat-cell bg-cream px-5 py-6 text-center">
                            <p class="font-display text-3xl text-forest" data-count="{{ $stat['value'] ?? '' }}">{{ $stat['value'] ?? '' }}</p>
                            <p class="mt-1 text-[12px] leading-relaxed text-muted">{{ $stat['label'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <a href="{{ route('about') }}" class="btn btn-outline mt-10">
                Derneği tanıyın
                <x-ui.icon name="arrow-right" class="h-4 w-4" />
            </a>
        </div>

        <div class="reveal reveal-right relative">
            <div class="overflow-hidden rounded-2xl">
                <img src="{{ $aboutImage }}" alt="{{ $settings['site_name'] }}" loading="lazy" class="media-zoom aspect-[4/5] w-full object-cover">
            </div>

            @if (filled($settings['address']))
                <div class="mt-4 rounded-2xl border border-line bg-paper p-5 sm:absolute sm:bottom-6 sm:left-6 sm:mt-0 sm:max-w-[19rem] sm:shadow-float">
                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-forest text-cream">
                            <x-ui.icon name="pin" class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gold">Adres</p>
                            <p class="mt-1.5 text-[13px] leading-relaxed text-muted">{{ $settings['address'] }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>

{{-- Programlar --}}
@if ($programs->isNotEmpty())
    <section class="border-y border-line bg-paper py-20 lg:py-24">
        <div class="shell">
            <x-section-heading
                class="reveal"
                eyebrow="Programlar"
                :title="$settings['home_programs_title'] ?: 'Yaklaşan programlar'"
                :text="$settings['home_programs_text'] ?: null"
                link-label="Tüm programlar"
                :link-url="route('programs.index')" />

            <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($programs as $program)
                    <div class="reveal" style="--reveal-delay: {{ $loop->index * 90 }}ms"><x-program-card :program="$program" /></div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- Çağrı bandı --}}
@if (filled($settings['cta_title']))
    <section class="shell py-16">
        <x-cta-band
            class="cinematic-panel reveal"
            :icon="$settings['cta_icon'] ?: 'users'"
            :title="$settings['cta_title']"
            :text="$settings['cta_text'] ?: null"
            :button-label="$settings['cta_button_label'] ?: null"
            :button-url="$settings['cta_button_url'] ?: null" />
    </section>
@endif

{{-- Etkinlikler --}}
@if ($events->isNotEmpty())
    <section class="shell py-16 lg:py-20">
        <div class="grid gap-12 lg:grid-cols-[22rem_minmax(0,1fr)] lg:gap-16">
            <div class="reveal reveal-left lg:sticky lg:top-32 lg:self-start">
                <p class="eyebrow">Etkinlikler</p>
                <h2 class="display-2 mt-3">{{ $settings['home_events_title'] ?: 'Etkinlik takvimi' }}</h2>
                @if (filled($settings['home_events_text']))
                    <p class="lead mt-4">{{ $settings['home_events_text'] }}</p>
                @endif
                <a href="{{ route('events.index') }}" class="btn btn-outline mt-8">
                    Takvimin tamamı
                    <x-ui.icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>

            <div>
                @foreach ($events as $event)
                    <div class="reveal" style="--reveal-delay: {{ $loop->index * 80 }}ms">
                        <x-event-row :event="$event" />
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- Yazılar --}}
@if ($posts->isNotEmpty())
    <section class="border-y border-line bg-paper py-20 lg:py-24">
        <div class="shell">
            <x-section-heading
                class="reveal"
                eyebrow="Gündem"
                :title="$settings['home_posts_title'] ?: 'Yazılar ve duyurular'"
                :text="$settings['home_posts_text'] ?: null"
                link-label="Tümünü gör"
                :link-url="route('posts.index')" />

            <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <div class="reveal" style="--reveal-delay: {{ $loop->index * 90 }}ms"><x-post-card :post="$post" /></div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- Medya --}}
@if ($albums->isNotEmpty())
    <section class="shell py-20 lg:py-24">
        <x-section-heading
            class="reveal"
            eyebrow="Arşiv"
            :title="$settings['home_media_title'] ?: 'Medya arşivi'"
            :text="$settings['home_media_text'] ?: null"
            link-label="Medya arşivi"
            :link-url="route('media.index')" />

        <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($albums as $album)
                <div class="reveal" style="--reveal-delay: {{ $loop->index * 90 }}ms"><x-album-card :album="$album" /></div>
            @endforeach
        </div>
    </section>
@endif

{{-- Seçkiler ve bağış --}}
<section class="shell pb-8 pt-4 lg:pb-16">
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="cinematic-panel reveal relative overflow-hidden rounded-2xl bg-forest p-8 text-cream sm:p-10">
            <div class="grain pointer-events-none absolute inset-0 opacity-30"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full border border-gold/40 text-gold-light">
                        <x-ui.icon name="instagram" class="h-5 w-5" />
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-gold-light">Seçkiler</p>
                </div>
                <h2 class="mt-5 font-display text-3xl leading-snug sm:text-4xl">Dernekten kareler</h2>
                <p class="mt-4 max-w-md text-sm leading-relaxed text-cream/70">{{ \App\Support\SiteSettings::socialIntro() }}</p>
                <a href="{{ route('social') }}" class="btn btn-cream mt-8">
                    Seçkilere bak
                    <x-ui.icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>
        </div>

        <div class="cinematic-panel reveal card grain flex flex-col p-8 sm:p-10" style="--reveal-delay: 100ms">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-full border border-line text-gold">
                    <x-ui.icon name="gift" class="h-5 w-5" />
                </span>
                <p class="eyebrow">Destek</p>
            </div>
            <h2 class="mt-5 font-display text-3xl leading-snug text-forest sm:text-4xl">Bağış</h2>
            <p class="mt-4 max-w-md text-sm leading-relaxed text-muted">{{ $settings['donate_intro'] }}</p>
            <a href="{{ route('donate') }}" class="btn btn-solid mt-8 self-start">
                Bağış bilgileri
                <x-ui.icon name="arrow-right" class="h-4 w-4" />
            </a>
        </div>
    </div>
</section>

{{-- Konum / harita — footer’ın hemen üstünde --}}
<x-location-band :settings="$settings" />

@endsection
