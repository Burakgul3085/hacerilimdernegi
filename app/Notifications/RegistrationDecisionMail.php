<?php

namespace App\Notifications;

use App\Notifications\Channels\PhpMailerChannel;
use App\Notifications\Contracts\SendsViaPhpMailer;
use Illuminate\Notifications\Notification;

/**
 * Faaliyet şablonundan üretilmiş onay veya ret e-postasını başvurana iletir.
 */
class RegistrationDecisionMail extends Notification implements SendsViaPhpMailer
{
    /**
     * @param  array{subject: string, html: string, text: string}  $mail
     */
    public function __construct(public array $mail) {}

    /**
     * @return list<class-string>
     */
    public function via(object $notifiable): array
    {
        return [PhpMailerChannel::class];
    }

    public function toPhpMailer(object $notifiable): array
    {
        return [
            'to' => [(string) $notifiable->routeNotificationFor('mail', $this)],
            'subject' => $this->mail['subject'],
            'html' => $this->mail['html'],
            'text' => $this->mail['text'],
        ];
    }
}
