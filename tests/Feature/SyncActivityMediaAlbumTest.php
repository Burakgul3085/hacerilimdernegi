<?php

namespace Tests\Feature;

use App\Actions\SyncActivityMediaAlbum;
use App\Enums\UserRole;
use App\Filament\Resources\Activities\Pages\EditActivity;
use App\Models\Activity;
use App\Models\MediaAlbum;
use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class SyncActivityMediaAlbumTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_sync_creates_a_draft_album_with_copied_cover_and_gallery(): void
    {
        Storage::disk('public')->put('activities/cover.jpg', 'cover');
        Storage::disk('public')->put('activities/gallery/one.jpg', 'one');
        Storage::disk('public')->put('activities/gallery/talk.mp4', 'video');

        $activity = Activity::factory()->create([
            'title' => 'Değdi mi Değecek mi?',
            'description' => '<p>Seminer açıklaması.</p>',
            'image' => 'activities/cover.jpg',
            'gallery' => [
                'activities/gallery/one.jpg',
                'activities/gallery/talk.mp4',
            ],
        ]);

        $result = app(SyncActivityMediaAlbum::class)->handle($activity, publish: false);

        $album = $result['album'];

        $this->assertTrue($result['created']);
        $this->assertSame($activity->id, $album->activity_id);
        $this->assertSame('Değdi mi Değecek mi?', $album->title);
        $this->assertSame('Seminer açıklaması.', $album->description);
        $this->assertFalse($album->is_published);
        $this->assertNotNull($album->cover);
        $this->assertNotSame('activities/cover.jpg', $album->cover);
        $this->assertTrue(Storage::disk('public')->exists($album->cover));
        $this->assertSame(2, $album->items()->count());
        $this->assertTrue(
            $album->items()->where('source_path', 'activities/gallery/one.jpg')->exists(),
        );
        $this->assertTrue(
            $album->items()->where('source_path', 'activities/gallery/talk.mp4')->exists(),
        );
        $this->assertTrue(Storage::disk('public')->exists('activities/gallery/one.jpg'));
    }

    public function test_second_sync_updates_the_same_album_and_does_not_duplicate_items(): void
    {
        Storage::disk('public')->put('activities/cover.jpg', 'cover');
        Storage::disk('public')->put('activities/gallery/one.jpg', 'one');
        Storage::disk('public')->put('activities/gallery/two.jpg', 'two');

        $activity = Activity::factory()->create([
            'title' => 'İlk başlık',
            'description' => '<p>İlk metin</p>',
            'image' => 'activities/cover.jpg',
            'gallery' => ['activities/gallery/one.jpg'],
        ]);

        $first = app(SyncActivityMediaAlbum::class)->handle($activity, publish: false);
        $albumId = $first['album']->id;

        MediaItem::query()->create([
            'media_album_id' => $albumId,
            'type' => 'photo',
            'path' => 'media/manual.jpg',
            'source_path' => null,
            'sort_order' => 99,
        ]);

        $activity->update([
            'title' => 'Güncel başlık',
            'description' => '<p>Güncel metin</p>',
            'gallery' => [
                'activities/gallery/one.jpg',
                'activities/gallery/two.jpg',
            ],
        ]);

        $second = app(SyncActivityMediaAlbum::class)->handle($activity->fresh(), publish: true);

        $this->assertFalse($second['created']);
        $this->assertSame($albumId, $second['album']->id);
        $this->assertSame(1, MediaAlbum::query()->where('activity_id', $activity->id)->count());
        $this->assertSame('Güncel başlık', $second['album']->title);
        $this->assertSame('Güncel metin', $second['album']->description);
        $this->assertTrue($second['album']->is_published);
        $this->assertSame(3, $second['album']->items()->count());
        $this->assertTrue(
            $second['album']->items()->whereNull('source_path')->where('path', 'media/manual.jpg')->exists(),
        );
    }

    public function test_sync_removes_stale_synced_items_when_gallery_shrinks(): void
    {
        Storage::disk('public')->put('activities/gallery/one.jpg', 'one');
        Storage::disk('public')->put('activities/gallery/two.jpg', 'two');

        $activity = Activity::factory()->create([
            'title' => 'Galeri',
            'gallery' => [
                'activities/gallery/one.jpg',
                'activities/gallery/two.jpg',
            ],
        ]);

        app(SyncActivityMediaAlbum::class)->handle($activity, publish: false);

        $activity->update(['gallery' => ['activities/gallery/one.jpg']]);

        $album = app(SyncActivityMediaAlbum::class)->handle($activity->fresh(), publish: false)['album'];

        $this->assertSame(1, $album->items()->count());
        $this->assertSame(
            'activities/gallery/one.jpg',
            $album->items()->first()->source_path,
        );
    }

    public function test_sync_requires_cover_or_gallery_media(): void
    {
        $activity = Activity::factory()->create([
            'image' => null,
            'gallery' => [],
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(SyncActivityMediaAlbum::class)->handle($activity, publish: false);
    }

    public function test_edit_activity_action_creates_album_from_header_button(): void
    {
        Storage::disk('public')->put('activities/cover.jpg', 'cover');
        Storage::disk('public')->put('activities/gallery/one.jpg', 'one');

        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        $activity = Activity::factory()->create([
            'title' => 'Panel semineri',
            'description' => '<p>Panel açıklaması</p>',
            'image' => 'activities/cover.jpg',
            'gallery' => ['activities/gallery/one.jpg'],
            'is_published' => true,
        ]);

        Livewire::test(EditActivity::class, ['record' => $activity->getRouteKey()])
            ->callAction('syncMediaAlbum', data: [
                'is_published' => true,
            ])
            ->assertNotified();

        $album = MediaAlbum::query()->where('activity_id', $activity->id)->first();

        $this->assertNotNull($album);
        $this->assertSame('Panel semineri', $album->title);
        $this->assertTrue($album->is_published);
        $this->assertSame(1, $album->items()->count());
    }
}
