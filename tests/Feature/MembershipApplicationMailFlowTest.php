<?php

namespace Tests\Feature;

use App\Actions\ProcessMembershipApplication;
use App\Actions\ReplyToMembershipApplication;
use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\MembershipApplication;
use App\Models\User;
use App\Notifications\MembershipApplicationAcknowledged;
use App\Notifications\MembershipApplicationReceivedForAdmin;
use App\Notifications\MembershipApplicationReplySent;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MembershipApplicationMailFlowTest extends TestCase
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

    public function test_membership_form_stores_application_and_dispatches_admin_and_ack_mails(): void
    {
        Notification::fake();

        $this->post('/uyelik', [
            'name' => 'Ayşe Yılmaz',
            'email' => 'ayse@example.com',
            'phone' => '05320000000',
            'city' => 'Gaziantep',
            'message' => 'Gönüllü olmak istiyorum.',
            'kvkk_accepted' => '1',
        ])->assertRedirect()->assertSessionHas('status', 'Başvurunuz iletildi. Size de bir onay e-postası gönderdik.');

        $application = MembershipApplication::query()->where('email', 'ayse@example.com')->firstOrFail();

        $this->assertSame(ApplicationStatus::Pending, $application->status);
        $this->assertDatabaseHas('membership_applications', [
            'email' => 'ayse@example.com',
            'name' => 'Ayşe Yılmaz',
        ]);

        Notification::assertSentOnDemand(MembershipApplicationReceivedForAdmin::class);
        Notification::assertSentTo($application, MembershipApplicationAcknowledged::class);
    }

    public function test_membership_form_rejects_an_empty_payload(): void
    {
        Notification::fake();

        $this->from('/uyelik')->post('/uyelik', [])
            ->assertRedirect('/uyelik')
            ->assertSessionHasErrors(['name', 'email', 'kvkk_accepted']);

        $this->assertDatabaseCount('membership_applications', 0);
        Notification::assertNothingSent();
    }

    public function test_process_membership_application_targets_the_otp_recipient(): void
    {
        Notification::fake();

        $application = MembershipApplication::query()->create([
            'name' => 'Ali',
            'email' => 'ali@example.com',
            'city' => 'Gaziantep',
            'message' => 'Katılmak istiyorum.',
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        app(ProcessMembershipApplication::class)->handle($application);

        Notification::assertSentOnDemand(
            MembershipApplicationReceivedForAdmin::class,
            function (MembershipApplicationReceivedForAdmin $notification, array $channels, object $notifiable): bool {
                $payload = $notification->toPhpMailer($notifiable);

                return $payload['to'] === ['alici@hacer.org']
                    && ($payload['reply_to'] ?? null) === 'ali@example.com';
            },
        );
    }

    public function test_admin_reply_is_stored_and_emailed_to_the_applicant(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $application = MembershipApplication::query()->create([
            'name' => 'Ayşe',
            'email' => 'ayse@example.com',
            'message' => 'Gönüllü olmak istiyorum.',
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        $reply = app(ReplyToMembershipApplication::class)->handle(
            $application,
            $admin,
            'Merhaba Ayşe, başvurunuz onay sürecine alındı.',
        );

        $this->assertDatabaseHas('membership_application_replies', [
            'id' => $reply->id,
            'membership_application_id' => $application->id,
            'user_id' => $admin->id,
        ]);

        $this->assertNotNull($application->fresh()->replied_at);

        Notification::assertSentTo($application, MembershipApplicationReplySent::class, function (MembershipApplicationReplySent $notification) use ($application): bool {
            $payload = $notification->toPhpMailer($application);

            return $payload['to'] === ['ayse@example.com']
                && str_starts_with($payload['subject'], 'Re:')
                && str_contains($payload['text'], 'onay sürecine alındı');
        });
    }

    public function test_acknowledgement_mail_tells_the_applicant_the_form_was_forwarded(): void
    {
        $application = MembershipApplication::query()->create([
            'name' => 'Zeynep',
            'email' => 'zeynep@example.com',
            'message' => 'Selam',
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        $payload = (new MembershipApplicationAcknowledged($application))->toPhpMailer($application);

        $this->assertSame(['zeynep@example.com'], $payload['to']);
        $this->assertStringContainsString('iletilmiştir', $payload['text']);
        $this->assertStringContainsString('Hâcer İlim ve Kültür Derneği', $payload['text']);
    }

    public function test_membership_mails_escape_applicant_content(): void
    {
        $application = MembershipApplication::query()->create([
            'name' => "<script>alert('xss')</script>",
            'email' => 'xss@example.com',
            'message' => '<img src=x onerror=alert(1)>',
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);

        $ackHtml = (new MembershipApplicationAcknowledged($application))->toPhpMailer($application)['html'];
        $adminHtml = (new MembershipApplicationReceivedForAdmin($application))->toPhpMailer($application)['html'];

        $this->assertStringContainsString('&lt;script&gt;', $ackHtml);
        $this->assertStringNotContainsString("<script>alert('xss')</script>", $ackHtml);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $adminHtml);
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $adminHtml);
    }
}
