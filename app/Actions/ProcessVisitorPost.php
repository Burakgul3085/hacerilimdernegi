<?php

namespace App\Actions;

use App\Models\Post;
use App\Notifications\VisitorPostAcknowledged;
use App\Notifications\VisitorPostReceivedForAdmin;
use App\Support\PhpMailerClient;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Ziyaretçi yazısı için yönetici bildirimi ve gönderene alındı mailini gönderir.
 */
class ProcessVisitorPost
{
    public function __construct(private PhpMailerClient $mailer) {}

    public function handle(Post $post): void
    {
        if (! $this->mailer->isConfigured()) {
            report(new \RuntimeException('Yazı kaydedildi ancak mailer ayarları eksik.'));

            return;
        }

        $adminRecipient = $this->mailer->otpRecipient(
            trim((string) SiteSettings::get('mailer_username', '')),
        );

        if ($adminRecipient === '') {
            report(new \RuntimeException('Yazı bildirimi için alıcı e-posta tanımlı değil.'));
        } else {
            try {
                Notification::route('mail', $adminRecipient)
                    ->notify(new VisitorPostReceivedForAdmin($post));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        try {
            $post->notify(new VisitorPostAcknowledged($post));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
