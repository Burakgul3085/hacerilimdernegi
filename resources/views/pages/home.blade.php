@extends('layouts.app')

@section('title', $settings['site_name'])
@section('description', $settings['tagline'])

@section('content')
<section class="relative overflow-hidden bg-forest text-cream">
    <div class="absolute inset-0 opacity-30" style="background-image:radial-gradient(circle at 20% 20%, #c4a35a 0, transparent 35%), radial-gradient(circle at 80% 0, #2d5a45 0, transparent 40%);"></div>
    <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-4 py-20 md:grid-cols-2 md:py-28">
        <div>
            <p class="text-gold tracking-[0.28em] uppercase text-xs">Gaziantep</p>
            <h1 class="mt-4 font-display text-5xl leading-tight md:text-6xl">İlim, sohbet ve kültür etrafında duran bir dernek.</h1>
            <p class="mt-6 max-w-xl text-lg text-gold-light/90">{{ $settings['about_excerpt'] }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('programs.index') }}" class="rounded-full bg-gold px-6 py-3 font-semibold text-forest-deep">Programlar</a>
                <a href="{{ route('membership') }}" class="rounded-full border border-gold/50 px-6 py-3">Üyelik / gönüllü</a>
            </div>
        </div>
        <div class="rounded-[2rem] border border-gold/30 bg-forest-deep/60 p-8 shadow-2xl">
            <p class="text-gold text-sm uppercase tracking-widest">Yaklaşan program</p>
            @forelse ($programs->take(1) as $program)
                <h2 class="mt-4 font-display text-3xl">{{ $program->title }}</h2>
                <p class="mt-2 text-gold-light">{{ optional($program->starts_at)->translatedFormat('d F Y, H:i') ?? 'Tarih duyurulacak' }}</p>
                <p class="mt-4 text-sm leading-relaxed">{{ \Illuminate\Support\Str::limit(strip_tags($program->description), 180) }}</p>
            @empty
                <h2 class="mt-4 font-display text-3xl">Dersler, sohbetler ve kitap tahlilleri</h2>
                <p class="mt-4 text-sm">Program takvimi panelden yayınlandığında burada görünür.</p>
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
        <p class="text-gold uppercase tracking-widest text-xs">Canlı yayın</p>
        <h2 class="mt-3 font-display text-4xl">Sohbet ve ders yayınları</h2>
        <p class="mt-4 text-gold-light">YouTube ve Instagram hesaplarımızdan canlı yayınları takip edebilirsiniz.</p>
        <a href="{{ route('live') }}" class="mt-6 inline-block rounded-full bg-gold px-5 py-2 font-semibold text-forest-deep">Canlı sayfası</a>
    </div>
    <div class="rounded-3xl border border-line bg-paper p-10">
        <p class="text-gold uppercase tracking-widest text-xs">Destek</p>
        <h2 class="mt-3 font-display text-4xl text-forest">Bağış</h2>
        <p class="mt-4 text-muted">Dernek faaliyetleri bağışlarınızla sürer. Online kart ödemesi yoktur; IBAN bilgisi yönetim panelinden yayınlanır.</p>
        <a href="{{ route('donate') }}" class="mt-6 inline-block rounded-full bg-forest px-5 py-2 text-cream">Bağış bilgisi</a>
    </div>
</section>
@endsection
