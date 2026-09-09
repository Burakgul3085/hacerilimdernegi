@php
    $youtube = $settings['live_youtube_url'] ?? '';
    $id = null;
    if (preg_match('~(?:v=|youtu\.be/|embed/)([A-Za-z0-9_-]{6,})~', $youtube, $m)) {
        $id = $m[1];
    }
@endphp
@extends('layouts.app')
@section('title', 'Canlı yayın')

@section('content')
<section class="mx-auto max-w-4xl px-4 py-16">
    <h1 class="font-display text-5xl text-forest">Canlı yayın</h1>
    <p class="mt-4 text-muted">Ders ve sohbet yayınları bu sayfadan takip edilir. Bağlantılar yönetim panelinden güncellenir.</p>
    @if (($settings['live_is_active'] ?? '0') === '1' && $id)
        <div class="mt-8 aspect-video overflow-hidden rounded-2xl bg-forest">
            <iframe class="h-full w-full" src="https://www.youtube.com/embed/{{ $id }}" title="Canlı yayın" allowfullscreen></iframe>
        </div>
    @else
        <div class="mt-8 rounded-2xl border border-line bg-paper p-8 text-muted">Şu anda aktif yayın yok.</div>
    @endif
    @if (!empty($settings['live_instagram_url']))
        <p class="mt-6"><a class="text-forest underline" href="{{ $settings['live_instagram_url'] }}" target="_blank" rel="noopener">Instagram yayını</a></p>
    @endif
    @if (!empty($settings['youtube']))
        <p class="mt-2"><a class="text-forest underline" href="{{ $settings['youtube'] }}" target="_blank" rel="noopener">YouTube kanalı</a></p>
    @endif
</section>
@endsection
