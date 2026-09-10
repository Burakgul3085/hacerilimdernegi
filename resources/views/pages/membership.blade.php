@extends('layouts.app')

@section('title', 'Üyelik ve gönüllülük')
@section('description', $settings['membership_intro'])

@section('content')

<x-page-header
    eyebrow="Üyelik"
    title="Üyelik / gönüllü"
    :lead="$settings['membership_intro']"
    :breadcrumbs="[['label' => 'Üyelik']]" />

<section class="shell py-14 lg:py-20">
    <div class="reveal overflow-hidden rounded-2xl border border-line bg-paper">
        <div class="grid lg:grid-cols-[22rem_minmax(0,1fr)]">
            <div class="grain flex flex-col justify-center bg-cream p-8 lg:p-10">
                <span class="flex h-14 w-14 items-center justify-center rounded-full border border-line bg-paper text-gold">
                    <x-ui.icon name="users" class="h-7 w-7" />
                </span>
                <p class="mt-6 font-display text-4xl leading-[1.15] text-forest">{{ $settings['membership_card_title'] }}</p>
                <p class="mt-4 text-sm leading-relaxed text-muted">{{ $settings['membership_card_text'] }}</p>

                <ul class="mt-8 space-y-3 border-t border-line pt-6">
                    <li><x-meta icon="check">Derslere ve sohbetlere düzenli katılım</x-meta></li>
                    <li><x-meta icon="check">Gönüllü çalışma gruplarında görev</x-meta></li>
                    <li><x-meta icon="check">Etkinlik duyurularına öncelikli erişim</x-meta></li>
                </ul>
            </div>

            <form method="POST" action="{{ route('membership.store') }}" class="relative space-y-5 p-8 lg:p-10">
                @csrf
                <x-honeypot />

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="name" label="Ad soyad" placeholder="Ad soyad" required autocomplete="name" />
                    <x-field name="email" type="email" label="E-posta" placeholder="E-posta" required autocomplete="email" />
                    <x-field name="phone" label="Telefon" placeholder="Telefon" autocomplete="tel" />
                    <x-field name="city" label="Şehir" placeholder="Şehir" autocomplete="address-level2" />
                </div>

                <x-field name="message" type="textarea" label="Kısaca kendinizi tanıtın" rows="5"
                         placeholder="İlgi alanlarınız ve katkı sunmak istediğiniz çalışmalar" />

                <x-consent />

                <button type="submit" class="btn btn-solid">
                    Başvuruyu gönder
                    <x-ui.icon name="arrow-right" class="h-4 w-4" />
                </button>
            </form>
        </div>
    </div>
</section>

@endsection
