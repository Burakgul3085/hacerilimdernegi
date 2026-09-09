@extends('layouts.app')

@section('title', $album->title)
@section('description', $album->description)

@php
    $photos = $album->items->filter(fn ($item) => $item->type?->value === 'photo' && filled($item->path));
    $links = $album->items->filter(fn ($item) => $item->type?->value !== 'photo' && filled($item->external_url));
@endphp

@section('content')

<x-page-header
    eyebrow="Albüm"
    :title="$album->title"
    :lead="$album->description"
    :breadcrumbs="[['label' => 'Medya', 'url' => route('media.index')], ['label' => $album->title]]" />

<section class="shell py-12 lg:py-16" x-data="{ open: false, src: '', caption: '' }">
    @if ($photos->isEmpty() && $links->isEmpty())
        <x-empty-state icon="photo" title="Albüm boş" text="Bu albüme henüz içerik eklenmedi." />
    @endif

    @if ($photos->isNotEmpty())
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($photos as $item)
                @php $url = \Illuminate\Support\Facades\Storage::disk('public')->url($item->path); @endphp
                <button type="button" class="group card card-hover overflow-hidden text-left"
                        @click="open = true; src = '{{ $url }}'; caption = @js($item->caption ?: $item->title)">
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

    @if ($links->isNotEmpty())
        <div class="mt-12">
            <p class="eyebrow">Video ve ses kayıtları</p>
            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($links as $item)
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

    <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-[70] flex items-center justify-center bg-forest-deep/90 p-4"
         @click="open = false" @keydown.escape.window="open = false" role="dialog" aria-modal="true">
        <button type="button" class="absolute right-5 top-5 flex h-11 w-11 items-center justify-center rounded-full border border-white/20 text-cream transition hover:bg-white/10">
            <span class="sr-only">Kapat</span>
            <x-ui.icon name="close" class="h-5 w-5" />
        </button>

        <figure class="max-h-full max-w-5xl" @click.stop>
            <img :src="src" alt="" class="max-h-[80vh] w-auto rounded-2xl object-contain">
            <figcaption class="mt-4 text-center text-sm text-cream/70" x-text="caption"></figcaption>
        </figure>
    </div>
</section>

@endsection
