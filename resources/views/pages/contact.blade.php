@extends('layouts.app')
@section('title', 'İletişim')

@section('content')
<section class="mx-auto max-w-6xl px-4 py-16 grid gap-12 md:grid-cols-2">
    <div>
        <h1 class="font-display text-5xl text-forest">İletişim</h1>
        <p class="mt-4 text-muted">{{ $settings['address'] }}</p>
        @if ($settings['phone'])
            <p class="mt-2">{{ $settings['phone'] }}</p>
        @endif
        <p>{{ $settings['email'] }}</p>
        <div class="mt-4 flex flex-wrap gap-3 text-sm">
            @if (!empty($settings['telegram']))<a class="underline" href="{{ $settings['telegram'] }}" target="_blank" rel="noopener">Telegram</a>@endif
            @if (!empty($settings['whatsapp']))<a class="underline" href="{{ $settings['whatsapp'] }}" target="_blank" rel="noopener">WhatsApp</a>@endif
            @if (!empty($settings['twitter']))<a class="underline" href="{{ $settings['twitter'] }}" target="_blank" rel="noopener">X</a>@endif
        </div>
        @if ($settings['map_embed'])
            <div class="mt-6 overflow-hidden rounded-2xl">{!! $settings['map_embed'] !!}</div>
        @endif
    </div>
    <form method="POST" action="{{ route('contact.store') }}" class="space-y-4">
        @csrf
        @include('partials.form-errors')
        <input name="name" required placeholder="Ad soyad" class="w-full rounded-lg border-line px-3 py-2" value="{{ old('name') }}">
        <input type="email" name="email" required placeholder="E-posta" class="w-full rounded-lg border-line px-3 py-2" value="{{ old('email') }}">
        <input name="phone" placeholder="Telefon" class="w-full rounded-lg border-line px-3 py-2" value="{{ old('phone') }}">
        <input name="subject" placeholder="Konu" class="w-full rounded-lg border-line px-3 py-2" value="{{ old('subject') }}">
        <textarea name="message" required rows="6" placeholder="Mesaj" class="w-full rounded-lg border-line px-3 py-2">{{ old('message') }}</textarea>
        <label class="flex items-start gap-2 text-sm text-muted">
            <input type="checkbox" name="kvkk_accepted" value="1" required>
            <span>KVKK metnini okudum ve kabul ediyorum.</span>
        </label>
        <button class="rounded-full bg-forest px-6 py-2 text-cream">Gönder</button>
    </form>
</section>
@endsection
