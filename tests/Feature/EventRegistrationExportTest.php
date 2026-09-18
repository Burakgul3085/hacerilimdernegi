<?php

namespace Tests\Feature;

use App\Actions\ExportEventRegistrations;
use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Resources\EventRegistrations\Pages\ListActivityRegistrations;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\RegistrationForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class EventRegistrationExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_workbook_uses_hacer_corporate_brand_template(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $activity = $this->activity('Deneme', 'deneme-brand', [
            ['key' => 'not', 'label' => 'Not', 'type' => 'textarea'],
        ]);
        $this->registration($activity, 'Ayşe', 'ayse@example.com', null, []);

        $workbook = app(ExportEventRegistrations::class)->handle($activity);
        $sheet = $this->sheet($workbook['contents'], 'Deneme');

        $this->assertStringContainsString('Hâcer İlim ve Kültür Topluluğu', $sheet);
        $this->assertStringContainsString('Program başvuru dökümü', $sheet);
        $this->assertStringContainsString('Anlık görüntü', $sheet);
        $this->assertStringContainsString('indirme sunucuda saklanmaz', $sheet);
        $this->assertStringContainsString('showGridLines="0"', $sheet);
        $this->assertStringContainsString('<mergeCells', $sheet);
        $this->assertStringContainsString('mergeCell ref="A1:', $sheet);
        $this->assertStringContainsString('Ayşe', $sheet);
    }

    public function test_workbook_xml_parts_are_well_formed_for_excel(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $activity = $this->activity('Deneme', 'deneme-xml', [
            ['key' => 'yas', 'label' => 'Yaş', 'type' => 'text'],
            ['key' => 'sehir', 'label' => 'Şehir', 'type' => 'text'],
        ]);
        $this->registration($activity, 'Ayşe', 'ayse@example.com', '0532', [
            ['key' => 'yas', 'label' => 'Yaş', 'value' => '20'],
            ['key' => 'sehir', 'label' => 'Şehir', 'value' => 'Gaziantep'],
        ]);

        $contents = app(ExportEventRegistrations::class)->handle($activity)['contents'];
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $contents);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));

        foreach ([
            '[Content_Types].xml',
            '_rels/.rels',
            'docProps/core.xml',
            'docProps/app.xml',
            'xl/workbook.xml',
            'xl/styles.xml',
            'xl/_rels/workbook.xml.rels',
            'xl/worksheets/sheet1.xml',
            'xl/worksheets/sheet2.xml',
        ] as $part) {
            $xml = $zip->getFromName($part);
            $this->assertIsString($xml, $part);
            $this->assertStringStartsWith('<?xml', $xml, $part);
            $this->assertNotFalse(simplexml_load_string($xml), $part.' parse failed');
        }

        $sheet = (string) $zip->getFromName('xl/worksheets/sheet2.xml');
        $this->assertStringContainsString('<mergeCells', $sheet);
        $this->assertMatchesRegularExpression('/<row r="1"[^>]*>\s*<c r="A1"/', $sheet);
        $this->assertDoesNotMatchRegularExpression('/<row r="1"[^>]*>.*<c r="B1"/s', $sheet);
        $this->assertStringContainsString('<dimension ref="A1:', $sheet);
        $this->assertStringContainsString('Ayşe', $sheet);
        $this->assertStringContainsString('Gaziantep', $sheet);
        $this->assertStringContainsString('Hâcer İlim ve Kültür Topluluğu', $sheet);
        $this->assertStringContainsString('indirme sunucuda saklanmaz', $sheet);

        $zip->close();
        @unlink($path);
    }

    public function test_activity_excel_modal_lists_all_applicants_selected_by_default(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $activity = $this->activity('Şiir', 'siir-modal', [
            ['key' => 'yas', 'label' => 'Yaş', 'type' => 'text'],
        ]);
        $ayse = $this->registration($activity, 'Ayşe Yılmaz', 'ayse@example.com', null, [
            ['key' => 'yas', 'label' => 'Yaş', 'value' => '28'],
        ]);
        $mehmet = $this->registration($activity, 'Mehmet Demir', 'mehmet@example.com', null, [
            ['key' => 'yas', 'label' => 'Yaş', 'value' => '40'],
        ]);

        Livewire::test(ListActivityRegistrations::class, ['activity' => $activity->getKey()])
            ->call('mountAction', 'excelActivity')
            ->assertActionDataSet([
                'registration_ids' => [
                    (string) $ayse->id,
                    (string) $mehmet->id,
                ],
            ]);
    }

    public function test_workbook_gives_each_activity_its_own_sheet_and_all_answers_by_default(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $poetry = $this->activity('Şiir Atölyesi', 'siir-atolyesi', [
            ['key' => 'yas', 'label' => 'Yaş', 'type' => 'text'],
            ['key' => 'sehir', 'label' => 'Şehir', 'type' => 'text'],
        ]);
        $voyage = $this->activity('Satır Arası Seferleri', 'satir-arasi', [
            ['key' => 'gemi', 'label' => 'Gemi adı', 'type' => 'text'],
        ]);

        $this->registration($poetry, 'Ayşe Yılmaz', 'ayse@example.com', '05320000000', [
            ['key' => 'yas', 'label' => 'Yaş', 'value' => '28'],
            ['key' => 'sehir', 'label' => 'Şehir', 'value' => 'Gaziantep'],
        ]);
        $this->registration($voyage, 'Mehmet Demir', 'mehmet@example.com', '05440000000', [
            ['key' => 'gemi', 'label' => 'Gemi adı', 'value' => 'Hacer'],
        ]);

        $workbook = app(ExportEventRegistrations::class)->handle();
        $poetrySheet = $this->sheet($workbook['contents'], 'Şiir Atölyesi');
        $voyageSheet = $this->sheet($workbook['contents'], 'Satır Arası Seferleri');

        $this->assertStringContainsString('Ayşe Yılmaz', $poetrySheet);
        $this->assertStringContainsString('05320000000', $poetrySheet);
        $this->assertStringContainsString('28', $poetrySheet);
        $this->assertStringContainsString('Gaziantep', $poetrySheet);
        $this->assertStringNotContainsString('Mehmet Demir', $poetrySheet);
        $this->assertStringContainsString('Mehmet Demir', $voyageSheet);
        $this->assertStringContainsString('Hacer', $voyageSheet);
        $this->assertStringNotContainsString('Ayşe Yılmaz', $voyageSheet);
        $this->assertSame(1, AuditLog::query()->where('action', 'exported')->count());
    }

    public function test_export_honors_selected_people_and_columns(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $activity = $this->activity('Şiir', 'siir', [
            ['key' => 'yas', 'label' => 'Yaş', 'type' => 'text'],
            ['key' => 'sehir', 'label' => 'Şehir', 'type' => 'text'],
        ]);
        $ayse = $this->registration($activity, 'Ayşe Yılmaz', 'ayse@example.com', '05320000000', [
            ['key' => 'yas', 'label' => 'Yaş', 'value' => '28'],
            ['key' => 'sehir', 'label' => 'Şehir', 'value' => 'Gaziantep'],
        ]);
        $this->registration($activity, 'Mehmet Demir', 'mehmet@example.com', '05440000000', [
            ['key' => 'yas', 'label' => 'Yaş', 'value' => '40'],
            ['key' => 'sehir', 'label' => 'Şehir', 'value' => 'İstanbul'],
        ]);

        $workbook = app(ExportEventRegistrations::class)->handle(
            $activity,
            registrationIds: [$ayse->id],
            columnKeys: ['name', 'q:yas'],
        );
        $sheet = $this->sheet($workbook['contents'], 'Şiir');

        $this->assertStringContainsString('Ayşe Yılmaz', $sheet);
        $this->assertStringContainsString('28', $sheet);
        $this->assertStringNotContainsString('Mehmet Demir', $sheet);
        $this->assertStringNotContainsString('Gaziantep', $sheet);
        $this->assertStringNotContainsString('05320000000', $sheet);
    }

    public function test_status_filter_and_unassigned_rows_stay_in_their_own_sheet(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $activity = $this->activity('İlmihal', 'ilmihal', [
            ['key' => 'not', 'label' => 'Not', 'type' => 'textarea'],
        ]);
        $this->registration($activity, 'Bekleyen', 'bekleyen@example.com', null, [], ApplicationStatus::Pending);
        $this->registration($activity, 'Onaylı', 'onayli@example.com', null, [], ApplicationStatus::Approved);

        $event = Event::query()->create([
            'title' => 'Bağımsız söyleşi',
            'slug' => 'bagimsiz-soylesi',
            'is_published' => true,
        ]);
        EventRegistration::query()->create([
            'event_id' => $event->id,
            'name' => 'Bağımsız Başvuran',
            'email' => 'bagimsiz@example.com',
            'notes' => 'Not düştü.',
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        $pending = app(ExportEventRegistrations::class)->handle($activity, ApplicationStatus::Pending);
        $pendingSheet = $this->sheet($pending['contents'], 'İlmihal');

        $this->assertStringContainsString('Bekleyen', $pendingSheet);
        $this->assertStringNotContainsString('Onaylı', $pendingSheet);
        $this->assertStringNotContainsString('Bağımsız Başvuran', $pendingSheet);

        $all = app(ExportEventRegistrations::class)->handle();
        $other = $this->sheet($all['contents'], 'Diğer başvurular');

        $this->assertStringContainsString('Bağımsız Başvuran', $other);
        $this->assertStringContainsString('Not düştü.', $other);
        $this->assertStringNotContainsString('Bağımsız Başvuran', $this->sheet($all['contents'], 'İlmihal'));
    }

    public function test_removed_question_stays_available_for_export_selection(): void
    {
        $activity = $this->activity('Kamp', 'kamp', [
            ['key' => 'yas', 'label' => 'Yaş', 'type' => 'number'],
        ]);
        $this->registration($activity, 'Ayşe', 'ayse@example.com', null, [
            ['key' => 'yas', 'label' => 'Yaş', 'value' => '17'],
        ]);

        $activity->update([
            'registration_fields' => RegistrationForm::normalize([
                ['key' => 'sehir', 'label' => 'Şehir', 'type' => 'text'],
            ]),
        ]);
        $activity->refresh();

        $this->assertSame('yas', $activity->excel_archived_questions[0]['key'] ?? null);

        $withoutArchive = app(ExportEventRegistrations::class)->handle(
            $activity,
            columnKeys: ['name', 'q:sehir'],
        );
        $this->assertStringNotContainsString('>17<', $this->sheet($withoutArchive['contents'], 'Kamp'));

        $withArchive = app(ExportEventRegistrations::class)->handle(
            $activity,
            columnKeys: ['name', 'q:yas'],
        );
        $this->assertStringContainsString('17', $this->sheet($withArchive['contents'], 'Kamp'));
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     */
    private function activity(string $title, string $slug, array $fields): Activity
    {
        return Activity::factory()->create([
            'title' => $title,
            'slug' => $slug,
            'registration_fields' => $fields,
        ]);
    }

    /**
     * @param  list<array{key: string, label: string, value: string}>  $answers
     */
    private function registration(
        Activity $activity,
        string $name,
        string $email,
        ?string $phone,
        array $answers,
        ApplicationStatus $status = ApplicationStatus::Pending,
    ): EventRegistration {
        return EventRegistration::query()->create([
            'activity_id' => $activity->id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'answers' => $answers,
            'kvkk_accepted' => true,
            'status' => $status,
        ]);
    }

    private function sheet(string $xlsx, string $name): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $xlsx);

        $zip = new ZipArchive;
        $zip->open($path);
        $workbook = $zip->getFromName('xl/workbook.xml');
        preg_match('/name="'.preg_quote($name, '/').'"[^>]*r:id="(rId\d+)"/', (string) $workbook, $match);
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        preg_match('/Id="'.$match[1].'"[^>]*Target="([^"]+)"/', (string) $rels, $target);
        $xml = $zip->getFromName('xl/'.ltrim($target[1], '/'));
        $zip->close();
        @unlink($path);

        return (string) $xml;
    }
}
