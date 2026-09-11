@extends('layouts.app')

@section('title', $page->seo_title ?: $page->title)
@section('description', $page->seo_description ?: $page->excerpt)

@php
    $stats = \App\Support\SiteSettings::list('stats');
    $isAbout = $page->slug === 'hakkimizda';
    $isBylaws = $page->isBylaws();
    $pdfUrl = $isBylaws ? $page->documentUrl() : null;
    $defaultBylawsBody = \App\Support\CorporatePages::definitions()[\App\Support\CorporatePages::BYLAWS_SLUG]['body'];
    $showBody = filled($page->body) && ! ($pdfUrl && $page->body === $defaultBylawsBody);
    $image = (! $isBylaws && filled($page->image))
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($page->image)
        : (! $isBylaws ? \App\Support\SiteSettings::aboutImageUrl() : null);
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
@else
<section class="shell py-16 lg:py-24">
    <div @class([
        'grid gap-12 lg:gap-16',
        'lg:grid-cols-[minmax(0,1fr)_24rem]' => ! $isBylaws,
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

        @unless ($isBylaws)
            <aside class="reveal lg:sticky lg:top-32 lg:self-start">
                <div class="overflow-hidden rounded-2xl">
                    <img src="{{ $image }}" alt="{{ $page->title }}" loading="lazy" class="aspect-[4/5] w-full object-cover">
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
