<?php

namespace App\Models;

use App\Enums\CalendarAssignmentStatus;
use App\Enums\CalendarReminderOffset;
use App\Enums\CalendarReminderStatus;
use App\Models\Concerns\Auditable;
use Database\Factories\AdminCalendarEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminCalendarEntry extends Model
{
    /** @use HasFactory<AdminCalendarEntryFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'user_id',
        'created_by',
        'assigned_at',
        'assignment_status',
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
            'assigned_at' => 'datetime',
            'all_day' => 'boolean',
            'reminder_enabled' => 'boolean',
            'reminder_offset' => CalendarReminderOffset::class,
            'reminder_status' => CalendarReminderStatus::class,
            'assignment_status' => CalendarAssignmentStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }

    public function isCreatedBy(User $user): bool
    {
        return (int) $this->created_by === (int) $user->id;
    }

    public function isAssigned(): bool
    {
        return $this->assigned_at !== null
            && $this->created_by !== null
            && (int) $this->created_by !== (int) $this->user_id;
    }

    public function isVisibleTo(User $user): bool
    {
        return $this->isOwnedBy($user) || $this->isCreatedBy($user);
    }

    /**
     * @param  Builder<AdminCalendarEntry>  $query
     * @return Builder<AdminCalendarEntry>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $inner) use ($user): void {
            $inner->where('user_id', $user->id)
                ->orWhere('created_by', $user->id);
        });
    }
}
