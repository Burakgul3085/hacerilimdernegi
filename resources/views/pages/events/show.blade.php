@extends('layouts.app')

@section('title', $event->title)
@section('description', \Illuminate\Support\Str::limit(strip_tags($event->description ?? ''), 160))

@section('content')

<x-page-header
    eyebrow="Program"
    :title="$event->title"
    :breadcrumbs="[['label' => 'Programlar', 'url' => route('programs.index')], ['label' => $event->title]]">
    <div class="flex flex-col gap-2">
        <x-meta icon="calendar">{{ $event->starts_at?->translatedFormat('d F Y, H:i') ?? 'Tarih duyurulacak' }}</x-meta>
        @if ($event->location)
            <x-meta icon="pin">{{ $event->location }}</x-meta>
        @endif
    </div>
</x-page-header>

<section class="shell py-14 lg:py-20">
    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_23rem] lg:gap-14">
        <div class="reveal">
            <x-cover :src="$event->image" :alt="$event->title" ratio="aspect-[16/10]" rounded="rounded-2xl" />
            @if ($event->galleryMedia() !== [])
                <x-content-gallery class="mt-4" :items="$event->galleryMedia()" :alt="$event->title" />
            @endif
        </div>

        <aside class="reveal space-y-4">
            <div class="card p-7">
                <p class="eyebrow">Program hakkında</p>
                <div class="prose-hacer mt-4 text-[15px]">{!! $event->description !!}</div>

                <div class="mt-6 space-y-3 border-t border-line pt-5">
                    <x-meta icon="calendar">{{ $event->starts_at?->translatedFormat('d F Y, H:i') ?? 'Tarih duyurulacak' }}</x-meta>
                    @if ($event->ends_at)
                        <x-meta icon="clock">Bitiş: {{ $event->ends_at->translatedFormat('d F Y, H:i') }}</x-meta>
                    @endif
                    @if ($event->location)
                        <x-meta icon="pin">{{ $event->location }}</x-meta>
                    @endif
                    @if ($event->capacity)
                        <x-meta icon="users">Kontenjan: {{ $event->capacity }}</x-meta>
                    @endif
                </div>

                @if ($calendarUrl)
                    <a href="{{ $calendarUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-solid btn-sm mt-6 w-full">
                        <x-ui.icon name="calendar" class="h-4 w-4" />
                        Takvime ekle
                    </a>
                @endif
            </div>

            @if ($related->isNotEmpty())
                <div class="card p-6">
                    <p class="eyebrow">Diğer programlar</p>
                    <ul class="mt-4 divide-y divide-line">
                        @foreach ($related as $item)
                            <li>
                                <a href="{{ route('events.show', $item) }}" class="group flex items-center justify-between gap-3 py-3">
                                    <span>
                                        <span class="block font-display text-lg leading-snug text-forest">{{ $item->title }}</span>
                                        <span class="mt-0.5 block text-[13px] text-muted">{{ $item->starts_at?->translatedFormat('d.m.Y') }}</span>
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

    @if ($event->registration_open)
        <x-participation-form :action="route('events.register', $event)" context="event" />
    @endif
</section>

@endsection
