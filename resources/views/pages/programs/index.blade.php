@extends('layouts.app')
@section('title', 'Programlar')

@section('content')
<section class="mx-auto max-w-6xl px-4 py-16">
    <h1 class="font-display text-5xl text-forest">Programlar</h1>
    <p class="mt-3 text-muted">Dersler, sohbetler ve kitap tahlilleri.</p>
    <div class="mt-8 flex flex-wrap gap-2">
        <a href="{{ route('programs.index') }}" class="rounded-full px-4 py-2 text-sm {{ $currentType === '' ? 'bg-forest text-cream' : 'border border-line' }}">Tümü</a>
        @foreach ($types as $type)
            <a href="{{ route('programs.index', ['tur' => $type->value]) }}" class="rounded-full px-4 py-2 text-sm {{ $currentType === $type->value ? 'bg-forest text-cream' : 'border border-line' }}">{{ $type->label() }}</a>
        @endforeach
    </div>
    <div class="mt-10 grid gap-6 md:grid-cols-3">
        @forelse ($programs as $program)
            <a href="{{ route('programs.show', $program) }}" class="rounded-2xl border border-line bg-paper p-6 hover:border-gold">
                <p class="text-xs uppercase tracking-widest text-gold">{{ $program->type->label() }}</p>
                <h2 class="mt-2 font-display text-2xl text-forest">{{ $program->title }}</h2>
                <p class="mt-2 text-sm text-muted">{{ optional($program->starts_at)->format('d.m.Y H:i') }} · {{ $program->instructor }}</p>
            </a>
        @empty
            <p class="text-muted">Yayında program yok.</p>
        @endforelse
    </div>
    <div class="mt-10">{{ $programs->links() }}</div>
</section>
@endsection
