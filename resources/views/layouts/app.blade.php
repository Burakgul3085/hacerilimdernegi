@php
    $navItems = \App\Support\SiteSettings::navItems();
    $currentPath = '/'.trim(request()->path(), '/');

    $isActive = function (string $url) use ($currentPath): bool {
        $url = '/'.trim($url, '/');

        if ($url === '/') {
            return request()->routeIs('home');
        }

        return $currentPath === $url || str_starts_with($currentPath, $url.'/');
    };

    $navItems = array_map(function (array $item) use ($isActive): array {
        $children = array_map(function (array $child) use ($isActive): array {
            $child['active'] = $isActive($child['url'] ?? '');

            return $child;
        }, $item['children'] ?? []);

        $item['children'] = $children;
        $item['active'] = $isActive($item['url'] ?? '')
            || collect($children)->contains(fn (array $child): bool => $child['active']);

        return $item;
    }, $navItems);

    $ctaLabel = $settings['nav_cta_label'] ?? 'Bağış';
    $ctaUrl = $settings['nav_cta_url'] ?: route('donate');
    $siteUrl = rtrim(\App\Support\MailTemplate::publicBaseUrl(), '/');
    $canonical = $siteUrl.request()->getPathInfo();
    $ogImage = \App\Support\SiteSettings::heroImageUrl();
    if (! str_starts_with($ogImage, 'http://') && ! str_starts_with($ogImage, 'https://')) {
        $ogImage = $siteUrl.'/'.ltrim($ogImage, '/');
    }
    $whatsappChatUrl = \App\Support\SiteSettings::whatsappChatUrl();

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
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="alternate" hreflang="tr" href="{{ $canonical }}">
    <link rel="alternate" hreflang="x-default" href="{{ $canonical }}">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="tr_TR">
    <meta property="og:site_name" content="{{ $settings['site_name'] }}">
    <meta property="og:title" content="@yield('title', $settings['site_name'])">
    <meta property="og:description" content="@yield('description', $settings['tagline'] ?? '')">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:alt" content="{{ $settings['site_name'] }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', $settings['site_name'])">
    <meta name="twitter:description" content="@yield('description', $settings['tagline'] ?? '')">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <link rel="icon" type="image/png" href="{{ \App\Support\SiteSettings::faviconUrl() }}">
    <link rel="apple-touch-icon" href="{{ $logoUrl }}">

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

    <script type="application/ld+json">{!! $organizationSchema !!}</script>

    @stack('head')
</head>
<body @class(['min-h-screen bg-cream text-ink', 'has-topbar' => filled($settings['topbar_text'] ?? null), 'page-home' => request()->routeIs('home')])
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
          topbarHidden: false,
          chromeScrolled: false,
          lastScrollY: 0,
          onChromeScroll() {
              const y = window.scrollY;
              this.chromeScrolled = y > 16;

              if (this.menu || this.search || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                  this.topbarHidden = false;
                  this.lastScrollY = y;
                  return;
              }

              if (y < 24) {
                  this.topbarHidden = false;
              } else if (y > this.lastScrollY + 6) {
                  this.topbarHidden = true;
              } else if (y < this.lastScrollY - 6) {
                  this.topbarHidden = false;
              }

              this.lastScrollY = y;
          },
          scrollToTop() {
              const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
              window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
          },
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
      x-on:scroll.window.passive="onChromeScroll()"
      x-on:keydown.escape.window="menu = false; search = false"
      :class="menu && 'overflow-hidden'">

    <x-page-veil :logo-url="$logoUrl" />

    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[70] focus:rounded-full focus:bg-forest focus:px-5 focus:py-3 focus:text-cream">
        İçeriğe geç
    </a>

    @if (request()->routeIs('home'))
        <div class="scroll-progress" data-scroll-progress aria-hidden="true"></div>
    @endif

    <div class="site-chrome"
         x-init="onChromeScroll()"
         :class="{ 'is-compact': topbarHidden, 'is-scrolled': chromeScrolled, 'is-open': menu }">
        @if (filled($settings['topbar_text'] ?? null))
            <div class="site-topbar">
                <div class="shell py-2.5 text-center">
                    <p class="text-[10px] font-medium uppercase tracking-[0.34em] text-cream/60">{{ $settings['topbar_text'] }}</p>
                </div>
            </div>
        @endif

        <header class="site-header">
            <div class="shell flex h-[5.25rem] items-center justify-between gap-8 lg:h-24">
                <a href="{{ route('home') }}" class="flex shrink-0 items-center" aria-label="{{ $settings['site_name'] }}">
                    <img src="{{ $logoUrl }}" alt="{{ $settings['site_name'] }}" class="h-14 w-auto max-w-[220px] object-contain lg:h-[3.75rem] lg:max-w-[240px]">
                </a>

                <nav class="hidden items-center gap-7 xl:flex xl:gap-8" aria-label="Ana menü">
                    <x-site-nav-items :items="$navItems" variant="desktop" />
                </nav>

                <div class="flex items-center gap-2 sm:gap-3">
                    @if (filled($ctaLabel))
                        <a href="{{ $ctaUrl }}" class="btn btn-solid btn-sm hidden sm:inline-flex">{{ $ctaLabel }}</a>
                    @endif

                    <a href="{{ route('contact') }}" class="nav-link hidden xl:inline" data-active="{{ $isActive('/iletisim') ? '1' : '0' }}">İletişim</a>

                    <button type="button" @click="search = !search; menu = false; topbarHidden = false; $nextTick(() => search && $refs.searchInput?.focus())"
                            class="flex h-10 w-10 items-center justify-center rounded-full border border-line/80 bg-paper/40 text-forest transition hover:border-gold hover:text-gold"
                            :aria-expanded="search" aria-controls="site-search">
                        <span class="sr-only">Arama</span>
                        <x-ui.icon name="search" class="h-[18px] w-[18px]" />
                    </button>

                    <button type="button" @click="menu = !menu; search = false; topbarHidden = false"
                            class="flex h-10 w-10 items-center justify-center rounded-full border border-line/80 bg-paper/40 text-forest transition hover:border-gold hover:text-gold"
                            :aria-expanded="menu" aria-controls="site-tray">
                        <span class="sr-only">Menüyü aç</span>
                        <x-ui.icon name="menu" class="h-[18px] w-[18px]" />
                    </button>
                </div>
            </div>

            <div id="site-search" x-show="search" x-cloak x-transition.opacity class="border-t border-line/50 bg-paper/70 backdrop-blur-xl">
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
    </div>

    <div x-show="menu" x-cloak x-transition.opacity class="fixed inset-0 z-[65] bg-forest-deep/55" @click="menu = false"></div>

    <div id="site-tray" x-show="menu" x-cloak role="dialog" aria-modal="true" aria-label="İletişim ve gönüllülük"
         x-transition:enter="transition duration-300 ease-out"
         x-transition:enter-start="-translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition duration-200 ease-in"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="-translate-y-full"
         class="site-tray">
        <button type="button" @click="menu = false"
                class="absolute right-5 top-5 z-10 flex h-11 w-11 items-center justify-center rounded-full border border-white/20 text-cream transition hover:border-cream hover:bg-white/5 sm:right-8 sm:top-6">
            <span class="sr-only">Paneli kapat</span>
            <x-ui.icon name="close" class="h-5 w-5" />
        </button>

            <div class="shell py-14 lg:py-16">
                <nav class="mb-8 border-b border-white/10 pb-8 xl:hidden" aria-label="Mobil menü">
                    <ul class="grid gap-1 sm:grid-cols-2">
                        <x-site-nav-items :items="$navItems" variant="mobile" />
                        <li>
                            <a href="{{ route('contact') }}"
                               class="flex items-center justify-between rounded-xl px-3 py-2.5 font-display text-xl text-cream transition hover:bg-white/5">
                                İletişim
                                <x-ui.icon name="chevron-right" class="h-4 w-4 text-gold" />
                            </a>
                        </li>
                    </ul>
                    @if (filled($ctaLabel))
                        <a href="{{ $ctaUrl }}" class="btn btn-cream mt-5 w-full sm:w-auto">{{ $ctaLabel }}</a>
                    @endif
                </nav>

                <div class="grid gap-10 lg:grid-cols-2 lg:gap-16">
                    <div>
                        <h2 class="font-display text-3xl text-cream">İletişime geçin</h2>
                        <ul class="mt-6 space-y-4 text-sm text-cream/70">
                            @if (filled($settings['email']))
                                <li class="flex items-start gap-3">
                                    <x-ui.icon name="mail" class="mt-0.5 h-[18px] w-[18px] shrink-0 text-gold" />
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gold">E-posta</p>
                                        <a href="mailto:{{ $settings['email'] }}" class="mt-1 block transition hover:text-cream">{{ $settings['email'] }}</a>
                                    </div>
                                </li>
                            @endif
                            @if (filled($settings['address']))
                                <li class="flex items-start gap-3">
                                    <x-ui.icon name="pin" class="mt-0.5 h-[18px] w-[18px] shrink-0 text-gold" />
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gold">Adres</p>
                                        <p class="mt-1 leading-relaxed">{{ $settings['address'] }}</p>
                                    </div>
                                </li>
                            @endif
                            @if (filled($settings['phone']))
                                <li class="flex items-start gap-3">
                                    <x-ui.icon name="phone" class="mt-0.5 h-[18px] w-[18px] shrink-0 text-gold" />
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gold">Telefon</p>
                                        <a href="tel:{{ preg_replace('/\s+/', '', $settings['phone']) }}" class="mt-1 block transition hover:text-cream">{{ $settings['phone'] }}</a>
                                    </div>
                                </li>
                            @endif
                        </ul>
                    </div>

                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-gold">Gönüllü ol</p>
                        <h2 class="mt-3 font-display text-3xl text-cream">{{ $settings['membership_card_title'] }}</h2>
                        @if (filled($settings['membership_card_text']))
                            <p class="mt-4 max-w-md text-sm leading-relaxed text-cream/70">{{ $settings['membership_card_text'] }}</p>
                        @endif
                        <a href="{{ route('membership') }}" class="btn btn-cream mt-7">
                            Üyelik / gönüllülük formu
                            <x-ui.icon name="arrow-right" class="h-4 w-4" />
                        </a>
                    </div>
                </div>

                <div class="mt-10 border-t border-white/10 pt-8">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-gold">Sosyal medyada bizi takip edin</p>
                    <x-social-links :settings="$settings" tone="dark" class="mt-5" />
                </div>
            </div>
    </div>

    @unless (request()->routeIs('home'))
        <div class="site-chrome-spacer" aria-hidden="true"></div>
    @endunless

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
                <div class="reveal lg:col-span-5">
                    <img src="{{ $logoUrl }}" alt="{{ $settings['site_name'] }}" class="h-12 w-auto object-contain brightness-0 invert sm:h-14">
                    <p class="mt-5 font-display text-[1.85rem] leading-snug tracking-tight sm:text-3xl">{{ $settings['site_name'] }}</p>
                    @if (filled($settings['tagline']))
                        <p class="mt-3 max-w-sm text-sm leading-relaxed text-cream/55">{{ $settings['tagline'] }}</p>
                    @endif
                    <x-social-links :settings="$settings" tone="dark" class="mt-8" />
                </div>

                <div class="reveal lg:col-span-4" style="--reveal-delay: 90ms">
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

                <div class="reveal lg:col-span-3" style="--reveal-delay: 180ms">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-gold">{{ $settings['newsletter_title'] ?: 'E-bülten' }}</p>
                    @if (filled($settings['newsletter_text']))
                        <p class="mt-3 text-sm leading-relaxed text-cream/55">{{ $settings['newsletter_text'] }}</p>
                    @endif
                    <form method="POST" action="{{ route('newsletter.store') }}" class="relative mt-5">
                        @csrf
                        <x-honeypot />
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

    @if (request()->routeIs('home'))
        <div class="site-float" :class="cookies ? 'bottom-28' : 'bottom-6'">
            <button type="button"
                    class="site-float-top"
                    x-show="chromeScrolled"
                    x-cloak
                    x-transition.opacity
                    @click="scrollToTop()"
                    aria-label="Sayfanın başına dön">
                <img src="{{ asset('images/icons/mouse.svg') }}" alt="" class="h-7 w-7" aria-hidden="true">
            </button>

            @if ($whatsappChatUrl)
                <a href="{{ $whatsappChatUrl }}"
                   class="site-float-whatsapp"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="WhatsApp ile yazın">
                    <span class="site-float-whatsapp-ping" aria-hidden="true"></span>
                    <span class="site-float-whatsapp-ping site-float-whatsapp-ping-delay" aria-hidden="true"></span>
                    <x-ui.icon name="whatsapp" class="relative z-10 h-7 w-7" />
                </a>
            @endif
        </div>
    @endif
</body>
</html>
