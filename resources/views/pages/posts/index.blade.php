@extends('layouts.app')

@section('title', 'Yazılar ve duyurular')
@section('description', $settings['posts_intro'])

@section('content')

<x-page-header
    eyebrow="Gündem"
    title="Yazılar ve duyurular"
    :lead="$settings['posts_intro']"
    :breadcrumbs="[['label' => 'Yazılar']]" />

<section class="shell py-12 lg:py-16">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('posts.index') }}" class="chip {{ $currentType === '' ? 'chip-active' : '' }}">Tümü</a>
        <a href="{{ route('posts.index', ['tur' => 'article']) }}" class="chip {{ $currentType === 'article' ? 'chip-active' : '' }}">Yazılar</a>
        <a href="{{ route('posts.index', ['tur' => 'announcement']) }}" class="chip {{ $currentType === 'announcement' ? 'chip-active' : '' }}">Duyurular</a>
    </div>

    @if ($posts->isEmpty())
        <x-empty-state class="mt-10" icon="document" title="Henüz yazı yok"
                       text="Yayınlanan yazı ve duyurular bu sayfada listelenir." />
    @else
        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                <div class="reveal"><x-post-card :post="$post" /></div>
            @endforeach
        </div>

        <div class="mt-12">{{ $posts->links() }}</div>
    @endif
</section>

@endsection
