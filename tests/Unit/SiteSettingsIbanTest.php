<?php

namespace Tests\Unit;

use App\Support\SiteSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SiteSettingsIbanTest extends TestCase
{
    #[DataProvider('displayValues')]
    public function test_groups_a_valid_iban_and_leaves_placeholder_text(string $iban, string $display): void
    {
        $this->assertSame($display, SiteSettings::formattedIban($iban));
    }

    #[DataProvider('copyValues')]
    public function test_copies_compact_iban_digits_or_the_original_placeholder(string $iban, string $copy): void
    {
        $this->assertSame($copy, SiteSettings::ibanCopyValue($iban));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function displayValues(): array
    {
        return [
            'turkish iban' => ['TR330006100519786457841326', 'TR33 0006 1005 1978 6457 8413 26'],
            'already grouped' => ['TR33 0006 1005 1978 6457 8413 26', 'TR33 0006 1005 1978 6457 8413 26'],
            'demo label' => ['DEMO — GERÇEK IBAN BEKLENİYOR', 'DEMO — GERÇEK IBAN BEKLENİYOR'],
            'empty' => ['', ''],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function copyValues(): array
    {
        return [
            'turkish iban' => ['TR33 0006 1005 1978 6457 8413 26', 'TR330006100519786457841326'],
            'demo label' => ['DEMO — GERÇEK IBAN BEKLENİYOR', 'DEMO — GERÇEK IBAN BEKLENİYOR'],
            'empty' => ['', ''],
        ];
    }
}
