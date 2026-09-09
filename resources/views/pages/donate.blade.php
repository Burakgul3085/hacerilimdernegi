@extends('layouts.app')

@section('title', 'Bağış')
@section('description', $settings['donate_intro'])

@section('content')

<x-page-header
    eyebrow="Destek"
    title="Bağış"
    :lead="$settings['donate_intro']"
    :breadcrumbs="[['label' => 'Bağış']]" />

<section class="shell py-14 lg:py-20">
    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem] lg:gap-14">
        <div class="reveal">
            @if (($settings['bank_details_are_demo'] ?? '1') === '1')
                <div class="flex items-start gap-4 rounded-2xl border-2 border-amber-500 bg-amber-50 p-6 text-amber-950" role="alert">
                    <x-ui.icon name="shield" class="mt-0.5 h-6 w-6 shrink-0" />
                    <div>
                        <p class="font-display text-2xl">Demo banka bilgisi — ödeme yapmayınız</p>
                        <p class="mt-2 text-sm leading-relaxed">Gerçek banka ve IBAN bilgileri dernek yönetimi tarafından henüz bildirilmedi.</p>
                    </div>
                </div>
            @endif

            @if (filled($settings['donation_note']))
                <p class="lead mt-8">{{ $settings['donation_note'] }}</p>
            @endif

            <dl class="mt-8 overflow-hidden rounded-2xl border border-line bg-paper">
                <div class="border-b border-line px-7 py-6">
                    <dt class="eyebrow">Hesap adı</dt>
                    <dd class="mt-2 font-display text-2xl text-forest">{{ $settings['bank_account_name'] ?: '—' }}</dd>
                </div>
                <div class="border-b border-line px-7 py-6">
                    <dt class="eyebrow">Banka</dt>
                    <dd class="mt-2 text-lg text-forest">{{ $settings['bank_name'] ?: '—' }}</dd>
                </div>
                <div class="px-7 py-6">
                    <dt class="eyebrow">IBAN</dt>
                    <dd class="mt-2 font-mono text-lg tracking-wide text-forest sm:text-xl">{{ $settings['iban'] ?: 'Panelden IBAN ekleyiniz' }}</dd>
                </div>
            </dl>
        </div>

        <aside class="reveal space-y-4">
            <div class="card grain p-7">
                <span class="flex h-12 w-12 items-center justify-center rounded-full border border-line bg-paper text-gold">
                    <x-ui.icon name="gift" class="h-6 w-6" />
                </span>
                <p class="mt-5 font-display text-2xl leading-snug text-forest">Online kart ödemesi yoktur</p>
                <p class="mt-3 text-sm leading-relaxed text-muted">
                    Bağışlar yalnızca yukarıdaki hesap bilgileriyle, banka havalesi/EFT yoluyla kabul edilir.
                    Site üzerinden kart bilgisi istenmez.
                </p>
            </div>

            <div class="card p-7">
                <p class="eyebrow">Sorularınız için</p>
                <ul class="mt-4 space-y-3">
                    @if (filled($settings['phone']))
                        <li><x-meta icon="phone">{{ $settings['phone'] }}</x-meta></li>
                    @endif
                    @if (filled($settings['email']))
                        <li><x-meta icon="mail">{{ $settings['email'] }}</x-meta></li>
                    @endif
                </ul>
                <a href="{{ route('contact') }}" class="btn btn-outline btn-sm mt-6 w-full">İletişim formu</a>
            </div>
        </aside>
    </div>
</section>

@endsection
