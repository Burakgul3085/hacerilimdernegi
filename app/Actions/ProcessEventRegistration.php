<?php

namespace App\Actions;

use App\Models\EventRegistration;
use App\Notifications\EventRegistrationAcknowledged;
use App\Notifications\EventRegistrationReceivedForAdmin;
use App\Support\PhpMailerClient;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Kayıtlı etkinlik başvurusu için yönetici bildirimi ve adaya alındı mailini gönderir.
 */
class ProcessEventRegistration
{
    public function __construct(private PhpMailerClient $mailer) {}

    public function handle(EventRegistration $registration): void
    {
        $registration->loadMissing('event');

        if (! $this->mailer->isConfigured()) {
            report(new \RuntimeException('Etkinlik başvurusu kaydedildi ancak mailer ayarları eksik.'));

            return;
        }

        $adminRecipient = $this->mailer->otpRecipient(
            trim((string) SiteSettings::get('mailer_username', '')),
        );

        if ($adminRecipient === '') {
            report(new \RuntimeException('Etkinlik bildirimi için alıcı e-posta tanımlı değil.'));
        } else {
            try {
                Notification::route('mail', $adminRecipient)
                    ->notify(new EventRegistrationReceivedForAdmin($registration));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        try {
            $registration->notify(new EventRegistrationAcknowledged($registration));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
