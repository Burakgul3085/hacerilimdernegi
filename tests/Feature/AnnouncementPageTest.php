<?php

namespace Tests\Feature;

use App\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_published_announcements(): void
    {
        $visible = Announcement::factory()->create([
            'title' => 'Ramazan programı duyurusu',
            'slug' => 'ramazan-programi',
        ]);
        Announcement::factory()->unpublished()->create([
            'title' => 'Taslak duyuru',
            'slug' => 'taslak-duyuru',
        ]);

        $this->get(route('announcements.index'))
            ->assertOk()
            ->assertSee('Duyurular')
            ->assertSee('Ramazan programı duyurusu')
            ->assertSee(route('announcements.show', $visible), false)
            ->assertSee('media-frame', false)
            ->assertSee('aspect-[4/5]', false)
            ->assertDontSee('Taslak duyuru');
    }

    public function test_show_page_renders_announcement_details(): void
    {
        $announcement = Announcement::factory()->create([
            'title' => 'Üyelik toplantısı',
            'slug' => 'uyelik-toplantisi',
            'excerpt' => 'Kısa duyuru özeti',
            'body' => '<p>'.str_repeat('kelime ', 200).'</p>',
            'image' => 'announcements/cover.jpg',
            'gallery' => ['announcements/gallery/one.jpg'],
        ]);

        $this->get(route('announcements.show', $announcement))
            ->assertOk()
            ->assertSee('Üyelik toplantısı')
            ->assertSee('Kısa duyuru özeti')
            ->assertSee('2 dk okuma')
            ->assertSee('/storage/announcements/cover.jpg', false)
            ->assertSee('/storage/announcements/gallery/one.jpg', false)
            ->assertSee('activity-hero-cover', false)
            ->assertSee('activity-cover-img', false)
            ->assertSee('activity-marquee', false)
            ->assertSee('activity-marquee-item', false)
            ->assertDontSee('post-gallery-item', false)
            ->assertSee('Bağlantıyı kopyala')
            ->assertSee('https://wa.me/?text=', false);
    }

    public function test_unpublished_announcement_returns_404(): void
    {
        $announcement = Announcement::factory()->unpublished()->create([
            'slug' => 'gizli-duyuru',
        ]);

        $this->get(route('announcements.show', $announcement))->assertNotFound();
    }

    public function test_future_announcement_returns_404(): void
    {
        $this->freezeTime();

        $announcement = Announcement::factory()->scheduled()->create([
            'slug' => 'ileri-tarihli-duyuru',
        ]);

        $this->get(route('announcements.show', $announcement))->assertNotFound();
    }
}
