<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Models\Concerns\Auditable;
use App\Support\MailTemplate;
use App\Support\RegistrationForm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventRegistration extends Model
{
    use Auditable, Notifiable;

    protected $fillable = [
        'event_id', 'program_id', 'activity_id', 'name', 'email', 'phone', 'notes', 'answers', 'kvkk_accepted', 'status', 'replied_at',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'kvkk_accepted' => 'boolean',
            'status' => ApplicationStatus::class,
            'replied_at' => 'datetime',
        ];
    }

    /**
     * @return list<array{key?: string, label: string, value: string}>
     */
    public function answerItems(): array
    {
        $answers = [];

        foreach ($this->answers ?? [] as $answer) {
            if (! is_array($answer)) {
                continue;
            }

            $label = trim((string) ($answer['label'] ?? ''));
            $value = trim((string) ($answer['value'] ?? ''));

            if ($label === '' || $value === '') {
                continue;
            }

            $answers[] = [
                'key' => (string) ($answer['key'] ?? ''),
                'label' => $label,
                'value' => $value,
            ];
        }

        return $answers;
    }

    public function hasStructuredAnswers(): bool
    {
        return $this->answerItems() !== [];
    }

    public function answersPreview(int $limit = 90): string
    {
        $items = $this->answerItems();

        if ($items !== []) {
            return RegistrationForm::previewSummary($items, $limit);
        }

        return Str::limit(trim((string) $this->notes), $limit);
    }

    public function originalSubmissionSummary(int $limit = 400): string
    {
        $items = $this->answerItems();

        if ($items !== []) {
            return Str::limit(RegistrationForm::notesSummary($items) ?? '', $limit);
        }

        return Str::limit(trim((string) ($this->notes ?: '—')), $limit);
    }

    public function panelEditUrl(): string
    {
        return rtrim(MailTemplate::publicBaseUrl(), '/').'/yonetim/event-registrations/'.$this->getKey().'/edit';
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
     * Başvurunun hangi formdan geldiğini gösterir. Faaliyet klasörünün içinde
     * faaliyet adı tekrarlanmaz; etkinlik veya program adı varsa o yazılır.
     */
    public function sourceLabel(): string
    {
        $this->loadMissing(['event', 'program']);

        if ($this->event) {
            return $this->event->title;
        }

        if ($this->program) {
            return $this->program->title;
        }

        if ($this->activity_id) {
            return 'Faaliyet formu';
        }

        return '—';
    }

    /**
     * @param  Builder<EventRegistration>  $query
     * @return Builder<EventRegistration>
     */
    public function scopeForActivity(Builder $query, Activity|int $activity): Builder
    {
        $activityId = $activity instanceof Activity ? $activity->getKey() : $activity;

        return $query->where($query->qualifyColumn('activity_id'), $activityId);
    }

    /**
     * @param  Builder<EventRegistration>  $query
     * @return Builder<EventRegistration>
     */
    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull($query->qualifyColumn('activity_id'));
    }

    /**
     * Eski etkinlik ve program başvurularını, bağlı oldukları faaliyetin klasörüne taşır.
     * activity_id dolu kayıtlar olduğu gibi kalır.
     */
    public static function fileUnderParentActivity(): void
    {
        DB::update(<<<'SQL'
            update event_registrations
            set activity_id = (
                select events.activity_id
                from events
                where events.id = event_registrations.event_id
            )
            where activity_id is null
              and event_id is not null
              and exists (
                  select 1
                  from events
                  where events.id = event_registrations.event_id
                    and events.activity_id is not null
              )
        SQL);

        DB::update(<<<'SQL'
            update event_registrations
            set activity_id = (
                select programs.activity_id
                from programs
                where programs.id = event_registrations.program_id
            )
            where activity_id is null
              and program_id is not null
              and exists (
                  select 1
                  from programs
                  where programs.id = event_registrations.program_id
                    and programs.activity_id is not null
              )
        SQL);
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
