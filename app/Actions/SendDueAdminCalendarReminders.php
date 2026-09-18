<?php

namespace App\Actions;

use App\Enums\CalendarReminderStatus;
use App\Models\AdminCalendarEntry;
use App\Notifications\AdminCalendarReminder;
use Illuminate\Support\Facades\DB;
use Throwable;

class SendDueAdminCalendarReminders
{
    /**
     * @return array{sent: int, failed: int}
     */
    public function handle(int $limit = 50): array
    {
        $sent = 0;
        $failed = 0;

        $dueIds = AdminCalendarEntry::query()
            ->where('reminder_status', CalendarReminderStatus::Pending)
            ->where('reminder_enabled', true)
            ->whereNotNull('remind_at')
            ->where('remind_at', '<=', now())
            ->orderBy('remind_at')
            ->limit($limit)
            ->pluck('id');

        foreach ($dueIds as $id) {
            try {
                $result = DB::transaction(function () use ($id): string {
                    /** @var AdminCalendarEntry|null $entry */
                    $entry = AdminCalendarEntry::query()
                        ->whereKey($id)
                        ->lockForUpdate()
                        ->first();

                    if ($entry === null || $entry->reminder_status !== CalendarReminderStatus::Pending) {
                        return 'skip';
                    }

                    $entry->loadMissing('user');

                    if ($entry->user === null || blank($entry->user->email)) {
                        $entry->update([
                            'reminder_status' => CalendarReminderStatus::Failed,
                            'reminder_error' => 'Kullanıcı e-postası bulunamadı.',
                        ]);

                        return 'failed';
                    }

                    $entry->user->notify(new AdminCalendarReminder($entry));

                    $entry->update([
                        'reminder_status' => CalendarReminderStatus::Sent,
                        'reminder_sent_at' => now(),
                        'reminder_error' => null,
                    ]);

                    return 'sent';
                });

                if ($result === 'sent') {
                    $sent++;
                } elseif ($result === 'failed') {
                    $failed++;
                }
            } catch (Throwable $exception) {
                $failed++;

                AdminCalendarEntry::query()->whereKey($id)->update([
                    'reminder_status' => CalendarReminderStatus::Failed,
                    'reminder_error' => mb_substr($exception->getMessage(), 0, 240),
                ]);
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }
}
