<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Resources\EventRegistrations\Pages\ListActivityRegistrations;
use App\Filament\Resources\EventRegistrations\Pages\ListEventRegistrations;
use App\Filament\Resources\RegistrationSpreadsheets\Pages\EditRegistrationSpreadsheet;
use App\Models\Activity;
use App\Models\EventRegistration;
use App\Models\RegistrationSpreadsheet;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationSpreadsheetActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_folder_e_tablo_action_opens_and_creates_spreadsheet(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $activity = Activity::factory()->create([
            'title' => 'Şiir',
            'registration_fields' => [
                ['key' => 'yas', 'label' => 'Yaş', 'type' => 'text'],
            ],
        ]);
        $registration = EventRegistration::query()->create([
            'activity_id' => $activity->id,
            'name' => 'Ayşe Yılmaz',
            'email' => 'ayse@example.com',
            'phone' => null,
            'notes' => null,
            'answers' => [],
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        $this->actingAs($editor);

        Livewire::test(ListActivityRegistrations::class, ['activity' => $activity->getKey()])
            ->assertSuccessful()
            ->call('mountAction', 'spreadsheetActivity')
            ->assertActionDataSet([
                'registration_ids' => [(string) $registration->id],
            ])
            ->setActionData([
                'status' => null,
                'registration_ids' => [(string) $registration->id],
                'column_keys' => ['name', 'email', 'status'],
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $spreadsheet = RegistrationSpreadsheet::query()->firstOrFail();

        $this->assertDatabaseHas('registration_spreadsheets', [
            'activity_id' => $activity->id,
            'user_id' => $editor->id,
        ]);

        Livewire::test(
            EditRegistrationSpreadsheet::class,
            ['record' => $spreadsheet->getKey()],
        )
            ->assertSuccessful()
            ->assertSee($spreadsheet->title)
            ->assertSee('Durum ve not sütunları')
            ->assertSet('grid.0.cells.name', 'Ayşe Yılmaz');
    }

    public function test_folder_row_e_tablo_action_creates_spreadsheet(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $activity = Activity::factory()->create(['title' => 'Şiir']);
        $registration = EventRegistration::query()->create([
            'activity_id' => $activity->id,
            'name' => 'Ayşe Yılmaz',
            'email' => 'ayse@example.com',
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        $this->actingAs($editor);

        Livewire::test(ListEventRegistrations::class)
            ->assertSuccessful()
            ->callAction(
                TestAction::make('spreadsheet')->table($activity),
                [
                    'status' => null,
                    'registration_ids' => [(string) $registration->id],
                    'column_keys' => ['name', 'email', 'status'],
                ],
            );

        $this->assertDatabaseCount('registration_spreadsheets', 1);
    }
}
