@extends('layouts.app')

@section('title', 'İletişim')
@section('description', $settings['contact_intro'])

@section('content')

<x-page-header
    eyebrow="İletişim"
    title="İletişim"
    :lead="$settings['contact_intro']"
    :breadcrumbs="[['label' => 'İletişim']]" />

<section class="shell py-14 lg:py-20">
    <div class="grid gap-10 lg:grid-cols-2 lg:gap-14">
        <div class="reveal">
            <ul class="space-y-5">
                @if (filled($settings['address']))
                    <li class="flex items-start gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-line text-gold">
                            <x-ui.icon name="pin" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gold">Adres</p>
                            <p class="mt-1 leading-relaxed text-muted">{{ $settings['address'] }}</p>
                        </div>
                    </li>
                @endif

                @if (filled($settings['phone']))
                    <li class="flex items-start gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-line text-gold">
                            <x-ui.icon name="phone" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gold">Telefon</p>
                            <a href="tel:{{ preg_replace('/\s+/', '', $settings['phone']) }}" class="mt-1 block text-muted transition hover:text-forest">{{ $settings['phone'] }}</a>
                        </div>
                    </li>
                @endif

                @if (filled($settings['email']))
                    <li class="flex items-start gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-line text-gold">
                            <x-ui.icon name="mail" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gold">E-posta</p>
                            <a href="mailto:{{ $settings['email'] }}" class="mt-1 block break-all text-muted transition hover:text-forest">{{ $settings['email'] }}</a>
                        </div>
                    </li>
                @endif
            </ul>

            <x-social-links :settings="$settings" class="mt-8" />
        </div>

        @if (filled(\App\Support\SiteSettings::safeMapEmbed()))
            <div class="reveal overflow-hidden rounded-2xl border border-line [&_iframe]:block [&_iframe]:h-full [&_iframe]:min-h-[22rem] [&_iframe]:w-full">
                {!! \App\Support\SiteSettings::safeMapEmbed() !!}
            </div>
        @endif
    </div>

    <div @class(['reveal mt-14 grid gap-6', 'lg:grid-cols-2' => filled($whatsappChatUrl ?? null)])>
        <div class="overflow-hidden rounded-2xl border border-line bg-paper">
            <div class="grain border-b border-line bg-cream px-5 py-6 sm:px-8 sm:py-7 lg:px-10">
                <span class="flex h-12 w-12 items-center justify-center rounded-full border border-line bg-paper text-gold">
                    <x-ui.icon name="mail" class="h-6 w-6" />
                </span>
                <p class="mt-5 font-display text-3xl leading-snug text-forest">E-posta ile yazın</p>
                <p class="mt-3 text-sm leading-relaxed text-muted">Soru, öneri ve iş birliği talepleriniz yönetim paneline düşer; size e-posta ile dönüş yapılır.</p>
            </div>

            <form method="POST" action="{{ route('contact.store') }}" class="relative space-y-5 p-5 sm:p-8 lg:p-10">
                @csrf
                <x-honeypot />
                <x-flash-status context="contact" />

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="name" label="Ad soyad" placeholder="Ad soyad" required autocomplete="name" />
                    <x-field name="email" type="email" label="E-posta" placeholder="E-posta" required autocomplete="email" />
                    <x-field name="phone" label="Telefon" placeholder="Telefon" autocomplete="tel" />
                    <x-field name="subject" label="Konu" placeholder="Konu" />
                </div>

                <x-field name="message" type="textarea" label="Mesaj" rows="6" placeholder="Mesajınız" required />

                <x-consent />

                <button type="submit" class="btn btn-solid">
                    Gönder
                    <x-ui.icon name="arrow-right" class="h-4 w-4" />
                </button>
            </form>
        </div>

        @if (filled($whatsappChatUrl ?? null))
            <div class="overflow-hidden rounded-2xl border border-line bg-paper">
                <div class="grain border-b border-line bg-cream px-5 py-6 sm:px-8 sm:py-7 lg:px-10">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full border border-[#128C7E]/25 bg-paper text-[#128C7E]">
                        <x-ui.icon name="whatsapp" class="h-6 w-6" />
                    </span>
                    <p class="mt-5 font-display text-3xl leading-snug text-forest">WhatsApp ile yazın</p>
                    <p class="mt-3 text-sm leading-relaxed text-muted">Mesajınız derneğin kayıtlı hattında hazır bir selam metniyle açılır; göndermek sizin onayınıza kalır.</p>
                </div>

                <form method="POST" action="{{ route('contact.whatsapp') }}" target="_blank" rel="noopener noreferrer" class="relative space-y-5 p-5 sm:p-8 lg:p-10">
                    @csrf
                    <x-honeypot id="whatsapp-website" />
                    <x-flash-status context="whatsapp" />

                    <x-field name="wa_name" label="Ad soyad" placeholder="Ad soyad" required autocomplete="name" />
                    <x-field name="wa_phone" label="Telefonunuz" placeholder="Telefon" autocomplete="tel" />
                    <x-field name="wa_message" type="textarea" label="Mesaj" rows="6" placeholder="WhatsApp’tan iletmek istediğiniz metin" required />

                    <x-consent />

                    <button type="submit" class="btn btn-whatsapp">
                        WhatsApp’ta aç
                        <x-ui.icon name="whatsapp" class="h-4 w-4" />
                    </button>
                </form>
            </div>
        @endif
    </div>
</section>

@endsection
