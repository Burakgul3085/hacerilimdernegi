@extends('layouts.app')

@section('title', 'Yazılar ve şiirler')
@section('description', $settings['posts_intro'])

@section('content')

<x-page-header
    eyebrow="Gündem"
    title="Yazılar ve şiirler"
    :lead="$settings['posts_intro']"
    :breadcrumbs="[['label' => 'Yazılar ve şiirler']]" />

<section class="shell py-12 lg:py-16">
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('posts.index') }}" class="chip {{ $currentType === '' ? 'chip-active' : '' }}">Tümü</a>
        <a href="{{ route('posts.index', ['tur' => 'article']) }}" class="chip {{ $currentType === 'article' ? 'chip-active' : '' }}">Yazılar</a>
        <a href="{{ route('posts.index', ['tur' => 'poem']) }}" class="chip {{ $currentType === 'poem' ? 'chip-active' : '' }}">Şiirler</a>
        <a href="#gonder" class="chip">Yazı veya şiir gönder</a>
    </div>

    @if ($posts->isEmpty())
        <x-empty-state class="mt-10" icon="document" title="Henüz yazı yok"
                       text="Yayınlanan yazı ve şiirler bu sayfada listelenir." />
    @else
        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                <div class="reveal"><x-post-card :post="$post" /></div>
            @endforeach
        </div>

        <div class="mt-12">{{ $posts->links() }}</div>
    @endif
</section>

<section id="gonder" class="shell pb-16 lg:pb-24">
    <div class="reveal overflow-hidden rounded-2xl border border-line bg-paper">
        <div class="grid lg:grid-cols-[22rem_minmax(0,1fr)]">
            <div class="grain flex flex-col justify-center bg-cream p-6 sm:p-8 lg:p-10">
                <span class="flex h-14 w-14 items-center justify-center rounded-full border border-line bg-paper text-gold">
                    <x-ui.icon name="document" class="h-7 w-7" />
                </span>
                <p class="mt-6 font-display text-4xl leading-[1.15] text-forest">Siz de yazın</p>
                <p class="mt-4 text-sm leading-relaxed text-muted">Yazınızı veya şiirinizi gönderin. Yönetim onayından sonra bu sayfada yayımlanır.</p>

                <ul class="mt-8 space-y-3 border-t border-line pt-6">
                    <li><x-meta icon="check">Yazı veya şiir olarak gönderin</x-meta></li>
                    <li><x-meta icon="check">Gönderince size e-posta gider</x-meta></li>
                    <li><x-meta icon="check">Onaylanınca sayfada yayınlanır</x-meta></li>
                </ul>
            </div>

            <form method="POST" action="{{ route('posts.store') }}" class="relative space-y-5 p-5 sm:p-8 lg:p-10">
                @csrf
                <x-honeypot />
                <x-flash-status context="posts" />

                <x-field name="type" type="select" label="Tür" required :options="['article' => 'Yazı', 'poem' => 'Şiir']" placeholder="Yazı veya şiir seçin" />

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="name" label="Ad soyad" placeholder="Ad soyad" required autocomplete="name" />
                    <x-field name="email" type="email" label="E-posta" placeholder="E-posta" required autocomplete="email" />
                </div>

                <x-field name="title" label="Başlık" placeholder="Yazı veya şiir başlığı" required />

                <x-field name="body" type="textarea" label="Metin" rows="8"
                         placeholder="Yazınızı veya şiirinizi buraya yazın" required />

                <x-consent />

                <button type="submit" class="btn btn-solid">
                    Gönder
                    <x-ui.icon name="arrow-right" class="h-4 w-4" />
                </button>
            </form>
        </div>
    </div>
</section>

@endsection
