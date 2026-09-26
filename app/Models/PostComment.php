<?php

namespace App\Models;

use App\Enums\PostCommentStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
