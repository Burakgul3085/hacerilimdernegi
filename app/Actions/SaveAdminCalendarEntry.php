<?php

namespace App\Actions;

use App\Enums\CalendarAssignmentStatus;
use App\Enums\CalendarReminderOffset;
use App\Enums\CalendarReminderStatus;
use App\Enums\UserRole;
use App\Models\AdminCalendarEntry;
use App\Models\User;
use App\Notifications\AdminCalendarAssignmentUpdated;
use App\Notifications\AdminCalendarTaskAssigned;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaveAdminCalendarEntry
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, array $data, ?AdminCalendarEntry $entry = null): AdminCalendarEntry
    {
        if ($entry !== null && ! $entry->isVisibleTo($actor)) {
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
            'assigned_to_id' => ['nullable', 'integer', 'exists:users,id'],
            'assignment_status' => ['nullable', Rule::enum(CalendarAssignmentStatus::class)],
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

        [$remindAt, $reminderOffset, $reminderEnabled, $reminderStatus] = $this->resolveReminder($validated, $startsAt);

        $wasAssigned = $entry?->isAssigned() ?? false;
        $previousAssigneeId = $entry ? (int) $entry->user_id : null;
        $previousStatus = $entry?->assignment_status;
        $actorIsAssigneeOnly = $entry !== null
            && $entry->isAssigned()
            && $entry->isOwnedBy($actor)
            && ! $entry->isCreatedBy($actor);

        if ($actorIsAssigneeOnly) {
            $ownerId = (int) $entry->user_id;
            $createdBy = (int) $entry->created_by;
            $assignedAt = $entry->assigned_at;
            $assignmentStatus = isset($validated['assignment_status'])
                ? CalendarAssignmentStatus::from((string) $validated['assignment_status'])
                : ($entry->assignment_status ?? CalendarAssignmentStatus::InProgress);
        } else {
            $assignedToId = isset($validated['assigned_to_id']) ? (int) $validated['assigned_to_id'] : null;

            if ($assignedToId !== null) {
                if (! $actor->isSuperAdmin()) {
                    throw ValidationException::withMessages([
                        'assigned_to_id' => 'Yalnızca süper yöneticiler görev atayabilir.',
                    ]);
                }

                if ($assignedToId === (int) $actor->id) {
                    throw ValidationException::withMessages([
                        'assigned_to_id' => 'Kendinize görev atayamazsınız.',
                    ]);
                }

                $assignee = User::query()->findOrFail($assignedToId);

                if ($assignee->role !== UserRole::SuperAdmin) {
                    throw ValidationException::withMessages([
                        'assigned_to_id' => 'Yalnızca süper yöneticilere görev atanabilir.',
                    ]);
                }

                $ownerId = $assignee->id;
                $createdBy = (int) ($entry?->created_by ?? $actor->id);
                $assignedAt = $entry?->assigned_at ?? now();
                $assignmentStatus = isset($validated['assignment_status'])
                    ? CalendarAssignmentStatus::from((string) $validated['assignment_status'])
                    : ($entry?->assignment_status ?? CalendarAssignmentStatus::InProgress);
            } else {
                $ownerId = (int) $actor->id;
                $createdBy = (int) ($entry?->created_by ?? $actor->id);
                $assignedAt = null;
                $assignmentStatus = null;
            }
        }

        $payload = [
            'user_id' => $ownerId,
            'created_by' => $createdBy,
            'assigned_at' => $assignedAt,
            'assignment_status' => $assignmentStatus,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'all_day' => $allDay,
            'reminder_enabled' => $reminderEnabled,
            'reminder_offset' => $reminderOffset,
            'remind_at' => $remindAt,
            'reminder_status' => $reminderStatus,
            'reminder_sent_at' => null,
            'reminder_error' => null,
        ];

        if ($entry === null) {
            $entry = AdminCalendarEntry::query()->create($payload)->load(['user', 'creator']);
            $this->sendAssignmentMail($entry);

            return $entry;
        }

        $entry->update($payload);
        $entry = $entry->refresh()->load(['user', 'creator']);

        $assigneeChanged = $entry->isAssigned()
            && (! $wasAssigned || $previousAssigneeId !== (int) $entry->user_id);

        if ($assigneeChanged) {
            $this->sendAssignmentMail($entry);
        }

        $statusChangedByAssignee = $actorIsAssigneeOnly
            && $entry->isAssigned()
            && $previousStatus?->value !== $entry->assignment_status?->value;

        if ($statusChangedByAssignee && $entry->creator !== null) {
            $entry->creator->notify(new AdminCalendarAssignmentUpdated($entry, $actor));
        }

        return $entry;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: ?Carbon, 1: ?CalendarReminderOffset, 2: bool, 3: CalendarReminderStatus}
     */
    private function resolveReminder(array $validated, Carbon $startsAt): array
    {
        $reminderEnabled = (bool) ($validated['reminder_enabled'] ?? false);

        if (! $reminderEnabled) {
            return [null, null, false, CalendarReminderStatus::None];
        }

        $offset = isset($validated['reminder_offset'])
            ? CalendarReminderOffset::from((string) $validated['reminder_offset'])
            : null;

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

        return [$remindAt, $offset, true, CalendarReminderStatus::Pending];
    }

    private function sendAssignmentMail(AdminCalendarEntry $entry): void
    {
        if (! $entry->isAssigned() || $entry->user === null) {
            return;
        }

        $entry->user->notify(new AdminCalendarTaskAssigned($entry));
    }
}
