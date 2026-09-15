<?php

namespace App\Actions;

use App\Models\Post;
use App\Notifications\VisitorPostApproved;
use App\Support\PhpMailerClient;
use Throwable;

/**
 * Panelde onaylanan ziyaretçi yazısı için gönderene yayın bildirimi gönderir.
 */
class NotifyVisitorPostApproved
{
    public function __construct(private PhpMailerClient $mailer) {}

    public function handle(Post $post): void
    {
        $post->refresh();

        if (! $post->shouldNotifyApproval()) {
            return;
        }

        if (! $this->mailer->isConfigured()) {
            report(new \RuntimeException('Yazı onaylandı ancak mailer ayarları eksik.'));

            return;
        }

        try {
            $post->notify(new VisitorPostApproved($post));
            $post->forceFill(['approval_notified_at' => now()])->save();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
