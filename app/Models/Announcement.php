<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasContentGallery;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use Auditable;

    use HasContentGallery;
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'image',
        'gallery',
        'published_at',
        'is_published',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_published' => true,
    ];

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'published_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Announcement $announcement): void {
            if (blank($announcement->slug) && filled($announcement->title)) {
                $announcement->slug = self::uniqueSlug($announcement->title, $announcement->getKey());
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(function (Builder $builder): void {
                $builder->whereNull('published_at')->orWhere('published_at', '<=', now()->endOfDay());
            });
    }

    public function isVisibleOnSite(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        return $this->published_at === null || $this->published_at->lte(now()->endOfDay());
    }

    public static function uniqueSlug(string $title, int|string|null $ignoreId = null): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'duyuru';
        }

        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public function readingMinutes(): int
    {
        $text = trim(strip_tags(implode(' ', array_filter([
            $this->title,
            $this->excerpt,
            $this->body,
        ]))));

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return max(1, (int) ceil(count($words) / 180));
    }

    public function coverUrl(): ?string
    {
        if (blank($this->image)) {
            return null;
        }

        return Storage::disk('public')->url($this->image);
    }
}
