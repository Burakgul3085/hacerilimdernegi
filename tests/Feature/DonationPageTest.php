<?php

namespace Tests\Feature;

use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_the_account_card_with_a_copy_control(): void
    {
        $this->get('/bagis')
            ->assertOk()
            ->assertSee('Resmî hesap bilgisi')
            ->assertSee('Nasıl bağış yapılır')
            ->assertSee('Online kart ödemesi yoktur')
            ->assertSee('Kopyala')
            ->assertSee('data-iban="DEMO — GERÇEK IBAN BEKLENİYOR"', false)
            ->assertSee('x-data="{ copied: false }"', false);
    }

    public function test_formats_a_live_iban_into_grouped_blocks(): void
    {
        SiteSettings::put('bank_details_are_demo', '0');
        SiteSettings::put('iban', 'TR330006100519786457841326');
        SiteSettings::put('bank_branch', 'Şehitkamil Şubesi');
        SiteSettings::put('donation_reference', 'Açıklamaya ad soyad yazınız');

        $this->get('/bagis')
            ->assertOk()
            ->assertDontSee('Demo banka bilgisi')
            ->assertSee('TR33 0006 1005 1978 6457 8413 26')
            ->assertSee('data-iban="TR330006100519786457841326"', false)
            ->assertSee('Şehitkamil Şubesi')
            ->assertSee('Açıklamaya ad soyad yazınız');
    }

    public function test_shows_donation_purposes_from_settings(): void
    {
        SiteSettings::put('donation_purposes_title', 'Katkınızın yönü');
        SiteSettings::put('donation_purposes', json_encode([
            ['icon' => 'book', 'title' => 'Panel ilim kalemi', 'text' => 'Panel ilim açıklaması'],
        ], JSON_UNESCAPED_UNICODE));

        $this->get('/bagis')
            ->assertOk()
            ->assertSee('Katkınızın yönü')
            ->assertSee('Panel ilim kalemi')
            ->assertSee('Panel ilim açıklaması');
    }

    public function test_hides_the_purpose_section_when_the_list_is_empty(): void
    {
        SiteSettings::put('donation_purposes_title', 'Katkınızın yönü');
        SiteSettings::put('donation_purposes', json_encode([], JSON_UNESCAPED_UNICODE));

        $this->get('/bagis')
            ->assertOk()
            ->assertDontSee('Katkınızın yönü');
    }

    public function test_escapes_donation_copy_that_comes_from_settings(): void
    {
        SiteSettings::put('bank_account_name', '<script>alert("xss")</script>');
        SiteSettings::put('donation_note', '<img src=x onerror=alert(1)>');
        SiteSettings::put('donation_purposes', json_encode([
            ['icon' => 'book', 'title' => '<b>İlim</b>', 'text' => '<script>alert(1)</script>'],
        ], JSON_UNESCAPED_UNICODE));

        $this->get('/bagis')
            ->assertOk()
            ->assertDontSee('<script>alert("xss")</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertDontSee('<b>İlim</b>', false)
            ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false);
    }

    public function test_offers_whatsapp_when_a_phone_number_is_saved(): void
    {
        SiteSettings::put('phone', '05426588530');

        $this->get('/bagis')
            ->assertOk()
            ->assertSee('https://wa.me/905426588530', false);
    }
}
