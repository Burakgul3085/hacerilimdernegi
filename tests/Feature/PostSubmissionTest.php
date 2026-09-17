<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\Pages\ViewPost;
use App\Models\Post;
use App\Models\User;
use App\Notifications\VisitorPostAcknowledged;
use App\Notifications\VisitorPostReceivedForAdmin;
use App\Support\SiteSettings;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PostSubmissionTest extends TestCase
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

    public function test_posts_index_shows_yazilar_ve_siirler_without_duyurular(): void
    {
        $this->get(route('posts.index'))
            ->assertOk()
            ->assertSee("Kalemim'İZ")
            ->assertSee('Yazı veya şiir gönder')
            ->assertSee('Formu aç')
            ->assertSee('Şiirler')
            ->assertSee('id="gonder"', false)
            ->assertSee('role="dialog"', false)
            ->assertDontSee('>Duyurular<', false)
            ->assertDontSee('tur=announcement', false);
    }

    public function test_visitor_can_submit_an_article_for_approval(): void
    {
        Notification::fake();

        $this->from(route('posts.index'))
            ->post(route('posts.store'), [
                'type' => 'article',
                'name' => 'Ayşe Yılmaz',
                'email' => 'ayse@example.com',
                'title' => 'İlim ve sohbet',
                'body' => "Kardeşlik üzerine bir yazı.\n\nİkinci paragraf.",
                'kvkk_accepted' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Yazınız bize ulaşmıştır. En kısa zamanda yayımlanacaktır.');

        $post = Post::query()->where('submitter_email', 'ayse@example.com')->firstOrFail();

        $this->assertSame('article', $post->type);
        $this->assertSame('Ayşe Yılmaz', $post->author_name);
        $this->assertTrue($post->submitted_from_public);
        $this->assertFalse($post->is_published);
        $this->assertNull($post->published_at);
        $this->assertStringContainsString('<p>', $post->body);
        $this->assertStringContainsString('Kardeşlik üzerine bir yazı.', $post->body);

        Notification::assertSentOnDemand(VisitorPostReceivedForAdmin::class);
        Notification::assertSentTo($post, VisitorPostAcknowledged::class);

        $this->get(route('posts.index'))->assertDontSee('İlim ve sohbet');
        $this->get(route('posts.show', $post))->assertNotFound();
    }

    public function test_visitor_can_submit_a_poem_for_approval(): void
    {
        Notification::fake();

        $this->post(route('posts.store'), [
            'type' => 'poem',
            'name' => 'Zeynep',
            'email' => 'zeynep@example.com',
            'title' => 'Sabah duası',
            'body' => "Bir dize.\nİkinci dize.",
            'kvkk_accepted' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('posts', [
            'title' => 'Sabah duası',
            'type' => 'poem',
            'submitter_email' => 'zeynep@example.com',
            'submitted_from_public' => true,
            'is_published' => false,
        ]);
    }

    public function test_visitor_submission_rejects_an_empty_payload(): void
    {
        Notification::fake();

        $this->from(route('posts.index'))
            ->post(route('posts.store'), [])
            ->assertRedirect(route('posts.index'))
            ->assertSessionHasErrors(['type', 'name', 'email', 'title', 'body', 'kvkk_accepted']);

        $this->assertDatabaseCount('posts', 0);
        Notification::assertNothingSent();
    }

    public function test_visitor_submission_rejects_announcement_type(): void
    {
        Notification::fake();

        $this->from(route('posts.index'))
            ->post(route('posts.store'), [
                'type' => 'announcement',
                'name' => 'Ayşe',
                'email' => 'ayse@example.com',
                'title' => 'Duyuru denemesi',
                'body' => 'Bu bir duyuru olmamalı.',
                'kvkk_accepted' => '1',
            ])
            ->assertRedirect(route('posts.index'))
            ->assertSessionHasErrors('type');

        $this->assertDatabaseCount('posts', 0);
        Notification::assertNothingSent();
    }

    public function test_list_filters_articles_and_poems(): void
    {
        $this->makePublishedPost([
            'type' => 'article',
            'title' => 'Yalnız yazı',
            'slug' => 'yalniz-yazi',
        ]);
        $this->makePublishedPost([
            'type' => 'poem',
            'title' => 'Yalnız şiir',
            'slug' => 'yalniz-siir',
        ]);

        $this->get(route('posts.index', ['tur' => 'article']))
            ->assertOk()
            ->assertSee('Yalnız yazı')
            ->assertDontSee('Yalnız şiir');

        $this->get(route('posts.index', ['tur' => 'poem']))
            ->assertOk()
            ->assertSee('Yalnız şiir')
            ->assertDontSee('Yalnız yazı');
    }

    public function test_honeypot_submission_does_not_store_a_post(): void
    {
        Notification::fake();

        $this->from(route('posts.index'))
            ->post(route('posts.store'), [
                'type' => 'article',
                'name' => 'Bot',
                'email' => 'bot@example.com',
                'title' => 'Spam',
                'body' => 'Spam metni',
                'kvkk_accepted' => '1',
                'website' => 'https://spam.example',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Yazınız bize ulaşmıştır. En kısa zamanda yayımlanacaktır.');

        $this->assertDatabaseMissing('posts', ['email' => 'bot@example.com']);
        $this->assertDatabaseMissing('posts', ['submitter_email' => 'bot@example.com']);
        Notification::assertNothingSent();
    }

    public function test_posts_index_shows_optional_image_fields(): void
    {
        $this->get(route('posts.index'))
            ->assertOk()
            ->assertSee('Kapak resmi')
            ->assertSee('Fotoğraf')
            ->assertSee('(isteğe bağlı)')
            ->assertSee('Zorunlu değil')
            ->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_visitor_can_submit_optional_cover_and_photo(): void
    {
        Storage::fake('public');
        Notification::fake();

        $cover = UploadedFile::fake()->image('kapak.jpg', 800, 600);
        $photo = UploadedFile::fake()->image('foto.png', 640, 480);

        $this->from(route('posts.index'))
            ->post(route('posts.store'), [
                'type' => 'poem',
                'name' => 'Ayşe Yılmaz',
                'email' => 'ayse@example.com',
                'title' => 'Sabah duası',
                'body' => 'Bir dize.',
                'cover' => $cover,
                'photo' => $photo,
                'kvkk_accepted' => '1',
            ])
            ->assertRedirect();

        $post = Post::query()->where('submitter_email', 'ayse@example.com')->firstOrFail();

        $this->assertSame('poem', $post->type);
        $this->assertNotNull($post->image);
        $this->assertNotEmpty($post->gallery);
        Storage::disk('public')->assertExists($post->image);
        Storage::disk('public')->assertExists($post->gallery[0]);
    }

    public function test_admin_list_opens_panel_view_for_published_and_pending_posts(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $published = $this->makePublishedPost([
            'title' => 'Yayındaki yazı',
            'slug' => 'yayindaki-yazi',
        ]);
        $pending = Post::query()->create([
            'type' => 'article',
            'title' => 'Bekleyen yazı',
            'slug' => 'bekleyen-yazi',
            'body' => '<p>Taslak gövde</p>',
            'author_name' => 'Ziyaretçi',
            'submitter_email' => 'ziyaretci@example.com',
            'submitted_from_public' => true,
            'is_published' => false,
        ]);

        Livewire::test(ListPosts::class)
            ->assertOk()
            ->assertActionVisible(TestAction::make('view')->table($published))
            ->assertActionVisible(TestAction::make('view')->table($pending));

        Livewire::test(ViewPost::class, ['record' => $pending->getRouteKey()])
            ->assertOk()
            ->assertSee('Bekleyen yazı')
            ->assertSee('Taslak gövde')
            ->assertSee('ziyaretci@example.com')
            ->assertSee('Onay bekliyor')
            ->assertDontSee(route('posts.show', $pending, absolute: false), false);

        Livewire::test(EditPost::class, ['record' => $published->getRouteKey()])
            ->assertOk()
            ->assertActionVisible('view');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makePublishedPost(array $attributes = []): Post
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
}
