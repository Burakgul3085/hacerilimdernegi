<?php

namespace Tests\Feature;

use App\Actions\ProcessEventRegistration;
use App\Actions\ReplyToEventRegistration;
use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Notifications\EventRegistrationAcknowledged;
use App\Notifications\EventRegistrationReceivedForAdmin;
use App\Notifications\EventRegistrationReplySent;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EventRegistrationMailFlowTest extends TestCase
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

    public function test_event_form_stores_registration_and_dispatches_admin_and_ack_mails(): void
    {
        Notification::fake();

        $event = $this->makeEvent();

        $this->post(route('events.register', $event), [
            'name' => 'Ayşe Yılmaz',
            'email' => 'ayse@example.com',
            'phone' => '05320000000',
            'notes' => 'Katılmak istiyorum.',
            'kvkk_accepted' => '1',
        ])->assertRedirect()->assertSessionHas('status', 'Katılım başvurunuz alındı. Size de bir onay e-postası gönderdik.');

        $registration = EventRegistration::query()->where('email', 'ayse@example.com')->firstOrFail();

        $this->assertSame(ApplicationStatus::Pending, $registration->status);
        $this->assertSame($event->id, $registration->event_id);
        $this->assertDatabaseHas('event_registrations', [
            'email' => 'ayse@example.com',
            'name' => 'Ayşe Yılmaz',
            'event_id' => $event->id,
        ]);

        Notification::assertSentOnDemand(EventRegistrationReceivedForAdmin::class);
        Notification::assertSentTo($registration, EventRegistrationAcknowledged::class);
    }

    public function test_event_form_rejects_an_empty_payload(): void
    {
        Notification::fake();

        $event = $this->makeEvent();

        $this->from(route('events.show', $event))->post(route('events.register', $event), [])
            ->assertRedirect(route('events.show', $event))
            ->assertSessionHasErrors(['name', 'email', 'kvkk_accepted']);

        $this->assertDatabaseCount('event_registrations', 0);
        Notification::assertNothingSent();
    }

    public function test_process_event_registration_targets_the_otp_recipient(): void
    {
        Notification::fake();

        $registration = $this->makeRegistration();

        app(ProcessEventRegistration::class)->handle($registration);

        Notification::assertSentOnDemand(
            EventRegistrationReceivedForAdmin::class,
            function (EventRegistrationReceivedForAdmin $notification, array $channels, object $notifiable): bool {
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
        $registration = $this->makeRegistration([
            'name' => 'Ayşe',
            'email' => 'ayse@example.com',
            'notes' => 'Katılmak istiyorum.',
        ]);

        $reply = app(ReplyToEventRegistration::class)->handle(
            $registration,
            $admin,
            'Merhaba Ayşe, yeriniz ayrıldı.',
        );

        $this->assertDatabaseHas('event_registration_replies', [
            'id' => $reply->id,
            'event_registration_id' => $registration->id,
            'user_id' => $admin->id,
        ]);

        $this->assertNotNull($registration->fresh()->replied_at);

        Notification::assertSentTo($registration, EventRegistrationReplySent::class, function (EventRegistrationReplySent $notification) use ($registration): bool {
            $payload = $notification->toPhpMailer($registration);

            return $payload['to'] === ['ayse@example.com']
                && str_starts_with($payload['subject'], 'Re:')
                && str_contains($payload['text'], 'yeriniz ayrıldı');
        });
    }

    public function test_acknowledgement_mail_tells_the_applicant_the_form_was_received(): void
    {
        $registration = $this->makeRegistration([
            'name' => 'Zeynep',
            'email' => 'zeynep@example.com',
        ]);

        $payload = (new EventRegistrationAcknowledged($registration))->toPhpMailer($registration);

        $this->assertSame(['zeynep@example.com'], $payload['to']);
        $this->assertStringContainsString('alınmıştır', $payload['text']);
        $this->assertStringContainsString('Dönem açılış programı', $payload['text']);
        $this->assertStringContainsString('Hâcer İlim ve Kültür Derneği', $payload['text']);
    }

    public function test_event_registration_mails_escape_applicant_content(): void
    {
        $registration = $this->makeRegistration([
            'name' => "<script>alert('xss')</script>",
            'email' => 'xss@example.com',
            'notes' => '<img src=x onerror=alert(1)>',
        ]);

        $ackHtml = (new EventRegistrationAcknowledged($registration))->toPhpMailer($registration)['html'];
        $adminHtml = (new EventRegistrationReceivedForAdmin($registration))->toPhpMailer($registration)['html'];

        $this->assertStringContainsString('&lt;script&gt;', $ackHtml);
        $this->assertStringNotContainsString("<script>alert('xss')</script>", $ackHtml);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $adminHtml);
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $adminHtml);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeRegistration(array $attributes = []): EventRegistration
    {
        return EventRegistration::query()->create([
            'event_id' => $this->makeEvent()->id,
            'name' => 'Ali',
            'email' => 'ali@example.com',
            'notes' => 'Katılmak istiyorum.',
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
            ...$attributes,
        ]);
    }

    private function makeEvent(): Event
    {
        return Event::query()->create([
            'title' => 'Dönem açılış programı',
            'slug' => 'acilis-programi-'.fake()->unique()->numerify('###'),
            'location' => 'Karacaahmet, Şehitkamil / Gaziantep',
            'registration_open' => true,
            'is_published' => true,
        ]);
    }
}
