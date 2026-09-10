<?php

namespace App\Actions;

use App\Models\MembershipApplication;
use App\Models\MembershipApplicationReply;
use App\Models\User;
use App\Notifications\MembershipApplicationReplySent;
use RuntimeException;

/**
 * Yönetim panelinden yazılan yanıtı başvurana e-posta olarak iletir, sonra kaydeder.
 */
class ReplyToMembershipApplication
{
    public function handle(MembershipApplication $application, User $admin, string $body): MembershipApplicationReply
    {
        $body = trim($body);

        if ($body === '') {
            throw new RuntimeException('Yanıt metni boş olamaz.');
        }

        $reply = new MembershipApplicationReply([
            'membership_application_id' => $application->id,
            'user_id' => $admin->id,
            'body' => $body,
            'sent_at' => now(),
        ]);

        $application->notify(new MembershipApplicationReplySent($application, $reply));

        $reply->save();

        $application->forceFill([
            'replied_at' => now(),
        ])->save();

        return $reply->refresh();
    }
}
