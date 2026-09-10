<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFormSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_sends_security_headers(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-Permitted-Cross-Domain-Policies', 'none')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');

        $this->assertNotEmpty($this->get('/')->headers->get('Content-Security-Policy'));
    }

    public function test_contact_honeypot_does_not_store_a_message(): void
    {
        $this->from(route('contact'))
            ->post('/iletisim', [
                'name' => 'Bot',
                'email' => 'bot@example.com',
                'message' => 'spam',
                'kvkk_accepted' => '1',
                'website' => 'https://spam.example',
            ])
            ->assertRedirect(route('contact'));

        $this->assertDatabaseMissing('contact_messages', [
            'email' => 'bot@example.com',
        ]);
    }

    public function test_newsletter_honeypot_does_not_store_a_subscriber(): void
    {
        $this->post('/bulten', [
            'email' => 'bot@example.com',
            'website' => 'https://spam.example',
        ])->assertRedirect();

        $this->assertDatabaseMissing('newsletter_subscribers', [
            'email' => 'bot@example.com',
        ]);
    }

    public function test_contact_form_is_rate_limited_after_five_attempts(): void
    {
        $payload = [
            'name' => 'Ayşe',
            'email' => 'ayse-limit@example.com',
            'message' => 'Merhaba',
            'kvkk_accepted' => '1',
        ];

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.40']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/iletisim', $payload)->assertRedirect();
        }

        $this->post('/iletisim', $payload)->assertStatus(429);
        $this->assertSame(5, ContactMessage::query()->where('email', 'ayse-limit@example.com')->count());
    }

    public function test_unsafe_map_embed_is_stripped(): void
    {
        SiteSettings::put('map_embed', '<iframe src="https://evil.example/x"></iframe><script>alert(1)</script>');

        $this->assertSame('', SiteSettings::safeMapEmbed());
    }

    public function test_google_maps_embed_is_kept(): void
    {
        SiteSettings::put(
            'map_embed',
            '<iframe src="https://www.google.com/maps?q=Gaziantep&output=embed"></iframe>',
        );

        $this->assertStringContainsString('www.google.com/maps', SiteSettings::safeMapEmbed());
        $this->assertStringNotContainsString('<script', SiteSettings::safeMapEmbed());
    }
}
