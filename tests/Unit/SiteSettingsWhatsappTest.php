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
