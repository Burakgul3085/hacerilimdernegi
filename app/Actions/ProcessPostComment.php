<?php

namespace App\Actions;

use App\Models\PostComment;
use App\Notifications\PostCommentAcknowledged;
use App\Notifications\PostCommentReceivedForAdmin;
use App\Support\PhpMailerClient;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Yeni yorum için yönetici bildirimi ve yazana alındı mailini gönderir.
 */
class ProcessPostComment
{
    public function __construct(private PhpMailerClient $mailer) {}

    public function handle(PostComment $comment): void
    {
        $comment->loadMissing('post');

        if (! $this->mailer->isConfigured()) {
            report(new \RuntimeException('Yorum kaydedildi ancak mailer ayarları eksik.'));

            return;
        }

        $adminRecipient = $this->mailer->otpRecipient(
            trim((string) SiteSettings::get('mailer_username', '')),
        );

        if ($adminRecipient === '') {
            report(new \RuntimeException('Yorum bildirimi için alıcı e-posta tanımlı değil.'));
        } else {
            try {
                Notification::route('mail', $adminRecipient)
                    ->notify(new PostCommentReceivedForAdmin($comment));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        try {
            $comment->notify(new PostCommentAcknowledged($comment));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
