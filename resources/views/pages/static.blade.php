@extends('layouts.app')
@section('title', $page->seo_title ?: $page->title)

@section('content')
<article class="mx-auto max-w-3xl px-4 py-16">
    <p class="text-gold uppercase tracking-widest text-xs">Kurum</p>
    <h1 class="mt-3 font-display text-5xl text-forest">{{ $page->title }}</h1>
    @if ($page->excerpt)
        <p class="mt-6 text-lg text-muted">{{ $page->excerpt }}</p>
    @endif
    <div class="prose-hacer mt-10">{!! $page->body !!}</div>
</article>
@endsection
