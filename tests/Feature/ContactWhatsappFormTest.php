<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactWhatsappFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_shows_the_whatsapp_form_when_a_phone_number_is_saved(): void
    {
        SiteSettings::put('phone', '05426588530');

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('WhatsApp ile yazın')
            ->assertSee('WhatsApp’ta aç')
            ->assertSee(route('contact.whatsapp', absolute: false), false);
    }

    public function test_contact_page_hides_the_whatsapp_form_when_no_phone_number_is_set(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('E-posta ile yazın')
            ->assertDontSee('WhatsApp ile yazın')
            ->assertDontSee(route('contact.whatsapp', absolute: false), false);
    }

    public function test_whatsapp_form_opens_a_chat_with_the_persons_message(): void
    {
        SiteSettings::put('phone', '05426588530');

        $response = $this->from(route('contact'))
            ->post(route('contact.whatsapp'), [
                'wa_name' => 'Ayşe Yılmaz',
                'wa_phone' => '0532 111 22 33',
                'wa_message' => 'Programlar hakkında bilgi almak istiyorum.',
                'kvkk_accepted' => '1',
            ]);

        $response->assertRedirect();

        $location = (string) $response->headers->get('Location');
        $this->assertStringStartsWith('https://wa.me/905426588530?text=', $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $text = $query['text'] ?? '';

        $this->assertStringContainsString('Esselamu aleyküm', $text);
        $this->assertStringContainsString('Ayşe Yılmaz', $text);
        $this->assertStringContainsString('0532 111 22 33', $text);
        $this->assertStringContainsString('Programlar hakkında bilgi almak istiyorum.', $text);
        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_whatsapp_form_requires_kvkk_consent(): void
    {
        SiteSettings::put('phone', '05426588530');

        $this->from(route('contact'))
            ->post(route('contact.whatsapp'), [
                'wa_name' => 'Ayşe',
                'wa_message' => 'Merhaba',
            ])
            ->assertRedirect(route('contact'))
            ->assertSessionHasErrors('kvkk_accepted');
    }

    public function test_whatsapp_honeypot_does_not_open_a_chat(): void
    {
        SiteSettings::put('phone', '05426588530');

        $this->from(route('contact'))
            ->post(route('contact.whatsapp'), [
                'wa_name' => 'Bot',
                'wa_message' => 'spam',
                'kvkk_accepted' => '1',
                'website' => 'https://spam.example',
            ])
            ->assertRedirect(route('contact'));
    }

    public function test_whatsapp_form_returns_to_contact_when_no_phone_number_is_saved(): void
    {
        $this->from(route('contact'))
            ->post(route('contact.whatsapp'), [
                'wa_name' => 'Ayşe',
                'wa_message' => 'Merhaba',
                'kvkk_accepted' => '1',
            ])
            ->assertRedirect(route('contact'))
            ->assertSessionHasErrors('wa_message');
    }
}
