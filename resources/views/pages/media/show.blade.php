@extends('layouts.app')

@section('title', $album->title)
@section('description', $album->description)

@php
    $breadcrumbs = [['label' => 'Medya', 'url' => route('media.index')]];

    if ($album->parent) {
        $breadcrumbs[] = ['label' => $album->parent->title, 'url' => route('media.show', $album->parent)];
    }

    $breadcrumbs[] = ['label' => $album->title];

    $visible = [
        'photo' => $groups['photos'],
        'video' => $groups['videos'],
        'audio' => $groups['audios'],
        'link' => $groups['links'],
    ];
    $filters = array_filter([
        'photo' => $groups['photos']->isNotEmpty() ? 'Fotoğraflar' : null,
        'video' => $groups['videos']->isNotEmpty() ? 'Videolar' : null,
        'audio' => $groups['audios']->isNotEmpty() ? 'Ses kayıtları' : null,
        'link' => $groups['links']->isNotEmpty() ? 'Bağlantılar' : null,
    ]);
    $hasMedia = $groups['photos']->isNotEmpty()
        || $groups['videos']->isNotEmpty()
        || $groups['audios']->isNotEmpty()
        || $groups['links']->isNotEmpty();
    $showMedia = ! $isCollection || $hasMedia;
    $filterUrl = function (?string $tur = null) use ($album, $query): string {
        $params = array_filter([
            'q' => $query,
            'tur' => $tur,
        ]);

        return $album->publicUrl().($params === [] ? '' : '?'.http_build_query($params));
    };
@endphp

@section('content')

<x-page-header
    :eyebrow="$isCollection ? 'Koleksiyon' : 'Albüm'"
    :title="$album->title"
    :lead="$album->description"
    :breadcrumbs="$breadcrumbs" />

<section class="shell py-12 lg:py-16" x-data="{ open: false, src: '', caption: '' }">
    @if ($isCollection)
        <div class="reveal mb-10 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-xl">
                <p class="eyebrow">İç albümler</p>
                <p class="mt-2 text-sm leading-relaxed text-muted">
                    @if ($query !== '')
                        “{{ $query }}” için {{ $children->count() }} albüm bulundu.
                    @else
                        {{ $children->count() }} albüm. Bir kaydı açarak fotoğraf, video ve ses arşivine ulaşabilirsiniz.
                    @endif
                </p>
                @if ($query !== '')
                    <a href="{{ $album->publicUrl() }}" class="mt-3 inline-flex text-sm font-medium text-gold transition hover:text-forest">Aramayı temizle</a>
                @endif
            </div>

            <form method="GET" action="{{ $album->publicUrl() }}" class="flex w-full max-w-md items-center gap-3 rounded-full border border-line bg-paper py-2 pl-5 pr-2">
                <x-ui.icon name="search" class="h-5 w-5 shrink-0 text-gold" />
                <label class="sr-only" for="album-search">Albüm ara</label>
                <input id="album-search" type="search" name="q" value="{{ $query }}" placeholder="İç albümlerde ara"
                       class="w-full bg-transparent text-base text-forest placeholder:text-muted/70 focus:outline-none">
                @if ($type !== '')
                    <input type="hidden" name="tur" value="{{ $type }}">
                @endif
                <button type="submit" class="btn btn-solid btn-sm shrink-0">Ara</button>
            </form>
        </div>

        @if ($children->isEmpty())
            <x-empty-state icon="folder" title="{{ $query !== '' ? 'Sonuç yok' : 'İç albüm yok' }}"
                           text="{{ $query !== '' ? 'Bu aramayla eşleşen albüm bulunamadı.' : 'Yayınlanan iç albümler burada kart olarak listelenir.' }}" />
        @else
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($children as $child)
                    <div class="reveal" style="--reveal-delay: {{ $loop->index * 60 }}ms">
                        <x-album-card :album="$child" />
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    @if ($showMedia && $hasMedia)
        <div class="{{ $isCollection ? 'mt-16 border-t border-line pt-14' : '' }}">
            @if ($isCollection)
                <p class="eyebrow mb-6">Doğrudan kayıtlar</p>
            @endif

            @if (count($filters) > 1)
                <div class="posts-filters mb-8" role="navigation" aria-label="Medya türü">
                    <a href="{{ $filterUrl() }}" class="chip {{ $type === '' ? 'chip-active' : '' }}">Tümü</a>
                    @foreach ($filters as $key => $label)
                        <a href="{{ $filterUrl($key) }}" class="chip {{ $type === $key ? 'chip-active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
            @endif

            @php
                $showPhotos = $groups['photos']->isNotEmpty() && ($type === '' || $type === 'photo');
                $showVideos = $groups['videos']->isNotEmpty() && ($type === '' || $type === 'video');
                $showAudios = $groups['audios']->isNotEmpty() && ($type === '' || $type === 'audio');
                $showLinks = $groups['links']->isNotEmpty() && ($type === '' || $type === 'link');
            @endphp

            @if ($showPhotos)
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($groups['photos'] as $item)
                        @php $url = \Illuminate\Support\Facades\Storage::disk('public')->url($item->path); @endphp
                        <button type="button" class="group card card-hover overflow-hidden text-left"
                                x-on:click="open = true; src = '{{ $url }}'; caption = @js($item->caption ?: $item->title)">
                            <div class="aspect-[4/3] overflow-hidden bg-cream-deep">
                                <img src="{{ $url }}" alt="{{ $item->title }}" loading="lazy"
                                     class="h-full w-full object-cover transition duration-700 group-hover:scale-[1.04]">
                            </div>
                            @if (filled($item->caption ?: $item->title))
                                <p class="px-5 py-4 text-sm text-muted">{{ $item->caption ?: $item->title }}</p>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif

            @if ($showVideos)
                <div class="{{ $showPhotos ? 'mt-12' : '' }} grid gap-4 sm:grid-cols-2">
                    @foreach ($groups['videos'] as $item)
                        @php $url = \Illuminate\Support\Facades\Storage::disk('public')->url($item->path); @endphp
                        <figure class="card overflow-hidden">
                            <video src="{{ $url }}" controls playsinline preload="metadata" class="album-video"></video>
                            @if (filled($item->caption ?: $item->title))
                                <figcaption class="px-5 py-4 text-sm text-muted">{{ $item->caption ?: $item->title }}</figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
            @endif

            @if ($showAudios)
                <div class="{{ $showPhotos || $showVideos ? 'mt-12' : '' }} grid gap-4 sm:grid-cols-2">
                    @foreach ($groups['audios'] as $item)
                        @php $url = \Illuminate\Support\Facades\Storage::disk('public')->url($item->path); @endphp
                        <figure class="card p-5">
                            <audio src="{{ $url }}" controls preload="metadata" class="w-full"></audio>
                            @if (filled($item->caption ?: $item->title))
                                <figcaption class="mt-3 text-sm text-muted">{{ $item->caption ?: $item->title }}</figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
            @endif

            @if ($showLinks)
                <div class="{{ $showPhotos || $showVideos || $showAudios ? 'mt-12' : '' }}">
                    @if ($type === '')
                        <p class="eyebrow">Video ve ses kayıtları</p>
                    @endif
                    <div class="{{ $type === '' ? 'mt-5' : '' }} grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($groups['links'] as $item)
                            <a href="{{ $item->external_url }}" target="_blank" rel="noopener noreferrer" class="group card card-hover flex items-center gap-4 p-5">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-line text-gold transition group-hover:border-gold group-hover:bg-gold group-hover:text-white">
                                    <x-ui.icon :name="$item->type?->value === 'audio' ? 'mic' : 'play'" class="h-5 w-5" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate font-display text-lg text-forest">{{ $item->title ?: 'Kaynağı aç' }}</span>
                                    @if ($item->caption)
                                        <span class="mt-0.5 block truncate text-[13px] text-muted">{{ $item->caption }}</span>
                                    @endif
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @elseif (! $isCollection)
        <x-empty-state icon="photo" title="Albüm boş" text="Bu albüme henüz içerik eklenmedi." />
    @endif

    <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-[70] flex items-center justify-center bg-forest-deep/90 p-4"
         x-on:click="open = false" x-on:keydown.escape.window="open = false" role="dialog" aria-modal="true">
        <button type="button" class="absolute right-5 top-5 flex h-11 w-11 items-center justify-center rounded-full border border-white/20 text-cream transition hover:bg-white/10">
            <span class="sr-only">Kapat</span>
            <x-ui.icon name="close" class="h-5 w-5" />
        </button>

        <figure class="max-h-full max-w-5xl" x-on:click.stop>
            <img :src="src" alt="" class="max-h-[80vh] w-auto rounded-2xl object-contain">
            <figcaption class="mt-4 text-center text-sm text-cream/70" x-text="caption"></figcaption>
        </figure>
    </div>
</section>

@endsection
