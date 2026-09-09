@extends('layouts.app')
@section('title', $post->title)

@section('content')
<article class="mx-auto max-w-3xl px-4 py-16">
    <p class="text-gold uppercase tracking-widest text-xs">{{ $post->type === 'announcement' ? 'Duyuru' : 'Yazı' }}</p>
    <h1 class="mt-3 font-display text-5xl text-forest">{{ $post->title }}</h1>
    <p class="mt-4 text-muted">{{ optional($post->published_at)->translatedFormat('d F Y') }}</p>
    <div class="prose-hacer mt-8">{!! $post->body !!}</div>
</article>
@endsection
