<?php

namespace Tests\Feature;

use App\Actions\ProcessContactMessage;
use App\Actions\ReplyToContactMessage;
use App\Enums\UserRole;
use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactMessageAcknowledged;
use App\Notifications\ContactMessageReceivedForAdmin;
use App\Notifications\ContactMessageReplySent;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContactMessageMailFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SiteSettings::put('mailer_username', 'gonderici@gmail.com');
        SiteSettings::put('mailer_password', 'uygulama-sifresi');
        SiteSettings::put('mailer_otp_to', 'alici@hacer.org');
        SiteSettings::put('site_name', 'Hâcer İlim ve Kültür Derneği');
    }

    public function test_contact_form_stores_message_and_dispatches_admin_and_ack_mails(): void
    {
        Notification::fake();

        $this->post('/iletisim', [
            'name' => 'Ayşe Yılmaz',
            'email' => 'ayse@example.com',
            'phone' => '05320000000',
            'subject' => 'Program sorusu',
            'message' => 'Haftalık sohbet saati nedir?',
            'kvkk_accepted' => '1',
        ])->assertRedirect();

        $message = ContactMessage::query()->where('email', 'ayse@example.com')->firstOrFail();

        Notification::assertSentOnDemand(ContactMessageReceivedForAdmin::class);
        Notification::assertSentTo($message, ContactMessageAcknowledged::class);
    }

    public function test_process_contact_message_targets_the_otp_recipient(): void
    {
        Notification::fake();

        $message = ContactMessage::query()->create([
            'name' => 'Ali',
            'email' => 'ali@example.com',
            'message' => 'Merhaba',
            'kvkk_accepted' => true,
        ]);

        app(ProcessContactMessage::class)->handle($message);

        Notification::assertSentOnDemand(
            ContactMessageReceivedForAdmin::class,
            function (ContactMessageReceivedForAdmin $notification, array $channels, object $notifiable): bool {
                $payload = $notification->toPhpMailer($notifiable);

                return $payload['to'] === ['alici@hacer.org']
                    && ($payload['reply_to'] ?? null) === 'ali@example.com';
            },
        );
    }

    public function test_admin_reply_is_stored_and_emailed_to_the_visitor(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $message = ContactMessage::query()->create([
            'name' => 'Ayşe',
            'email' => 'ayse@example.com',
            'subject' => 'Soru',
            'message' => 'Bilgi alabilir miyim?',
            'kvkk_accepted' => true,
        ]);

        $reply = app(ReplyToContactMessage::class)->handle(
            $message,
            $admin,
            'Merhaba Ayşe, sohbetlerimiz salı 19:30’da.',
        );

        $this->assertDatabaseHas('contact_message_replies', [
            'id' => $reply->id,
            'contact_message_id' => $message->id,
            'user_id' => $admin->id,
        ]);

        $this->assertNotNull($message->fresh()->replied_at);
        $this->assertTrue($message->fresh()->is_read);

        Notification::assertSentTo($message, ContactMessageReplySent::class, function (ContactMessageReplySent $notification) use ($message): bool {
            $payload = $notification->toPhpMailer($message);

            return $payload['to'] === ['ayse@example.com']
                && str_starts_with($payload['subject'], 'Re:')
                && str_contains($payload['text'], 'salı 19:30');
        });
    }

    public function test_acknowledgement_mail_mentions_the_association(): void
    {
        $message = ContactMessage::query()->create([
            'name' => 'Zeynep',
            'email' => 'zeynep@example.com',
            'message' => 'Selam',
            'kvkk_accepted' => true,
        ]);

        $payload = (new ContactMessageAcknowledged($message))->toPhpMailer($message);

        $this->assertSame(['zeynep@example.com'], $payload['to']);
        $this->assertStringContainsString('ulaşmıştır', $payload['text']);
        $this->assertStringContainsString('En kısa zamanda dönüş', $payload['text']);
        $this->assertStringContainsString('Hâcer İlim ve Kültür Derneği', $payload['text']);
    }
}
