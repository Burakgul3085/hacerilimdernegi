<?php

namespace App\Actions;

use App\Enums\CalendarReminderOffset;
use App\Enums\CalendarReminderStatus;
use App\Models\AdminCalendarEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaveAdminCalendarEntry
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data, ?AdminCalendarEntry $entry = null): AdminCalendarEntry
    {
        if ($entry !== null && ! $entry->isOwnedBy($user)) {
            abort(403);
        }

        $validated = Validator::make($data, [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'all_day' => ['sometimes', 'boolean'],
            'reminder_enabled' => ['sometimes', 'boolean'],
            'reminder_offset' => ['nullable', Rule::enum(CalendarReminderOffset::class)],
            'remind_at' => ['nullable', 'date'],
        ])->validate();

        $allDay = (bool) ($validated['all_day'] ?? false);
        $startsAt = Carbon::parse($validated['starts_at'])->timezone((string) config('app.timezone'));
        $endsAt = isset($validated['ends_at'])
            ? Carbon::parse($validated['ends_at'])->timezone((string) config('app.timezone'))
            : null;

        if ($allDay) {
            $startsAt = $startsAt->copy()->startOfDay();
            $endsAt = ($endsAt ?? $startsAt->copy())->copy()->endOfDay();
        } elseif ($endsAt === null) {
            $endsAt = $startsAt->copy()->addHour();
        }

        $reminderEnabled = (bool) ($validated['reminder_enabled'] ?? false);
        $offset = isset($validated['reminder_offset'])
            ? CalendarReminderOffset::from((string) $validated['reminder_offset'])
            : null;

        $remindAt = null;
        $reminderStatus = CalendarReminderStatus::None;

        if ($reminderEnabled) {
            if ($offset === null) {
                throw ValidationException::withMessages([
                    'reminder_offset' => 'Hatırlatma zamanı seçin.',
                ]);
            }

            if ($offset === CalendarReminderOffset::Custom) {
                if (empty($validated['remind_at'])) {
                    throw ValidationException::withMessages([
                        'remind_at' => 'Özel hatırlatma zamanı gerekli.',
                    ]);
                }

                $remindAt = Carbon::parse($validated['remind_at'])->timezone((string) config('app.timezone'));
            } else {
                $remindAt = $offset->remindAt($startsAt);
            }

            if ($remindAt === null || $remindAt->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages([
                    'remind_at' => 'Hatırlatma zamanı gelecekte olmalıdır.',
                ]);
            }

            $reminderStatus = CalendarReminderStatus::Pending;
        }

        $payload = [
            'user_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'all_day' => $allDay,
            'reminder_enabled' => $reminderEnabled,
            'reminder_offset' => $reminderEnabled ? $offset : null,
            'remind_at' => $remindAt,
            'reminder_status' => $reminderStatus,
            'reminder_sent_at' => null,
            'reminder_error' => null,
        ];

        if ($entry === null) {
            return AdminCalendarEntry::query()->create($payload);
        }

        $entry->update($payload);

        return $entry->refresh();
    }
}
