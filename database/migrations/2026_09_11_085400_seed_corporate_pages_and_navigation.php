<?php

use App\Models\Page;
use App\Support\CorporatePages;
use App\Support\SiteSettings;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (CorporatePages::definitions() as $slug => $page) {
            Page::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => $page['title'],
                    'excerpt' => $page['excerpt'],
                    'body' => $page['body'],
                    'is_published' => true,
                ],
            );
        }

        SiteSettings::put(
            'nav_items',
            json_encode(SiteSettings::defaultNavItems(), JSON_UNESCAPED_UNICODE),
        );
    }

    public function down(): void
    {
        foreach (array_keys(CorporatePages::prettyRoutes()) as $slug) {
            Page::query()->where('slug', $slug)->delete();
        }

        SiteSettings::put(
            'nav_items',
            json_encode([
                ['label' => 'Hakkımızda', 'url' => '/hakkimizda'],
                ['label' => 'Programlar', 'url' => '/programlar'],
                ['label' => 'Etkinlikler', 'url' => '/etkinlikler'],
                ['label' => 'Yazılar', 'url' => '/yazilar'],
                ['label' => 'Medya', 'url' => '/medya'],
                ['label' => 'Seçkiler', 'url' => '/seckiler'],
                ['label' => 'Üyelik', 'url' => '/uyelik'],
            ], JSON_UNESCAPED_UNICODE),
        );
    }
};
