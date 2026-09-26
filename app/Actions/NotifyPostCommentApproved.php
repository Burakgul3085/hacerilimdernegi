<?php

namespace App\Actions;

use App\Models\PostComment;
use App\Notifications\PostCommentApproved;
use App\Support\PhpMailerClient;
use Throwable;

/**
 * Panelde onaylanan yorum için yazana yayın bildirimi gönderir.
 */
class NotifyPostCommentApproved
{
    public function __construct(private PhpMailerClient $mailer) {}

    public function handle(PostComment $comment): bool
    {
        $comment->refresh();

        if (! $comment->shouldNotifyApproval()) {
            return true;
        }

        if (! $this->mailer->isConfigured()) {
            report(new \RuntimeException('Yorum onaylandı ancak mailer ayarları eksik.'));

            return false;
        }

        try {
            $comment->notify(new PostCommentApproved($comment));
            $comment->forceFill(['approval_notified_at' => now()])->save();

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
