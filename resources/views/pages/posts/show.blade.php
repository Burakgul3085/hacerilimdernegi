@extends('layouts.app')

@section('title', $post->title)
@section('description', $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->body ?? ''), 160))

@section('content')

<x-page-header
    :eyebrow="$post->type === 'announcement' ? 'Duyuru' : 'Yazı'"
    :title="$post->title"
    :lead="$post->excerpt"
    :breadcrumbs="[['label' => 'Yazılar', 'url' => route('posts.index')], ['label' => $post->title]]" />

<article class="shell py-14 lg:py-20">
    <div class="mx-auto max-w-3xl">
        <div class="reveal flex flex-wrap items-center gap-x-6 gap-y-2 border-b border-line pb-6">
            <x-meta icon="calendar">{{ $post->published_at?->translatedFormat('d F Y') ?? '' }}</x-meta>
            @if ($post->author)
                <x-meta icon="mic">{{ $post->author->name }}</x-meta>
            @endif
            @if ($post->category)
                <x-meta icon="star">{{ $post->category->name }}</x-meta>
            @endif
        </div>

        @if ($post->image)
            <div class="reveal mt-10">
                <x-cover :src="$post->image" :alt="$post->title" ratio="aspect-[16/9]" rounded="rounded-2xl" />
            </div>
        @endif

        <div class="prose-hacer reveal mt-10">{!! $post->body !!}</div>
    </div>

    @if ($related->isNotEmpty())
        <div class="reveal mt-20">
            <x-section-heading eyebrow="Devamı" title="Diğer yazılar" link-label="Tümünü gör" :link-url="route('posts.index')" />

            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ($related as $item)
                    <x-post-card :post="$item" />
                @endforeach
            </div>
        </div>
    @endif
</article>

@endsection
