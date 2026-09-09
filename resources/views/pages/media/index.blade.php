@extends('layouts.app')
@section('title', 'Medya')

@section('content')
<section class="mx-auto max-w-6xl px-4 py-16">
    <h1 class="font-display text-5xl text-forest">Medya</h1>
    <div class="mt-10 grid gap-6 md:grid-cols-3">
        @forelse ($albums as $album)
            <a href="{{ route('media.show', $album) }}" class="rounded-2xl border border-line bg-paper p-6 hover:border-gold">
                <h2 class="font-display text-2xl text-forest">{{ $album->title }}</h2>
                <p class="mt-2 text-sm text-muted">{{ $album->description }}</p>
            </a>
        @empty
            <p class="text-muted">Albüm yok.</p>
        @endforelse
    </div>
    <div class="mt-10">{{ $albums->links() }}</div>
</section>
@endsection
