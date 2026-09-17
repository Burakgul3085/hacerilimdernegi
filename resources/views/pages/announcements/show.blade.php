@extends('layouts.app')

@section('title', $announcement->title)
@section('description', $announcement->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($announcement->body ?? ''), 160))
@section('og_type', 'article')

@php
    $coverUrl = $announcement->coverUrl();
    $articleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'headline' => $announcement->title,
        'description' => $announcement->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $announcement->body), 160),
        'datePublished' => $announcement->published_at?->toIso8601String(),
        'dateModified' => $announcement->updated_at?->toIso8601String(),
        'publisher' => [
            '@type' => 'Organization',
            'name' => $settings['site_name'] ?? 'Hâcer İlim ve Kültür Derneği',
        ],
        'image' => $coverUrl,
        'mainEntityOfPage' => $shareUrl,
    ];
@endphp

@push('head')
    <script type="application/ld+json">@json($articleSchema)</script>
@endpush

@section('content')

<x-page-header
    eyebrow="Duyuru"
    :title="$announcement->title"
    :lead="$announcement->excerpt"
    :breadcrumbs="[['label' => 'Duyurular', 'url' => route('announcements.index')], ['label' => $announcement->title]]">
    <div class="flex flex-col gap-2">
        <x-meta icon="calendar">{{ $announcement->published_at?->translatedFormat('d F Y') ?: $announcement->created_at->translatedFormat('d F Y') }}</x-meta>
        <x-meta icon="clock">{{ $announcement->readingMinutes() }} dk okuma</x-meta>
    </div>
</x-page-header>

<article class="shell py-14 lg:py-20">
    <div class="post-layout">
        <div class="reveal min-w-0" x-data="{ lightbox: null }" x-on:keydown.escape.window="lightbox = null">
            @if ($coverUrl)
                <button type="button"
                        class="activity-hero-cover post-cover rounded-2xl"
                        data-src="{{ $coverUrl }}"
                        x-on:click="lightbox = $el.dataset.src">
                    <img src="{{ $coverUrl }}" alt="{{ $announcement->title }}" class="activity-cover-img" loading="lazy" decoding="async">
                </button>
            @else
                <x-cover :src="$announcement->image" :alt="$announcement->title" fit="natural" rounded="rounded-2xl" class="activity-hero-cover" />
            @endif

            @if ($announcement->galleryMedia() !== [])
                <x-activity-media-marquee class="mt-6" :items="$announcement->galleryMedia()" :alt="$announcement->title" />
            @endif

            <div class="prose-hacer mt-10">{!! $announcement->body !!}</div>

            @if ($previous || $next)
                <nav class="post-pager mt-14" aria-label="Diğer duyurular">
                    @if ($previous)
                        <a href="{{ route('announcements.show', $previous) }}" class="post-pager-link">
                            <span class="eyebrow">Önceki</span>
                            <span class="post-pager-title">{{ $previous->title }}</span>
                        </a>
                    @else
                        <span></span>
                    @endif

                    @if ($next)
                        <a href="{{ route('announcements.show', $next) }}" class="post-pager-link post-pager-link-next">
                            <span class="eyebrow">Sonraki</span>
                            <span class="post-pager-title">{{ $next->title }}</span>
                        </a>
                    @endif
                </nav>
            @endif

            <div x-cloak
                 x-show="lightbox"
                 x-on:click.self="lightbox = null"
                 class="post-lightbox"
                 role="dialog"
                 aria-modal="true"
                 aria-label="Görsel">
                <img :src="lightbox" alt="{{ $announcement->title }}">
            </div>
        </div>

        <aside class="reveal space-y-4 lg:sticky lg:top-32 lg:self-start">
            <div class="card p-6">
                <p class="eyebrow">Duyuru bilgileri</p>
                <ul class="mt-4 space-y-3.5">
                    <li><x-meta icon="calendar">{{ $announcement->published_at?->translatedFormat('d F Y') ?: $announcement->created_at->translatedFormat('d F Y') }}</x-meta></li>
                    <li><x-meta icon="clock">{{ $announcement->readingMinutes() }} dk okuma</x-meta></li>
                    <li><x-meta icon="document">Duyuru</x-meta></li>
                </ul>

                <div class="mt-6 border-t border-line pt-5" x-data="{ copied: false }">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gold">Paylaş</p>
                    <div class="post-share mt-3">
                        <a href="{{ $whatsappShareUrl }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="btn btn-outline btn-sm">
                            <x-ui.icon name="whatsapp" class="h-4 w-4" />
                            WhatsApp
                        </a>
                        <button type="button"
                                class="btn btn-outline btn-sm"
                                data-url="{{ $shareUrl }}"
                                x-on:click="
                                    const value = $el.dataset.url ?? '';
                                    if (! value || ! navigator.clipboard?.writeText) {
                                        return;
                                    }
                                    navigator.clipboard.writeText(value).then(() => {
                                        copied = true;
                                        window.setTimeout(() => copied = false, 1800);
                                    }).catch(() => {});
                                ">
                            <span class="inline-flex items-center gap-1.5" x-show="! copied">
                                <x-ui.icon name="clipboard" class="h-4 w-4" />
                                Bağlantıyı kopyala
                            </span>
                            <span class="inline-flex items-center gap-1.5" x-cloak x-show="copied">
                                <x-ui.icon name="check" class="h-4 w-4" />
                                Kopyalandı
                            </span>
                        </button>
                    </div>
                </div>

                <a href="{{ route('contact') }}" class="btn btn-solid btn-sm mt-6 w-full">Bu duyuru hakkında yazın</a>
            </div>

            @if ($related->isNotEmpty())
                <div class="card p-6">
                    <p class="eyebrow">Diğer duyurular</p>
                    <ul class="mt-4 divide-y divide-line">
                        @foreach ($related as $item)
                            <li>
                                <a href="{{ route('announcements.show', $item) }}" class="group flex items-center justify-between gap-3 py-3">
                                    <span>
                                        <span class="block font-display text-lg leading-snug text-forest">{{ $item->title }}</span>
                                        <span class="mt-0.5 block text-[13px] text-muted">
                                            {{ $item->published_at?->translatedFormat('d.m.Y') }}
                                        </span>
                                    </span>
                                    <x-ui.icon name="chevron-right" class="h-4 w-4 shrink-0 text-line transition group-hover:text-gold" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
</article>

@endsection
