@extends('layouts.app')

@section('title', 'Etkinlikler')
@section('description', $settings['events_intro'])

@section('content')

<x-page-header
    eyebrow="Etkinlikler"
    title="Etkinlik takvimi"
    :lead="$settings['events_intro']"
    :breadcrumbs="[['label' => 'Etkinlikler']]">
    @if (filled($monthOptions))
        <form action="{{ route('events.index') }}" method="GET">
            <label for="event-month" class="sr-only">Ay seçin</label>
            <select id="event-month" name="ay" x-on:change="$el.form.submit()"
                    class="rounded-full border border-line bg-paper py-2.5 pl-5 pr-10 text-sm font-medium text-forest focus:border-gold focus:outline-none">
                <option value="">Tüm aylar</option>
                @foreach ($monthOptions as $value => $label)
                    <option value="{{ $value }}" @selected($currentMonth === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    @endif
</x-page-header>

<section class="shell py-12 lg:py-16">
    @forelse ($grouped as $month => $events)
        <div class="reveal mb-10">
            <div class="mb-7 flex items-center gap-5">
                <h2 class="font-display text-2xl text-gold">{{ $month }}</h2>
                <span class="rule flex-1"></span>
            </div>

            @foreach ($events as $event)
                <x-event-row :event="$event" />
            @endforeach
        </div>
    @empty
        <x-empty-state icon="calendar" title="Yaklaşan etkinlik yok"
                       text="Yeni etkinlikler yönetim panelinden yayınlandığında takvimde görünür." />
    @endforelse
</section>

@endsection
