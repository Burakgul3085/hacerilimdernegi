<?php

namespace App\Models;

use App\Enums\CalendarReminderOffset;
use App\Enums\CalendarReminderStatus;
use App\Models\Concerns\Auditable;
use Database\Factories\AdminCalendarEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminCalendarEntry extends Model
{
    /** @use HasFactory<AdminCalendarEntryFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'all_day',
        'reminder_enabled',
        'reminder_offset',
        'remind_at',
        'reminder_status',
        'reminder_sent_at',
        'reminder_error',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'remind_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'all_day' => 'boolean',
            'reminder_enabled' => 'boolean',
            'reminder_offset' => CalendarReminderOffset::class,
            'reminder_status' => CalendarReminderStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }
}
