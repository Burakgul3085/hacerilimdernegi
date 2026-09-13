@extends('layouts.app')

@section('title', 'Faaliyetler')
@section('description', $settings['programs_intro'])

@section('content')

<x-page-header
    eyebrow="Faaliyetler"
    title="Faaliyetler"
    :lead="$settings['programs_intro']"
    :breadcrumbs="[['label' => 'Faaliyetler']]">
    <div class="program-scope" role="group" aria-label="Faaliyet durumu">
        <a href="{{ route('activities.index') }}"
           @class(['program-scope-link', 'is-active' => $currentScope === ''])>Tümü</a>
        <a href="{{ route('activities.index', ['durum' => 'devam']) }}"
           @class(['program-scope-link', 'is-active' => $currentScope === 'devam'])>Devam ediyor</a>
        <a href="{{ route('activities.index', ['durum' => 'tamamlandi']) }}"
           @class(['program-scope-link', 'is-active' => $currentScope === 'tamamlandi'])>Tamamlandı</a>
    </div>
</x-page-header>

<section class="shell py-12 lg:py-16">
    @if ($activities->isNotEmpty())
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($activities as $activity)
                <div class="reveal" style="--reveal-delay: {{ $loop->index * 70 }}ms">
                    <x-activity-card :activity="$activity" />
                </div>
            @endforeach
        </div>
    @else
        <x-empty-state
            icon="book"
            :title="$currentScope === 'tamamlandi' ? 'Tamamlanan faaliyet yok' : 'Bu kapsamda faaliyet yok'"
            text="Yeni faaliyet hatları yönetim panelinden yayınlandığında burada görünür." />
    @endif
</section>

@endsection
