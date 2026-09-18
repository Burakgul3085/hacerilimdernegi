<?php

namespace Tests\Feature;

use App\Actions\SaveAdminCalendarEntry;
use App\Actions\SendDueAdminCalendarReminders;
use App\Enums\CalendarReminderOffset;
use App\Enums\CalendarReminderStatus;
use App\Enums\UserRole;
use App\Filament\Pages\MyCalendar;
use App\Models\AdminCalendarEntry;
use App\Models\User;
use App\Notifications\AdminCalendarReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_personal_calendar_page(): void
    {
        $user = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($user);

        Livewire::test(MyCalendar::class)
            ->assertSuccessful()
            ->assertSee('Takvimim')
            ->assertSee('Kişisel ajanda');
    }

    public function test_entries_are_isolated_per_admin(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Editor]);
        $other = User::factory()->create(['role' => UserRole::Editor]);

        $entry = AdminCalendarEntry::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Sadece bana özel',
        ]);

        $this->assertTrue($owner->can('view', $entry));
        $this->assertFalse($other->can('view', $entry));
        $this->assertFalse($other->can('update', $entry));
        $this->assertFalse($other->can('delete', $entry));
    }

    public function test_save_action_creates_pending_reminder_in_the_future(): void
    {
        $user = User::factory()->create(['role' => UserRole::Editor]);
        $startsAt = now()->addDays(2)->setTime(15, 0);

        $entry = app(SaveAdminCalendarEntry::class)->handle($user, [
            'title' => 'Arama',
            'description' => 'Ayşe ile konuş',
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $startsAt->copy()->addHour()->toDateTimeString(),
            'all_day' => false,
            'reminder_enabled' => true,
            'reminder_offset' => CalendarReminderOffset::Hour1->value,
        ]);

        $this->assertSame('Arama', $entry->title);
        $this->assertTrue($entry->reminder_enabled);
        $this->assertSame(CalendarReminderStatus::Pending, $entry->reminder_status);
        $this->assertTrue($entry->remind_at?->equalTo($startsAt->copy()->subHour()));
    }

    public function test_past_reminder_time_is_rejected(): void
    {
        $user = User::factory()->create(['role' => UserRole::Editor]);

        $this->expectException(ValidationException::class);

        app(SaveAdminCalendarEntry::class)->handle($user, [
            'title' => 'Geçmiş',
            'starts_at' => now()->addHour()->toDateTimeString(),
            'reminder_enabled' => true,
            'reminder_offset' => CalendarReminderOffset::Custom->value,
            'remind_at' => now()->subMinute()->toDateTimeString(),
        ]);
    }

    public function test_due_reminders_are_sent_once_to_owner_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => UserRole::Editor,
            'email' => 'editor@hacer.test',
        ]);

        $entry = AdminCalendarEntry::factory()->due()->create([
            'user_id' => $user->id,
            'title' => 'Hatırlat beni',
        ]);

        $result = app(SendDueAdminCalendarReminders::class)->handle();

        $this->assertSame(1, $result['sent']);
        Notification::assertSentTo($user, AdminCalendarReminder::class);

        $entry->refresh();
        $this->assertSame(CalendarReminderStatus::Sent, $entry->reminder_status);
        $this->assertNotNull($entry->reminder_sent_at);

        $second = app(SendDueAdminCalendarReminders::class)->handle();
        $this->assertSame(0, $second['sent']);
        Notification::assertSentToTimes($user, AdminCalendarReminder::class, 1);
    }

    public function test_at_start_reminder_uses_event_start_time(): void
    {
        $user = User::factory()->create(['role' => UserRole::Editor]);
        $startsAt = now()->addMinutes(10)->startOfMinute();

        $entry = app(SaveAdminCalendarEntry::class)->handle($user, [
            'title' => 'Hemen önce',
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $startsAt->copy()->addHour()->toDateTimeString(),
            'reminder_enabled' => true,
            'reminder_offset' => CalendarReminderOffset::AtStart->value,
        ]);

        $this->assertSame(CalendarReminderStatus::Pending, $entry->reminder_status);
        $this->assertSame(
            $startsAt->format('Y-m-d H:i:s'),
            $entry->remind_at?->timezone((string) config('app.timezone'))->format('Y-m-d H:i:s'),
        );
    }

    public function test_calendar_reminder_command_runs(): void
    {
        Notification::fake();

        $this->artisan('calendar:send-reminders')
            ->assertSuccessful();
    }

    public function test_livewire_save_creates_owned_entry(): void
    {
        $user = User::factory()->create(['role' => UserRole::Editor]);
        $this->actingAs($user);

        $startsAt = now()->addDay()->setTime(10, 0);

        Livewire::test(MyCalendar::class)
            ->call('openCreate')
            ->set('data.title', 'Panel notu')
            ->set('data.description', 'Detay')
            ->set('data.starts_at', $startsAt)
            ->set('data.ends_at', $startsAt->copy()->addHour())
            ->set('data.all_day', false)
            ->set('data.reminder_enabled', false)
            ->call('saveEntry')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('admin_calendar_entries', [
            'user_id' => $user->id,
            'title' => 'Panel notu',
        ]);
    }
}
