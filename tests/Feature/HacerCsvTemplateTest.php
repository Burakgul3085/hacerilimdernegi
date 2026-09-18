<?php

namespace Tests\Feature;

use App\Support\HacerCsvTemplate;
use App\Support\HacerXlsxTemplate;
use Tests\TestCase;

class HacerCsvTemplateTest extends TestCase
{
    public function test_write_includes_brand_block_headers_and_footer(): void
    {
        $handle = fopen('php://memory', 'r+');
        $this->assertNotFalse($handle);

        HacerCsvTemplate::write(
            $handle,
            ['Ad soyad', 'Telefon'],
            [['Ayşe Yılmaz', HacerCsvTemplate::formatCell('phone', 'Telefon', '905551112233')]],
            'Deneme etkinlik',
            '1 satır',
        );

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        $this->assertStringStartsWith(HacerCsvTemplate::BOM, $csv);
        $this->assertStringContainsString(HacerXlsxTemplate::ORGANIZATION, $csv);
        $this->assertStringContainsString(HacerXlsxTemplate::DOCUMENT_KIND, $csv);
        $this->assertStringContainsString('Deneme etkinlik', $csv);
        $this->assertStringContainsString('1 satır', $csv);
        $this->assertStringContainsString('Ad soyad', $csv);
        $this->assertStringContainsString('Telefon', $csv);
        $this->assertStringContainsString('Ayşe Yılmaz', $csv);
        $this->assertStringContainsString('=""905551112233""', $csv);
        $this->assertStringContainsString(HacerXlsxTemplate::footerNote(), $csv);
    }

    public function test_phone_and_long_numbers_are_forced_as_plain_text(): void
    {
        $phone = HacerCsvTemplate::formatCell('phone', 'Telefon', '905551112233');
        $sayi = HacerCsvTemplate::formatCell('answer_sayi', 'Sayı', '4234567890');

        $this->assertSame('="905551112233"', $phone);
        $this->assertSame('="4234567890"', $sayi);
        $this->assertTrue(HacerCsvTemplate::shouldForcePlainText('phone', 'Telefon', '905551112233'));
    }

    public function test_iso_dates_are_formatted_for_turkish_display(): void
    {
        $formatted = HacerCsvTemplate::formatCell('submitted_at', 'Başvuru tarihi', '2026-09-18 07:49:00');

        $this->assertSame('="18.09.2026 07:49"', $formatted);
    }

    public function test_turkish_dates_are_not_reparsed(): void
    {
        $this->assertSame(
            '18.09.2026 08:28',
            HacerCsvTemplate::formatDateTime('18.09.2026 08:28'),
        );
    }
}
