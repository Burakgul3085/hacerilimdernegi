@extends('layouts.app')
@section('title', $event->title)

@section('content')
<article class="mx-auto max-w-3xl px-4 py-16">
    <h1 class="font-display text-5xl text-forest">{{ $event->title }}</h1>
    <p class="mt-4 text-muted">{{ optional($event->starts_at)->translatedFormat('d F Y H:i') }} · {{ $event->location }}</p>
    <div class="prose-hacer mt-8">{!! $event->description !!}</div>

    @if ($event->registration_open)
        <form method="POST" action="{{ route('events.register', $event) }}" class="mt-12 space-y-4 rounded-2xl border border-line bg-paper p-6">
            @csrf
            <h2 class="font-display text-2xl text-forest">Katılım başvurusu</h2>
            @include('partials.form-errors')
            <input name="name" required placeholder="Ad soyad" class="w-full rounded-lg border-line px-3 py-2" value="{{ old('name') }}">
            <input type="email" name="email" required placeholder="E-posta" class="w-full rounded-lg border-line px-3 py-2" value="{{ old('email') }}">
            <input name="phone" placeholder="Telefon" class="w-full rounded-lg border-line px-3 py-2" value="{{ old('phone') }}">
            <textarea name="notes" rows="4" placeholder="Not" class="w-full rounded-lg border-line px-3 py-2">{{ old('notes') }}</textarea>
            <label class="flex items-start gap-2 text-sm text-muted">
                <input type="checkbox" name="kvkk_accepted" value="1" required>
                <span><a class="underline" href="{{ route('legal', 'kvkk') }}">KVKK aydınlatma metnini</a> okudum, kişisel verilerimin Almanya’daki sunucuda işlenmesini kabul ediyorum.</span>
            </label>
            <button class="rounded-full bg-forest px-5 py-2 text-cream">Başvur</button>
        </form>
    @endif
</article>
@endsection
