<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\MailTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Post extends Model
{
    use Auditable;

    protected $fillable = [
        'category_id',
        'author_id',
        'type',
        'title',
        'subtitle',
        'slug',
        'excerpt',
        'location',
        'body',
        'image',
        'gallery',
        'featured_quote',
        'source_url',
        'source_label',
        'published_at',
        'is_published',
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
        static::saving(function (Post $post): void {
            if (blank($post->slug) && filled($post->title)) {
                $post->slug = Str::slug($post->title);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(function (Builder $builder): void {
                $builder->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function isVisibleOnSite(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        return $this->published_at === null || $this->published_at->lte(now());
    }

    public function typeLabel(): string
    {
        return $this->type === 'announcement' ? 'Duyuru' : 'Yazı';
    }

    public function readingMinutes(): int
    {
        $text = trim(strip_tags(implode(' ', array_filter([
            $this->title,
            $this->subtitle,
            $this->excerpt,
            $this->featured_quote,
            $this->body,
        ]))));

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return max(1, (int) ceil(count($words) / 180));
    }

    /**
     * @return list<string>
     */
    public function galleryImages(): array
    {
        return collect($this->gallery ?? [])
            ->filter(fn (mixed $path): bool => is_string($path) && filled($path))
            ->values()
            ->all();
    }

    public function coverUrl(): ?string
    {
        if (blank($this->image)) {
            return null;
        }

        return Storage::disk('public')->url($this->image);
    }

    public function coverAbsoluteUrl(): ?string
    {
        $url = $this->coverUrl();

        if ($url === null) {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim(MailTemplate::publicBaseUrl(), '/').'/'.ltrim($url, '/');
    }

    public function sourceHref(): ?string
    {
        $url = trim((string) $this->source_url);

        if ($url === '' || preg_match('/^https?:\/\//i', $url) !== 1) {
            return null;
        }

        return $url;
    }
}
