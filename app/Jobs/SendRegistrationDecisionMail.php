<?php

namespace App\Jobs;

use App\Enums\ApplicationStatus;
use App\Enums\DecisionMailState;
use App\Models\EventRegistration;
use App\Notifications\RegistrationDecisionMail;
use App\Support\DecisionMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Onay veya ret e-postasını arka planda gönderir ve sonucu başvuruya işler.
 */
class SendRegistrationDecisionMail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [30, 120];

    public function __construct(
        public EventRegistration $registration,
        public ApplicationStatus $status,
    ) {}

    public function handle(): void
    {
        $registration = $this->registration->fresh(['activity', 'program', 'event']);

        if ($registration === null) {
            return;
        }

        if ($registration->status !== $this->status) {
            $registration->forceFill([
                'decision_mail_status' => null,
                'decision_mail_state' => null,
                'decision_mail_at' => null,
            ])->saveQuietly();

            return;
        }

        $activityTitle = $registration->subjectTitle();
        $mail = DecisionMail::compose(
            DecisionMail::settingsFor($registration->activity, $this->status),
            $this->status,
            $registration->name,
            $activityTitle,
        );

        $registration->notify(new RegistrationDecisionMail($mail));

        $registration->forceFill([
            'decision_mail_status' => $this->status,
            'decision_mail_state' => DecisionMailState::Sent,
            'decision_mail_at' => now(),
        ])->saveQuietly();
    }

    public function failed(?Throwable $exception): void
    {
        $this->registration->forceFill([
            'decision_mail_status' => $this->status,
            'decision_mail_state' => DecisionMailState::Failed,
            'decision_mail_at' => now(),
        ])->saveQuietly();
    }
}
