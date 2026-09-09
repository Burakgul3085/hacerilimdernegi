@extends('layouts.app')

@section('title', $title)

@section('content')

<x-page-header
    eyebrow="Yasal"
    :title="$title"
    :breadcrumbs="[['label' => $title]]" />

<article class="shell py-14 lg:py-20">
    <div class="mx-auto max-w-3xl">
        <div class="prose-hacer whitespace-pre-line">{{ $body ?: 'Bu metin yönetim panelinden düzenlenir. Kişisel veriler Hostinger KVM sunucusunda (Almanya, Frankfurt) saklanır.' }}</div>

        <div class="mt-12 flex flex-wrap gap-2 border-t border-line pt-8">
            <a href="{{ route('legal', 'kvkk') }}" class="chip {{ request()->routeIs('legal') && request()->route('type') === 'kvkk' ? 'chip-active' : '' }}">KVKK aydınlatma metni</a>
            <a href="{{ route('legal', 'gizlilik') }}" class="chip {{ request()->route('type') === 'gizlilik' ? 'chip-active' : '' }}">Gizlilik politikası</a>
            <a href="{{ route('legal', 'cerezler') }}" class="chip {{ request()->route('type') === 'cerezler' ? 'chip-active' : '' }}">Çerez politikası</a>
        </div>
    </div>
</article>

@endsection
