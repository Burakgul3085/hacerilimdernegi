<?php

namespace Tests\Feature;

use App\Models\MediaAlbum;
use App\Models\MediaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NestedMediaAlbumTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_index_lists_only_root_albums(): void
    {
        $parent = MediaAlbum::query()->create([
            'title' => 'Seminerler',
            'slug' => 'seminerler',
            'is_published' => true,
        ]);
        MediaAlbum::query()->create([
            'parent_id' => $parent->id,
            'title' => 'A kişi semineri',
            'slug' => 'a-kisi-semineri',
            'is_published' => true,
        ]);

        $this->get(route('media.index'))
            ->assertOk()
            ->assertSee('Seminerler')
            ->assertSee('Koleksiyon')
            ->assertDontSee('A kişi semineri');
    }

    public function test_media_album_cards_and_photos_match_activity_media_framing(): void
    {
        $album = MediaAlbum::query()->create([
            'title' => 'Kamp albümü',
            'slug' => 'kamp-albumu-cerceve',
            'cover' => 'albums/kamp.jpg',
            'description' => 'Kamp kareleri',
            'is_published' => true,
        ]);

        MediaItem::query()->create([
            'media_album_id' => $album->id,
            'type' => 'photo',
            'title' => 'Kamp karesi',
            'path' => 'media/kamp-kare.jpg',
            'sort_order' => 1,
        ]);

        $this->get(route('media.index'))
            ->assertOk()
            ->assertSee('album-card', false)
            ->assertSee('media-frame', false)
            ->assertSee('aspect-[4/5]', false)
            ->assertDontSee('aspect-[4/3]', false)
            ->assertDontSee('media-frame-blur', false);

        $this->get(route('media.show', $album))
            ->assertOk()
            ->assertSee('album-media-gallery', false)
            ->assertSee('album-media-tile', false)
            ->assertSee('/storage/media/kamp-kare.jpg', false)
            ->assertDontSee('object-cover', false)
            ->assertDontSee('aspect-[4/3]', false)
            ->assertDontSee('media-frame-blur', false);
    }

    public function test_collection_page_lists_children_and_search_filters_them(): void
    {
        $parent = $this->makeCollection();

        $this->get(route('media.show', $parent))
            ->assertOk()
            ->assertSee('A kişi semineri')
            ->assertSee('B kişi semineri')
            ->assertSee('İç albümler');

        $this->get(route('media.show', ['album' => $parent, 'q' => 'A kişi']))
            ->assertOk()
            ->assertSee('A kişi semineri')
            ->assertDontSee('B kişi semineri');
    }

    public function test_child_album_page_shows_media_and_type_filter(): void
    {
        $parent = $this->makeCollection();
        $child = MediaAlbum::query()->where('slug', 'a-kisi-semineri')->firstOrFail();

        MediaItem::query()->create([
            'media_album_id' => $child->id,
            'type' => 'photo',
            'title' => 'Seminer karesi',
            'path' => 'media/kare.jpg',
            'sort_order' => 1,
        ]);
        MediaItem::query()->create([
            'media_album_id' => $child->id,
            'type' => 'video',
            'title' => 'Seminer videosu',
            'path' => 'media/kayit.mp4',
            'sort_order' => 2,
        ]);

        $this->get(route('media.children.show', ['album' => $parent, 'child' => $child->slug]))
            ->assertOk()
            ->assertSee('Seminer karesi')
            ->assertSee('Seminer videosu')
            ->assertSee('Fotoğraflar')
            ->assertSee('Videolar');

        $this->get(route('media.children.show', ['album' => $parent, 'child' => $child->slug, 'tur' => 'photo']))
            ->assertOk()
            ->assertSee('Seminer karesi')
            ->assertDontSee('Seminer videosu');
    }

    public function test_unpublished_parent_hides_the_child_page(): void
    {
        $parent = MediaAlbum::query()->create([
            'title' => 'Gizli koleksiyon',
            'slug' => 'gizli-koleksiyon',
            'is_published' => false,
        ]);
        $child = MediaAlbum::query()->create([
            'parent_id' => $parent->id,
            'title' => 'Gizli seminer',
            'slug' => 'gizli-seminer',
            'is_published' => true,
        ]);

        $this->get(route('media.children.show', ['album' => $parent, 'child' => $child->slug]))
            ->assertNotFound();
        $this->get(route('media.show', $child))->assertNotFound();
    }

    public function test_an_album_that_already_has_children_cannot_become_a_child(): void
    {
        $parent = $this->makeCollection();
        $other = MediaAlbum::query()->create([
            'title' => 'Başka koleksiyon',
            'slug' => 'baska-koleksiyon',
            'is_published' => true,
        ]);

        $parent->parent_id = $other->id;
        $parent->save();

        $this->assertNull($parent->fresh()->parent_id);
    }

    private function makeCollection(): MediaAlbum
    {
        $parent = MediaAlbum::query()->create([
            'title' => 'Seminerler',
            'slug' => 'seminerler',
            'is_published' => true,
        ]);

        foreach (['A kişi semineri' => 'a-kisi-semineri', 'B kişi semineri' => 'b-kisi-semineri'] as $title => $slug) {
            MediaAlbum::query()->create([
                'parent_id' => $parent->id,
                'title' => $title,
                'slug' => $slug,
                'is_published' => true,
            ]);
        }

        return $parent;
    }
}
