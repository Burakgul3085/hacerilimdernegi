<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $settings['site_name'] ?? 'Hacer İlim ve Kültür Derneği')</title>
    <meta name="description" content="@yield('description', $settings['tagline'] ?? '')">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:500,600,700|source-sans-3:400,500,600,700" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        :root {
            --color-forest: {{ $settings['color_primary'] ?? '#143D2C' }};
            --color-gold: {{ $settings['color_gold'] ?? '#C4A35A' }};
        }
    </style>
</head>
<body class="min-h-screen bg-cream text-ink" x-data="{ open: false, cookies: localStorage.getItem('hacer_cookies') !== '1' }">
    <div class="bg-forest-deep text-gold-light text-center text-xs tracking-[0.22em] uppercase py-2">
        Gaziantep · İlim · Sohbet · Kültür
    </div>
    <header class="sticky top-0 z-40 border-b border-line/70 bg-paper/95 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="{{ $logoUrl }}" alt="{{ $settings['site_name'] }}" class="h-12 w-12 rounded-xl shadow-sm">
                <span>
                    <span class="block font-display text-xl leading-none text-forest">Hacer İlim</span>
                    <span class="block text-xs text-muted">ve Kültür Derneği</span>
                </span>
            </a>
            <nav class="hidden items-center gap-6 text-sm font-medium text-forest lg:flex">
                <a href="{{ route('about') }}">Hakkımızda</a>
                <a href="{{ route('programs.index') }}">Programlar</a>
                <a href="{{ route('events.index') }}">Etkinlikler</a>
                <a href="{{ route('posts.index') }}">Yazılar</a>
                <a href="{{ route('media.index') }}">Medya</a>
                <a href="{{ route('live') }}">Canlı</a>
                <a href="{{ route('donate') }}" class="rounded-full bg-gold px-4 py-2 text-forest-deep">Bağış</a>
                <a href="{{ route('contact') }}">İletişim</a>
            </nav>
            <button class="lg:hidden rounded-md border border-line px-3 py-2 text-sm" @click="open = !open" type="button">Menü</button>
        </div>
        <div class="lg:hidden border-t border-line bg-paper px-4 py-4 space-y-3" x-show="open" x-cloak>
            <a class="block" href="{{ route('about') }}">Hakkımızda</a>
            <a class="block" href="{{ route('programs.index') }}">Programlar</a>
            <a class="block" href="{{ route('events.index') }}">Etkinlikler</a>
            <a class="block" href="{{ route('posts.index') }}">Yazılar</a>
            <a class="block" href="{{ route('media.index') }}">Medya</a>
            <a class="block" href="{{ route('live') }}">Canlı</a>
            <a class="block" href="{{ route('membership') }}">Üyelik</a>
            <a class="block" href="{{ route('donate') }}">Bağış</a>
            <a class="block" href="{{ route('contact') }}">İletişim</a>
        </div>
    </header>

    @if (session('status'))
        <div class="mx-auto mt-6 max-w-6xl px-4">
            <div class="rounded-xl border border-gold bg-gold-light/40 px-4 py-3 text-sm text-forest">{{ session('status') }}</div>
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    <footer class="mt-20 border-t border-line bg-forest-deep text-cream">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 md:grid-cols-4">
            <div class="md:col-span-2">
                <p class="font-display text-3xl">Hacer İlim ve Kültür Derneği</p>
                <p class="mt-3 max-w-md text-sm text-gold-light/90">{{ $settings['tagline'] }}</p>
            </div>
            <div class="text-sm space-y-2">
                <p class="text-gold tracking-wide uppercase text-xs">İletişim</p>
                <p>{{ $settings['address'] }}</p>
                <p>{{ $settings['phone'] }}</p>
                <p>{{ $settings['email'] }}</p>
            </div>
            <div>
                <p class="text-gold tracking-wide uppercase text-xs mb-3">E-bülten</p>
                <form method="POST" action="{{ route('newsletter.store') }}" class="space-y-2">
                    @csrf
                    <input type="email" name="email" required placeholder="E-posta" class="w-full rounded-lg bg-forest px-3 py-2 text-sm text-cream placeholder:text-gold-light/70">
                    <button class="w-full rounded-lg bg-gold px-3 py-2 text-sm font-semibold text-forest-deep">Kaydol</button>
                </form>
            </div>
        </div>
        <div class="border-t border-white/10 px-4 py-4 text-center text-xs text-gold-light/80">
            <a href="{{ route('legal', 'kvkk') }}">KVKK</a> ·
            <a href="{{ route('legal', 'gizlilik') }}">Gizlilik</a> ·
            <a href="{{ route('legal', 'cerezler') }}">Çerezler</a>
            <span class="mx-2">|</span>
            Kişisel veriler Almanya (Frankfurt) sunucusunda işlenir.
        </div>
    </footer>

    <div class="fixed inset-x-0 bottom-0 z-50 bg-forest text-cream px-4 py-4 text-sm shadow-2xl" x-show="cookies" x-cloak>
        <div class="mx-auto flex max-w-6xl flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <p>Site deneyimi için zorunlu çerezler kullanılır. Ayrıntı: <a class="underline text-gold" href="{{ route('legal', 'cerezler') }}">çerez politikası</a>.</p>
            <button type="button" class="rounded-full bg-gold px-4 py-2 text-forest-deep" @click="localStorage.setItem('hacer_cookies','1'); cookies=false">Kabul</button>
        </div>
    </div>
</body>
</html>
