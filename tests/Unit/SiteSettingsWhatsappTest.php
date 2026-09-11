<?php

namespace Tests\Unit;

use App\Support\SiteSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SiteSettingsWhatsappTest extends TestCase
{
    #[DataProvider('chatNumbers')]
    public function test_builds_a_wa_me_link_from_local_and_international_numbers(string $number, string $url): void
    {
        $this->assertSame($url, SiteSettings::whatsappMeUrl($number));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function chatNumbers(): array
    {
        return [
            'spaced local' => ['0532 123 45 67', 'https://wa.me/905321234567'],
            'plus ninety' => ['+90 532 123 45 67', 'https://wa.me/905321234567'],
            'already international' => ['905321234567', 'https://wa.me/905321234567'],
            'ten digit mobile' => ['5321234567', 'https://wa.me/905321234567'],
            'zero zero prefix' => ['00905321234567', 'https://wa.me/905321234567'],
        ];
    }

    #[DataProvider('rejectedNumbers')]
    public function test_returns_null_when_the_number_cannot_open_a_chat(string $number): void
    {
        $this->assertNull(SiteSettings::whatsappMeUrl($number));
    }

    public function test_builds_a_corporate_whatsapp_message_with_the_senders_details(): void
    {
        $this->assertSame(
            "Esselamu aleyküm,\n\nHâcer İlim ve Kültür Derneği iletişim sayfasından yazıyorum.\n\nAdım: Ayşe Yılmaz\nTelefonum: 0532 111 22 33\n\nMesajım:\nProgramlar hakkında bilgi almak istiyorum.\n\nHayırlı çalışmalar dilerim.",
            SiteSettings::whatsappMessageText('Ayşe Yılmaz', 'Programlar hakkında bilgi almak istiyorum.', '0532 111 22 33'),
        );
    }

    public function test_omits_the_phone_line_when_the_sender_does_not_leave_a_number(): void
    {
        $text = SiteSettings::whatsappMessageText('Ayşe', 'Merhaba');

        $this->assertStringContainsString('Adım: Ayşe', $text);
        $this->assertStringContainsString('Merhaba', $text);
        $this->assertStringNotContainsString('Telefonum:', $text);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function rejectedNumbers(): array
    {
        return [
            'empty' => [''],
            'letters' => ['whatsapp'],
            'too short' => ['53212'],
        ];
    }
}
