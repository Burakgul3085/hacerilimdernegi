<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SiteSettings
{
    /**
     * Ayar anahtarlarından hangileri JSON listesi tutar.
     *
     * @var list<string>
     */
    public const LIST_KEYS = ['nav_items', 'value_pillars', 'stats', 'instagram_posts'];

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'site_name' => 'Hâcer İlim ve Kültür Derneği',
            'tagline' => 'Gaziantep’te ilim, sohbet ve kültür',
            'about_excerpt' => 'Hâcer İlim ve Kültür Derneği, Gaziantep’te dersler, sohbetler ve kitap tahlilleri etrafında bir ilim muhiti kurar.',
            'topbar_text' => 'Gaziantep · İlim · Sohbet · Kültür',
            'address' => 'Karacaahmet, 38012 Nolu Cadde No: 36A, Bina 111 Kat 1 Daire 1, 27590 Şehitkamil / Gaziantep',
            'phone' => '',
            'email' => 'info@hacerilimvekulturdernegi.org',
            'domain' => 'hacerilimvekulturdernegi.org',
            'map_embed' => '<iframe src="https://maps.google.com/maps?q=Karacaahmet%2038012%20Nolu%20Cadde%2036A%20%C5%9Eehitkamil%20Gaziantep&t=&z=16&ie=UTF8&iwloc=&output=embed" width="100%" height="420" style="border:0;" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Dernek konumu" allowfullscreen></iframe>',
            'facebook' => '',
            'instagram' => 'https://www.instagram.com/hacerilimkultur',
            'youtube' => '',
            'telegram' => 'https://t.me/+A1atig-4aOYyYjE8',
            'whatsapp' => 'https://whatsapp.com/channel/0029VbBGQs30AgW5W0OZ4F3y',
            'twitter' => 'https://x.com/hacerilimkultur',
            'instagram_posts' => json_encode([], JSON_UNESCAPED_UNICODE),
            'bank_account_name' => 'Hâcer İlim ve Kültür Derneği',
            'bank_name' => 'Demo Bankası',
            'bank_details_are_demo' => '1',
            'donation_note' => 'Banka ve IBAN bilgileri henüz dernek yönetimi tarafından bildirilmedi. Aşağıdaki bilgiler yalnızca tasarım ön izlemesi içindir; bu bilgilere ödeme yapmayınız.',
            'iban' => 'DEMO — GERÇEK IBAN BEKLENİYOR',
            'logo' => '',
            'favicon' => '',
            'color_primary' => '#161513',
            'color_gold' => '#8A7A62',
            'kvkk_text' => '',
            'privacy_text' => '',
            'cookie_text' => '',

            'nav_items' => json_encode(static::defaultNavItems(), JSON_UNESCAPED_UNICODE),
            'nav_cta_label' => 'Bağış',
            'nav_cta_url' => '/bagis',

            'hero_eyebrow' => 'Gaziantep · Şehitkamil',
            'hero_title' => 'İlim, sohbet ve kültür etrafında duran bir dernek.',
            'hero_text' => 'Hâcer İlim ve Kültür Derneği; Gaziantep’te dersler, sohbetler ve kitap tahlilleri etrafında bir ilim muhiti kurar.',
            'hero_image' => '',
            'hero_primary_label' => 'Programlar',
            'hero_primary_url' => '/programlar',
            'hero_secondary_label' => 'Üyelik / gönüllü',
            'hero_secondary_url' => '/uyelik',
            'hero_quote' => 'İlim, hayatı güzelleştirir; insanı, toplumu ve yarınları inşa eder.',
            'hero_quote_author' => '',

            'value_pillars' => json_encode([
                ['icon' => 'book', 'title' => 'İlim', 'text' => 'Kur’an ve sünnet ışığında sağlam bilgi.'],
                ['icon' => 'chat', 'title' => 'Sohbet', 'text' => 'Samimi ve faydalı bir muhitte buluşmak.'],
                ['icon' => 'leaf', 'title' => 'Kültür', 'text' => 'Köklü değerleri bugüne taşımak.'],
                ['icon' => 'heart', 'title' => 'Kardeşlik', 'text' => 'İyiliği birlikte büyütmek.'],
            ], JSON_UNESCAPED_UNICODE),

            'stats' => json_encode([
                ['value' => '2017', 'label' => 'Kuruluşumuzdan bu yana'],
                ['value' => '100+', 'label' => 'Ders, sohbet ve program'],
                ['value' => 'Gönüllü', 'label' => 'Katılıma açık topluluk'],
            ], JSON_UNESCAPED_UNICODE),

            'cta_icon' => 'users',
            'cta_title' => 'Tüm programları keşfedin',
            'cta_text' => 'Güncel ders, sohbet ve etkinlik takvimine göz atın.',
            'cta_button_label' => 'Tüm programlar',
            'cta_button_url' => '/programlar',

            'home_programs_title' => 'Yaklaşan programlar',
            'home_programs_text' => 'Dersler, sohbetler ve kitap tahlilleri.',
            'home_events_title' => 'Etkinlik takvimi',
            'home_events_text' => 'Katılıma açık yaklaşan etkinlikler.',
            'home_posts_title' => 'Yazılar ve duyurular',
            'home_posts_text' => 'Dernek gündeminden seçmeler.',
            'home_media_title' => 'Medya arşivi',
            'home_media_text' => 'Program ve etkinliklerden kareler.',
            'home_location_eyebrow' => 'Bizi ziyaret edin',
            'home_location_title' => 'Dernek konumu',
            'home_location_button' => 'Haritayı aç',

            'about_image' => '',
            'about_quote' => 'İlim, hayatı güzelleştirir; insanı, toplumu ve yarınları inşa eder.',

            'programs_intro' => 'Dersler, sohbetler ve kitap tahlilleri.',
            'events_intro' => 'Aylara göre yaklaşan programlar. Katılım başvurusu etkinlik detayındadır.',
            'posts_intro' => 'Dernek gündemine dair yazılar ve resmî duyurular.',
            'media_intro' => 'Program ve etkinliklerimizden fotoğraf, video ve ses kayıtları.',
            'membership_intro' => 'Dernek çalışmalarına katılmak için formu doldurun.',
            'donate_intro' => 'Dernek faaliyetleri bağışlarınızla sürer.',
            'contact_intro' => 'Bizimle iletişime geçebilirsiniz.',
            'live_intro' => 'Instagram hesabından seçilen kareler ve kısa videolar, sitede vitrin olarak durur.',

            'membership_card_title' => 'Birlikte daha güçlüyüz',
            'membership_card_text' => 'İlim, kültür ve kardeşlik çalışmalarında sen de yerini al.',

            'newsletter_title' => 'E-bülten',
            'newsletter_text' => 'Gelişmelerden haberdar olun.',
            'footer_note' => '',

            'developer_label' => 'Tasarım ve yazılım',
            'developer_name' => 'Burak Gül',
            'developer_url' => 'https://www.linkedin.com/in/burakgul1006/',
            'developer_email' => 'burakgul3085@gmail.com',

            'mailer_username' => '',
            'mailer_password' => '',
            'mailer_from_name' => 'Hâcer İlim Yönetim',
            'mailer_otp_to' => '',
        ];
    }

    /**
     * Uygulama şifresi gibi hassas ayarlar şifreli saklanır.
     *
     * @var list<string>
     */
    public const SECRET_KEYS = ['mailer_password'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::all();

        if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '') {
            return $all[$key];
        }

        return $default ?? (static::defaults()[$key] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return Cache::remember('site_settings', 60, function () {
            $stored = Setting::query()->pluck('value', 'key')->all();

            return array_merge(static::defaults(), $stored);
        });
    }

    /**
     * JSON olarak saklanan tekrarlı içerikleri (menü, değerler, istatistikler) diziye çevirir.
     *
     * @return list<array<string, mixed>>
     */
    public static function list(string $key): array
    {
        $decoded = json_decode((string) static::get($key), true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, 'is_array'));
    }

    /**
     * Varsayılan ana menü ağacı.
     *
     * @return list<array{label: string, url: string, children?: list<array{label: string, url: string}>}>
     */
    public static function defaultNavItems(): array
    {
        return [
            [
                'label' => 'Kurumsal',
                'url' => '/hakkimizda',
                'children' => [
                    ['label' => 'Hakkımızda', 'url' => '/hakkimizda'],
                    ['label' => 'Vizyon ve misyon', 'url' => '/vizyon-misyon'],
                    ['label' => 'Başkanın mesajı', 'url' => '/baskanin-mesaji'],
                    ['label' => 'Yönetim kadrosu', 'url' => '/yonetim-kadrosu'],
                    ['label' => 'Dernek tüzüğü', 'url' => '/dernek-tuzugu'],
                ],
            ],
            [
                'label' => 'Projeler',
                'url' => '/programlar',
                'children' => [
                    ['label' => 'Programlar', 'url' => '/programlar'],
                    ['label' => 'Etkinlikler', 'url' => '/etkinlikler'],
                ],
            ],
            ['label' => 'Yazılar', 'url' => '/yazilar'],
            ['label' => 'Medya', 'url' => '/medya'],
            ['label' => 'Vitrin', 'url' => '/vitrin'],
            ['label' => 'Üyelik', 'url' => '/uyelik'],
        ];
    }

    /**
     * Eski düz menüyü ve Seçkiler / Canlı / Sosyal kayıtlarını güncel ağaca taşır.
     * Ana sayfa bağlantısını her zaman ilk sırada tutar.
     *
     * @return list<array{label: string, url: string, children?: list<array{label: string, url: string}>}>
     */
    public static function navItems(): array
    {
        $stored = static::list('nav_items');
        $items = static::shouldReplaceStoredNav($stored)
            ? static::defaultNavItems()
            : array_values(array_filter(
                array_map(fn (array $item): ?array => static::normalizeNavItem($item), $stored),
            ));

        array_unshift($items, [
            'label' => 'Ana sayfa',
            'url' => '/',
        ]);

        return $items;
    }

    /**
     * Eski canlı yayın, Sosyal ve Seçkiler giriş yazılarını Vitrin metnine çevirir.
     */
    public static function socialIntro(): string
    {
        $intro = (string) static::get('live_intro');

        $legacy = [
            'Ders ve sohbet yayınları bu sayfadan takip edilir.',
            'Instagram paylaşımlarımız bu sayfada yer alır.',
            'Derneğin Instagram hesabından seçilen kareler ve kısa videolar.',
        ];

        if (in_array($intro, $legacy, true)) {
            return (string) static::defaults()['live_intro'];
        }

        return $intro;
    }

    /**
     * Eski düz menüyü veya Medya’yı Projeler altına koyan yanlış ağacı güncel menüyle değiştirir.
     *
     * @param  list<array<string, mixed>>  $items
     */
    private static function shouldReplaceStoredNav(array $items): bool
    {
        $urls = array_map(
            fn (array $item): string => rtrim((string) ($item['url'] ?? ''), '/') ?: '/',
            $items,
        );
        sort($urls);

        if ($urls === ['/etkinlikler', '/hakkimizda', '/medya', '/programlar', '/seckiler', '/uyelik', '/yazilar']) {
            return true;
        }

        foreach ($items as $item) {
            if (($item['label'] ?? '') !== 'Projeler') {
                continue;
            }

            $childUrls = [];

            foreach ($item['children'] ?? [] as $child) {
                if (! is_array($child)) {
                    continue;
                }

                $childUrls[] = rtrim((string) ($child['url'] ?? ''), '/') ?: '/';
            }

            if (in_array('/medya', $childUrls, true) && ! in_array('/etkinlikler', $childUrls, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{label: string, url: string, children?: list<array{label: string, url: string}>}|null
     */
    private static function normalizeNavItem(array $item): ?array
    {
        $label = trim((string) ($item['label'] ?? ''));
        $url = static::remapNavUrl((string) ($item['url'] ?? ''));
        $children = [];

        foreach ($item['children'] ?? [] as $child) {
            if (! is_array($child)) {
                continue;
            }

            $normalized = static::normalizeNavItem($child);

            if ($normalized !== null) {
                unset($normalized['children']);
                $children[] = $normalized;
            }
        }

        if ($label === '' || $label === 'Ana sayfa' || $url === '/') {
            return null;
        }

        $label = static::remapNavLabel($label, $url);

        if ($children === [] && ($url === '' || $url === '#')) {
            return null;
        }

        $normalized = [
            'label' => $label,
            'url' => $url !== '' ? $url : ($children[0]['url'] ?? '#'),
        ];

        if ($children !== []) {
            $normalized['children'] = $children;
        }

        return $normalized;
    }

    private static function remapNavUrl(string $url): string
    {
        $url = rtrim($url, '/') ?: '';

        return match ($url) {
            '/canli', '/sosyal', '/seckiler' => '/vitrin',
            default => $url,
        };
    }

    private static function remapNavLabel(string $label, string $url): string
    {
        if ($url === '/vitrin' && in_array($label, ['Canlı', 'Sosyal', 'Seçkiler', ''], true)) {
            return 'Vitrin';
        }

        return $label;
    }

    public static function put(string $key, mixed $value): void
    {
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        if (in_array($key, static::SECRET_KEYS, true)) {
            $value = filled($value) ? Crypt::encryptString((string) $value) : '';
        }

        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );

        Cache::forget('site_settings');
    }

    /**
     * Şifreli saklanan ayarı çözer; boş veya bozuk kayıtta boş metin döner.
     */
    public static function secret(string $key): string
    {
        $stored = Setting::query()->where('key', $key)->value('value');

        if (! is_string($stored) || $stored === '') {
            return '';
        }

        try {
            return Crypt::decryptString($stored);
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * İletişim telefonundan WhatsApp sohbet bağlantısı; boş veya geçersizse null.
     */
    public static function whatsappChatUrl(): ?string
    {
        return static::whatsappMeUrl((string) static::get('phone', ''));
    }

    /**
     * Yazılan numarayı wa.me bağlantısına çevirir.
     */
    public static function whatsappMeUrl(string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = '90'.substr($digits, 1);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            $digits = '90'.$digits;
        }

        if (strlen($digits) < 10) {
            return null;
        }

        return 'https://wa.me/'.$digits;
    }

    public static function logoUrl(): string
    {
        return static::imageUrl('logo') ?? asset('images/logo-mark.png');
    }

    /**
     * Harita gömme kodundan yalnızca Google Maps iframe'ini bırakır.
     */
    public static function safeMapEmbed(): string
    {
        $html = (string) static::get('map_embed');

        if ($html === '' || preg_match('/<iframe\b[^>]*\bsrc=["\']([^"\']+)["\'][^>]*>/i', $html, $matches) !== 1) {
            return '';
        }

        $src = html_entity_decode($matches[1], ENT_QUOTES);
        $host = parse_url($src, PHP_URL_HOST);

        if (! is_string($host)) {
            return '';
        }

        $allowed = [
            'www.google.com',
            'maps.google.com',
            'www.google.com.tr',
            'maps.googleapis.com',
        ];

        if (! in_array(strtolower($host), $allowed, true)) {
            return '';
        }

        return '<iframe src="'.e($src).'" width="100%" height="420" style="border:0;" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Dernek konumu" allowfullscreen></iframe>';
    }

    public static function faviconUrl(): string
    {
        return static::imageUrl('favicon') ?? asset('images/favicon.png');
    }

    /**
     * Panelden görsel yüklenmediğinde projeyle gelen varsayılan görseller kullanılır.
     */
    public static function heroImageUrl(): string
    {
        return static::imageUrl('hero_image') ?? asset('images/hero-default.jpg');
    }

    public static function aboutImageUrl(): string
    {
        return static::imageUrl('about_image') ?? asset('images/about-default.jpg');
    }

    /**
     * Panelden yüklenen görselin genel URL'ini döndürür; yüklenmemişse null.
     */
    public static function imageUrl(string $key): ?string
    {
        $path = static::get($key);

        if (! is_string($path) || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
