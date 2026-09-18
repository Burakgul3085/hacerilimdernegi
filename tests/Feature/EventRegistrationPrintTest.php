<?php

namespace Tests\Feature;

use App\Actions\PrintEventRegistrations;
use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRegistrationPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_print_page(): void
    {
        $token = '11111111-1111-1111-1111-111111111111';

        $this->get(route('admin.registrations.print', ['token' => $token]))
            ->assertRedirect('/yonetim/login');
    }

    public function test_editor_can_print_selected_applicants_and_columns(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $activity = $this->activity();
        $ayse = $this->registration($activity, 'Ayşe Yılmaz', 'ayse@example.com', '0532');
        $this->registration($activity, 'Mehmet Demir', 'mehmet@example.com', '0544');

        $this->actingAs($editor);

        $token = app(PrintEventRegistrations::class)->issueToken(
            activity: $activity,
            registrationIds: [$ayse->id],
            columnKeys: ['name', 'email'],
            userId: (int) $editor->id,
        );

        $this->get(route('admin.registrations.print', ['token' => $token]))
            ->assertOk()
            ->assertSee('Hâcer İlim ve Kültür Topluluğu', false)
            ->assertSee('Ayşe Yılmaz', false)
            ->assertSee('ayse@example.com', false)
            ->assertDontSee('Mehmet Demir', false)
            ->assertDontSee('0532', false)
            ->assertSee('window.print()', false)
            ->assertSee('size: A4 landscape', false)
            ->assertSee('print-color-adjust: exact', false)
            ->assertSee('history.back()', false);

        $this->assertSame(1, AuditLog::query()->where('action', 'printed')->count());
    }

    public function test_print_token_belongs_to_issuing_user(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Editor]);
        $other = User::factory()->create(['role' => UserRole::Editor]);
        $activity = $this->activity();
        $registration = $this->registration($activity);

        $token = app(PrintEventRegistrations::class)->issueToken(
            activity: $activity,
            registrationIds: [$registration->id],
            columnKeys: ['name'],
            userId: (int) $owner->id,
        );

        $this->actingAs($other)
            ->get(route('admin.registrations.print', ['token' => $token]))
            ->assertForbidden();
    }

    public function test_unknown_print_token_returns_not_found(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($editor)
            ->get(route('admin.registrations.print', ['token' => '11111111-1111-1111-1111-111111111111']))
            ->assertNotFound();
    }

    private function activity(): Activity
    {
        return Activity::factory()->create([
            'title' => 'Şiir',
            'slug' => 'siir-print-'.uniqid(),
            'registration_fields' => [
                ['key' => 'yas', 'label' => 'Yaş', 'type' => 'text'],
            ],
        ]);
    }

    private function registration(
        ?Activity $activity = null,
        string $name = 'Ayşe',
        string $email = 'ayse@example.com',
        ?string $phone = null,
    ): EventRegistration {
        $activity ??= $this->activity();

        return EventRegistration::query()->create([
            'activity_id' => $activity->id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'answers' => [],
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);
    }
}
