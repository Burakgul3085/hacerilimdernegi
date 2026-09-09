@extends('layouts.app')

@section('title', $settings['site_name'])
@section('description', $settings['tagline'])

@section('content')
<section class="border-b border-line bg-cream">
    <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-16 md:grid-cols-2 md:py-24">
        <div>
            <p class="text-gold tracking-[0.28em] uppercase text-xs">Gaziantep · Şehitkamil</p>
            <h1 class="mt-4 font-display text-5xl leading-tight text-forest md:text-6xl">İlim, sohbet ve kültür etrafında duran bir dernek.</h1>
            <p class="mt-6 max-w-xl text-lg text-muted">{{ $settings['about_excerpt'] }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('programs.index') }}" class="rounded-full bg-forest px-6 py-3 font-semibold text-cream">Programlar</a>
                <a href="{{ route('membership') }}" class="rounded-full border border-forest/30 px-6 py-3">Üyelik / gönüllü</a>
            </div>
        </div>
        <div class="flex flex-col items-center rounded-[2rem] border border-line bg-paper p-10 shadow-sm">
            <img src="{{ \App\Support\SiteSettings::logoUrl() }}" alt="{{ $settings['site_name'] }}" class="h-40 w-auto object-contain">
            <p class="mt-8 text-xs uppercase tracking-[0.3em] text-gold">Yaklaşan program</p>
            @forelse ($programs->take(1) as $program)
                <h2 class="mt-3 text-center font-display text-3xl text-forest">{{ $program->title }}</h2>
                <p class="mt-2 text-muted">{{ optional($program->starts_at)->translatedFormat('d F Y, H:i') ?? 'Tarih duyurulacak' }}</p>
            @empty
                <h2 class="mt-3 text-center font-display text-3xl text-forest">Dersler, sohbetler, kitap tahlilleri</h2>
            @endforelse
        </div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 py-16">
    <div class="flex items-end justify-between">
        <h2 class="font-display text-4xl text-forest">Faaliyetler</h2>
        <a class="text-sm text-forest underline decoration-gold" href="{{ route('programs.index') }}">Tümü</a>
    </div>
    <div class="mt-8 grid gap-6 md:grid-cols-3">
        @foreach ($programTypes as $type)
            <a href="{{ route('programs.index', ['tur' => $type->value]) }}" class="rounded-2xl border border-line bg-paper p-6 hover:border-gold">
                <p class="text-gold text-xs uppercase tracking-widest">{{ $type->label() }}</p>
                <p class="mt-3 text-muted text-sm">Dernek bünyesinde düzenli {{ mb_strtolower($type->label()) }} programları.</p>
            </a>
        @endforeach
    </div>
</section>

<section class="bg-paper py-16">
    <div class="mx-auto max-w-6xl px-4">
        <h2 class="font-display text-4xl text-forest">Duyurular ve yazılar</h2>
        <div class="mt-8 grid gap-6 md:grid-cols-3">
            @forelse ($posts as $post)
                <article class="rounded-2xl border border-line bg-cream p-6">
                    <p class="text-xs uppercase tracking-widest text-gold">{{ $post->type === 'announcement' ? 'Duyuru' : 'Yazı' }}</p>
                    <h3 class="mt-2 font-display text-2xl text-forest"><a href="{{ route('posts.show', $post) }}">{{ $post->title }}</a></h3>
                    <p class="mt-3 text-sm text-muted">{{ $post->excerpt }}</p>
                </article>
            @empty
                <p class="text-muted">Henüz yayınlanmış yazı yok.</p>
            @endforelse
        </div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 py-16 grid gap-10 md:grid-cols-2">
    <div class="rounded-3xl bg-forest p-10 text-cream">
        <p class="text-gold-light uppercase tracking-widest text-xs">Canlı yayın</p>
        <h2 class="mt-3 font-display text-4xl">Sohbet ve ders yayınları</h2>
        <p class="mt-4 text-cream/75">Telegram, WhatsApp ve X hesaplarımızdan duyuruları takip edebilirsiniz.</p>
        <a href="{{ route('live') }}" class="mt-6 inline-block rounded-full bg-cream px-5 py-2 font-semibold text-forest">Canlı sayfası</a>
    </div>
    <div class="rounded-3xl border border-line bg-paper p-10">
        <p class="text-gold uppercase tracking-widest text-xs">Destek</p>
        <h2 class="mt-3 font-display text-4xl text-forest">Bağış</h2>
        <p class="mt-4 text-muted">Dernek faaliyetleri bağışlarınızla sürer. Online kart ödemesi yoktur; IBAN bilgisi yönetim panelinden yayınlanır.</p>
        <a href="{{ route('donate') }}" class="mt-6 inline-block rounded-full bg-forest px-5 py-2 text-cream">Bağış bilgisi</a>
    </div>
</section>
@endsection
