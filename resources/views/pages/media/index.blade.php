@extends('layouts.app')

@section('title', 'Medya')
@section('description', $settings['media_intro'])

@section('content')

<x-page-header
    eyebrow="Arşiv"
    title="Medya"
    :lead="$settings['media_intro']"
    :breadcrumbs="[['label' => 'Medya']]" />

<section class="shell py-12 lg:py-16">
    @if ($albums->isEmpty())
        <x-empty-state icon="photo" title="Albüm yok"
                       text="Fotoğraf, video ve ses kayıtları yönetim panelinden albüm olarak yayınlanır." />
    @else
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($albums as $album)
                <div class="reveal"><x-album-card :album="$album" /></div>
            @endforeach
        </div>

        <div class="mt-12">{{ $albums->links() }}</div>
    @endif
</section>

@endsection
