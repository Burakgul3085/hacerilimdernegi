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
            'site_name' => 'Hacer İlim ve Kültür Derneği',
            'tagline' => 'İlim, sohbet ve kültürle gaziantepli bir yapılanma',
            'about_excerpt' => 'Gaziantep merkezli derneğimiz; dersler, sohbetler ve kitap tahlilleri ile ilim ve kültür etrafında bir araya gelir.',
            'address' => 'Gaziantep, Türkiye',
            'phone' => '',
            'email' => 'info@hacerilim.org',
            'map_embed' => '',
            'facebook' => '',
            'instagram' => '',
            'youtube' => '',
            'live_youtube_url' => '',
            'live_instagram_url' => '',
            'live_is_active' => '0',
            'iban' => '',
            'bank_account_name' => 'Hacer İlim ve Kültür Derneği',
            'bank_name' => '',
            'donation_note' => 'Bağışlarınızı dernek IBAN hesabına EFT/havale ile iletebilirsiniz. Açıklama kısmına adınızı yazmanız yeterlidir. Online kart ödemesi alınmamaktadır.',
            'logo' => '',
            'favicon' => '',
            'color_primary' => '#143D2C',
            'color_gold' => '#C4A35A',
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

        return asset('images/logo.svg');
    }
}
