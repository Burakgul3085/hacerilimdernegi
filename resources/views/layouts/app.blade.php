@php
    $navItems = \App\Support\SiteSettings::list('nav_items');
    $currentPath = '/'.trim(request()->path(), '/');

    $isActive = function (string $url) use ($currentPath): bool {
        $url = '/'.trim($url, '/');

        return $url !== '/' && ($currentPath === $url || str_starts_with($currentPath, $url.'/'));
    };

    $ctaLabel = $settings['nav_cta_label'] ?? 'Bağış';
    $ctaUrl = $settings['nav_cta_url'] ?: route('donate');
    $siteUrl = 'https://'.($settings['domain'] ?: 'hacerilimvekulturdernegi.org');
    $canonical = $siteUrl.request()->getPathInfo();

    $organizationSchema = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'NGO',
        'name' => $settings['site_name'],
        'description' => $settings['tagline'],
        'url' => $siteUrl,
        'logo' => $logoUrl,
        'email' => $settings['email'] ?: null,
        'telephone' => $settings['phone'] ?: null,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $settings['address'],
            'addressLocality' => 'Gaziantep',
            'addressCountry' => 'TR',
        ],
        'sameAs' => array_values(array_filter([
            $settings['telegram'] ?? null,
            $settings['whatsapp'] ?? null,
            $settings['twitter'] ?? null,
            $settings['instagram'] ?? null,
            $settings['youtube'] ?? null,
            $settings['facebook'] ?? null,
        ])),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $legalDocs = [
        'kvkk' => [
            'title' => 'KVKK aydınlatma metni',
            'body' => filled($settings['kvkk_text'] ?? null)
                ? $settings['kvkk_text']
                : 'Bu metin yönetim panelinden düzenlenir. Kişisel veriler Hostinger KVM sunucusunda (Almanya, Frankfurt) saklanır.',
        ],
        'gizlilik' => [
            'title' => 'Gizlilik politikası',
            'body' => filled($settings['privacy_text'] ?? null)
                ? $settings['privacy_text']
                : 'Bu metin yönetim panelinden düzenlenir.',
        ],
        'cerezler' => [
            'title' => 'Çerez politikası',
            'body' => filled($settings['cookie_text'] ?? null)
                ? $settings['cookie_text']
                : 'Bu metin yönetim panelinden düzenlenir.',
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $settings['color_primary'] ?: '#161513' }}">

    <title>@hasSection('title')@yield('title') — {{ $settings['site_name'] }}@else{{ $settings['site_name'] }}@endif</title>
    <meta name="description" content="@yield('description', $settings['tagline'] ?? '')">
    <link rel="canonical" href="{{ $canonical }}">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="tr_TR">
    <meta property="og:site_name" content="{{ $settings['site_name'] }}">
    <meta property="og:title" content="@yield('title', $settings['site_name'])">
    <meta property="og:description" content="@yield('description', $settings['tagline'] ?? '')">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ \App\Support\SiteSettings::heroImageUrl() }}">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" type="image/png" href="{{ \App\Support\SiteSettings::faviconUrl() }}">
    <link rel="apple-touch-icon" href="{{ $logoUrl }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:500,600,700|source-sans-3:400,500,600,700" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        <script>document.documentElement.classList.add('js');</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        :root {
            --color-forest: {{ $settings['color_primary'] ?: '#161513' }};
            --color-gold: {{ $settings['color_gold'] ?: '#8A7A62' }};
        }
    </style>

    <script type="application/ld+json">{!! $organizationSchema !!}</script>

    @stack('head')
</head>
<body class="min-h-screen bg-cream text-ink"
      x-data="{
          menu: false,
          search: false,
          cookies: localStorage.getItem('hacer_cookies') !== '1',
          legal: null,
          legalDocs: @js($legalDocs),
          legalTabs: [
              { key: 'kvkk', short: 'KVKK' },
              { key: 'gizlilik', short: 'Gizlilik' },
              { key: 'cerezler', short: 'Çerezler' },
          ],
          openLegal(type) {
              this.legal = type;
              this.menu = false;
              this.search = false;
          },
          closeLegal() {
              this.legal = null;
          },
          legalTitle() {
              return this.legalDocs[this.legal]?.title ?? '';
          },
          legalBody() {
              return this.legalDocs[this.legal]?.body ?? '';
          },
      }"
      :class="menu && 'overflow-hidden'">

    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[70] focus:rounded-full focus:bg-forest focus:px-5 focus:py-3 focus:text-cream">
        İçeriğe geç
    </a>

    @if (filled($settings['topbar_text'] ?? null))
        <div class="bg-forest-deep">
            <div class="shell py-2.5 text-center">
                <p class="text-[10px] font-medium uppercase tracking-[0.34em] text-cream/60">{{ $settings['topbar_text'] }}</p>
            </div>
        </div>
    @endif

    <header class="sticky top-0 z-50 border-b border-line bg-paper/85 backdrop-blur-xl">
        <div class="shell flex h-[4.5rem] items-center justify-between gap-6 lg:h-20">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center" aria-label="{{ $settings['site_name'] }}">
                <img src="{{ $logoUrl }}" alt="{{ $settings['site_name'] }}" class="h-11 w-auto max-w-[200px] object-contain lg:h-14">
            </a>

            <nav class="hidden items-center gap-6 xl:flex" aria-label="Ana menü">
                @foreach ($navItems as $item)
                    <a href="{{ $item['url'] ?? '#' }}" class="nav-link" data-active="{{ $isActive($item['url'] ?? '') ? '1' : '0' }}">
                        {{ $item['label'] ?? '' }}
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2 sm:gap-3">
                @if (filled($ctaLabel))
                    <a href="{{ $ctaUrl }}" class="btn btn-solid btn-sm hidden sm:inline-flex">{{ $ctaLabel }}</a>
                @endif

                <a href="{{ route('contact') }}" class="nav-link hidden xl:inline" data-active="{{ $isActive('/iletisim') ? '1' : '0' }}">İletişim</a>

                <button type="button" @click="search = !search; $nextTick(() => search && $refs.searchInput?.focus())"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-line text-forest transition hover:border-gold hover:text-gold"
                        :aria-expanded="search" aria-controls="site-search">
                    <span class="sr-only">Arama</span>
                    <x-ui.icon name="search" class="h-[18px] w-[18px]" />
                </button>

                <button type="button" @click="menu = true"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-line text-forest transition hover:border-gold hover:text-gold xl:hidden">
                    <span class="sr-only">Menüyü aç</span>
                    <x-ui.icon name="menu" class="h-[18px] w-[18px]" />
                </button>
            </div>
        </div>

        <div id="site-search" x-show="search" x-cloak x-transition.opacity class="border-t border-line bg-paper">
            <form action="{{ route('search') }}" method="GET" class="shell flex items-center gap-3 py-4">
                <x-ui.icon name="search" class="h-5 w-5 shrink-0 text-gold" />
                <label for="site-search-input" class="sr-only">Sitede ara</label>
                <input id="site-search-input" x-ref="searchInput" type="search" name="q" value="{{ request('q') }}"
                       placeholder="Program, etkinlik veya yazı arayın…"
                       class="w-full bg-transparent text-base text-forest placeholder:text-muted/70 focus:outline-none">
                <button type="submit" class="btn btn-solid btn-sm shrink-0">Ara</button>
                <button type="button" @click="search = false" class="shrink-0 text-muted transition hover:text-forest">
                    <span class="sr-only">Aramayı kapat</span>
                    <x-ui.icon name="close" class="h-5 w-5" />
                </button>
            </form>
        </div>
    </header>

    <div x-show="menu" x-cloak class="fixed inset-0 z-[60] xl:hidden" role="dialog" aria-modal="true" aria-label="Menü">
        <div class="absolute inset-0 bg-forest-deep/70" @click="menu = false" x-transition.opacity></div>

        <div class="absolute inset-y-0 right-0 flex w-full max-w-sm flex-col bg-paper shadow-float"
             x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-full"
             x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="translate-x-full">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <img src="{{ $logoUrl }}" alt="" class="h-10 w-auto object-contain">
                <button type="button" @click="menu = false" class="flex h-10 w-10 items-center justify-center rounded-full border border-line text-forest">
                    <span class="sr-only">Menüyü kapat</span>
                    <x-ui.icon name="close" class="h-5 w-5" />
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-5 py-6">
                <ul class="space-y-1">
                    @foreach ($navItems as $item)
                        <li>
                            <a href="{{ $item['url'] ?? '#' }}"
                               class="flex items-center justify-between rounded-xl px-4 py-3 font-display text-2xl text-forest transition hover:bg-cream {{ $isActive($item['url'] ?? '') ? 'bg-cream' : '' }}">
                                {{ $item['label'] ?? '' }}
                                <x-ui.icon name="chevron-right" class="h-4 w-4 text-gold" />
                            </a>
                        </li>
                    @endforeach
                    <li>
                        <a href="{{ route('contact') }}" class="flex items-center justify-between rounded-xl px-4 py-3 font-display text-2xl text-forest transition hover:bg-cream">
                            İletişim
                            <x-ui.icon name="chevron-right" class="h-4 w-4 text-gold" />
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="space-y-4 border-t border-line px-5 py-5">
                @if (filled($ctaLabel))
                    <a href="{{ $ctaUrl }}" class="btn btn-solid w-full">{{ $ctaLabel }}</a>
                @endif
                <x-social-links :settings="$settings" />
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="shell pt-8">
            <div class="flex items-start gap-3 rounded-2xl border border-gold/40 bg-gold-light/25 px-5 py-4 text-sm text-forest" role="status">
                <x-ui.icon name="check" class="mt-0.5 h-5 w-5 shrink-0 text-gold" />
                <p>{{ session('status') }}</p>
            </div>
        </div>
    @endif

    <main id="main" @class(['pb-16 lg:pb-24' => ! request()->routeIs('home')])>
        @yield('content')
    </main>

    <footer class="bg-forest-deep text-cream">
        <div class="shell py-14 sm:py-16 lg:py-20">
            <div class="grid gap-12 lg:grid-cols-12 lg:gap-14">
                <div class="lg:col-span-5">
                    <img src="{{ $logoUrl }}" alt="{{ $settings['site_name'] }}" class="h-12 w-auto object-contain brightness-0 invert sm:h-14">
                    <p class="mt-5 font-display text-[1.85rem] leading-snug tracking-tight sm:text-3xl">{{ $settings['site_name'] }}</p>
                    @if (filled($settings['tagline']))
                        <p class="mt-3 max-w-sm text-sm leading-relaxed text-cream/55">{{ $settings['tagline'] }}</p>
                    @endif
                    <x-social-links :settings="$settings" tone="dark" class="mt-8" />
                </div>

                <div class="lg:col-span-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-gold">İletişim</p>
                    <ul class="mt-5 space-y-4 text-sm text-cream/70">
                        @if (filled($settings['address']))
                            <li class="flex items-start gap-3">
                                <x-ui.icon name="pin" class="mt-0.5 h-[18px] w-[18px] shrink-0 text-gold" />
                                <span class="leading-relaxed">{{ $settings['address'] }}</span>
                            </li>
                        @endif
                        @if (filled($settings['phone']))
                            <li class="flex items-start gap-3">
                                <x-ui.icon name="phone" class="mt-0.5 h-[18px] w-[18px] shrink-0 text-gold" />
                                <a href="tel:{{ preg_replace('/\s+/', '', $settings['phone']) }}" class="transition hover:text-cream">{{ $settings['phone'] }}</a>
                            </li>
                        @endif
                        @if (filled($settings['email']))
                            <li class="flex items-start gap-3">
                                <x-ui.icon name="mail" class="mt-0.5 h-[18px] w-[18px] shrink-0 text-gold" />
                                <a href="mailto:{{ $settings['email'] }}" class="break-all transition hover:text-cream">{{ $settings['email'] }}</a>
                            </li>
                        @endif
                    </ul>
                </div>

                <div class="lg:col-span-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-gold">{{ $settings['newsletter_title'] ?: 'E-bülten' }}</p>
                    @if (filled($settings['newsletter_text']))
                        <p class="mt-3 text-sm leading-relaxed text-cream/55">{{ $settings['newsletter_text'] }}</p>
                    @endif
                    <form method="POST" action="{{ route('newsletter.store') }}" class="mt-5">
                        @csrf
                        <label for="footer-newsletter" class="sr-only">E-posta adresiniz</label>
                        <div class="flex items-center gap-2 rounded-full border border-white/12 bg-white/[0.04] py-1.5 pl-4 pr-1.5 transition focus-within:border-gold/70">
                            <input id="footer-newsletter" type="email" name="email" required placeholder="E-posta adresiniz"
                                   class="w-full bg-transparent text-sm text-cream placeholder:text-cream/35 focus:outline-none">
                            <button type="submit" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cream text-forest transition hover:bg-gold hover:text-white">
                                <span class="sr-only">Kaydol</span>
                                <x-ui.icon name="arrow-right" class="h-4 w-4" />
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="border-t border-white/10">
            <div class="shell flex flex-col gap-5 py-5 text-[12px] text-cream/45 lg:flex-row lg:items-center lg:justify-between lg:gap-8">
                <p class="order-1 text-cream/50">© {{ date('Y') }} {{ $settings['site_name'] }}</p>

                <nav class="order-2 flex flex-wrap items-center gap-x-1 gap-y-2 lg:justify-center" aria-label="Yasal metinler">
                    <button type="button" class="rounded-full px-3 py-1 transition hover:bg-white/5 hover:text-cream" @click="openLegal('kvkk')">KVKK</button>
                    <span class="text-cream/20" aria-hidden="true">·</span>
                    <button type="button" class="rounded-full px-3 py-1 transition hover:bg-white/5 hover:text-cream" @click="openLegal('gizlilik')">Gizlilik</button>
                    <span class="text-cream/20" aria-hidden="true">·</span>
                    <button type="button" class="rounded-full px-3 py-1 transition hover:bg-white/5 hover:text-cream" @click="openLegal('cerezler')">Çerezler</button>
                </nav>

                <div class="order-3 flex flex-wrap items-center gap-x-3 gap-y-2 lg:justify-end">
                    @if (filled($settings['footer_note']))
                        <p class="text-cream/40">{{ $settings['footer_note'] }}</p>
                    @endif

                    @if (filled($settings['developer_name']))
                        <p class="inline-flex flex-wrap items-center gap-x-2.5 gap-y-1">
                            <span>{{ $settings['developer_label'] ?: 'Tasarım ve yazılım' }}</span>
                            @if (filled($settings['developer_url']))
                                <a href="{{ $settings['developer_url'] }}" target="_blank" rel="noopener noreferrer author"
                                   class="inline-flex items-center gap-1.5 font-medium text-cream/70 transition hover:text-gold">
                                    <x-ui.icon name="linkedin" class="h-3.5 w-3.5" />
                                    {{ $settings['developer_name'] }}
                                </a>
                            @else
                                <span class="font-medium text-cream/70">{{ $settings['developer_name'] }}</span>
                            @endif
                            @if (filled($settings['developer_email']))
                                <a href="mailto:{{ $settings['developer_email'] }}"
                                   aria-label="{{ $settings['developer_name'] }} ile e-posta üzerinden iletişime geçin"
                                   class="inline-flex items-center gap-1.5 transition hover:text-gold">
                                    <x-ui.icon name="mail" class="h-3.5 w-3.5" />
                                    <span>E-posta</span>
                                </a>
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </footer>

    <div x-show="cookies" x-cloak x-transition.opacity class="fixed inset-x-0 bottom-0 z-50 px-4 pb-4">
        <div class="shell">
            <div class="flex flex-col gap-4 rounded-2xl bg-forest px-6 py-5 text-sm text-cream shadow-float sm:flex-row sm:items-center sm:justify-between">
                <p class="text-cream/80">
                    Site deneyimi için zorunlu çerezler kullanılır. Ayrıntı:
                    <button type="button" class="underline decoration-gold underline-offset-4" @click="openLegal('cerezler')">çerez politikası</button>.
                </p>
                <button type="button" class="btn btn-cream btn-sm shrink-0"
                        @click="localStorage.setItem('hacer_cookies','1'); cookies = false">Kabul ediyorum</button>
            </div>
        </div>
    </div>

    <x-legal-modal />
</body>
</html>
