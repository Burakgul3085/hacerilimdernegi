<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasContentGallery;
use App\Support\RegistrationExportPlan;
use App\Support\RegistrationForm;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use Auditable;

    use HasContentGallery;
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'cadence', 'description', 'highlights', 'image', 'gallery', 'status', 'sort_order', 'is_published', 'registration_open', 'registration_fields', 'excel_columns', 'excel_archived_questions',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActivityStatus::class,
            'gallery' => 'array',
            'highlights' => 'array',
            'registration_fields' => 'array',
            'excel_columns' => 'array',
            'excel_archived_questions' => 'array',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
            'registration_open' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Activity $activity): void {
            if (blank($activity->slug) && filled($activity->title)) {
                $activity->slug = Str::slug($activity->title);
            }

            if ($activity->isDirty('registration_fields')) {
                $previous = $activity->getOriginal('registration_fields');
                $activity->registration_fields = RegistrationForm::normalize($activity->registration_fields);

                if ($activity->exists) {
                    $activity->excel_archived_questions = RegistrationExportPlan::mergeArchive(
                        $previous,
                        $activity->registration_fields,
                        $activity->excel_archived_questions,
                    );
                }
            }
        });
    }

    /**
     * @return list<array{key: string, label: string, type: string, required: bool, options: list<string>}>
     */
    public function registrationFieldDefinitions(): array
    {
        return RegistrationForm::normalize($this->registration_fields);
    }

    public function registrationFormUrl(): string
    {
        return route('activities.register.form', $this);
    }

    /**
     * @return HasMany<ActivitySession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(ActivitySession::class)->orderBy('starts_at');
    }

    /**
     * @return HasMany<EventRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function acceptsRegistrations(): bool
    {
        return $this->is_published
            && $this->registration_open
            && $this->status === ActivityStatus::Ongoing;
    }

    /**
     * @return HasMany<Program, $this>
     */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * @return HasOne<MediaAlbum, $this>
     */
    public function mediaAlbum(): HasOne
    {
        return $this->hasOne(MediaAlbum::class);
    }

    public function nextSession(): ?ActivitySession
    {
        return $this->sessions->first(
            fn (ActivitySession $session): bool => $session->starts_at->gte(now()),
        );
    }

    public function lastSession(): ?ActivitySession
    {
        return $this->sessions
            ->filter(fn (ActivitySession $session): bool => $session->starts_at->lt(now()))
            ->last();
    }

    /**
     * @return Collection<int, ActivitySession>
     */
    public function upcomingSessions(): Collection
    {
        return $this->sessions
            ->filter(fn (ActivitySession $session): bool => $session->starts_at->gte(now()))
            ->values();
    }

    /**
     * @return Collection<int, ActivitySession>
     */
    public function pastSessions(): Collection
    {
        return $this->sessions
            ->filter(fn (ActivitySession $session): bool => $session->starts_at->lt(now()))
            ->reverse()
            ->take(4)
            ->values();
    }

    /**
     * @return list<array{title: string, text: string}>
     */
    public function highlightItems(): array
    {
        $items = [];

        foreach ($this->highlights ?? [] as $highlight) {
            if (! is_array($highlight)) {
                continue;
            }

            $title = trim((string) ($highlight['title'] ?? ''));
            $text = trim((string) ($highlight['text'] ?? ''));

            if ($title === '' || $text === '') {
                continue;
            }

            $items[] = ['title' => $title, 'text' => $text];
        }

        return array_slice($items, 0, 3);
    }

    public function sessionHeadline(): ?string
    {
        $next = $this->nextSession();
        $last = $this->lastSession();

        if ($last?->isToday() && $next !== null) {
            return 'Bugün yapıldı · sonraki '.$next->shortDate();
        }

        if ($next?->isToday()) {
            return 'Bugün · '.$next->timeLabel();
        }

        if ($next !== null) {
            return 'Sonraki oturum · '.$next->longDate();
        }

        if ($last?->isToday()) {
            return 'Bugün yapıldı';
        }

        return null;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }
}
