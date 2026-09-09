@extends('layouts.app')
@section('title', 'Etkinlikler')

@section('content')
<section class="mx-auto max-w-6xl px-4 py-16">
    <h1 class="font-display text-5xl text-forest">Etkinlik takvimi</h1>
    <p class="mt-3 text-muted">Aylara göre yaklaşan programlar. Katılım başvurusu etkinlik detayındadır.</p>
    @forelse ($grouped as $month => $events)
        <h2 class="mt-12 font-display text-2xl text-gold">{{ $month }}</h2>
        <div class="mt-4 grid gap-6 md:grid-cols-3">
            @foreach ($events as $event)
                <a href="{{ route('events.show', $event) }}" class="rounded-2xl border border-line bg-paper p-6 hover:border-gold">
                    <p class="text-xs uppercase tracking-widest text-gold">{{ optional($event->starts_at)->format('d.m.Y H:i') }}</p>
                    <h3 class="mt-2 font-display text-2xl text-forest">{{ $event->title }}</h3>
                    <p class="mt-2 text-sm text-muted">{{ $event->location }}</p>
                </a>
            @endforeach
        </div>
    @empty
        <p class="mt-10 text-muted">Yaklaşan etkinlik yok.</p>
    @endforelse
</section>
@endsection
