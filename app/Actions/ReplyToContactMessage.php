<?php

namespace App\Actions;

use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Models\User;
use App\Notifications\ContactMessageReplySent;
use RuntimeException;

/**
 * Yönetim panelinden yazılan yanıtı ziyaretçiye e-posta olarak iletir, sonra kaydeder.
 */
class ReplyToContactMessage
{
    public function handle(ContactMessage $message, User $admin, string $body): ContactMessageReply
    {
        $body = trim($body);

        if ($body === '') {
            throw new RuntimeException('Yanıt metni boş olamaz.');
        }

        $reply = new ContactMessageReply([
            'contact_message_id' => $message->id,
            'user_id' => $admin->id,
            'body' => $body,
            'sent_at' => now(),
        ]);

        $message->notify(new ContactMessageReplySent($message, $reply));

        $reply->save();

        $message->forceFill([
            'is_read' => true,
            'replied_at' => now(),
        ])->save();

        return $reply->refresh();
    }
}
