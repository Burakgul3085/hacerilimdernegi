<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SiteSettings
{
    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'site_name' => 'Hâcer İlim ve Kültür Derneği',
            'tagline' => 'Gaziantep’te ilim, sohbet ve kültür',
            'about_excerpt' => 'Hâcer İlim ve Kültür Derneği, Gaziantep’te dersler, sohbetler ve kitap tahlilleri etrafında bir ilim muhiti kurar.',
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
        $logo = static::get('logo');

        if (is_string($logo) && $logo !== '') {
            return Storage::disk('public')->url($logo);
        }

        return asset('images/logo-mark.png');
    }
}
