@extends('layouts.app')

@section('title', $term !== '' ? '“'.$term.'” için sonuçlar' : 'Arama')
@section('robots', 'noindex, follow')

@section('content')

<x-page-header
    eyebrow="Arama"
    :title="$term !== '' ? '“'.$term.'” için sonuçlar' : 'Sitede ara'"
    :lead="$term !== '' ? $results->count().' sonuç bulundu.' : 'Program, etkinlik, yazı ve albümler arasında arama yapın.'"
    :breadcrumbs="[['label' => 'Arama']]" />

<section class="shell py-12 lg:py-16">
    <form action="{{ route('search') }}" method="GET" class="flex items-center gap-3 rounded-full border border-line bg-paper py-2 pl-5 pr-2">
        <x-ui.icon name="search" class="h-5 w-5 shrink-0 text-gold" />
        <label for="search-page-input" class="sr-only">Arama terimi</label>
        <input id="search-page-input" type="search" name="q" value="{{ $term }}" placeholder="Program, etkinlik veya yazı arayın…"
               class="w-full bg-transparent text-base text-forest placeholder:text-muted/70 focus:outline-none">
        <button type="submit" class="btn btn-solid btn-sm shrink-0">Ara</button>
    </form>

    @if ($term === '')
        <x-empty-state class="mt-10" icon="search" title="Arama terimi girin"
                       text="Aramak istediğiniz kelimeyi yukarıdaki alana yazın." />
    @elseif ($results->isEmpty())
        <x-empty-state class="mt-10" icon="search" title="Sonuç bulunamadı"
                       text="Farklı bir kelimeyle tekrar deneyebilir veya menüden ilgili bölüme göz atabilirsiniz." />
    @else
        <ul class="mt-10 divide-y divide-line overflow-hidden rounded-2xl border border-line bg-paper">
            @foreach ($results as $result)
                <li>
                    <a href="{{ $result['url'] }}" class="group flex items-center gap-5 px-6 py-5 transition hover:bg-cream">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-line text-gold transition group-hover:border-gold group-hover:bg-gold group-hover:text-white">
                            <x-ui.icon :name="$result['icon']" class="h-5 w-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="tag">{{ $result['label'] }}</span>
                            <span class="mt-1 block font-display text-xl leading-snug text-forest">{{ $result['title'] }}</span>
                            @if (filled($result['excerpt']))
                                <span class="mt-1 block truncate text-sm text-muted">{{ $result['excerpt'] }}</span>
                            @endif
                        </span>
                        <x-ui.icon name="arrow-right" class="h-4 w-4 shrink-0 text-line transition group-hover:text-gold" />
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</section>

@endsection
