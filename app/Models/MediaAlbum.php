<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MediaAlbum extends Model
{
    use Auditable;

    protected $fillable = ['title', 'slug', 'description', 'cover', 'is_published'];

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
                $album->slug = Str::slug($album->title);
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(MediaItem::class)->orderBy('sort_order');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
