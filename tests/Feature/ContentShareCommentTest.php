<?php

namespace Tests\Feature;

use App\Enums\PostCommentStatus;
use App\Models\Activity;
use App\Models\Announcement;
use App\Models\MediaAlbum;
use App\Models\Post;
use App\Models\PostComment;
use App\Support\OpenGraphImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentShareCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_page_shares_its_cover_and_accepts_a_moderated_comment(): void
    {
        $activity = Activity::factory()->create([
            'title' => 'Şiir atölyesi',
            'slug' => 'siir-atolyesi',
            'image' => 'activities/cover.jpg',
        ]);

        $this->get(route('activities.show', $activity))
            ->assertOk()
            ->assertSee('WhatsApp')
            ->assertSee('Bağlantıyı kopyala')
            ->assertSee('Yorum yaz')
            ->assertSee('href="#yorumlar"', false)
            ->assertSee(route('activities.comments.store', $activity), false)
            ->assertSee('property="og:image" content="'.$activity->openGraphImage()['url'].'"', false)
            ->assertDontSee('property="og:image" content="'.OpenGraphImage::logo()['url'].'"', false);

        $this->post(route('activities.comments.store', $activity), $this->commentPayload())
            ->assertRedirect(route('activities.show', $activity).'#yorumlar');

        $comment = PostComment::query()->firstOrFail();
        $this->assertSame(Activity::class, $comment->commentable_type);
        $this->assertSame($activity->id, $comment->commentable_id);
        $this->assertNull($comment->post_id);
        $this->assertSame(PostCommentStatus::Pending, $comment->status);

        $this->get(route('activities.show', $activity))
            ->assertOk()
            ->assertDontSee('Atölye çok iyiydi.');
    }

    public function test_activity_without_a_cover_uses_the_association_logo(): void
    {
        $activity = Activity::factory()->create([
            'title' => 'Kapaksız hat',
            'slug' => 'kapaksiz-hat',
            'image' => null,
        ]);

        $this->get(route('activities.show', $activity))
            ->assertOk()
            ->assertSee('property="og:image" content="'.OpenGraphImage::logo()['url'].'"', false);
    }

    public function test_unpublished_activity_rejects_comments(): void
    {
        $activity = Activity::factory()->unpublished()->create([
            'slug' => 'gizli-faaliyet',
        ]);

        $this->post(route('activities.comments.store', $activity), $this->commentPayload())
            ->assertNotFound();
    }

    public function test_announcement_link_uses_its_own_cover(): void
    {
        $announcement = Announcement::factory()->create([
            'title' => 'Toplantı duyurusu',
            'slug' => 'toplanti-duyurusu',
            'image' => 'announcements/cover.jpg',
        ]);

        $this->get(route('announcements.show', $announcement))
            ->assertOk()
            ->assertSee('property="og:image" content="'.$announcement->openGraphImage()['url'].'"', false)
            ->assertSee(route('announcements.comments.store', $announcement), false);
    }

    public function test_album_and_child_album_use_their_own_covers(): void
    {
        $parent = MediaAlbum::query()->create([
            'title' => 'Seminerler',
            'slug' => 'seminerler',
            'cover' => 'albums/parent.jpg',
            'is_published' => true,
        ]);
        $child = MediaAlbum::query()->create([
            'parent_id' => $parent->id,
            'title' => 'A kişisi',
            'slug' => 'a-kisi',
            'cover' => 'albums/child.jpg',
            'is_published' => true,
        ]);

        $this->get(route('media.show', $parent))
            ->assertOk()
            ->assertSee('Yorum yaz')
            ->assertSee(route('media.comments.store', $parent), false)
            ->assertSee('property="og:image" content="'.$parent->openGraphImage()['url'].'"', false);

        $this->get(route('media.children.show', ['album' => $parent, 'child' => $child->slug]))
            ->assertOk()
            ->assertSee(route('media.children.comments.store', ['album' => $parent, 'child' => $child->slug]), false)
            ->assertSee('property="og:image" content="'.$child->openGraphImage()['url'].'"', false)
            ->assertDontSee('property="og:image" content="'.$parent->openGraphImage()['url'].'"', false);

        $this->post(route('media.children.comments.store', ['album' => $parent, 'child' => $child->slug]), $this->commentPayload())
            ->assertRedirect($child->publicUrl().'#yorumlar');

        $this->assertSame($child->id, PostComment::query()->firstOrFail()->commentable_id);
    }

    public function test_vitrine_share_uses_the_logo_and_has_no_comments(): void
    {
        $this->get(route('social'))
            ->assertOk()
            ->assertSee('https://wa.me/?text=', false)
            ->assertSee('Bağlantıyı kopyala')
            ->assertSee('property="og:image" content="'.OpenGraphImage::logo()['url'].'"', false)
            ->assertDontSee('Yorum yaz')
            ->assertDontSee('id="yorumlar"', false);
    }

    public function test_post_without_a_cover_uses_the_association_logo(): void
    {
        $post = Post::query()->create([
            'type' => 'article',
            'title' => 'Kapaksız yazı',
            'slug' => 'kapaksiz-yazi',
            'body' => '<p>Metin</p>',
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('property="og:image" content="'.OpenGraphImage::logo()['url'].'"', false);
    }

    /**
     * @return array<string, string>
     */
    private function commentPayload(): array
    {
        return [
            'first_name' => 'Ayşe',
            'last_name' => 'Yılmaz',
            'email' => 'ayse@example.com',
            'body' => 'Atölye çok iyiydi.',
            'kvkk_accepted' => '1',
        ];
    }
}
