@extends('layouts.app')

@section('title', 'Duyurular')
@section('description', $settings['announcements_intro'])

@section('content')

<x-page-header
    eyebrow="Gündem"
    title="Duyurular"
    :lead="$settings['announcements_intro']"
    :breadcrumbs="[['label' => 'Duyurular']]" />

<section class="posts-stage">
    <div class="shell py-12 lg:py-16">
        @if ($announcements->isEmpty())
            <x-empty-state class="reveal" icon="document" title="Henüz duyuru yok"
                           text="Yayınlanan duyurular bu sayfada listelenir." />
        @else
            <div class="posts-grid grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($announcements as $announcement)
                    <div class="reveal" style="--reveal-delay: {{ $loop->index * 70 }}ms">
                        <x-announcement-card :announcement="$announcement" />
                    </div>
                @endforeach
            </div>

            <div class="mt-12">{{ $announcements->links() }}</div>
        @endif
    </div>
</section>

@endsection
