@extends('layouts.app')
@section('title', 'Üyelik ve gönüllülük')

@section('content')
<section class="mx-auto max-w-xl px-4 py-16">
    <h1 class="font-display text-5xl text-forest">Üyelik / gönüllü</h1>
    <p class="mt-4 text-muted">Dernek çalışmalarına katılmak için formu doldurun.</p>
    <form method="POST" action="{{ route('membership.store') }}" class="mt-8 space-y-4">
        @csrf
        @include('partials.form-errors')
        <input name="name" required placeholder="Ad soyad" class="w-full rounded-lg border-line px-3 py-2" value="{{ old('name') }}">
        <input type="email" name="email" required placeholder="E-posta" class="w-full rounded-lg border-line px-3 py-2" value="{{ old('email') }}">
        <input name="phone" placeholder="Telefon" class="w-full rounded-lg border-line px-3 py-2" value="{{ old('phone') }}">
        <input name="city" placeholder="Şehir" class="w-full rounded-lg border-line px-3 py-2" value="{{ old('city') }}">
        <textarea name="message" rows="5" placeholder="Kısaca kendinizi tanıtın" class="w-full rounded-lg border-line px-3 py-2">{{ old('message') }}</textarea>
        <label class="flex items-start gap-2 text-sm text-muted">
            <input type="checkbox" name="kvkk_accepted" value="1" required>
            <span>KVKK metnini okudum, verilerimin Almanya’daki sunucuda işlenmesini kabul ediyorum.</span>
        </label>
        <button class="rounded-full bg-forest px-6 py-2 text-cream">Gönder</button>
    </form>
</section>
@endsection
