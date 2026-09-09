@extends('layouts.app')
@section('title', 'Etkinlikler')

@section('content')
<section class="mx-auto max-w-6xl px-4 py-16">
    <h1 class="font-display text-5xl text-forest">Etkinlikler</h1>
    <div class="mt-10 grid gap-6 md:grid-cols-3">
        @forelse ($events as $event)
            <a href="{{ route('events.show', $event) }}" class="rounded-2xl border border-line bg-paper p-6 hover:border-gold">
                <p class="text-xs uppercase tracking-widest text-gold">{{ optional($event->starts_at)->format('d.m.Y') }}</p>
                <h2 class="mt-2 font-display text-2xl text-forest">{{ $event->title }}</h2>
                <p class="mt-2 text-sm text-muted">{{ $event->location }}</p>
            </a>
        @empty
            <p class="text-muted">Yaklaşan etkinlik yok.</p>
        @endforelse
    </div>
    <div class="mt-10">{{ $events->links() }}</div>
</section>
@endsection
