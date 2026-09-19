<?php

namespace Database\Factories;

use App\Enums\CalendarAssignmentStatus;
use App\Enums\CalendarReminderOffset;
use App\Enums\CalendarReminderStatus;
use App\Models\AdminCalendarEntry;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminCalendarEntry>
 */
class AdminCalendarEntryFactory extends Factory
{
    protected $model = AdminCalendarEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDay()->setTime(14, 0);

        return [
            'user_id' => User::factory(),
            'created_by' => null,
            'assigned_at' => null,
            'assignment_status' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'all_day' => false,
            'reminder_enabled' => false,
            'reminder_offset' => null,
            'remind_at' => null,
            'reminder_status' => CalendarReminderStatus::None,
            'reminder_sent_at' => null,
            'reminder_error' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (AdminCalendarEntry $entry): void {
            if ($entry->created_by === null && $entry->user_id !== null) {
                $entry->created_by = $entry->user_id;
            }
        })->afterCreating(function (AdminCalendarEntry $entry): void {
            if ($entry->created_by === null) {
                $entry->forceFill(['created_by' => $entry->user_id])->saveQuietly();
            }
        });
    }

    public function assignedTo(User $assignee, User $assigner): static
    {
        return $this->state(fn (): array => [
            'user_id' => $assignee->id,
            'created_by' => $assigner->id,
            'assigned_at' => now(),
            'assignment_status' => CalendarAssignmentStatus::InProgress,
        ]);
    }

    public function withReminder(CalendarReminderOffset $offset = CalendarReminderOffset::Hour1): static
    {
        return $this->state(function (array $attributes) use ($offset): array {
            $startsAt = $attributes['starts_at'] instanceof CarbonInterface
                ? $attributes['starts_at']->copy()
                : Carbon::parse($attributes['starts_at']);

            $remindAt = $offset === CalendarReminderOffset::Custom
                ? $startsAt->copy()->subMinutes(30)
                : $offset->remindAt($startsAt);

            return [
                'reminder_enabled' => true,
                'reminder_offset' => $offset,
                'remind_at' => $remindAt,
                'reminder_status' => CalendarReminderStatus::Pending,
            ];
        });
    }

    public function due(): static
    {
        return $this->state(fn (): array => [
            'reminder_enabled' => true,
            'reminder_offset' => CalendarReminderOffset::Custom,
            'remind_at' => now()->subMinute(),
            'reminder_status' => CalendarReminderStatus::Pending,
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(2),
        ]);
    }
}
