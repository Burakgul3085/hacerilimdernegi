<?php

namespace App\Actions;

use App\Models\Activity;
use App\Models\MediaAlbum;
use App\Models\MediaItem;
use App\Support\ContentMedia;
use App\Support\UploadRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Faaliyet kapağı ve galerisini Medya albümüne kopyalar; bağlı albüm varsa günceller.
 */
class SyncActivityMediaAlbum
{
    /**
     * @return array{album: MediaAlbum, created: bool}
     */
    public function handle(Activity $activity, bool $publish): array
    {
        $sourcePaths = ContentMedia::paths($activity->gallery ?? []);
        $hasCover = filled($activity->image);

        if (! $hasCover && $sourcePaths === []) {
            throw new InvalidArgumentException('Albüm için önce faaliyete kapak veya galeri medyası ekleyin.');
        }

        return DB::transaction(function () use ($activity, $publish, $sourcePaths): array {
            $album = MediaAlbum::query()->where('activity_id', $activity->getKey())->first();
            $created = $album === null;

            if ($created) {
                $album = new MediaAlbum([
                    'activity_id' => $activity->getKey(),
                    'title' => $activity->title,
                    'slug' => MediaAlbum::uniqueSlug($activity->title),
                ]);
            }

            $album->title = $activity->title;
            $album->description = $this->albumDescription($activity);
            $album->is_published = $publish;
            $album->cover = $this->syncCover($activity, $album);
            $album->save();

            $this->syncGalleryItems($activity, $album, $sourcePaths);

            return ['album' => $album->fresh(['items']), 'created' => $created];
        });
    }

    private function albumDescription(Activity $activity): ?string
    {
        $fromBody = trim(html_entity_decode(strip_tags((string) $activity->description), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($fromBody !== '') {
            return $fromBody;
        }

        $excerpt = trim((string) $activity->excerpt);

        return $excerpt !== '' ? $excerpt : null;
    }

    private function syncCover(Activity $activity, MediaAlbum $album): ?string
    {
        $source = trim((string) $activity->image);

        if ($source === '') {
            return $album->cover;
        }

        $copied = $this->copyPublicFile(
            $source,
            'albums/from-activities/'.$activity->getKey(),
            'cover',
        );

        return $copied ?? $album->cover;
    }

    /**
     * @param  list<string>  $sourcePaths
     */
    private function syncGalleryItems(Activity $activity, MediaAlbum $album, array $sourcePaths): void
    {
        $existing = $album->items()
            ->whereNotNull('source_path')
            ->get()
            ->keyBy('source_path');

        $kept = [];
        $sort = (int) $album->items()->max('sort_order');

        foreach ($sourcePaths as $sourcePath) {
            /** @var MediaItem|null $item */
            $item = $existing->get($sourcePath);

            if ($item !== null) {
                $kept[] = $sourcePath;

                continue;
            }

            $copied = $this->copyPublicFile(
                $sourcePath,
                'media/from-activities/'.$activity->getKey(),
                pathinfo($sourcePath, PATHINFO_FILENAME) ?: 'media',
            );

            if ($copied === null) {
                continue;
            }

            $sort++;

            $album->items()->create([
                'type' => UploadRules::typeFromPath($copied),
                'path' => $copied,
                'source_path' => $sourcePath,
                'sort_order' => $sort,
            ]);

            $kept[] = $sourcePath;
        }

        $stale = $album->items()->whereNotNull('source_path');

        if ($kept !== []) {
            $stale->whereNotIn('source_path', $kept);
        }

        $stale->get()->each(function (MediaItem $item): void {
            $this->deletePublicFileIfManaged((string) $item->path);
            $item->delete();
        });
    }

    private function copyPublicFile(string $source, string $directory, string $nameHint): ?string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($source)) {
            return null;
        }

        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION) ?: 'bin');
        $base = Str::slug(Str::limit($nameHint, 60, '')) ?: 'media';
        $fingerprint = substr(sha1($source), 0, 10);
        $destination = trim($directory, '/').'/'.$base.'-'.$fingerprint.'.'.$extension;

        if (! $disk->exists($destination)) {
            $copied = $disk->copy($source, $destination);

            if (! $copied) {
                throw new RuntimeException('Medya dosyası albüme kopyalanamadı.');
            }
        }

        return $destination;
    }

    private function deletePublicFileIfManaged(string $path): void
    {
        if ($path === '' || ! str_contains($path, '/from-activities/')) {
            return;
        }

        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }
}
