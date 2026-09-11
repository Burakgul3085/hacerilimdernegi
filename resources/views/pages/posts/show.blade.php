@extends('layouts.app')

@section('title', $post->title)
@section('description', $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->body ?? ''), 160))
@section('og_type', 'article')

@php
    $coverUrl = $post->coverUrl();
    $galleryUrls = collect($post->galleryImages())
        ->map(fn (string $path): string => \Illuminate\Support\Facades\Storage::disk('public')->url($path))
        ->values();
    $sourceHref = $post->sourceHref();
    $sourceLabel = filled($post->source_label) ? $post->source_label : 'Kaynağı aç';
    $articleSchema = [
        '@context' => 'https://schema.org',
        '@type' => $post->isAnnouncement() ? 'NewsArticle' : 'Article',
        'headline' => $post->title,
        'description' => $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $post->body), 160),
        'datePublished' => $post->published_at?->toIso8601String(),
        'dateModified' => $post->updated_at?->toIso8601String(),
        'author' => [
            '@type' => 'Person',
            'name' => $post->byline() ?: ($settings['site_name'] ?? 'Hâcer İlim ve Kültür Derneği'),
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => $settings['site_name'] ?? 'Hâcer İlim ve Kültür Derneği',
        ],
        'image' => $post->coverAbsoluteUrl(),
        'mainEntityOfPage' => $shareUrl,
    ];
@endphp

@push('head')
    <script type="application/ld+json">@json($articleSchema)</script>
@endpush

@section('content')

<x-page-header
    :eyebrow="$post->typeLabel()"
    :title="$post->title"
    :lead="$post->excerpt"
    :breadcrumbs="[['label' => 'Yazılar', 'url' => route('posts.index')], ['label' => $post->title]]">
    <div class="flex flex-col gap-2">
        @if ($post->subtitle)
            <p class="max-w-sm text-sm leading-relaxed text-muted">{{ $post->subtitle }}</p>
        @endif
        <x-meta icon="calendar">{{ $post->published_at?->translatedFormat('d F Y') ?: $post->created_at->translatedFormat('d F Y') }}</x-meta>
        <x-meta icon="clock">{{ $post->readingMinutes() }} dk okuma</x-meta>
    </div>
</x-page-header>

<article class="shell py-14 lg:py-20">
    <div class="post-layout">
        <div class="reveal min-w-0" x-data="{ lightbox: null }" @keydown.escape.window="lightbox = null">
            @if ($coverUrl)
                <button type="button"
                        class="post-cover"
                        data-src="{{ $coverUrl }}"
                        x-on:click="lightbox = $el.dataset.src">
                    <img src="{{ $coverUrl }}" alt="{{ $post->title }}" class="post-cover-img" loading="lazy" decoding="async">
                </button>
            @else
                <x-cover :src="$post->image" :alt="$post->title" rounded="rounded-2xl" fit="contain" />
            @endif

            @if ($galleryUrls->isNotEmpty())
                <div class="post-gallery mt-3">
                    @foreach ($galleryUrls as $index => $url)
                        <button type="button"
                                class="post-gallery-item group"
                                data-src="{{ $url }}"
                                x-on:click="lightbox = $el.dataset.src">
                            <img src="{{ $url }}" alt="{{ $post->title }} görseli {{ $index + 1 }}" loading="lazy" decoding="async">
                        </button>
                    @endforeach
                </div>
            @endif

            @if (filled($post->featured_quote))
                <figure class="post-pullquote mt-10">
                    <x-ui.icon name="quote" class="h-7 w-7 text-gold" />
                    <blockquote>“{{ $post->featured_quote }}”</blockquote>
                </figure>
            @endif

            <div class="prose-hacer mt-10">{!! $post->body !!}</div>

            @if ($sourceHref)
                <p class="mt-10">
                    <a href="{{ $sourceHref }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center gap-2 text-sm font-medium text-forest underline decoration-gold underline-offset-4 hover:text-gold">
                        {{ $sourceLabel }}
                        <x-ui.icon name="arrow-up-right" class="h-4 w-4" />
                    </a>
                </p>
            @endif

            @if ($previous || $next)
                <nav class="post-pager mt-14" aria-label="Diğer yazılar">
                    @if ($previous)
                        <a href="{{ route('posts.show', $previous) }}" class="post-pager-link">
                            <span class="eyebrow">Önceki</span>
                            <span class="post-pager-title">{{ $previous->title }}</span>
                        </a>
                    @else
                        <span></span>
                    @endif

                    @if ($next)
                        <a href="{{ route('posts.show', $next) }}" class="post-pager-link post-pager-link-next">
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
                <img :src="lightbox" alt="{{ $post->title }}">
            </div>
        </div>

        <aside class="reveal space-y-4 lg:sticky lg:top-32 lg:self-start">
            <div class="card p-6">
                <p class="eyebrow">{{ $post->isAnnouncement() ? 'Duyuru bilgileri' : 'Yazı bilgileri' }}</p>
                <ul class="mt-4 space-y-3.5">
                    <li><x-meta icon="calendar">{{ $post->published_at?->translatedFormat('d F Y') ?: $post->created_at->translatedFormat('d F Y') }}</x-meta></li>
                    <li><x-meta icon="clock">{{ $post->readingMinutes() }} dk okuma</x-meta></li>
                    @if ($post->byline())
                        <li><x-meta icon="mic">{{ $post->byline() }}</x-meta></li>
                    @endif
                    @if ($post->category)
                        <li><x-meta icon="star">{{ $post->category->name }}</x-meta></li>
                    @endif
                    @if (filled($post->location))
                        <li><x-meta icon="pin">{{ $post->location }}</x-meta></li>
                    @endif
                    <li><x-meta icon="document">{{ $post->typeLabel() }}</x-meta></li>
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

                <a href="{{ route('contact') }}" class="btn btn-solid btn-sm mt-6 w-full">Bu yazı hakkında yazın</a>
            </div>

            @if ($related->isNotEmpty())
                <div class="card p-6">
                    <p class="eyebrow">{{ $post->isAnnouncement() ? 'Diğer duyurular' : 'Benzer yazılar' }}</p>
                    <ul class="mt-4 divide-y divide-line">
                        @foreach ($related as $item)
                            <li>
                                <a href="{{ route('posts.show', $item) }}" class="group flex items-center justify-between gap-3 py-3">
                                    <span>
                                        <span class="block font-display text-lg leading-snug text-forest">{{ $item->title }}</span>
                                        <span class="mt-0.5 block text-[13px] text-muted">
                                            {{ $item->published_at?->translatedFormat('d.m.Y') }}
                                            · {{ $item->typeLabel() }}
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
