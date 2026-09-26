<?php

namespace Tests\Feature;

use App\Actions\NotifyPostCommentApproved;
use App\Enums\PostCommentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Posts\RelationManagers\CommentsRelationManager;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use App\Notifications\PostCommentAcknowledged;
use App\Notifications\PostCommentApproved;
use App\Notifications\PostCommentReceivedForAdmin;
use App\Support\SiteSettings;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class PostCommentTest extends TestCase
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

    public function test_post_page_offers_a_comment_form_under_the_writing(): void
    {
        $post = $this->makePost();

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('Yorum yaz')
            ->assertSee('İsmimi sitede gizle')
            ->assertSee('KVKK aydınlatma metnini', false)
            ->assertSee('name="website"', false)
            ->assertSee(route('posts.comments.store', $post), false)
            ->assertSee('href="#yorumlar"', false)
            ->assertSee('post-rail', false)
            ->assertDontSee('Bu yazı hakkında yazın');
    }

    public function test_visitor_comment_waits_for_approval_and_sends_mail(): void
    {
        Notification::fake();

        $post = $this->makePost(['title' => 'Hâcer Ol!', 'slug' => 'hacer-ol']);

        $this->post(route('posts.comments.store', $post), $this->commentPayload())
            ->assertRedirect(route('posts.show', $post).'#yorumlar')
            ->assertSessionHas('status', 'Yorumunuz alındı. Yayınlanması için yönetici onayı bekleniyor.');

        $comment = PostComment::query()->firstOrFail();

        $this->assertSame($post->id, $comment->post_id);
        $this->assertSame('Ayşe', $comment->first_name);
        $this->assertSame('Yılmaz', $comment->last_name);
        $this->assertSame('ayse@example.com', $comment->email);
        $this->assertSame("Bu yazı bana çok iyi geldi.\nİkinci satır.", $comment->body);
        $this->assertFalse($comment->hide_name);
        $this->assertSame(PostCommentStatus::Pending, $comment->status);
        $this->assertNotNull($comment->ip_address);

        Notification::assertSentOnDemand(
            PostCommentReceivedForAdmin::class,
            function (PostCommentReceivedForAdmin $notification, array $channels, object $notifiable): bool {
                $payload = $notification->toPhpMailer($notifiable);

                return $payload['to'] === ['alici@hacer.org']
                    && ($payload['reply_to'] ?? null) === 'ayse@example.com'
                    && str_contains($payload['text'], 'Hâcer Ol!');
            },
        );
        Notification::assertSentTo($comment, PostCommentAcknowledged::class);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertDontSee('Bu yazı bana çok iyi geldi.')
            ->assertDontSee('ayse@example.com');
    }

    public function test_approved_comment_with_a_hidden_name_shows_ziyaretci(): void
    {
        $post = $this->makePost();
        $this->makeComment($post, [
            'hide_name' => true,
            'status' => PostCommentStatus::Approved,
            'body' => 'Sessiz bir not.',
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('Ziyaretçi')
            ->assertSee('Sessiz bir not.')
            ->assertDontSee('Ayşe')
            ->assertDontSee('ayse@example.com');
    }

    public function test_approved_comments_keep_the_visible_name_and_oldest_first_order(): void
    {
        $post = $this->makePost();
        $this->makeComment($post, [
            'first_name' => 'Eski',
            'last_name' => 'Yorum',
            'body' => 'Önce gelen düşünce',
            'status' => PostCommentStatus::Approved,
            'created_at' => now()->subDay(),
        ]);
        $this->makeComment($post, [
            'first_name' => 'Yeni',
            'last_name' => 'Yorum',
            'email' => 'yeni@example.com',
            'body' => 'Sonra gelen düşünce',
            'status' => PostCommentStatus::Approved,
            'created_at' => now(),
        ]);
        $this->makeComment($post, [
            'body' => 'Henüz gizli düşünce',
            'status' => PostCommentStatus::Pending,
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSeeInOrder(['Eski Yorum', 'Önce gelen düşünce', 'Yeni Yorum', 'Sonra gelen düşünce'])
            ->assertDontSee('Henüz gizli düşünce');
    }

    public function test_empty_comment_is_rejected_in_turkish(): void
    {
        Notification::fake();

        $post = $this->makePost();

        $this->post(route('posts.comments.store', $post), [])
            ->assertRedirect(route('posts.show', $post).'#yorumlar')
            ->assertSessionHasErrors([
                'first_name' => 'Ad gerekli.',
                'last_name' => 'Soyad gerekli.',
                'email' => 'E-posta gerekli.',
                'body' => 'Yorum gerekli.',
                'kvkk_accepted' => 'KVKK aydınlatma metnini kabul etmelisiniz.',
            ]);

        $this->assertDatabaseCount('post_comments', 0);
        Notification::assertNothingSent();
    }

    public function test_html_only_comment_is_rejected_and_markup_is_stripped(): void
    {
        Notification::fake();

        $post = $this->makePost();

        $this->post(route('posts.comments.store', $post), $this->commentPayload([
            'first_name' => '<b></b>',
            'body' => '<script>alert(1)</script>',
        ]))->assertRedirect(route('posts.show', $post).'#yorumlar')
            ->assertSessionHasErrors('first_name');

        $this->assertDatabaseCount('post_comments', 0);

        $this->post(route('posts.comments.store', $post), $this->commentPayload([
            'body' => '<script>alert(1)</script>Merhaba',
        ]))->assertRedirect();

        $this->assertSame('alert(1)Merhaba', PostComment::query()->firstOrFail()->body);
    }

    public function test_honeypot_does_not_store_a_comment(): void
    {
        Notification::fake();

        $post = $this->makePost();

        $this->post(route('posts.comments.store', $post), $this->commentPayload([
            'website' => 'https://spam.example',
        ]))
            ->assertRedirect(route('posts.show', $post).'#yorumlar')
            ->assertSessionHas('status', 'Yorumunuz alındı. Yayınlanması için yönetici onayı bekleniyor.');

        $this->assertDatabaseCount('post_comments', 0);
        Notification::assertNothingSent();
    }

    public function test_unpublished_post_does_not_accept_a_comment(): void
    {
        Notification::fake();

        $post = $this->makePost([
            'is_published' => false,
            'slug' => 'taslak',
        ]);

        $this->post(route('posts.comments.store', $post), $this->commentPayload())
            ->assertNotFound();

        $this->assertDatabaseCount('post_comments', 0);
        Notification::assertNothingSent();
    }

    public function test_approving_a_comment_sends_the_publication_mail_once(): void
    {
        Notification::fake();

        $comment = $this->makeComment($this->makePost());

        $comment->update(['status' => PostCommentStatus::Approved]);

        $this->assertTrue(app(NotifyPostCommentApproved::class)->handle($comment));

        Notification::assertSentTo($comment, PostCommentApproved::class, function (PostCommentApproved $notification) use ($comment): bool {
            $payload = $notification->toPhpMailer($comment);

            return $payload['to'] === ['ayse@example.com']
                && str_contains($payload['text'], '#yorumlar')
                && str_contains($payload['text'], 'Adınız yorumun yanında görünür.');
        });

        $this->assertTrue(app(NotifyPostCommentApproved::class)->handle($comment->fresh()));
        Notification::assertSentToTimes($comment, PostCommentApproved::class, 1);
    }

    public function test_rejected_comment_stays_off_the_page(): void
    {
        $post = $this->makePost();
        $this->makeComment($post, [
            'body' => 'Reddedilen not',
            'status' => PostCommentStatus::Rejected,
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertDontSee('Reddedilen not');
    }

    public function test_pending_comments_do_not_change_the_kalemimiz_navigation_badge(): void
    {
        $this->makeComment($this->makePost());

        $this->assertNull(PostResource::getNavigationBadge());
    }

    public function test_admin_sees_the_pending_count_and_can_approve_from_the_post(): void
    {
        Notification::fake();

        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $post = $this->makePost(['title' => 'Paneldeki yazı', 'slug' => 'paneldeki-yazi']);
        $comment = $this->makeComment($post, ['body' => 'Panelden onaylanacak yorum']);

        Livewire::test(ListPosts::class)
            ->assertOk()
            ->assertSee('Bekleyen yorum')
            ->assertTableColumnStateSet('pending_comments_count', 1, $post);

        Livewire::test(CommentsRelationManager::class, [
            'ownerRecord' => $post,
            'pageClass' => EditPost::class,
        ])
            ->assertOk()
            ->assertSee('Panelden onaylanacak yorum')
            ->assertSee('İsim görünür')
            ->callAction(TestAction::make('approve')->table($comment));

        $comment->refresh();

        $this->assertSame(PostCommentStatus::Approved, $comment->status);
        $this->assertNotNull($comment->approval_notified_at);
        Notification::assertSentTo($comment, PostCommentApproved::class);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('Panelden onaylanacak yorum')
            ->assertSee('Ayşe Yılmaz');
    }

    public function test_admin_can_delete_a_comment_from_the_post(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $post = $this->makePost(['slug' => 'silinecek-yorum']);
        $comment = $this->makeComment($post, ['body' => 'Silinecek yorum']);

        Livewire::test(CommentsRelationManager::class, [
            'ownerRecord' => $post,
            'pageClass' => EditPost::class,
        ])
            ->assertOk()
            ->assertActionVisible(TestAction::make('delete')->table($comment))
            ->assertSee('Sil')
            ->callAction(TestAction::make('delete')->table($comment));

        $this->assertDatabaseMissing('post_comments', ['id' => $comment->id]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function commentPayload(array $overrides = []): array
    {
        return [
            'first_name' => 'Ayşe',
            'last_name' => 'Yılmaz',
            'email' => 'ayse@example.com',
            'body' => "Bu yazı bana çok iyi geldi.\nİkinci satır.",
            'kvkk_accepted' => '1',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makePost(array $attributes = []): Post
    {
        return Post::query()->create([
            'type' => 'article',
            'title' => 'Web sitemiz yayında',
            'slug' => 'web-sitemiz-yayinda',
            'excerpt' => 'Kısa özet',
            'body' => '<p>Yazı gövdesi</p>',
            'published_at' => now()->subDay(),
            'is_published' => true,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeComment(Post $post, array $attributes = []): PostComment
    {
        $createdAt = $attributes['created_at'] ?? null;
        unset($attributes['created_at']);

        $comment = $post->comments()->create([
            'first_name' => 'Ayşe',
            'last_name' => 'Yılmaz',
            'email' => 'ayse@example.com',
            'body' => 'Bir yorum',
            'hide_name' => false,
            'status' => PostCommentStatus::Pending,
            ...$attributes,
        ]);

        if ($createdAt !== null) {
            $comment->forceFill(['created_at' => $createdAt])->save();
        }

        return $comment->fresh();
    }
}
