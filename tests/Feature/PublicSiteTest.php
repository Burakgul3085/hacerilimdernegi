<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Hâcer')
            ->assertSee('data-page-veil', false)
            ->assertSee('info@hacerilimvekulturdernegi.org')
            ->assertSee('https://x.com/hacerilimkultur', false);
    }

    public function test_app_layout_composer_provides_logo_and_settings(): void
    {
        $view = view('layouts.app');
        app('view')->callComposer($view);

        $this->assertNotEmpty($view['logoUrl']);
        $this->assertIsArray($view['settings']);
    }

    public function test_seeded_admin_password_is_verified_without_double_hashing(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'info@hacerilimvekulturdernegi.org')->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_unknown_path_renders_branded_not_found_page(): void
    {
        $this->get('/olmayan-sayfa')
            ->assertNotFound()
            ->assertSee('Aradığınız sayfa bulunamadı')
            ->assertSee('Ana sayfaya dön')
            ->assertSee('data-page-veil', false)
            ->assertSee(route('home'), false);
    }

    public function test_donation_page_marks_placeholder_bank_details_as_demo(): void
    {
        $this->get('/bagis')
            ->assertOk()
            ->assertSee('Demo banka bilgisi')
            ->assertSee('ödeme yapmayınız')
            ->assertSee('DEMO — GERÇEK IBAN BEKLENİYOR');
    }

    public function test_contact_requires_kvkk(): void
    {
        $this->post('/iletisim', [
            'name' => 'Ayşe',
            'email' => 'ayse@example.com',
            'message' => 'Merhaba',
        ])->assertSessionHasErrors('kvkk_accepted');
    }

    public function test_contact_stores_message(): void
    {
        $this->post('/iletisim', [
            'name' => 'Ayşe',
            'email' => 'ayse@example.com',
            'message' => 'Merhaba',
            'kvkk_accepted' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'ayse@example.com',
        ]);
    }

    public function test_event_registration_is_rate_limited_path(): void
    {
        $event = Event::query()->create([
            'title' => 'Test etkinlik',
            'slug' => 'test-etkinlik',
            'registration_open' => true,
            'is_published' => true,
        ]);

        $this->post(route('events.register', $event), [
            'name' => 'Ali',
            'email' => 'ali@example.com',
            'kvkk_accepted' => '1',
        ])->assertRedirect();
    }
}
