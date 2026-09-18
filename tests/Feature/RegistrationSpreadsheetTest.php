<?php

namespace Tests\Feature;

use App\Actions\CreateRegistrationSpreadsheet;
use App\Actions\ExportRegistrationSpreadsheetCsv;
use App\Actions\SaveRegistrationSpreadsheet;
use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Resources\RegistrationSpreadsheets\Pages\EditRegistrationSpreadsheet;
use App\Filament\Resources\RegistrationSpreadsheets\Pages\ListRegistrationSpreadsheets;
use App\Models\Activity;
use App\Models\EventRegistration;
use App\Models\RegistrationSpreadsheet;
use App\Models\User;
use App\Support\HacerXlsxTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class RegistrationSpreadsheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_spreadsheet_copies_selected_applicants_and_columns(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $activity = $this->activity();
        $ayse = $this->registration($activity, 'Ayşe Yılmaz', 'ayse@example.com');
        $this->registration($activity, 'Mehmet Demir', 'mehmet@example.com');

        $this->actingAs($editor);

        $spreadsheet = app(CreateRegistrationSpreadsheet::class)->handle(
            user: $editor,
            activity: $activity,
            registrationIds: [$ayse->id],
            columnKeys: ['name', 'email', 'status'],
        );

        $this->assertDatabaseHas('registration_spreadsheets', [
            'id' => $spreadsheet->id,
            'user_id' => $editor->id,
            'activity_id' => $activity->id,
        ]);
        $this->assertCount(1, $spreadsheet->rows);
        $this->assertSame('Ayşe Yılmaz', $spreadsheet->rows->first()->cells['name']);
        $this->assertSame('pending', $spreadsheet->rows->first()->cells['status']);
        $this->assertSame(['id', 'name', 'email', 'status'], $spreadsheet->column_keys);
    }

    public function test_saving_spreadsheet_syncs_status_and_notes_to_registration(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $activity = $this->activity();
        $registration = $this->registration($activity, 'Ayşe Yılmaz', 'ayse@example.com');

        $this->actingAs($editor);

        $spreadsheet = app(CreateRegistrationSpreadsheet::class)->handle(
            user: $editor,
            activity: $activity,
            registrationIds: [$registration->id],
            columnKeys: ['name', 'status', 'legacy_notes'],
        );

        $row = $spreadsheet->rows->first();

        app(SaveRegistrationSpreadsheet::class)->handle($spreadsheet, [
            'title' => 'Güncel e-tablo',
            'rows' => [[
                'id' => $row->id,
                'cells' => [
                    'id' => (string) $registration->id,
                    'name' => 'Ayşe Yılmaz',
                    'status' => ApplicationStatus::Approved->value,
                    'legacy_notes' => 'Telefonla teyit edildi.',
                ],
            ]],
        ]);

        $registration->refresh();
        $row->refresh();

        $this->assertSame(ApplicationStatus::Approved, $registration->status);
        $this->assertSame('Telefonla teyit edildi.', $registration->notes);
        $this->assertSame('approved', $row->cells['status']);
        $this->assertSame('Güncel e-tablo', $spreadsheet->fresh()->title);
    }

    public function test_editors_can_open_spreadsheet_list_and_edit_page(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $spreadsheet = RegistrationSpreadsheet::factory()->create(['user_id' => $editor->id]);

        $this->actingAs($editor);

        Livewire::test(ListRegistrationSpreadsheets::class)
            ->assertSuccessful();

        Livewire::test(EditRegistrationSpreadsheet::class, ['record' => $spreadsheet->getKey()])
            ->assertSuccessful()
            ->assertSet('data.title', $spreadsheet->title);
    }

    public function test_csv_export_downloads_corporate_xlsx_with_status_labels_and_dates(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $activity = $this->activity();
        $registration = $this->registration($activity, 'Ayşe Yılmaz', 'ayse@example.com');

        $this->actingAs($editor);

        $spreadsheet = app(CreateRegistrationSpreadsheet::class)->handle(
            user: $editor,
            activity: $activity,
            registrationIds: [$registration->id],
            columnKeys: ['name', 'email', 'status', 'submitted_at'],
        );

        $response = app(ExportRegistrationSpreadsheetCsv::class)->download($spreadsheet);
        ob_start();
        $response->sendContent();
        $binary = (string) ob_get_clean();

        $this->assertStringStartsWith('PK', $binary);

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $this->assertNotFalse($path);
        file_put_contents($path, $binary);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($path);

        $this->assertStringContainsString(HacerXlsxTemplate::ORGANIZATION, $sheet);
        $this->assertStringContainsString('Ayşe Yılmaz', $sheet);
        $this->assertStringContainsString('Beklemede', $sheet);
        $this->assertStringContainsString($registration->created_at->timezone(config('app.timezone'))->format('d.m.Y'), $sheet);
        $this->assertStringNotContainsString('pending', $sheet);
        $this->assertStringNotContainsString('2021', $sheet);
        $this->assertStringContainsString('<mergeCells', $sheet);
        $this->assertMatchesRegularExpression('/<row r="1"[^>]*>\s*<c r="A1"/', $sheet);
        $this->assertDoesNotMatchRegularExpression('/<row r="1"[^>]*>.*<c r="B1"/s', $sheet);
    }

    public function test_guests_are_redirected_from_spreadsheet_pages(): void
    {
        $this->get('/yonetim/registration-spreadsheets')->assertRedirect();
    }

    private function activity(): Activity
    {
        return Activity::factory()->create([
            'title' => 'Şiir',
            'slug' => 'siir-sheet-'.uniqid(),
            'registration_fields' => [
                ['key' => 'yas', 'label' => 'Yaş', 'type' => 'text'],
            ],
        ]);
    }

    private function registration(
        Activity $activity,
        string $name = 'Ayşe',
        string $email = 'ayse@example.com',
    ): EventRegistration {
        return EventRegistration::query()->create([
            'activity_id' => $activity->id,
            'name' => $name,
            'email' => $email,
            'phone' => null,
            'notes' => null,
            'answers' => [],
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);
    }
}
