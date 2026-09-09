@extends('layouts.app')

@section('title', 'Programlar')
@section('description', $settings['programs_intro'])

@section('content')

<x-page-header
    eyebrow="Programlar"
    title="Programlar"
    :lead="$settings['programs_intro']"
    :breadcrumbs="[['label' => 'Programlar']]">
    <form action="{{ route('programs.index') }}" method="GET" class="flex items-center gap-2 rounded-full border border-line bg-paper py-1.5 pl-4 pr-1.5">
        @if ($currentType)
            <input type="hidden" name="tur" value="{{ $currentType }}">
        @endif
        <label for="program-search" class="sr-only">Programlarda ara</label>
        <input id="program-search" type="search" name="ara" value="{{ $currentSearch }}" placeholder="Programlarda ara…"
               class="w-48 bg-transparent text-sm text-forest placeholder:text-muted/70 focus:outline-none sm:w-56">
        <button type="submit" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-forest text-cream transition hover:bg-gold">
            <span class="sr-only">Ara</span>
            <x-ui.icon name="search" class="h-4 w-4" />
        </button>
    </form>
</x-page-header>

<section class="shell py-12 lg:py-16">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('programs.index', ['ara' => $currentSearch ?: null]) }}"
           class="chip {{ $currentType === '' ? 'chip-active' : '' }}">Tümü</a>
        @foreach ($types as $type)
            <a href="{{ route('programs.index', ['tur' => $type->value, 'ara' => $currentSearch ?: null]) }}"
               class="chip {{ $currentType === $type->value ? 'chip-active' : '' }}">{{ $type->label() }}</a>
        @endforeach
    </div>

    @if ($programs->isEmpty())
        <x-empty-state class="mt-10" icon="cap" title="Yayında program yok"
                       text="Yeni ders, sohbet ve kitap tahlilleri yönetim panelinden eklendiğinde burada listelenir." />
    @else
        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($programs as $program)
                <div class="reveal"><x-program-card :program="$program" /></div>
            @endforeach
        </div>

        <div class="mt-12">{{ $programs->links() }}</div>
    @endif
</section>

@endsection
