<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MediaAlbum extends Model
{
    use Auditable;

    protected $fillable = ['parent_id', 'activity_id', 'title', 'slug', 'description', 'cover', 'is_published'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (MediaAlbum $album): void {
            if (blank($album->slug) && filled($album->title)) {
                $album->slug = self::uniqueSlug($album->title, $album->getKey());
            }

            $album->parent_id = self::allowedParentId($album->parent_id, $album->getKey());
        });
    }

    /**
     * @return BelongsTo<MediaAlbum, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * @return HasMany<MediaAlbum, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('title');
    }

    /**
     * @return HasMany<MediaItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MediaItem::class)->orderBy('sort_order');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function isVisibleOnSite(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        $this->loadMissing('parent');

        return $this->parent === null || $this->parent->is_published;
    }

    public function isCollection(): bool
    {
        if ($this->parent_id !== null) {
            return false;
        }

        if (isset($this->children_count)) {
            return $this->children_count > 0;
        }

        return $this->children()->published()->exists();
    }

    public function cardLabel(): string
    {
        return $this->isCollection() ? 'Koleksiyon' : 'Albüm';
    }

    public function cardCountLabel(): string
    {
        if ($this->isCollection()) {
            $count = $this->children_count ?? $this->children()->published()->count();

            return $count.' albüm';
        }

        $count = $this->items_count ?? $this->items()->count();

        return $count.' içerik';
    }

    public function publicUrl(): string
    {
        $this->loadMissing('parent');

        if ($this->parent_id !== null && $this->parent !== null) {
            return route('media.children.show', [
                'album' => $this->parent,
                'child' => $this->slug,
            ]);
        }

        return route('media.show', $this);
    }

    public static function uniqueSlug(string $title, int|string|null $ignoreId = null): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'album';
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

    private static function allowedParentId(mixed $parentId, int|string|null $albumId): ?int
    {
        if (blank($parentId)) {
            return null;
        }

        $parentId = (int) $parentId;

        if ($albumId !== null && $parentId === (int) $albumId) {
            return null;
        }

        $parent = static::query()->find($parentId);

        if ($parent === null || $parent->parent_id !== null) {
            return null;
        }

        if ($albumId !== null && static::query()->where('parent_id', $albumId)->exists()) {
            return null;
        }

        return $parentId;
    }
}
