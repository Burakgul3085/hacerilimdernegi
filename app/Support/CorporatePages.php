<?php

namespace App\Support;

/**
 * Kurumsal statik sayfalar: güzel URL, panel içeriği ve yedek metin.
 */
final class CorporatePages
{
    public const BYLAWS_SLUG = 'dernek-tuzugu';

    /**
     * @return array<string, array{title: string, excerpt: string, body: string}>
     */
    public static function definitions(): array
    {
        return [
            'hakkimizda' => [
                'title' => 'Hakkımızda',
                'excerpt' => 'Gaziantep Şehitkamil’de faaliyet gösteren Hâcer İlim ve Kültür Derneği; Kur’an ve sünnet ışığında ilim, kültür ve kardeşlik çalışmalarını sürdürür.',
                'body' => '<p>Hâcer İlim ve Kültür Derneği, 2017’den bu yana Gaziantep’te ilim ve kültür faaliyetleri yürüten bağımsız bir topluluktur.</p><p>Gayemiz; Kur’an-ı Kerim’i ve hadis-i şerifleri daha iyi anlayıp hayatımıza geçirmek, ilim ve kardeşlik etrafında faydalı çalışmalar yapmaktır.</p><h2>Faaliyetlerimiz</h2><p>Kur’an-ı Kerim ve hadis dersleri, ilmihâl dersleri, lise gençlik ve çocuk çalışmaları, seminerler, kitap tahlilleri ve kamplar düzenlenir. Programların güncel tarih ve kapsamı etkinlik takviminde duyurulur.</p><p>Adres: Karacaahmet, 38012 Nolu Cadde No: 36A, Bina 111 Kat 1 Daire 1, 27590 Şehitkamil / Gaziantep.</p>',
            ],
            'vizyon-misyon' => [
                'title' => 'Vizyon ve misyon',
                'excerpt' => 'Derneğin yönü, gayesi ve çalışma ilkeleri.',
                'body' => '<p>Bu sayfanın içeriği yönetim panelinden eklenecektir.</p>',
            ],
            'baskanin-mesaji' => [
                'title' => 'Başkanın mesajı',
                'excerpt' => 'Dernek başkanının ziyaretçilere mesajı.',
                'body' => '<p>Bu sayfanın içeriği yönetim panelinden eklenecektir.</p>',
            ],
            'yonetim-kadrosu' => [
                'title' => 'Yönetim kadrosu',
                'excerpt' => 'Dernek yönetiminde görev alan isimler.',
                'body' => '<p>Bu sayfanın içeriği yönetim panelinden eklenecektir.</p>',
            ],
            self::BYLAWS_SLUG => [
                'title' => 'Dernek tüzüğü',
                'excerpt' => 'Hâcer İlim ve Kültür Derneği tüzüğü.',
                'body' => '<p>Bu sayfanın içeriği yönetim panelinden eklenecektir.</p>',
            ],
        ];
    }

    public static function has(string $slug): bool
    {
        return array_key_exists($slug, self::definitions());
    }

    public static function isBylaws(string $slug): bool
    {
        return $slug === self::BYLAWS_SLUG;
    }

    /**
     * Hakkımızda dışındaki kurumsal sayfaların güzel yolları.
     *
     * @return array<string, string>
     */
    public static function prettyRoutes(): array
    {
        return [
            'vizyon-misyon' => 'corporate.vision',
            'baskanin-mesaji' => 'corporate.message',
            'yonetim-kadrosu' => 'corporate.board',
            self::BYLAWS_SLUG => 'corporate.bylaws',
        ];
    }

    public static function routeName(string $slug): ?string
    {
        if ($slug === 'hakkimizda') {
            return 'about';
        }

        return self::prettyRoutes()[$slug] ?? null;
    }
}
