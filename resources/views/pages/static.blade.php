@extends('layouts.app')

@section('title', $page->seo_title ?: $page->title)
@section('description', $page->seo_description ?: $page->excerpt)

@php
    $stats = \App\Support\SiteSettings::list('stats');
    $isAbout = $page->isAbout();
    $isBylaws = $page->isBylaws();
    $isBoard = $page->isBoard();
    $isMessage = $page->isMessage();
    $isVision = $page->isVision();
    $pdfUrl = $isBylaws ? $page->documentUrl() : null;
    $boardTiers = $isBoard ? $page->boardMembersByTier() : [];
    $hasBoard = $boardTiers !== [];
    $presidentPhoto = $isMessage ? $page->imageUrl() : null;
    $visionHtml = $isVision ? $page->visionHtml() : null;
    $missionHtml = $isVision ? $page->missionHtml() : null;
    $aboutImage = $isAbout ? ($page->imageUrl() ?: \App\Support\SiteSettings::aboutImageUrl()) : null;
    $defaultBody = \App\Support\CorporatePages::definitions()[(string) $page->slug]['body'] ?? null;
    $showBody = filled($page->body) && ! (($pdfUrl || $hasBoard || $isMessage || $isVision) && $page->body === $defaultBody);
    $image = (! $isBylaws && ! $isBoard && ! $isMessage && ! $isVision && ! $isAbout && filled($page->image))
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($page->image)
        : (! $isBylaws && ! $isBoard && ! $isMessage && ! $isVision && ! $isAbout ? \App\Support\SiteSettings::aboutImageUrl() : null);
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
@elseif ($isVision)
    <section class="vision-mission relative overflow-hidden pb-20 pt-10 lg:pb-28 lg:pt-14">
        <div class="vision-mission-glow" aria-hidden="true"></div>

        <div class="shell relative">
            <div class="vision-mission-grid">
                <x-vision-card
                    title="Vizyon"
                    index="01"
                    icon="sparkles"
                    eyebrow="Yönümüz"
                    :html="$visionHtml"
                    delay="0ms"
                    direction="left"
                />

                <div class="vision-mission-spine" aria-hidden="true">
                    <span></span>
                </div>

                <x-vision-card
                    title="Misyon"
                    index="02"
                    icon="heart"
                    eyebrow="Gayemiz"
                    :html="$missionHtml"
                    delay="140ms"
                    direction="right"
                />
            </div>
        </div>
    </section>
@elseif ($isAbout)
    <section class="about-story relative overflow-hidden pb-10 pt-10 lg:pb-14 lg:pt-14">
        <div class="about-story-glow" aria-hidden="true"></div>

        <div class="shell relative">
            <div class="about-story-grid">
                <div class="about-story-copy reveal reveal-left">
                    <p class="eyebrow">Kuruluş</p>
                    @if ($showBody)
                        <div class="prose-hacer about-story-body mt-5">{!! $page->body !!}</div>
                    @endif
                </div>

                <aside class="about-story-media reveal reveal-right">
                    <div class="about-portrait">
                        <img src="{{ $aboutImage }}" alt="{{ $page->title }}" loading="lazy" decoding="async"
                             class="about-portrait-image media-zoom">
                    </div>

                    @if (filled($settings['address']) || filled($settings['phone']) || filled($settings['email']))
                        <div class="about-place">
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
                            <a href="{{ route('contact') }}" class="btn btn-outline btn-sm mt-5 w-full">İletişime geçin</a>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>

    @if (filled($settings['about_quote']))
        <section class="about-quote-band">
            <figure class="shell reveal reveal-scale">
                <div class="about-quote-mark" aria-hidden="true">
                    <span></span>
                    <x-ui.icon name="quote" class="h-7 w-7 text-gold" />
                    <span></span>
                </div>
                <blockquote class="about-quote-text">“{{ $settings['about_quote'] }}”</blockquote>
                <figcaption class="about-quote-cite">Hâcer İlim ve Kültür Derneği</figcaption>
            </figure>
        </section>
    @endif

    @if (filled($stats))
        <section class="about-stats">
            <div class="shell">
                <div class="about-stats-grid">
                    @foreach ($stats as $stat)
                        <div class="about-stat reveal" style="--reveal-delay: {{ $loop->index * 90 }}ms">
                            <p class="about-stat-value" data-count="{{ $stat['value'] ?? '' }}">{{ $stat['value'] ?? '' }}</p>
                            <p class="about-stat-label">{{ $stat['label'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="about-paths shell pb-20 pt-14 lg:pb-28 lg:pt-20">
        <p class="eyebrow reveal">Kurumsal</p>
        <h2 class="display-3 mt-3 reveal">Daha yakından tanıyın</h2>
        <p class="lead mt-3 max-w-xl reveal">Yönümüz, başkanın sözü ve yönetim yapısı.</p>

        <div class="mt-10 grid gap-5 lg:grid-cols-3">
            <x-about-path
                title="Vizyon ve misyon"
                text="Derneğin yönü, gayesi ve çalışma ilkeleri."
                :href="route('corporate.vision')"
                icon="sparkles"
                index="01"
                delay="0ms"
            />
            <x-about-path
                title="Başkanın mesajı"
                text="Dernek başkanının ziyaretçilere sözü."
                :href="route('corporate.message')"
                icon="quote"
                index="02"
                delay="90ms"
            />
            <x-about-path
                title="Yönetim kadrosu"
                text="Görev dağılımı ve kurumsal yapı."
                :href="route('corporate.board')"
                icon="users"
                index="03"
                delay="180ms"
            />
        </div>
    </section>
@elseif ($isMessage)
    <section class="president-message shell overflow-x-clip pb-20 pt-10 lg:pb-28 lg:pt-12">
        <div class="president-message-grid grid min-w-0 items-center gap-10 lg:grid-cols-[minmax(0,18.5rem)_minmax(0,1fr)] lg:gap-16">
            <aside class="reveal mx-auto w-full max-w-[18.5rem] min-w-0 lg:mx-0">
                <div @class([
                    'president-portrait relative w-full overflow-hidden rounded-2xl',
                    'bg-cream-deep aspect-[4/5]' => blank($presidentPhoto),
                ])>
                    @if ($presidentPhoto)
                        <img src="{{ $presidentPhoto }}" alt="{{ $page->presidentName() ?: $page->title }}" loading="lazy" decoding="async"
                             class="president-portrait-image">
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

            <div class="reveal min-w-0 max-w-2xl">
                <x-ui.icon name="quote" class="h-8 w-8 text-gold" />

                @if ($showBody)
                    <div class="prose-hacer president-message-body mt-5 max-w-full break-words">{!! $page->body !!}</div>
                @endif
            </div>
        </div>
    </section>
@else
<section class="shell py-16 lg:py-24">
    <div @class([
        'grid gap-12 lg:gap-16',
        'lg:grid-cols-[minmax(0,1fr)_24rem]' => ! $isBylaws && ! $isBoard,
    ])>
        <div class="reveal">
            @if ($showBody)
                <div class="prose-hacer">{!! $page->body !!}</div>
            @endif
        </div>

        @unless ($isBylaws || $isBoard)
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
