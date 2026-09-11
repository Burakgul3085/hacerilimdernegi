<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\ManageSettings;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SocialPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_page_renders_admin_instagram_links_as_cards(): void
    {
        SiteSettings::put('instagram_posts', json_encode([
            ['url' => 'https://www.instagram.com/p/AbC123xyz/?igsh=token'],
            ['url' => 'https://www.instagram.com/reel/ReelsCode99/'],
        ], JSON_UNESCAPED_UNICODE));

        $this->get('/vitrin')
            ->assertOk()
            ->assertSee('Vitrin')
            ->assertSee('Arşiv')
            ->assertSee('Resmî hesap')
            ->assertSee('Seçilen paylaşımlar')
            ->assertSee('2 paylaşım')
            ->assertSee('Instagram hesabından seçilen kareler ve kısa videolar, sitede vitrin olarak durur.')
            ->assertSee('https://www.instagram.com/p/AbC123xyz/embed/', false)
            ->assertSee('https://www.instagram.com/p/AbC123xyz/embed/captioned/', false)
            ->assertSee('https://www.instagram.com/reel/ReelsCode99/', false)
            ->assertDontSee('igsh=token');
    }

    public function test_social_page_rejects_non_instagram_links_from_the_vitrine(): void
    {
        SiteSettings::put('instagram_posts', json_encode([
            ['url' => 'javascript:alert(1)'],
            ['url' => 'https://evil.example/p/AbC123xyz'],
        ], JSON_UNESCAPED_UNICODE));

        $this->get('/vitrin')
            ->assertOk()
            ->assertDontSee('javascript:', false)
            ->assertDontSee('evil.example', false)
            ->assertSee('Vitrin henüz oluşmadı');
    }

    public function test_settings_save_stores_instagram_post_links(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        Livewire::test(ManageSettings::class)
            ->set('data.instagram_posts', [
                ['url' => 'https://www.instagram.com/p/PanelPost99/'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(
            [['url' => 'https://www.instagram.com/p/PanelPost99/']],
            SiteSettings::list('instagram_posts'),
        );
    }

    public function test_vitrine_places_the_official_account_beside_the_title(): void
    {
        $this->get('/vitrin')
            ->assertOk()
            ->assertSee('Resmî hesap')
            ->assertSee('@hacerilimkultur')
            ->assertSee('https://www.instagram.com/hacerilimkultur', false)
            ->assertSee('vitrine-account', false)
            ->assertDontSee('Kurum arşivi');
    }

    public function test_legacy_live_social_and_seckiler_urls_redirect_to_the_vitrine(): void
    {
        $this->get('/canli')->assertRedirect('/vitrin');
        $this->get('/sosyal')->assertRedirect('/vitrin');
        $this->get('/seckiler')->assertRedirect('/vitrin');
    }

    public function test_vitrine_page_allows_instagram_embeds_in_the_content_security_policy(): void
    {
        $csp = (string) $this->get('/vitrin')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('https://www.instagram.com', $csp);
    }
}
