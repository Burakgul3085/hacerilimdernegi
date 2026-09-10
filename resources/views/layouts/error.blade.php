<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="{{ $settings['color_primary'] ?: '#161513' }}">

    <title>@yield('title', 'Sayfa bulunamadı') — {{ $settings['site_name'] }}</title>
    <meta name="description" content="@yield('description', 'Aradığınız sayfa bulunamadı.')">

    <link rel="icon" type="image/png" href="{{ \App\Support\SiteSettings::faviconUrl() }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:500,600,700|source-sans-3:400,500,600,700" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        <script>
            document.documentElement.classList.add('js');
            try {
                if (! window.matchMedia('(prefers-reduced-motion: reduce)').matches
                    && (sessionStorage.getItem('hacer_veil') === '1' || ! sessionStorage.getItem('hacer_veil_seen'))) {
                    document.documentElement.classList.add('veil-pending');
                }
            } catch (error) {}
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        :root {
            --color-forest: {{ $settings['color_primary'] ?: '#161513' }};
            --color-gold: {{ $settings['color_gold'] ?: '#8A7A62' }};
        }

        .page-veil { display: none; }
        html.js.veil-pending .page-veil {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: grid;
            place-items: center;
            background: #0c0b0a;
        }
    </style>
</head>
<body class="relative min-h-screen overflow-hidden bg-cream text-ink">
    <x-page-veil :logo-url="$logoUrl" />

    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_50%_18%,rgb(212_203_190/0.45),transparent_58%)]"></div>
    <div class="grain pointer-events-none absolute inset-0 opacity-40"></div>

    <main id="main" class="relative grid min-h-screen place-items-center px-5 py-16">
        @yield('content')
    </main>
</body>
</html>
