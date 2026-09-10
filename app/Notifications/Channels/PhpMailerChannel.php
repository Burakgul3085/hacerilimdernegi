<?php

namespace App\Notifications\Channels;

use App\Notifications\Contracts\SendsViaPhpMailer;
use App\Support\MailTemplate;
use App\Support\PhpMailerClient;
use Illuminate\Notifications\Notification;
use LogicException;

class PhpMailerChannel
{
    public function __construct(private PhpMailerClient $mailer) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof SendsViaPhpMailer) {
            throw new LogicException(sprintf(
                'Notification [%s] must implement [%s] to use the PHPMailer channel.',
                $notification::class,
                SendsViaPhpMailer::class,
            ));
        }

        $payload = $notification->toPhpMailer($notifiable);

        $this->mailer->send(
            to: $payload['to'],
            subject: $payload['subject'],
            htmlBody: $payload['html'],
            textBody: $payload['text'] ?? null,
            replyTo: $payload['reply_to'] ?? null,
            replyToName: $payload['reply_to_name'] ?? null,
            embeds: $payload['embeds'] ?? MailTemplate::embeddedImages(),
        );
    }
}
