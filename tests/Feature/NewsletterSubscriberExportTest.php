<?php

namespace Tests\Feature;

use App\Actions\ExportNewsletterSubscribers;
use App\Enums\UserRole;
use App\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use App\Filament\Resources\NewsletterSubscribers\Pages\ListNewsletterSubscribers;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Support\SiteSettings;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class NewsletterSubscriberExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_excel_export_puts_each_field_in_its_own_cell(): void
    {
        $this->travelTo('2026-09-10 12:24:00');

        NewsletterSubscriber::query()->create([
            'email' => 'burakgul3085@gmail.com',
            'confirmed_at' => '2026-09-09 08:16:37',
        ]);

        $workbook = app(ExportNewsletterSubscribers::class)->handle();
        $sheet = $this->xlsxPart($workbook['contents'], 'xl/worksheets/sheet1.xml');
        $styles = $this->xlsxPart($workbook['contents'], 'xl/styles.xml');

        $this->assertSame('Hacer-E-Bulten-Aboneler-2026-09-10.xlsx', $workbook['filename']);
        $this->assertStringContainsString('E-bülten abone listesi', $sheet);
        $this->assertStringContainsString('burakgul3085@gmail.com', $sheet);
        $this->assertStringContainsString('09.09.2026 08:16', $sheet);
        $this->assertStringContainsString('r="B10"', $sheet);
        $this->assertStringContainsString('r="C10"', $sheet);
        $this->assertStringContainsString('r="D10"', $sheet);
        $this->assertStringNotContainsString('email,confirmed_at,created_at', $sheet);
        $this->assertStringNotContainsString('burakgul3085@gmail.com;"2026-09-09', $sheet);
        $this->assertStringContainsString('wrapText="0"', $styles);
        $this->assertStringContainsString('width="44"', $sheet);
        $this->assertStringNotContainsString('state="frozen"', $sheet);
        $this->assertSame(1, substr_count($sheet, 'E-bülten abone listesi'));
    }

    public function test_excel_export_embeds_the_association_logo_and_identity(): void
    {
        SiteSettings::put('site_name', 'Hâcer İlim ve Kültür Derneği');
        SiteSettings::put('tagline', 'Gaziantep’te ilim, sohbet ve kültür');
        SiteSettings::put('address', 'Şehitkamil / Gaziantep');

        NewsletterSubscriber::query()->create([
            'email' => 'uye@example.com',
            'confirmed_at' => now(),
        ]);

        $workbook = app(ExportNewsletterSubscribers::class)->handle();
        $sheet = $this->xlsxPart($workbook['contents'], 'xl/worksheets/sheet1.xml');
        $files = $this->zipFiles($workbook['contents']);

        $this->assertStringContainsString('Hâcer İlim ve Kültür Derneği', $sheet);
        $this->assertStringContainsString('Şehitkamil / Gaziantep', $sheet);
        $this->assertContains('xl/media/logo.png', $files);
        $this->assertContains('xl/drawings/drawing1.xml', $files);
    }

    public function test_excel_export_escapes_site_content_in_the_workbook_xml(): void
    {
        SiteSettings::put('site_name', "Hâcer <script>alert('xss')</script>");

        $workbook = app(ExportNewsletterSubscribers::class)->handle();
        $sheet = $this->xlsxPart($workbook['contents'], 'xl/worksheets/sheet1.xml');

        $this->assertStringContainsString('&lt;script&gt;', $sheet);
        $this->assertStringNotContainsString("<script>alert('xss')</script>", $sheet);
    }

    public function test_editors_can_see_the_excel_export_action(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        NewsletterSubscriber::query()->create([
            'email' => 'uye@example.com',
            'confirmed_at' => now(),
        ]);

        Livewire::test(ListNewsletterSubscribers::class)
            ->assertOk()
            ->assertActionVisible(TestAction::make('export')->table());
    }

    public function test_media_managers_cannot_access_newsletter_subscribers(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Media]));

        $this->assertFalse(NewsletterSubscriberResource::canAccess());
    }

    /**
     * @return list<string>
     */
    private function zipFiles(string $contents): array
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $contents);

        $zip = new ZipArchive;
        $zip->open($path);

        $files = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $files[] = $zip->getNameIndex($i);
        }

        $zip->close();
        @unlink($path);

        return $files;
    }

    private function xlsxPart(string $contents, string $name): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $contents);

        $zip = new ZipArchive;
        $zip->open($path);
        $xml = $zip->getFromName($name);
        $zip->close();
        @unlink($path);

        $this->assertNotFalse($xml);

        return $xml;
    }
}
