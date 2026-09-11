<?php

namespace App\Actions;

use App\Models\EventRegistration;
use App\Models\EventRegistrationReply;
use App\Models\User;
use App\Notifications\EventRegistrationReplySent;
use RuntimeException;

/**
 * Yönetim panelinden yazılan yanıtı başvurana e-posta olarak iletir, sonra kaydeder.
 */
class ReplyToEventRegistration
{
    public function handle(EventRegistration $registration, User $admin, string $body): EventRegistrationReply
    {
        $body = trim($body);

        if ($body === '') {
            throw new RuntimeException('Yanıt metni boş olamaz.');
        }

        $registration->loadMissing('event');

        $reply = new EventRegistrationReply([
            'event_registration_id' => $registration->id,
            'user_id' => $admin->id,
            'body' => $body,
            'sent_at' => now(),
        ]);

        $registration->notify(new EventRegistrationReplySent($registration, $reply));

        $reply->save();

        $registration->forceFill([
            'replied_at' => now(),
        ])->save();

        return $reply->refresh();
    }
}
