@extends('layouts.app')
@section('title', 'Bağış')

@section('content')
<section class="mx-auto max-w-2xl px-4 py-16">
    <h1 class="font-display text-5xl text-forest">Bağış</h1>
    <p class="mt-4 text-muted">{{ $settings['donation_note'] }}</p>
    @if (($settings['bank_details_are_demo'] ?? '1') === '1')
        <div class="mt-6 rounded-2xl border-2 border-amber-500 bg-amber-50 p-5 text-amber-950" role="alert">
            <p class="font-bold">Demo banka bilgisi — ödeme yapmayınız</p>
            <p class="mt-1 text-sm">Gerçek banka ve IBAN bilgileri dernek yönetimi tarafından henüz bildirilmedi.</p>
        </div>
    @endif
    <dl class="mt-8 space-y-4 rounded-2xl border border-line bg-paper p-8">
        <div>
            <dt class="text-xs uppercase tracking-widest text-gold">Hesap adı</dt>
            <dd class="mt-1 text-lg">{{ $settings['bank_account_name'] }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-widest text-gold">Banka</dt>
            <dd class="mt-1">{{ $settings['bank_name'] ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-widest text-gold">IBAN</dt>
            <dd class="mt-1 font-mono text-xl text-forest">{{ $settings['iban'] ?: 'Panelden IBAN ekleyiniz' }}</dd>
        </div>
    </dl>
</section>
@endsection
