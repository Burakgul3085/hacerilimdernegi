@extends('layouts.app')

@section('title', $event->title)
@section('description', \Illuminate\Support\Str::limit(strip_tags($event->description ?? ''), 160))

@section('content')

<x-page-header
    eyebrow="Etkinlik"
    :title="$event->title"
    :breadcrumbs="[['label' => 'Etkinlikler', 'url' => route('events.index')], ['label' => $event->title]]">
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
        </div>

        <aside class="reveal space-y-4">
            <div class="card p-7">
                <p class="eyebrow">Etkinlik hakkında</p>
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
                    <p class="eyebrow">Diğer etkinlikler</p>
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
        <div class="reveal mt-16 overflow-hidden rounded-2xl border border-line bg-paper">
            <div class="grid lg:grid-cols-[20rem_minmax(0,1fr)]">
                <div class="grain flex flex-col justify-center bg-cream p-8 lg:p-10">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full border border-line bg-paper text-gold">
                        <x-ui.icon name="hand" class="h-6 w-6" />
                    </span>
                    <p class="mt-5 font-display text-3xl leading-snug text-forest">Katılım başvurusu</p>
                    <p class="mt-3 text-sm leading-relaxed text-muted">Formu doldurun, dernek yönetimi sizinle iletişime geçsin.</p>
                </div>

                <form method="POST" action="{{ route('events.register', $event) }}" class="relative space-y-5 p-8 lg:p-10">
                    @csrf
                    <x-honeypot />

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field name="name" label="Ad soyad" placeholder="Ad soyad" required autocomplete="name" />
                        <x-field name="email" type="email" label="E-posta" placeholder="E-posta" required autocomplete="email" />
                        <x-field name="phone" label="Telefon" placeholder="Telefon" autocomplete="tel" class="sm:col-span-2" />
                    </div>

                    <x-field name="notes" type="textarea" label="Not" rows="4" placeholder="Eklemek istedikleriniz" />

                    <x-consent />

                    <button type="submit" class="btn btn-solid">
                        Başvuruyu gönder
                        <x-ui.icon name="arrow-right" class="h-4 w-4" />
                    </button>
                </form>
            </div>
        </div>
    @endif
</section>

@endsection
