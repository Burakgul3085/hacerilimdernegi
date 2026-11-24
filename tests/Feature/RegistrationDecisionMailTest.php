<?php

namespace Tests\Feature;

use App\Actions\DecideEventRegistration;
use App\Enums\ApplicationStatus;
use App\Enums\DecisionMailState;
use App\Enums\UserRole;
use App\Filament\Resources\Activities\Pages\EditActivity;
use App\Filament\Resources\EventRegistrations\Pages\EditEventRegistration;
use App\Filament\Resources\EventRegistrations\Pages\ListActivityRegistrations;
use App\Jobs\SendRegistrationDecisionMail;
use App\Models\Activity;
use App\Models\EventRegistration;
use App\Models\User;
use App\Notifications\RegistrationDecisionMail;
use App\Support\DecisionMail;
use App\Support\MailRichText;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class RegistrationDecisionMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_mail_uses_the_activity_template_and_whatsapp_link(): void
    {
        Notification::fake();

        $activity = Activity::factory()->create([
            'title' => 'Riyâzü’s-Sâlihîn Dersleri',
            'approval_mail_subject' => 'Kaydınız tamam',
            'approval_mail_body' => '<p>Cumartesi <strong>14.00</strong> dersinde görüşmek üzere.</p><script>alert(1)</script>',
            'approval_mail_button_label' => 'Konumu aç',
            'approval_mail_button_url' => 'https://maps.example.com/hacer',
            'whatsapp_group_enabled' => true,
            'whatsapp_group_url' => 'https://chat.whatsapp.com/AbCdEfGhIjKlMn',
        ]);
        $registration = $this->registrationFor($activity);

        $queued = app(DecideEventRegistration::class)->handle($registration, ApplicationStatus::Approved);

        $this->assertTrue($queued);
        $this->assertSame(ApplicationStatus::Approved, $registration->fresh()->status);
        $this->assertSame(DecisionMailState::Sent, $registration->fresh()->decision_mail_state);

        Notification::assertSentTo($registration, RegistrationDecisionMail::class, function (RegistrationDecisionMail $notification) use ($registration): bool {
            $mail = $notification->toPhpMailer($registration);

            return $mail['subject'] === 'Kaydınız tamam'
                && str_contains($mail['html'], 'Merhaba Kübra Selma Öz,')
                && str_contains($mail['html'], 'Riyâzü’s-Sâlihîn Dersleri')
                && str_contains($mail['html'], '14.00')
                && ! str_contains($mail['html'], '<script')
                && str_contains($mail['html'], 'https://maps.example.com/hacer')
                && str_contains($mail['html'], 'https://chat.whatsapp.com/AbCdEfGhIjKlMn')
                && str_contains($mail['text'], 'https://chat.whatsapp.com/AbCdEfGhIjKlMn');
        });
    }

    public function test_rejection_mail_falls_back_to_the_default_text_and_respects_whatsapp_placement(): void
    {
        Notification::fake();

        $activity = Activity::factory()->create([
            'rejection_mail_body' => '<p></p>',
            'whatsapp_group_enabled' => true,
            'whatsapp_group_url' => 'https://chat.whatsapp.com/AbCdEfGhIjKlMn',
            'whatsapp_group_in_rejection' => false,
        ]);
        $registration = $this->registrationFor($activity);

        app(DecideEventRegistration::class)->handle($registration, ApplicationStatus::Rejected);

        Notification::assertSentTo($registration, RegistrationDecisionMail::class, function (RegistrationDecisionMail $notification) use ($registration, $activity): bool {
            $mail = $notification->toPhpMailer($registration);

            return $mail['subject'] === DecisionMail::defaultSubject(ApplicationStatus::Rejected, $activity->title)
                && str_contains($mail['html'], 'Kontenjanımız dolduğu için')
                && ! str_contains($mail['html'], 'chat.whatsapp.com');
        });
    }

    public function test_the_same_decision_mail_is_not_sent_twice_but_a_new_decision_is(): void
    {
        Queue::fake();

        $registration = $this->registrationFor(Activity::factory()->create());
        $decider = app(DecideEventRegistration::class);

        $this->assertTrue($decider->handle($registration, ApplicationStatus::Rejected));
        $this->assertFalse($decider->handle($registration, ApplicationStatus::Rejected));
        $this->assertTrue($decider->handle($registration, ApplicationStatus::Approved));

        Queue::assertPushed(SendRegistrationDecisionMail::class, 2);
    }

    public function test_disabled_template_or_unchecked_box_changes_only_the_status(): void
    {
        Queue::fake();

        $registration = $this->registrationFor(Activity::factory()->create(['approval_mail_enabled' => false]));
        $other = $this->registrationFor(Activity::factory()->create(), 'diger@example.com');

        $this->assertFalse(app(DecideEventRegistration::class)->handle($registration, ApplicationStatus::Approved));
        $this->assertFalse(app(DecideEventRegistration::class)->handle($other, ApplicationStatus::Approved, sendMail: false));

        $this->assertSame(ApplicationStatus::Approved, $registration->fresh()->status);
        $this->assertSame(ApplicationStatus::Approved, $other->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_failed_delivery_is_marked_so_it_can_be_resent(): void
    {
        $registration = $this->registrationFor(Activity::factory()->create());
        $registration->update(['status' => ApplicationStatus::Approved]);

        (new SendRegistrationDecisionMail($registration, ApplicationStatus::Approved))->failed(new RuntimeException('SMTP kapalı'));

        $this->assertSame(DecisionMailState::Failed, $registration->fresh()->decision_mail_state);
        $this->assertSame('Onay e-postası gönderilemedi', $registration->fresh()->decisionMailLabel());

        Queue::fake();
        $this->assertTrue(app(DecideEventRegistration::class)->resend($registration->fresh()));
        Queue::assertPushed(SendRegistrationDecisionMail::class);
    }

    public function test_admin_approves_from_the_registration_page(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $registration = $this->registrationFor(Activity::factory()->create());

        Livewire::test(EditEventRegistration::class, ['record' => $registration->getKey()])
            ->callAction('approve', ['send_mail' => true])
            ->assertNotified('Başvuru onaylandı');

        $this->assertSame(ApplicationStatus::Approved, $registration->fresh()->status);
        Queue::assertPushed(SendRegistrationDecisionMail::class);
    }

    public function test_admin_rejects_selected_registrations_in_bulk(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $activity = Activity::factory()->create();
        $first = $this->registrationFor($activity, 'bir@example.com');
        $second = $this->registrationFor($activity, 'iki@example.com');
        $untouched = $this->registrationFor($activity, 'uc@example.com');

        Livewire::test(ListActivityRegistrations::class, ['activity' => $activity->getKey()])
            ->selectTableRecords([$first, $second])
            ->callAction(TestAction::make('bulkReject')->table()->bulk(), ['send_mail' => true])
            ->assertNotified();

        $this->assertSame(ApplicationStatus::Rejected, $first->fresh()->status);
        $this->assertSame(ApplicationStatus::Rejected, $second->fresh()->status);
        $this->assertSame(ApplicationStatus::Pending, $untouched->fresh()->status);
        Queue::assertPushed(SendRegistrationDecisionMail::class, 2);
    }

    public function test_activity_page_saves_mail_settings_and_rejects_a_wrong_whatsapp_link(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $activity = Activity::factory()->create();

        Livewire::test(EditActivity::class, ['record' => $activity->getRouteKey()])
            ->fillForm([
                'whatsapp_group_enabled' => true,
                'whatsapp_group_url' => 'https://example.com/grup',
            ])
            ->call('save')
            ->assertHasFormErrors(['whatsapp_group_url']);

        Livewire::test(EditActivity::class, ['record' => $activity->getRouteKey()])
            ->assertFormSet([
                'approval_mail_subject' => DecisionMail::defaultSubject(ApplicationStatus::Approved, $activity->title),
            ])
            ->fillForm([
                'approval_mail_subject' => 'Hoş geldiniz',
                'whatsapp_group_enabled' => true,
                'whatsapp_group_url' => 'https://chat.whatsapp.com/AbCdEfGhIjKlMn',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $activity->refresh();

        $this->assertSame('Hoş geldiniz', $activity->approval_mail_subject);
        $this->assertTrue($activity->whatsapp_group_enabled);
        $this->assertStringContainsString('katılımınız onaylanmıştır', (string) $activity->approval_mail_body);
    }

    public function test_rich_text_is_reduced_to_safe_email_markup(): void
    {
        $html = MailRichText::toEmailHtml('<p onclick="x()">Merhaba <a href="javascript:alert(1)">tıkla</a> <a href="https://hacer.org">site</a></p><iframe src="x"></iframe>');

        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringContainsString('href="https://hacer.org"', $html);
    }

    private function registrationFor(Activity $activity, string $email = 'kubra@example.com'): EventRegistration
    {
        return EventRegistration::query()->create([
            'activity_id' => $activity->id,
            'name' => 'Kübra Selma Öz',
            'email' => $email,
            'kvkk_accepted' => true,
            'status' => ApplicationStatus::Pending,
        ]);
    }
}
