<?php

namespace App\Models;

use App\Enums\PostCommentStatus;
use App\Models\Concerns\Auditable;
use App\Support\MailTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Notifications\Notifiable;

class PostComment extends Model
{
    use Auditable;
    use Notifiable;

    protected $fillable = [
        'post_id',
        'first_name',
        'last_name',
        'email',
        'body',
        'hide_name',
        'status',
        'ip_address',
        'approval_notified_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'hide_name' => false,
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'hide_name' => 'boolean',
            'status' => PostCommentStatus::class,
            'approval_notified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PostComment $comment): void {
            if ($comment->commentable_type === Post::class && blank($comment->post_id)) {
                $comment->post_id = $comment->commentable_id;
            }

            if (filled($comment->post_id) && blank($comment->commentable_type)) {
                $comment->commentable_type = Post::class;
                $comment->commentable_id = $comment->post_id;
            }
        });

        static::saving(function (PostComment $comment): void {
            $comment->first_name = self::plainName($comment->first_name);
            $comment->last_name = self::plainName($comment->last_name);
            $comment->email = trim($comment->email);
            $comment->body = self::plainBody($comment->body);
        });
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): ?Model
    {
        if (filled($this->commentable_type)) {
            return $this->commentable;
        }

        return $this->post;
    }

    public function kindLabel(): string
    {
        return match ($this->commentable_type) {
            Activity::class => 'Faaliyet',
            Announcement::class => 'Duyuru',
            MediaAlbum::class => 'Albüm',
            default => 'Yazı',
        };
    }

    public function placePhrase(): string
    {
        return match ($this->commentable_type) {
            Activity::class => 'faaliyetin',
            Announcement::class => 'duyurunun',
            MediaAlbum::class => 'albümün',
            default => 'yazının',
        };
    }

    public function sectionLabel(): string
    {
        return match ($this->commentable_type) {
            Activity::class => 'Faaliyetler',
            Announcement::class => 'Duyurular',
            MediaAlbum::class => 'Medya',
            default => "Kalemim'İZ",
        };
    }

    public function pageUrl(?string $fragment = null): ?string
    {
        $subject = $this->subject();

        if (! $subject instanceof Model) {
            return null;
        }

        $url = match (true) {
            $subject instanceof Post => $subject->publicUrl(),
            $subject instanceof Activity => route('activities.show', $subject),
            $subject instanceof Announcement => route('announcements.show', $subject),
            $subject instanceof MediaAlbum => $subject->publicUrl(),
            default => null,
        };

        if ($url === null) {
            return null;
        }

        return $fragment === null ? $url : $url.'#'.ltrim($fragment, '#');
    }

    public function panelUrl(): ?string
    {
        $subject = $this->subject();

        if (! $subject instanceof Model) {
            return null;
        }

        $segment = match ($subject::class) {
            Post::class => 'posts',
            Activity::class => 'activities',
            Announcement::class => 'announcements',
            MediaAlbum::class => 'media-albums',
            default => null,
        };

        if ($segment === null) {
            return null;
        }

        return rtrim(MailTemplate::publicBaseUrl(), '/').'/yonetim/'.$segment.'/'.$subject->getKey().'/edit';
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PostCommentStatus::Pending);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', PostCommentStatus::Approved);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function publicName(): string
    {
        return $this->hide_name ? 'Ziyaretçi' : $this->fullName();
    }

    public function shouldNotifyApproval(): bool
    {
        return $this->status === PostCommentStatus::Approved
            && $this->approval_notified_at === null
            && filled($this->email);
    }

    public function routeNotificationForMail(): ?string
    {
        return filled($this->email) ? $this->email : null;
    }

    public static function plainName(string $value): string
    {
        $plain = trim(strip_tags($value));
        $plain = preg_replace('/\s+/u', ' ', $plain) ?? $plain;

        return trim($plain);
    }

    public static function plainBody(string $value): string
    {
        $plain = trim(strip_tags($value));
        $plain = preg_replace("/\r\n|\r/u", "\n", $plain) ?? $plain;

        return trim($plain);
    }
}
