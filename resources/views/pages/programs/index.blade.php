@extends('layouts.app')

@section('title', 'Programlar')
@section('description', $settings['programs_intro'])

@section('content')

<x-page-header
    eyebrow="Programlar"
    title="Programlar"
    :lead="$settings['programs_intro']"
    :breadcrumbs="[['label' => 'Programlar']]">
    <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
        <div class="program-scope" role="group" aria-label="Takvim kapsamı">
            <a href="{{ route('programs.index', array_filter(['ay' => $currentMonth ?: null])) }}"
               @class(['program-scope-link', 'is-active' => $currentScope === 'yaklasan'])>Yaklaşan</a>
            <a href="{{ route('programs.index', array_filter(['durum' => 'gecmis', 'ay' => $currentMonth ?: null])) }}"
               @class(['program-scope-link', 'is-active' => $currentScope === 'gecmis'])>Geçmiş</a>
        </div>

        @if (filled($monthOptions))
            <form action="{{ route('programs.index') }}" method="GET">
                @if ($currentScope === 'gecmis')
                    <input type="hidden" name="durum" value="gecmis">
                @endif
                <label for="program-month" class="sr-only">Ay seçin</label>
                <select id="program-month" name="ay" x-on:change="$el.form.submit()"
                        class="w-full rounded-full border border-line bg-paper py-2.5 pl-5 pr-10 text-sm font-medium text-forest focus:border-gold focus:outline-none sm:w-auto">
                    <option value="">Tüm aylar</option>
                    @foreach ($monthOptions as $value => $label)
                        <option value="{{ $value }}" @selected($currentMonth === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>
</x-page-header>

<section class="shell py-12 lg:py-16">
    @forelse ($grouped as $month => $items)
        <div class="reveal mb-10">
            <div class="mb-7 flex items-center gap-5">
                <h2 class="font-display text-2xl text-gold">{{ $month }}</h2>
                <span class="rule flex-1"></span>
            </div>

            @foreach ($items as $item)
                <div class="reveal" style="--reveal-delay: {{ $loop->index * 70 }}ms">
                    <x-work-row :item="$item" />
                </div>
            @endforeach
        </div>
    @empty
        <x-empty-state
            icon="calendar"
            :title="$currentScope === 'gecmis' ? 'Bu aralıkta geçmiş program yok' : 'Yaklaşan program yok'"
            text="Yeni ders, sohbet ve kayıtlı programlar yönetim panelinden yayınlandığında burada görünür." />
    @endforelse
</section>

@endsection
