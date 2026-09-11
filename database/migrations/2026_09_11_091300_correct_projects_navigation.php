<?php

use App\Support\SiteSettings;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SiteSettings::put(
            'nav_items',
            json_encode(SiteSettings::defaultNavItems(), JSON_UNESCAPED_UNICODE),
        );
    }

    public function down(): void
    {
        SiteSettings::put(
            'nav_items',
            json_encode([
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
                        ['label' => 'Medya', 'url' => '/medya'],
                    ],
                ],
                ['label' => 'Yazılar', 'url' => '/yazilar'],
                ['label' => 'Vitrin', 'url' => '/vitrin'],
                ['label' => 'Üyelik', 'url' => '/uyelik'],
            ], JSON_UNESCAPED_UNICODE),
        );
    }
};
