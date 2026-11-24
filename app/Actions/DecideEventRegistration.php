<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Enums\DecisionMailState;
use App\Jobs\SendRegistrationDecisionMail;
use App\Models\EventRegistration;
use App\Support\DecisionMail;
use Throwable;

/**
 * Başvuruyu onaylar veya reddeder; faaliyetin şablonuyla karar e-postasını kuyruğa alır.
 */
class DecideEventRegistration
{
    /**
     * Durumu kaydeder ve istenirse e-postayı gönderir. E-posta kuyruğa alındıysa true döner.
     */
    public function handle(EventRegistration $registration, ApplicationStatus $status, bool $sendMail = true): bool
    {
        $registration->update(['status' => $status]);

        if (! $sendMail || $this->mailBlocker($registration, $status) !== null) {
            return false;
        }

        return $this->queue($registration, $status);
    }

    /**
     * Başvurunun mevcut kararına ait e-postayı yeniden gönderir.
     */
    public function resend(EventRegistration $registration): bool
    {
        $status = $registration->status;

        if ($status === ApplicationStatus::Pending || ! $this->templateIsEnabled($registration, $status)) {
            return false;
        }

        return $this->queue($registration, $status);
    }

    /**
     * Bu karar için e-posta neden gönderilmeyecek; gönderilecekse null.
     */
    public function mailBlocker(EventRegistration $registration, ApplicationStatus $status): ?string
    {
        $mailName = $status === ApplicationStatus::Approved ? 'onay' : 'ret';

        if (! $this->templateIsEnabled($registration, $status)) {
            return "Bu faaliyette {$mailName} e-postası kapalı; e-posta gönderilmeyecek.";
        }

        if ($registration->decision_mail_status === $status
            && in_array($registration->decision_mail_state, [DecisionMailState::Queued, DecisionMailState::Sent], true)) {
            return "Bu kişiye {$mailName} e-postası daha önce gönderildi; tekrar gönderilmeyecek.";
        }

        return null;
    }

    public function templateIsEnabled(EventRegistration $registration, ApplicationStatus $status): bool
    {
        if ($status === ApplicationStatus::Pending) {
            return false;
        }

        $registration->loadMissing('activity');

        return DecisionMail::settingsFor($registration->activity, $status)['enabled'];
    }

    private function queue(EventRegistration $registration, ApplicationStatus $status): bool
    {
        $registration->forceFill([
            'decision_mail_status' => $status,
            'decision_mail_state' => DecisionMailState::Queued,
            'decision_mail_at' => now(),
        ])->saveQuietly();

        try {
            SendRegistrationDecisionMail::dispatch($registration, $status);
        } catch (Throwable $exception) {
            report($exception);

            $registration->forceFill([
                'decision_mail_state' => DecisionMailState::Failed,
                'decision_mail_at' => now(),
            ])->saveQuietly();

            return false;
        }

        return true;
    }
}
