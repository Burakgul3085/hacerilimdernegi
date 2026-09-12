<?php

namespace App\Filament\Resources\MediaAlbums\Concerns;

use App\Support\UploadRules;

trait StoresIncomingAlbumMedia
{
    /**
     * @var list<string>
     */
    private array $incomingMediaPaths = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function pullIncomingMedia(array $data): array
    {
        $files = $data['incoming_media'] ?? [];
        unset($data['incoming_media']);

        $this->incomingMediaPaths = collect(is_array($files) ? $files : [])
            ->filter(fn (mixed $path): bool => is_string($path) && $path !== '')
            ->values()
            ->all();

        return $data;
    }

    protected function storeIncomingMedia(): void
    {
        $album = $this->record;

        if ($album === null || $this->incomingMediaPaths === []) {
            return;
        }

        $sort = (int) $album->items()->max('sort_order');

        foreach ($this->incomingMediaPaths as $path) {
            $sort++;

            $album->items()->create([
                'type' => UploadRules::typeFromPath($path),
                'path' => $path,
                'sort_order' => $sort,
            ]);
        }

        $this->incomingMediaPaths = [];
    }
}
