@extends('layouts.app')
@section('title', 'Yazılar')

@section('content')
<section class="mx-auto max-w-6xl px-4 py-16">
    <h1 class="font-display text-5xl text-forest">Yazılar ve duyurular</h1>
    <div class="mt-8 flex gap-2">
        <a class="rounded-full px-4 py-2 text-sm {{ $currentType === '' ? 'bg-forest text-cream' : 'border border-line' }}" href="{{ route('posts.index') }}">Tümü</a>
        <a class="rounded-full px-4 py-2 text-sm {{ $currentType === 'article' ? 'bg-forest text-cream' : 'border border-line' }}" href="{{ route('posts.index', ['tur' => 'article']) }}">Yazılar</a>
        <a class="rounded-full px-4 py-2 text-sm {{ $currentType === 'announcement' ? 'bg-forest text-cream' : 'border border-line' }}" href="{{ route('posts.index', ['tur' => 'announcement']) }}">Duyurular</a>
    </div>
    <div class="mt-10 grid gap-6 md:grid-cols-3">
        @forelse ($posts as $post)
            <a href="{{ route('posts.show', $post) }}" class="rounded-2xl border border-line bg-paper p-6 hover:border-gold">
                <p class="text-xs uppercase tracking-widest text-gold">{{ $post->type === 'announcement' ? 'Duyuru' : 'Yazı' }}</p>
                <h2 class="mt-2 font-display text-2xl text-forest">{{ $post->title }}</h2>
                <p class="mt-2 text-sm text-muted">{{ $post->excerpt }}</p>
            </a>
        @empty
            <p class="text-muted">Kayıt yok.</p>
        @endforelse
    </div>
    <div class="mt-10">{{ $posts->links() }}</div>
</section>
@endsection
