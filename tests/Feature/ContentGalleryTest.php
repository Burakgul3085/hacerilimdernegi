<?php

namespace Tests\Feature;

use App\Enums\MediaType;
use App\Enums\ProgramType;
use App\Enums\UserRole;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\MediaAlbums\Pages\CreateMediaAlbum;
use App\Filament\Resources\MediaItems\Pages\CreateMediaItem;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Programs\Pages\EditProgram;
use App\Filament\Support\ContentUploads;
use App\Models\Event;
use App\Models\MediaAlbum;
use App\Models\MediaItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use App\Models\User;
use App\Support\ContentMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContentGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_upload_field_allows_multiple_files(): void
    {
        $this->assertTrue(ContentUploads::gallery('gallery', 'pages/gallery')->isMultiple());
    }

    public function test_gallery_paths_ignore_empty_and_non_string_entries(): void
    {
        $this->assertSame(
            ['pages/gallery/one.jpg'],
            ContentMedia::paths(['pages/gallery/one.jpg', '', null, 12]),
        );
    }

    public function test_about_page_renders_unlimited_gallery_images_and_videos(): void
    {
        Page::query()->where('slug', 'hakkimizda')->update([
            'gallery' => [
                'pages/gallery/one.jpg',
                'pages/gallery/two.jpg',
                'pages/gallery/talk.mp4',
            ],
            'is_published' => true,
        ]);

        $this->get('/hakkimizda')
            ->assertOk()
            ->assertSee('/storage/pages/gallery/one.jpg', false)
            ->assertSee('/storage/pages/gallery/two.jpg', false)
            ->assertSee('/storage/pages/gallery/talk.mp4', false)
            ->assertSee('<video', false)
            ->assertSee('post-gallery-item', false);
    }

    public function test_vision_and_message_pages_render_their_galleries(): void
    {
        Page::query()->where('slug', 'vizyon-misyon')->update([
            'gallery' => ['pages/gallery/vizyon.jpg'],
            'is_published' => true,
        ]);
        Page::query()->where('slug', 'baskanin-mesaji')->update([
            'gallery' => ['pages/gallery/mesaj.mp4'],
            'is_published' => true,
        ]);

        $this->get('/vizyon-misyon')
            ->assertOk()
            ->assertSee('/storage/pages/gallery/vizyon.jpg', false);

        $this->get('/baskanin-mesaji')
            ->assertOk()
            ->assertSee('/storage/pages/gallery/mesaj.mp4', false)
            ->assertSee('<video', false);
    }

    public function test_event_and_program_pages_render_gallery_media(): void
    {
        $event = Event::query()->create([
            'title' => 'Dönem açılışı',
            'slug' => 'donem-acilisi-galeri',
            'description' => '<p>Etkinlik</p>',
            'image' => 'events/cover.jpg',
            'gallery' => ['events/gallery/one.jpg', 'events/gallery/konusma.mp4'],
            'is_published' => true,
        ]);
        $program = Program::query()->create([
            'type' => ProgramType::Sohbet,
            'title' => 'Haftalık sohbet',
            'slug' => 'haftalik-sohbet-galeri',
            'description' => '<p>Sohbet</p>',
            'gallery' => ['programs/gallery/ders.mp4'],
            'is_published' => true,
        ]);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('/storage/events/gallery/one.jpg', false)
            ->assertSee('/storage/events/gallery/konusma.mp4', false)
            ->assertSee('<video', false);

        $this->get(route('programs.show', $program))
            ->assertOk()
            ->assertSee('/storage/programs/gallery/ders.mp4', false)
            ->assertSee('<video', false);
    }

    public function test_post_gallery_renders_more_than_eight_images_and_a_video(): void
    {
        $gallery = [];

        for ($index = 1; $index <= 9; $index++) {
            $gallery[] = 'posts/gallery/'.$index.'.jpg';
        }

        $gallery[] = 'posts/gallery/sohbet.mp4';

        $post = Post::query()->create([
            'type' => 'article',
            'title' => 'Galeri yazısı',
            'slug' => 'galeri-yazisi',
            'body' => '<p>Gövde</p>',
            'gallery' => $gallery,
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $response = $this->get(route('posts.show', $post))->assertOk();

        $response->assertSee('/storage/posts/gallery/9.jpg', false);
        $response->assertSee('/storage/posts/gallery/sohbet.mp4', false);
        $response->assertSee('<video', false);
    }

    public function test_album_renders_an_uploaded_video_and_corrects_its_type(): void
    {
        $album = MediaAlbum::query()->create([
            'title' => 'Kamp albümü',
            'slug' => 'kamp-albumu',
            'is_published' => true,
        ]);

        $item = MediaItem::query()->create([
            'media_album_id' => $album->id,
            'type' => MediaType::Photo,
            'title' => 'Kamp videosu',
            'path' => 'media/kamp.mp4',
            'sort_order' => 1,
        ]);

        $this->assertSame(MediaType::Video, $item->fresh()->type);

        $this->get(route('media.show', $album))
            ->assertOk()
            ->assertSee('/storage/media/kamp.mp4', false)
            ->assertSee('<video', false)
            ->assertSee('Kamp videosu');
    }

    public function test_album_still_renders_an_external_video_link(): void
    {
        $album = MediaAlbum::query()->create([
            'title' => 'Açılış programı',
            'slug' => 'acilis-programi-link',
            'is_published' => true,
        ]);

        MediaItem::query()->create([
            'media_album_id' => $album->id,
            'type' => MediaType::Video,
            'title' => 'Program kaydı',
            'external_url' => 'https://youtube.com/watch?v=abcdef',
            'sort_order' => 1,
        ]);

        $this->get(route('media.show', $album))
            ->assertOk()
            ->assertSee('https://youtube.com/watch?v=abcdef', false)
            ->assertSee('Program kaydı');
    }

    public function test_admin_forms_expose_unlimited_gallery_and_media_uploads(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        $page = Page::query()->where('slug', 'hakkimizda')->firstOrFail();
        $post = Post::query()->create([
            'type' => 'article',
            'title' => 'Panel yazısı',
            'slug' => 'panel-yazisi',
            'body' => '<p>Gövde</p>',
            'is_published' => true,
        ]);
        $event = Event::query()->create([
            'title' => 'Panel etkinliği',
            'slug' => 'panel-etkinligi',
            'is_published' => true,
        ]);
        $program = Program::query()->create([
            'type' => ProgramType::Sohbet,
            'title' => 'Panel programı',
            'slug' => 'panel-programi',
            'is_published' => true,
        ]);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertFormFieldIsVisible('gallery');
        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertFormFieldIsVisible('gallery');
        Livewire::test(EditEvent::class, ['record' => $event->getRouteKey()])
            ->assertFormFieldIsVisible('gallery');
        Livewire::test(EditProgram::class, ['record' => $program->getRouteKey()])
            ->assertFormFieldIsVisible('gallery');
        Livewire::test(CreateMediaAlbum::class)
            ->assertFormFieldIsVisible('incoming_media')
            ->assertFormFieldIsVisible('items');
        Livewire::test(CreateMediaItem::class)
            ->assertFormFieldIsVisible('files')
            ->assertFormFieldIsHidden('path');
    }

    public function test_creating_a_media_item_saves_each_selected_file(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        $album = MediaAlbum::query()->create([
            'title' => 'Çoklu yükleme',
            'slug' => 'coklu-yukleme',
            'is_published' => true,
        ]);

        Livewire::test(CreateMediaItem::class)
            ->fillForm([
                'media_album_id' => $album->id,
                'type' => MediaType::Photo->value,
                'files' => ['media/sohbet.jpg', 'media/kamp.mp4'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(2, $album->items()->count());
        $this->assertTrue($album->items()->where('path', 'media/sohbet.jpg')->exists());
        $this->assertTrue($album->items()->where('path', 'media/kamp.mp4')->where('type', MediaType::Video)->exists());
    }
}
