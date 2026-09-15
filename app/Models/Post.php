<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasContentGallery;
use App\Support\MailTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Post extends Model
{
    use Auditable;
    use HasContentGallery;
    use Notifiable;

    protected $fillable = [
        'category_id',
        'author_id',
        'author_name',
        'submitter_email',
        'submitted_from_public',
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
        'approval_notified_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'article',
        'is_published' => true,
        'submitted_from_public' => false,
    ];

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'published_at' => 'datetime',
            'is_published' => 'boolean',
            'submitted_from_public' => 'boolean',
            'approval_notified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Post $post): void {
            if (blank($post->slug) && filled($post->title)) {
                $post->slug = self::uniqueSlug($post->title, $post->getKey());
            }

            $post->type = self::normalizeType($post->type);
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

    /**
     * @return array<string, string>
     */
    public static function typeOptions(?self $record = null): array
    {
        $options = [
            'article' => 'Yazı',
            'poem' => 'Şiir',
        ];

        $type = $record ? self::normalizeType($record->type) : null;

        if (filled($type) && ! isset($options[$type])) {
            $options[$type] = self::labelForType($type);
        }

        return $options;
    }

    public static function normalizeType(?string $type): string
    {
        $type = trim((string) $type);

        if ($type === '') {
            return 'article';
        }

        $key = str_replace([' ', '-', '_'], '', Str::lower(Str::ascii($type)));

        return match ($key) {
            'yazi', 'yazilar', 'article', 'articles' => 'article',
            'siir', 'siirler', 'poem', 'poems' => 'poem',
            'duyuru', 'duyurular', 'announcement', 'announcements' => 'article',
            default => $type,
        };
    }

    public static function labelForType(?string $type): string
    {
        return match (self::normalizeType($type)) {
            'poem' => 'Şiir',
            'article' => 'Yazı',
            default => trim((string) $type),
        };
    }

    public function typeLabel(): string
    {
        return self::labelForType($this->type);
    }

    public function isPoem(): bool
    {
        return self::normalizeType($this->type) === 'poem';
    }

    public function isPendingVisitorSubmission(): bool
    {
        return $this->submitted_from_public && ! $this->is_published;
    }

    public function shouldNotifyApproval(): bool
    {
        return $this->submitted_from_public
            && $this->is_published
            && filled($this->submitter_email)
            && $this->approval_notified_at === null;
    }

    public function routeNotificationForMail(): ?string
    {
        return filled($this->submitter_email) ? $this->submitter_email : null;
    }

    public static function uniqueSlug(string $title, int|string|null $ignoreId = null): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'yazi';
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

    public static function bodyFromPlainText(string $text): string
    {
        $normalized = preg_replace("/\r\n|\r/u", "\n", trim($text)) ?? trim($text);
        $paragraphs = preg_split("/\n{2,}/u", $normalized) ?: [$normalized];

        return collect($paragraphs)
            ->map(function (string $paragraph): string {
                $paragraph = trim($paragraph);

                if ($paragraph === '') {
                    return '';
                }

                return '<p>'.nl2br(e($paragraph), false).'</p>';
            })
            ->filter()
            ->implode('');
    }

    public function byline(): ?string
    {
        if (filled($this->author_name)) {
            return $this->author_name;
        }

        return $this->author?->name;
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

    public function publicUrl(): string
    {
        return rtrim(MailTemplate::publicBaseUrl(), '/').route('posts.show', $this, false);
    }
}
