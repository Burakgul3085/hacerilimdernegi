<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class EventRegistration extends Model
{
    use Auditable, Notifiable;

    protected $fillable = [
        'event_id', 'program_id', 'activity_id', 'name', 'email', 'phone', 'notes', 'kvkk_accepted', 'status', 'replied_at',
    ];

    protected function casts(): array
    {
        return [
            'kvkk_accepted' => 'boolean',
            'status' => ApplicationStatus::class,
            'replied_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function subjectTitle(): string
    {
        $this->loadMissing(['event', 'program', 'activity']);

        return $this->activity?->title
            ?: ($this->program?->title ?: ($this->event?->title ?: 'Program'));
    }

    /**
     * @return HasMany<EventRegistrationReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(EventRegistrationReply::class)->latest('sent_at');
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
