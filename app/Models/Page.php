<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\CorporatePages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Page extends Model
{
    use Auditable;

    protected $fillable = [
        'slug', 'title', 'excerpt', 'body', 'image', 'document', 'seo_title', 'seo_description', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page): void {
            if (blank($page->slug) && filled($page->title)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function isBylaws(): bool
    {
        return CorporatePages::isBylaws((string) $this->slug);
    }

    public function documentUrl(): ?string
    {
        if (blank($this->document)) {
            return null;
        }

        return Storage::disk('public')->url($this->document);
    }
}
