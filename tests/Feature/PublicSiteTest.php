<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders(): void
    {
        $this->get('/')->assertOk()->assertSee('Hacer İlim');
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
