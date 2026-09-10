@extends('layouts.app')

@section('title', 'Seçkiler')
@section('description', $intro)

@section('content')

<x-page-header
    eyebrow="Gündem"
    title="Seçkiler"
    :lead="$intro"
    :breadcrumbs="[['label' => 'Seçkiler']]" />

<section class="shell py-14 lg:py-20" x-data="{ openIndex: null }" @keydown.escape.window="openIndex = null">
    <div class="reveal mb-12 grid gap-8 border-b border-line pb-12 lg:grid-cols-[minmax(0,1fr)_19rem] lg:items-center">
        <div>
            <p class="eyebrow">Kurum arşivi</p>
            <p class="mt-3 max-w-xl text-base leading-relaxed text-muted">
                Bu sayfada derneğin resmî Instagram hesabından seçilen kareler yer alır.
                Her kartı açarak açıklamayı ve Instagram’daki görünümü inceleyebilirsiniz.
            </p>
            @if ($posts !== [])
                <p class="mt-5 text-[13px] font-medium tracking-wide text-gold">{{ count($posts) }} seçki</p>
            @endif
        </div>

        @if ($profileUrl)
            <a href="{{ $profileUrl }}" target="_blank" rel="noopener noreferrer" class="card card-hover flex items-center gap-4 p-5">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-line bg-cream text-gold">
                    <x-ui.icon name="instagram" class="h-5 w-5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-[11px] font-semibold uppercase tracking-[0.22em] text-gold">Resmî hesap</span>
                    <span class="mt-1 block truncate font-medium text-forest">{{ '@'.$profileUsername }}</span>
                </span>
                <span class="arrow-btn">
                    <x-ui.icon name="arrow-up-right" class="h-4 w-4" />
                </span>
            </a>
        @endif
    </div>

    @if ($posts === [])
        <x-empty-state
            icon="instagram"
            title="Seçki henüz oluşmadı"
            text="Yönetim panelinden Instagram gönderi veya Reels bağlantısı eklendiğinde burada yayımlanır." />
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $index => $post)
                <article
                    class="group card card-hover overflow-hidden"
                    x-on:click="openIndex = {{ $index }}"
                    x-on:keydown.enter.prevent="openIndex = {{ $index }}"
                    x-on:keydown.space.prevent="openIndex = {{ $index }}"
                    role="button"
                    tabindex="0"
                    aria-label="{{ $post->label() }} seçkisini aç">
                    <div class="relative aspect-[4/5] overflow-hidden bg-cream-deep">
                        <iframe
                            class="pointer-events-none absolute left-1/2 top-0 h-[46rem] w-[22.5rem] -translate-x-1/2 border-0"
                            src="{{ $post->previewEmbedUrl() }}"
                            title="{{ $post->label() }} önizleme"
                            loading="lazy"
                            scrolling="no"></iframe>
                        <span class="absolute left-4 top-4 rounded-full border border-line bg-paper/95 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-gold">
                            {{ $post->label() }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-line px-5 py-4">
                        <span class="text-sm font-medium text-forest">İncele</span>
                        <span class="arrow-btn">
                            <x-ui.icon name="arrow-up-right" class="h-4 w-4" />
                        </span>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <div
        x-show="openIndex !== null"
        x-cloak
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-[80] flex items-end justify-center p-0 sm:items-center sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="instagram-modal-title">
        <div class="absolute inset-0 bg-forest-deep/55 backdrop-blur-[2px]" @click="openIndex = null" aria-hidden="true"></div>

        <div
            x-show="openIndex !== null"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-6 opacity-0 sm:scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            class="relative z-10 flex max-h-[min(94vh,52rem)] w-full max-w-lg flex-col overflow-hidden rounded-t-3xl border border-line bg-paper shadow-lift sm:rounded-3xl"
            @click.stop>
            <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                <div>
                    <p class="eyebrow">Seçki</p>
                    <h2 id="instagram-modal-title" class="mt-1 font-display text-2xl text-forest">Paylaşım</h2>
                </div>
                <button type="button"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-line text-forest transition hover:bg-cream"
                        @click="openIndex = null"
                        aria-label="Kapat">
                    <x-ui.icon name="close" class="h-5 w-5" />
                </button>
            </div>

            <div class="min-h-[28rem] flex-1 overflow-y-auto bg-cream">
                @foreach ($posts as $index => $post)
                    <template x-if="openIndex === {{ $index }}">
                        <iframe
                            class="min-h-[36rem] w-full border-0"
                            src="{{ $post->embedUrl() }}"
                            title="{{ $post->label() }}"
                            loading="lazy"></iframe>
                    </template>
                @endforeach
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t border-line bg-cream/60 px-5 py-4">
                @foreach ($posts as $index => $post)
                    <a x-show="openIndex === {{ $index }}"
                       href="{{ $post->url }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="btn btn-solid btn-sm">
                        Instagram’da aç
                        <x-ui.icon name="arrow-up-right" class="h-3.5 w-3.5" />
                    </a>
                @endforeach
                <button type="button" class="btn btn-outline btn-sm ml-auto" @click="openIndex = null">Kapat</button>
            </div>
        </div>
    </div>
</section>

@endsection
