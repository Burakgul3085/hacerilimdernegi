<?php

namespace Tests\Feature;

use App\Actions\NotifyVisitorPostApproved;
use App\Actions\ProcessVisitorPost;
use App\Models\Post;
use App\Notifications\VisitorPostAcknowledged;
use App\Notifications\VisitorPostApproved;
use App\Notifications\VisitorPostReceivedForAdmin;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PostMailFlowTest extends TestCase
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

    public function test_process_visitor_post_targets_the_otp_recipient(): void
    {
        Notification::fake();

        $post = $this->makePendingVisitorPost();

        app(ProcessVisitorPost::class)->handle($post);

        Notification::assertSentOnDemand(
            VisitorPostReceivedForAdmin::class,
            function (VisitorPostReceivedForAdmin $notification, array $channels, object $notifiable): bool {
                $payload = $notification->toPhpMailer($notifiable);

                return $payload['to'] === ['alici@hacer.org']
                    && ($payload['reply_to'] ?? null) === 'ayse@example.com';
            },
        );

        Notification::assertSentTo($post, VisitorPostAcknowledged::class);
    }

    public function test_acknowledgement_mail_says_the_piece_will_be_published_soon(): void
    {
        $post = $this->makePendingVisitorPost([
            'type' => 'poem',
            'title' => 'Sabah duası',
        ]);

        $payload = (new VisitorPostAcknowledged($post))->toPhpMailer($post);

        $this->assertSame(['ayse@example.com'], $payload['to']);
        $this->assertSame('Şiiriniz bize ulaştı', $payload['subject']);
        $this->assertStringContainsString('ulaşmıştır', $payload['text']);
        $this->assertStringContainsString('En kısa zamanda yayımlanacaktır', $payload['text']);
        $this->assertStringContainsString('Hâcer İlim ve Kültür Derneği', $payload['text']);
    }

    public function test_approving_a_visitor_post_sends_the_approval_mail_once(): void
    {
        Notification::fake();

        $post = $this->makePendingVisitorPost();

        $post->forceFill([
            'is_published' => true,
            'published_at' => now(),
        ])->save();

        app(NotifyVisitorPostApproved::class)->handle($post);

        Notification::assertSentTo($post, VisitorPostApproved::class, function (VisitorPostApproved $notification) use ($post): bool {
            $payload = $notification->toPhpMailer($post);

            return $payload['to'] === ['ayse@example.com']
                && $payload['subject'] === 'Yazınız onaylanmıştır'
                && str_contains($payload['text'], 'onaylanmıştır')
                && str_contains($payload['text'], $post->publicUrl());
        });

        $this->assertNotNull($post->fresh()->approval_notified_at);

        Notification::fake();
        app(NotifyVisitorPostApproved::class)->handle($post->fresh());
        Notification::assertNothingSent();
    }

    public function test_approval_mail_is_not_sent_for_panel_authored_posts(): void
    {
        Notification::fake();

        $post = Post::query()->create([
            'type' => 'article',
            'title' => 'Panel yazısı',
            'slug' => 'panel-yazisi',
            'body' => '<p>Gövde</p>',
            'author_name' => 'Editör',
            'submitted_from_public' => false,
            'is_published' => true,
            'published_at' => now(),
        ]);

        app(NotifyVisitorPostApproved::class)->handle($post);

        Notification::assertNothingSent();
        $this->assertNull($post->fresh()->approval_notified_at);
    }

    public function test_saving_an_unpublished_visitor_post_does_not_send_approval_mail(): void
    {
        Notification::fake();

        $post = $this->makePendingVisitorPost();

        app(NotifyVisitorPostApproved::class)->handle($post);

        Notification::assertNothingSent();
        $this->assertNull($post->fresh()->approval_notified_at);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makePendingVisitorPost(array $attributes = []): Post
    {
        return Post::query()->create([
            'type' => 'article',
            'title' => 'İlim ve sohbet',
            'slug' => 'ilim-ve-sohbet',
            'excerpt' => 'Kısa özet',
            'body' => '<p>Yazı gövdesi</p>',
            'author_name' => 'Ayşe Yılmaz',
            'submitter_email' => 'ayse@example.com',
            'submitted_from_public' => true,
            'is_published' => false,
            'published_at' => null,
            ...$attributes,
        ]);
    }
}
