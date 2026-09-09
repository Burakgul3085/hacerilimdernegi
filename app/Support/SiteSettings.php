<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SiteSettings
{
    /**
     * Ayar anahtarlarından hangileri JSON listesi tutar.
     *
     * @var list<string>
     */
    public const LIST_KEYS = ['nav_items', 'value_pillars', 'stats'];

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
            'map_embed' => '<iframe src="https://maps.google.com/maps?q=Karacaahmet%2038012%20Nolu%20Cadde%2036A%20%C5%9Eehitkamil%20Gaziantep&t=&z=16&ie=UTF8&iwloc=&output=embed" width="100%" height="280" style="border:0;" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Dernek konumu"></iframe>',
            'facebook' => '',
            'instagram' => '',
            'youtube' => '',
            'telegram' => 'https://t.me/+A1atig-4aOYyYjE8',
            'whatsapp' => 'https://whatsapp.com/channel/0029VbBGQs30AgW5W0OZ4F3y',
            'twitter' => 'https://x.com/hacerilimkultur',
            'live_youtube_url' => '',
            'live_instagram_url' => '',
            'live_is_active' => '0',
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

            'nav_items' => json_encode([
                ['label' => 'Hakkımızda', 'url' => '/hakkimizda'],
                ['label' => 'Programlar', 'url' => '/programlar'],
                ['label' => 'Etkinlikler', 'url' => '/etkinlikler'],
                ['label' => 'Yazılar', 'url' => '/yazilar'],
                ['label' => 'Medya', 'url' => '/medya'],
                ['label' => 'Canlı', 'url' => '/canli'],
                ['label' => 'Üyelik', 'url' => '/uyelik'],
            ], JSON_UNESCAPED_UNICODE),
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

            'about_image' => '',
            'about_quote' => 'İlim, hayatı güzelleştirir; insanı, toplumu ve yarınları inşa eder.',

            'programs_intro' => 'Dersler, sohbetler ve kitap tahlilleri.',
            'events_intro' => 'Aylara göre yaklaşan programlar. Katılım başvurusu etkinlik detayındadır.',
            'posts_intro' => 'Dernek gündemine dair yazılar ve resmî duyurular.',
            'media_intro' => 'Program ve etkinliklerimizden fotoğraf, video ve ses kayıtları.',
            'membership_intro' => 'Dernek çalışmalarına katılmak için formu doldurun.',
            'donate_intro' => 'Dernek faaliyetleri bağışlarınızla sürer.',
            'contact_intro' => 'Bizimle iletişime geçebilirsiniz.',
            'live_intro' => 'Ders ve sohbet yayınları bu sayfadan takip edilir.',

            'membership_card_title' => 'Birlikte daha güçlüyüz',
            'membership_card_text' => 'İlim, kültür ve kardeşlik çalışmalarında sen de yerini al.',

            'newsletter_title' => 'E-bülten',
            'newsletter_text' => 'Gelişmelerden haberdar olun.',
            'footer_note' => 'Kişisel veriler Almanya (Frankfurt) sunucusunda işlenir.',

            'developer_label' => 'Tasarım ve yazılım',
            'developer_name' => 'Burak Gül',
            'developer_url' => 'https://www.linkedin.com/in/burakgul100',
            'developer_email' => 'burakgul3085@gmail.com',
        ];
    }

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
     * @return list<array<string, string>>
     */
    public static function list(string $key): array
    {
        $decoded = json_decode((string) static::get($key), true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, 'is_array'));
    }

    public static function put(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_bool($value) ? ($value ? '1' : '0') : $value],
        );

        Cache::forget('site_settings');
    }

    public static function logoUrl(): string
    {
        return static::imageUrl('logo') ?? asset('images/logo-mark.png');
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
