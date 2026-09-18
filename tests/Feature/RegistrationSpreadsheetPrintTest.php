<?php

namespace Tests\Feature;

use App\Actions\CreateRegistrationSpreadsheet;
use App\Actions\PrintRegistrationSpreadsheet;
use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationSpreadsheetPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_print_spreadsheet_with_same_corporate_page(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $activity = $this->activity();
        $registration = $this->registration($activity, 'Ayşe Yılmaz', 'ayse@example.com');

        $this->actingAs($editor);

        $spreadsheet = app(CreateRegistrationSpreadsheet::class)->handle(
            user: $editor,
            activity: $activity,
            registrationIds: [$registration->id],
            columnKeys: ['name', 'email', 'status'],
        );

        $token = app(PrintRegistrationSpreadsheet::class)->issueToken(
            spreadsheet: $spreadsheet,
            userId: (int) $editor->id,
        );

        $this->get(route('admin.registrations.print', ['token' => $token]))
            ->assertOk()
            ->assertSee('Hâcer İlim ve Kültür Topluluğu', false)
            ->assertSee('Ayşe Yılmaz', false)
            ->assertSee('ayse@example.com', false)
            ->assertSee('Beklemede', false)
            ->assertSee('window.print()', false)
            ->assertSee('size: A4 landscape', false)
            ->assertSee('history.back()', false);

        $this->assertSame(1, AuditLog::query()
            ->where('action', 'printed')
            ->where('model_type', $spreadsheet::class)
            ->count());
    }

    public function test_print_token_belongs_to_issuing_user(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Editor]);
        $other = User::factory()->create(['role' => UserRole::Editor]);
        $activity = $this->activity();
        $registration = $this->registration($activity);

        $this->actingAs($owner);

        $spreadsheet = app(CreateRegistrationSpreadsheet::class)->handle(
            user: $owner,
            activity: $activity,
            registrationIds: [$registration->id],
            columnKeys: ['name'],
        );

        $token = app(PrintRegistrationSpreadsheet::class)->issueToken(
            spreadsheet: $spreadsheet,
            userId: (int) $owner->id,
        );

        $this->actingAs($other)
            ->get(route('admin.registrations.print', ['token' => $token]))
            ->assertForbidden();
    }

    private function activity(): Activity
    {
        return Activity::factory()->create([
            'title' => 'Şiir',
            'slug' => 'siir-sheet-print-'.uniqid(),
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
