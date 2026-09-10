<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\ManageSettings;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_the_settings_page(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ManageSettings::class)->assertOk();
    }

    public function test_editors_cannot_open_the_settings_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $this->assertFalse(ManageSettings::canAccess());
    }

    public function test_saving_a_phone_number_does_not_replace_the_channel_url(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ManageSettings::class)
            ->set('data.whatsapp', 'https://whatsapp.com/channel/example')
            ->set('data.phone', '05426588530')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('https://whatsapp.com/channel/example', SiteSettings::get('whatsapp'));
        $this->assertSame('05426588530', SiteSettings::get('phone'));
        $this->assertSame('https://wa.me/905426588530', SiteSettings::whatsappChatUrl());
    }

    public function test_saving_stores_scalar_and_repeater_settings(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ManageSettings::class)
            ->set('data.hero_title', 'Yeni giriş başlığı')
            ->set('data.value_pillars', [
                ['icon' => 'book', 'title' => 'İlim', 'text' => 'Sağlam bilgi'],
                ['icon' => 'heart', 'title' => 'Kardeşlik', 'text' => 'Birlikte büyümek'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Yeni giriş başlığı', SiteSettings::get('hero_title'));
        $this->assertSame(
            [
                ['icon' => 'book', 'title' => 'İlim', 'text' => 'Sağlam bilgi'],
                ['icon' => 'heart', 'title' => 'Kardeşlik', 'text' => 'Birlikte büyümek'],
            ],
            SiteSettings::list('value_pillars'),
        );
    }

    public function test_saved_settings_appear_on_the_public_site(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ManageSettings::class)
            ->set('data.hero_title', 'Panelden yazılan başlık')
            ->set('data.nav_items', [['label' => 'Panel menüsü', 'url' => '/panel-menusu']])
            ->call('save')
            ->assertHasNoErrors();

        auth()->logout();

        $this->get('/')
            ->assertOk()
            ->assertSee('Panelden yazılan başlık')
            ->assertSee('Panel menüsü');
    }

    public function test_every_site_setting_is_editable_in_the_panel(): void
    {
        $this->actingAs($this->superAdmin());

        $page = Livewire::test(ManageSettings::class)->instance();
        $fields = array_keys($page->form->getFlatFields(withHidden: true));

        $missing = array_values(array_diff(array_keys(SiteSettings::defaults()), $fields));

        $this->assertSame([], $missing, 'Panelde karşılığı olmayan ayarlar: '.implode(', ', $missing));
    }

    public function test_required_settings_are_validated(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ManageSettings::class)
            ->set('data.site_name', '')
            ->call('save')
            ->assertHasErrors('data.site_name');
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => UserRole::SuperAdmin]);
    }
}
