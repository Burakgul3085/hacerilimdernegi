<?php

namespace Tests\Feature;

use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_groups_corporate_and_project_links_and_renames_the_vitrine(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Kurumsal')
            ->assertSee('Vizyon ve misyon')
            ->assertSee('/vizyon-misyon', false)
            ->assertSee('Başkanın mesajı')
            ->assertSee('Yönetim kadrosu')
            ->assertSee('Dernek tüzüğü')
            ->assertSee('Projeler')
            ->assertSee('Programlar')
            ->assertSee('Etkinlikler')
            ->assertSee('/etkinlikler', false)
            ->assertSee('Medya')
            ->assertSee('/medya', false)
            ->assertSee('Vitrin')
            ->assertSee('/vitrin', false)
            ->assertDontSee('>Seçkiler<', false);
    }

    public function test_stored_flat_menu_is_replaced_with_the_grouped_tree(): void
    {
        SiteSettings::put('nav_items', json_encode([
            ['label' => 'Hakkımızda', 'url' => '/hakkimizda'],
            ['label' => 'Programlar', 'url' => '/programlar'],
            ['label' => 'Etkinlikler', 'url' => '/etkinlikler'],
            ['label' => 'Yazılar', 'url' => '/yazilar'],
            ['label' => 'Medya', 'url' => '/medya'],
            ['label' => 'Seçkiler', 'url' => '/seckiler'],
            ['label' => 'Üyelik', 'url' => '/uyelik'],
        ], JSON_UNESCAPED_UNICODE));

        $this->get('/')
            ->assertOk()
            ->assertSee('Kurumsal')
            ->assertSee('Projeler')
            ->assertSee('Etkinlikler')
            ->assertSee('/etkinlikler', false)
            ->assertSee('Medya')
            ->assertSee('Vitrin')
            ->assertSee('/vitrin', false)
            ->assertDontSee('/seckiler', false);
    }

    public function test_projects_menu_with_media_child_is_corrected_to_events(): void
    {
        SiteSettings::put('nav_items', json_encode([
            [
                'label' => 'Kurumsal',
                'url' => '/hakkimizda',
                'children' => [
                    ['label' => 'Hakkımızda', 'url' => '/hakkimizda'],
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
        ], JSON_UNESCAPED_UNICODE));

        $this->get('/')
            ->assertOk()
            ->assertSee('Etkinlikler')
            ->assertSee('/etkinlikler', false)
            ->assertSeeInOrder(['Yazılar', 'Medya', 'Vitrin']);
    }
}
