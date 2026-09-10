<?php

namespace App\Actions;

use App\Models\ContactMessage;
use App\Notifications\ContactMessageAcknowledged;
use App\Notifications\ContactMessageReceivedForAdmin;
use App\Support\PhpMailerClient;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Kayıtlı iletişim mesajı için yönetici bildirimi ve ziyaretçi alındı mailini gönderir.
 */
class ProcessContactMessage
{
    public function __construct(private PhpMailerClient $mailer) {}

    public function handle(ContactMessage $message): void
    {
        if (! $this->mailer->isConfigured()) {
            report(new \RuntimeException('İletişim mesajı kaydedildi ancak mailer ayarları eksik.'));

            return;
        }

        $adminRecipient = $this->mailer->otpRecipient(
            trim((string) SiteSettings::get('mailer_username', '')),
        );

        if ($adminRecipient === '') {
            report(new \RuntimeException('İletişim bildirimi için alıcı e-posta tanımlı değil.'));
        } else {
            try {
                Notification::route('mail', $adminRecipient)
                    ->notify(new ContactMessageReceivedForAdmin($message));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        try {
            $message->notify(new ContactMessageAcknowledged($message));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
