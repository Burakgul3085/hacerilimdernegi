<?php

namespace App\Actions;

use App\Models\MembershipApplication;
use App\Notifications\MembershipApplicationAcknowledged;
use App\Notifications\MembershipApplicationReceivedForAdmin;
use App\Support\PhpMailerClient;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Kayıtlı üyelik başvurusu için yönetici bildirimi ve adaya alındı mailini gönderir.
 */
class ProcessMembershipApplication
{
    public function __construct(private PhpMailerClient $mailer) {}

    public function handle(MembershipApplication $application): void
    {
        if (! $this->mailer->isConfigured()) {
            report(new \RuntimeException('Üyelik başvurusu kaydedildi ancak mailer ayarları eksik.'));

            return;
        }

        $adminRecipient = $this->mailer->otpRecipient(
            trim((string) SiteSettings::get('mailer_username', '')),
        );

        if ($adminRecipient === '') {
            report(new \RuntimeException('Üyelik bildirimi için alıcı e-posta tanımlı değil.'));
        } else {
            try {
                Notification::route('mail', $adminRecipient)
                    ->notify(new MembershipApplicationReceivedForAdmin($application));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        try {
            $application->notify(new MembershipApplicationAcknowledged($application));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
