@extends('layouts.app')
@section('title', $album->title)

@section('content')
<section class="mx-auto max-w-6xl px-4 py-16">
    <h1 class="font-display text-5xl text-forest">{{ $album->title }}</h1>
    <p class="mt-4 text-muted">{{ $album->description }}</p>
    <div class="mt-10 grid gap-6 md:grid-cols-3">
        @foreach ($album->items as $item)
            <figure class="rounded-2xl border border-line bg-paper p-4">
                @if ($item->type->value === 'photo' && $item->path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->path) }}" alt="{{ $item->title }}" class="rounded-xl">
                @elseif ($item->external_url)
                    <a class="text-forest underline" href="{{ $item->external_url }}" rel="noopener" target="_blank">{{ $item->title ?: 'Kaynağı aç' }}</a>
                @endif
                <figcaption class="mt-3 text-sm text-muted">{{ $item->caption ?: $item->title }}</figcaption>
            </figure>
        @endforeach
    </div>
</section>
@endsection
