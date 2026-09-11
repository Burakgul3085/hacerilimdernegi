<?php

namespace Tests\Feature;

use App\Actions\SendNewsletterMail;
use App\Enums\UserRole;
use App\Filament\Resources\NewsletterSubscribers\Pages\ListNewsletterSubscribers;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Notifications\NewsletterMailSent;
use App\Support\SiteSettings;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class NewsletterMailFlowTest extends TestCase
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

    public function test_sending_to_one_subscriber_dispatches_the_templated_mail(): void
    {
        Notification::fake();

        $subscriber = NewsletterSubscriber::query()->create([
            'email' => 'ayse@example.com',
            'confirmed_at' => now(),
        ]);

        $result = app(SendNewsletterMail::class)->handle(
            collect([$subscriber]),
            'Haftalık sohbet',
            'Bu hafta Cuma 20.00’de sohbetimiz var.',
        );

        $this->assertSame(['sent' => 1, 'failed' => 0], $result);

        Notification::assertSentTo($subscriber, NewsletterMailSent::class, function (NewsletterMailSent $notification) use ($subscriber): bool {
            $payload = $notification->toPhpMailer($subscriber);

            return $payload['to'] === ['ayse@example.com']
                && $payload['subject'] === 'Haftalık sohbet'
                && str_contains($payload['text'], 'Cuma 20.00');
        });
    }

    public function test_sending_to_several_subscribers_mails_each_address_separately(): void
    {
        Notification::fake();

        $first = NewsletterSubscriber::query()->create([
            'email' => 'bir@example.com',
            'confirmed_at' => now(),
        ]);
        $second = NewsletterSubscriber::query()->create([
            'email' => 'iki@example.com',
            'confirmed_at' => now(),
        ]);
        $third = NewsletterSubscriber::query()->create([
            'email' => 'uc@example.com',
            'confirmed_at' => now(),
        ]);

        $result = app(SendNewsletterMail::class)->handle(
            collect([$first, $second]),
            'Duyuru',
            'Yarın programımız var.',
        );

        $this->assertSame(['sent' => 2, 'failed' => 0], $result);

        Notification::assertSentTo($first, NewsletterMailSent::class);
        Notification::assertSentTo($second, NewsletterMailSent::class);
        Notification::assertNotSentTo($third, NewsletterMailSent::class);
    }

    public function test_empty_subject_or_body_is_rejected_without_sending(): void
    {
        Notification::fake();

        $subscriber = NewsletterSubscriber::query()->create([
            'email' => 'ayse@example.com',
            'confirmed_at' => now(),
        ]);

        try {
            app(SendNewsletterMail::class)->handle(collect([$subscriber]), '  ', 'Metin');
            $this->fail('An empty subject should be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Konu ve metin zorunludur.', $exception->getMessage());
        }

        Notification::assertNothingSent();
    }

    public function test_newsletter_mail_escapes_html_in_the_body(): void
    {
        $subscriber = NewsletterSubscriber::query()->create([
            'email' => 'xss@example.com',
            'confirmed_at' => now(),
        ]);

        $html = (new NewsletterMailSent(
            'Duyuru',
            "<script>alert('xss')</script>",
        ))->toPhpMailer($subscriber)['html'];

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString("<script>alert('xss')</script>", $html);
    }

    public function test_editors_can_send_mail_to_one_subscriber_from_the_list(): void
    {
        Notification::fake();

        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $subscriber = NewsletterSubscriber::query()->create([
            'email' => 'uye@example.com',
            'confirmed_at' => now(),
        ]);

        Livewire::test(ListNewsletterSubscribers::class)
            ->callAction(TestAction::make('sendMail')->table($subscriber), [
                'subject' => 'Haftalık sohbet',
                'body' => 'Cuma akşamı derneğimizdeyiz.',
            ])
            ->assertNotified();

        Notification::assertSentTo($subscriber, NewsletterMailSent::class);
    }

    public function test_editors_can_bulk_send_mail_to_selected_subscribers(): void
    {
        Notification::fake();

        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $first = NewsletterSubscriber::query()->create([
            'email' => 'bir@example.com',
            'confirmed_at' => now(),
        ]);
        $second = NewsletterSubscriber::query()->create([
            'email' => 'iki@example.com',
            'confirmed_at' => now(),
        ]);
        $skipped = NewsletterSubscriber::query()->create([
            'email' => 'uc@example.com',
            'confirmed_at' => now(),
        ]);

        Livewire::test(ListNewsletterSubscribers::class)
            ->selectTableRecords([$first, $second])
            ->callAction(TestAction::make('sendMail')->table()->bulk(), [
                'subject' => 'Duyuru',
                'body' => 'Yarın programımız var.',
            ])
            ->assertNotified();

        Notification::assertSentTo($first, NewsletterMailSent::class);
        Notification::assertSentTo($second, NewsletterMailSent::class);
        Notification::assertNotSentTo($skipped, NewsletterMailSent::class);
    }

    public function test_editors_can_see_the_send_all_mail_action(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        NewsletterSubscriber::query()->create([
            'email' => 'uye@example.com',
            'confirmed_at' => now(),
        ]);

        Livewire::test(ListNewsletterSubscribers::class)
            ->assertOk()
            ->assertActionVisible(TestAction::make('sendAllMail')->table());
    }
}
