<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ProgramType;
use App\Enums\UserRole;
use App\Filament\Resources\EventRegistrations\EventRegistrationResource;
use App\Filament\Resources\EventRegistrations\Pages\ListActivityRegistrations;
use App\Filament\Resources\EventRegistrations\Pages\ListEventRegistrations;
use App\Filament\Resources\EventRegistrations\Pages\ListUnassignedEventRegistrations;
use App\Models\Activity;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventRegistrationInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_inbox_groups_applications_by_existing_activities(): void
    {
        $this->actingAs($this->editor());

        $poetry = Activity::factory()->create([
            'title' => 'Şiir Atölyesi',
            'sort_order' => 1,
        ]);
        $voyage = Activity::factory()->create([
            'title' => 'Satır Arası Seferleri',
            'sort_order' => 2,
        ]);
        Activity::factory()->create([
            'title' => 'Henüz başvurmayan faaliyet',
            'sort_order' => 3,
        ]);

        $this->registration($poetry, 'Ayşe Yılmaz', 'ayse@example.com');
        $this->registration($voyage, 'Mehmet Demir', 'mehmet@example.com');

        Livewire::test(ListEventRegistrations::class)
            ->assertOk()
            ->assertSee('Şiir Atölyesi')
            ->assertSee('Satır Arası Seferleri')
            ->assertSee('Henüz başvurmayan faaliyet')
            ->assertDontSee('ayse@example.com')
            ->assertDontSee('mehmet@example.com')
            ->assertDontSee('Diğer başvurular');

        Livewire::test(ListActivityRegistrations::class, ['activity' => $poetry->getKey()])
            ->assertOk()
            ->assertSee('Ayşe Yılmaz')
            ->assertSee('ayse@example.com')
            ->assertDontSee('Mehmet Demir')
            ->assertDontSee('mehmet@example.com');
    }

    public function test_unassigned_applications_stay_out_of_activity_folders(): void
    {
        $this->actingAs($this->editor());

        $activity = Activity::factory()->create(['title' => 'Şiir Atölyesi']);
        $this->registration($activity, 'Ayşe Yılmaz', 'ayse@example.com');

        $event = Event::query()->create([
            'title' => 'Bağımsız söyleşi',
            'slug' => 'bagimsiz-soylesi',
            'is_published' => true,
            'registration_open' => true,
        ]);
        EventRegistration::query()->create([
            'event_id' => $event->id,
            'name' => 'Bağımsız Başvuran',
            'email' => 'bagimsiz@example.com',
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        Livewire::test(ListEventRegistrations::class)
            ->assertOk()
            ->assertSee('Diğer başvurular (1)');

        Livewire::test(ListUnassignedEventRegistrations::class)
            ->assertOk()
            ->assertSee('Bağımsız Başvuran')
            ->assertDontSee('Ayşe Yılmaz');

        Livewire::test(ListActivityRegistrations::class, ['activity' => $activity->getKey()])
            ->assertOk()
            ->assertSee('Ayşe Yılmaz')
            ->assertDontSee('Bağımsız Başvuran');
    }

    public function test_legacy_event_applications_are_filed_under_the_parent_activity(): void
    {
        $activity = Activity::factory()->create(['title' => 'Satır Arası Seferleri']);
        $event = Event::query()->create([
            'activity_id' => $activity->id,
            'title' => 'Dönem açılışı',
            'slug' => 'donem-acilisi',
            'is_published' => true,
            'registration_open' => true,
        ]);
        $registration = EventRegistration::query()->create([
            'event_id' => $event->id,
            'name' => 'Eski Başvuran',
            'email' => 'eski@example.com',
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        EventRegistration::fileUnderParentActivity();

        $this->assertSame($activity->id, $registration->refresh()->activity_id);
        $this->assertSame(0, EventRegistration::query()->unassigned()->count());
    }

    public function test_event_and_program_forms_file_the_application_under_the_parent_activity(): void
    {
        $activity = Activity::factory()->create();
        $event = Event::query()->create([
            'activity_id' => $activity->id,
            'title' => 'Dönem açılışı',
            'slug' => 'donem-acilisi',
            'is_published' => true,
            'registration_open' => true,
        ]);
        $program = Program::query()->create([
            'activity_id' => $activity->id,
            'type' => ProgramType::Sohbet,
            'title' => 'Haftalık sohbet',
            'slug' => 'haftalik-sohbet',
            'is_published' => true,
        ]);

        $this->post(route('events.register', $event), $this->formPayload('ayse@example.com'))
            ->assertRedirect();
        $this->post(route('programs.register', $program), $this->formPayload('mehmet@example.com'))
            ->assertRedirect();

        $this->assertDatabaseHas('event_registrations', [
            'email' => 'ayse@example.com',
            'event_id' => $event->id,
            'activity_id' => $activity->id,
        ]);
        $this->assertDatabaseHas('event_registrations', [
            'email' => 'mehmet@example.com',
            'program_id' => $program->id,
            'activity_id' => $activity->id,
        ]);
    }

    public function test_unknown_activity_folder_returns_404(): void
    {
        $this->actingAs($this->editor());

        $this->get('/yonetim/event-registrations/faaliyet/999999')->assertNotFound();
    }

    public function test_guests_are_redirected_from_the_program_inbox(): void
    {
        $this->get('/yonetim/event-registrations')->assertRedirect();
    }

    public function test_media_managers_cannot_open_the_program_inbox(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Media]));

        $this->assertFalse(EventRegistrationResource::canAccess());
        $this->get('/yonetim/event-registrations')->assertForbidden();
    }

    private function editor(): User
    {
        return User::factory()->create(['role' => UserRole::Editor]);
    }

    private function registration(Activity $activity, string $name, string $email): EventRegistration
    {
        return EventRegistration::query()->create([
            'activity_id' => $activity->id,
            'name' => $name,
            'email' => $email,
            'phone' => '05320000000',
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function formPayload(string $email): array
    {
        return [
            'name' => 'Ayşe Yılmaz',
            'email' => $email,
            'phone' => '05320000000',
            'notes' => 'Katılmak istiyorum.',
            'kvkk_accepted' => '1',
        ];
    }
}
