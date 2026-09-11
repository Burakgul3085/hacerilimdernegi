@extends('layouts.app')

@section('title', $page->seo_title ?: $page->title)
@section('description', $page->seo_description ?: $page->excerpt)

@php
    $stats = \App\Support\SiteSettings::list('stats');
    $isAbout = $page->slug === 'hakkimizda';
    $isBylaws = $page->isBylaws();
    $isBoard = $page->isBoard();
    $isMessage = $page->isMessage();
    $pdfUrl = $isBylaws ? $page->documentUrl() : null;
    $boardTiers = $isBoard ? $page->boardMembersByTier() : [];
    $hasBoard = $boardTiers !== [];
    $presidentPhoto = $isMessage ? $page->imageUrl() : null;
    $defaultBody = \App\Support\CorporatePages::definitions()[(string) $page->slug]['body'] ?? null;
    $showBody = filled($page->body) && ! (($pdfUrl || $hasBoard || $isMessage) && $page->body === $defaultBody);
    $image = (! $isBylaws && ! $isBoard && ! $isMessage && filled($page->image))
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($page->image)
        : (! $isBylaws && ! $isBoard && ! $isMessage ? \App\Support\SiteSettings::aboutImageUrl() : null);
    $isCorporate = \App\Support\CorporatePages::has((string) $page->slug);
    $breadcrumbs = $isCorporate
        ? [['label' => 'Kurumsal'], ['label' => $page->title]]
        : [['label' => $page->title]];
@endphp

@section('content')

<x-page-header
    eyebrow="Kurum"
    :title="$page->title"
    :lead="$page->excerpt"
    :breadcrumbs="$breadcrumbs" />

@if ($pdfUrl)
    <section class="shell pb-16 pt-8 lg:pb-24 lg:pt-10">
        @if ($showBody)
            <div class="prose-hacer mb-8">{!! $page->body !!}</div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-line bg-forest-deep shadow-soft">
            <iframe
                src="{{ $pdfUrl }}"
                title="{{ $page->title }}"
                class="bylaws-pdf"
                style="height: min(85vh, 56rem); min-height: 35rem; width: 100%; border: 0; display: block;"
            ></iframe>
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ $pdfUrl }}" class="btn btn-outline btn-sm" target="_blank" rel="noopener">PDF'yi aç</a>
            <a href="{{ $pdfUrl }}" class="btn btn-outline btn-sm" download>PDF'yi indir</a>
        </div>
    </section>
@elseif ($hasBoard)
    <section class="board-directory shell pb-20 pt-10 lg:pb-28 lg:pt-12">
        @if ($showBody)
            <div class="prose-hacer mx-auto mb-14 max-w-2xl text-center">{!! $page->body !!}</div>
        @endif

        <div class="flex flex-col">
            @foreach ($boardTiers as $tierValue => $members)
                @php
                    $tier = \App\Enums\BoardTier::tryFrom((int) $tierValue);
                    $featured = $tier === \App\Enums\BoardTier::President;
                @endphp

                @if (! $loop->first)
                    <div class="board-spine mx-auto" aria-hidden="true"></div>
                @endif

                <div>
                    @if ($tier)
                        <p class="eyebrow text-center">{{ $tier->label() }}</p>
                    @endif

                    <div @class([
                        'mt-7 flex flex-wrap justify-center gap-6',
                    ])>
                        @foreach ($members as $index => $member)
                            @php
                                $cardWidth = match ($tier) {
                                    \App\Enums\BoardTier::President => 'w-full max-w-sm',
                                    \App\Enums\BoardTier::VicePresident => 'w-full max-w-[20rem]',
                                    \App\Enums\BoardTier::Officer => 'w-full max-w-[18rem]',
                                    default => 'w-full max-w-[16.5rem]',
                                };
                            @endphp
                            <x-board-member
                                class="reveal {{ $cardWidth }}"
                                style="--reveal-delay: {{ $index * 80 }}ms"
                                :name="$member['name']"
                                :title="$member['title']"
                                :photo="$member['photo']"
                                :bio="$member['bio']"
                                :initials="$member['initials']"
                                :featured="$featured"
                            />
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@elseif ($isMessage)
    <section class="president-message shell pb-20 pt-10 lg:pb-28 lg:pt-12">
        <div class="grid items-start gap-12 lg:grid-cols-[minmax(16rem,20rem)_minmax(0,1fr)] lg:gap-16">
            <aside class="reveal mx-auto w-full max-w-sm lg:mx-0">
                <div @class([
                    'president-portrait relative overflow-hidden rounded-2xl',
                    'bg-forest' => filled($presidentPhoto),
                    'bg-cream-deep' => blank($presidentPhoto),
                ])>
                    @if ($presidentPhoto)
                        <img src="{{ $presidentPhoto }}" alt="" aria-hidden="true" class="board-photo-fill">
                        <img src="{{ $presidentPhoto }}" alt="{{ $page->presidentName() ?: $page->title }}" loading="lazy" decoding="async"
                             class="board-photo-fit">
                    @else
                        <div class="president-silhouette flex h-full w-full items-center justify-center">
                            <x-ui.icon name="user" class="h-24 w-24 text-gold/70" />
                        </div>
                    @endif
                </div>

                @if ($page->presidentName())
                    <h2 class="mt-6 font-display text-3xl leading-snug text-forest">{{ $page->presidentName() }}</h2>
                @endif
                <p class="{{ $page->presidentName() ? 'mt-2' : 'mt-6' }} text-[13px] leading-relaxed text-muted">
                    {{ $page->presidentTitle() }}
                </p>
            </aside>

            <div class="reveal min-w-0">
                <x-ui.icon name="quote" class="h-8 w-8 text-gold" />

                @if ($showBody)
                    <div class="prose-hacer mt-5">{!! $page->body !!}</div>
                @endif
            </div>
        </div>
    </section>
@else
<section class="shell py-16 lg:py-24">
    <div @class([
        'grid gap-12 lg:gap-16',
        'lg:grid-cols-[minmax(0,1fr)_24rem]' => ! $isBylaws && ! $isBoard && ! $isMessage,
    ])>
        <div class="reveal">
            @if ($showBody)
                <div class="prose-hacer">{!! $page->body !!}</div>
            @endif

            @if ($isAbout && filled($settings['about_quote']))
                <figure class="mt-12 rounded-2xl border border-line bg-paper p-8">
                    <x-ui.icon name="quote" class="h-7 w-7 text-gold" />
                    <blockquote class="mt-4 font-display text-2xl leading-snug text-forest sm:text-3xl">
                        “{{ $settings['about_quote'] }}”
                    </blockquote>
                </figure>
            @endif

            @if ($isAbout && filled($stats))
                <div class="mt-12 grid grid-cols-1 gap-px bg-line sm:grid-cols-3">
                    @foreach ($stats as $stat)
                        <div class="bg-cream px-5 py-7 text-center">
                            <p class="font-display text-4xl text-forest">{{ $stat['value'] ?? '' }}</p>
                            <p class="mt-2 text-[12px] leading-relaxed text-muted">{{ $stat['label'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @unless ($isBylaws || $isBoard || $isMessage)
            <aside class="reveal lg:sticky lg:top-32 lg:self-start">
                <div class="overflow-hidden rounded-2xl">
                    <img src="{{ $image }}" alt="{{ $page->title }}" loading="lazy" class="page-aside-photo aspect-[4/5] w-full object-cover">
                </div>

                <div class="card mt-4 p-6">
                    <p class="eyebrow">İletişim</p>
                    <ul class="mt-4 space-y-3">
                        @if (filled($settings['address']))
                            <li><x-meta icon="pin">{{ $settings['address'] }}</x-meta></li>
                        @endif
                        @if (filled($settings['phone']))
                            <li><x-meta icon="phone">{{ $settings['phone'] }}</x-meta></li>
                        @endif
                        @if (filled($settings['email']))
                            <li><x-meta icon="mail">{{ $settings['email'] }}</x-meta></li>
                        @endif
                    </ul>
                    <a href="{{ route('contact') }}" class="btn btn-outline btn-sm mt-6 w-full">İletişime geçin</a>
                </div>
            </aside>
        @endunless
    </div>
</section>
@endif

@if (filled($settings['cta_title']))
    <section class="shell pb-8">
        <x-cta-band
            class="reveal"
            :icon="$settings['cta_icon'] ?: 'users'"
            :title="$settings['cta_title']"
            :text="$settings['cta_text'] ?: null"
            :button-label="$settings['cta_button_label'] ?: null"
            :button-url="$settings['cta_button_url'] ?: null" />
    </section>
@endif

@endsection
