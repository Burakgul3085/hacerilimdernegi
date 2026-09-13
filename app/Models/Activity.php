<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasContentGallery;
use App\Support\WorkItem;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use Auditable;

    use HasContentGallery;
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'description', 'image', 'gallery', 'status', 'sort_order', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActivityStatus::class,
            'gallery' => 'array',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Activity $activity): void {
            if (blank($activity->slug) && filled($activity->title)) {
                $activity->slug = Str::slug($activity->title);
            }
        });
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
     * @return Collection<int, WorkItem>
     */
    public function sessionItems(): Collection
    {
        return collect()
            ->concat($this->programs->map(fn (Program $program): WorkItem => WorkItem::fromProgram($program)))
            ->concat($this->events->map(fn (Event $event): WorkItem => WorkItem::fromEvent($event)))
            ->sortBy(fn (WorkItem $item): int => $item->startsAt?->timestamp ?? PHP_INT_MAX)
            ->values();
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
