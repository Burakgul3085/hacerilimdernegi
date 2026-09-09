<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $settings['site_name'] ?? 'Hâcer İlim ve Kültür Derneği')</title>
    <meta name="description" content="@yield('description', $settings['tagline'] ?? '')">
    <link rel="canonical" href="{{ 'https://'.($settings['domain'] ?? 'hacerilimvekulturdernegi.org').request()->getPathInfo() }}">
    <link rel="icon" type="image/png" href="{{ $logoUrl }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:500,600,700|source-sans-3:400,500,600,700" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        :root {
            --color-forest: {{ $settings['color_primary'] ?? '#161513' }};
            --color-gold: {{ $settings['color_gold'] ?? '#8A7A62' }};
        }
    </style>
</head>
<body class="min-h-screen bg-cream text-ink" x-data="{ open: false, cookies: localStorage.getItem('hacer_cookies') !== '1' }">
    <div class="bg-forest-deep text-cream/80 text-center text-[11px] tracking-[0.28em] uppercase py-2">
        Gaziantep · İlim · Sohbet · Kültür
    </div>
    <header class="sticky top-0 z-40 border-b border-line bg-paper/95 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3">
                <img src="{{ $logoUrl }}" alt="{{ $settings['site_name'] }}" class="h-14 w-auto max-w-[220px] object-contain">
            </a>
            <nav class="hidden items-center gap-5 text-sm font-medium text-forest lg:flex">
                <a href="{{ route('about') }}">Hakkımızda</a>
                <a href="{{ route('programs.index') }}">Programlar</a>
                <a href="{{ route('events.index') }}">Etkinlikler</a>
                <a href="{{ route('posts.index') }}">Yazılar</a>
                <a href="{{ route('media.index') }}">Medya</a>
                <a href="{{ route('live') }}">Canlı</a>
                <a href="{{ route('membership') }}">Üyelik</a>
                <a href="{{ route('donate') }}" class="rounded-full bg-forest px-4 py-2 text-cream">Bağış</a>
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
                <img src="{{ $logoUrl }}" alt="{{ $settings['site_name'] }}" class="mb-4 h-16 w-auto brightness-0 invert">
                <p class="font-display text-3xl">Hâcer İlim ve Kültür Derneği</p>
                <p class="mt-3 max-w-md text-sm text-cream/70">{{ $settings['tagline'] }}</p>
            </div>
            <div class="text-sm space-y-2">
                <p class="text-gold tracking-wide uppercase text-xs">İletişim</p>
                <p>{{ $settings['address'] }}</p>
                @if ($settings['phone'])<p>{{ $settings['phone'] }}</p>@endif
                <p>{{ $settings['email'] }}</p>
                <div class="flex flex-wrap gap-3 pt-2 text-gold-light">
                    @if (!empty($settings['telegram']))<a href="{{ $settings['telegram'] }}" target="_blank" rel="noopener">Telegram</a>@endif
                    @if (!empty($settings['whatsapp']))<a href="{{ $settings['whatsapp'] }}" target="_blank" rel="noopener">WhatsApp</a>@endif
                    @if (!empty($settings['twitter']))<a href="{{ $settings['twitter'] }}" target="_blank" rel="noopener">X</a>@endif
                    @if (!empty($settings['instagram']))<a href="{{ $settings['instagram'] }}" target="_blank" rel="noopener">Instagram</a>@endif
                    @if (!empty($settings['youtube']))<a href="{{ $settings['youtube'] }}" target="_blank" rel="noopener">YouTube</a>@endif
                </div>
            </div>
            <div>
                <p class="text-gold tracking-wide uppercase text-xs mb-3">E-bülten</p>
                <form method="POST" action="{{ route('newsletter.store') }}" class="space-y-2">
                    @csrf
                    <input type="email" name="email" required placeholder="E-posta" class="w-full rounded-lg bg-forest px-3 py-2 text-sm text-cream placeholder:text-cream/50">
                    <button class="w-full rounded-lg bg-cream px-3 py-2 text-sm font-semibold text-forest">Kaydol</button>
                </form>
            </div>
        </div>
        <div class="border-t border-white/10 px-4 py-4 text-center text-xs text-cream/60">
            <a href="{{ route('legal', 'kvkk') }}">KVKK</a> ·
            <a href="{{ route('legal', 'gizlilik') }}">Gizlilik</a> ·
            <a href="{{ route('legal', 'cerezler') }}">Çerezler</a>
            <span class="mx-2">|</span>
            Kişisel veriler Almanya (Frankfurt) sunucusunda işlenir.
        </div>
    </footer>

    <div class="fixed inset-x-0 bottom-0 z-50 bg-forest text-cream px-4 py-4 text-sm shadow-2xl" x-show="cookies" x-cloak>
        <div class="mx-auto flex max-w-6xl flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <p>Site deneyimi için zorunlu çerezler kullanılır. Ayrıntı: <a class="underline text-gold-light" href="{{ route('legal', 'cerezler') }}">çerez politikası</a>.</p>
            <button type="button" class="rounded-full bg-cream px-4 py-2 text-forest" @click="localStorage.setItem('hacer_cookies','1'); cookies=false">Kabul</button>
        </div>
    </div>
</body>
</html>
