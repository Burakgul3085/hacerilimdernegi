<?php

namespace App\Filament\Resources\MediaItems\Pages;

use App\Filament\Resources\MediaItems\MediaItemResource;
use App\Models\MediaItem;
use App\Support\UploadRules;
use Filament\Resources\Pages\CreateRecord;

class CreateMediaItem extends CreateRecord
{
    protected static string $resource = MediaItemResource::class;

    /**
     * @var list<string>
     */
    private array $extraMediaPaths = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $files = $data['files'] ?? [];
        unset($data['files']);

        $paths = collect(is_array($files) ? $files : [])
            ->filter(fn (mixed $path): bool => is_string($path) && $path !== '')
            ->values()
            ->all();

        if ($paths === []) {
            return $data;
        }

        $first = array_shift($paths);
        $data['path'] = $first;
        $data['type'] = UploadRules::typeFromPath($first);
        $this->extraMediaPaths = $paths;

        return $data;
    }

    protected function afterCreate(): void
    {
        $item = $this->record;

        if (! $item instanceof MediaItem || $this->extraMediaPaths === []) {
            return;
        }

        $sort = (int) MediaItem::query()
            ->where('media_album_id', $item->media_album_id)
            ->max('sort_order');

        foreach ($this->extraMediaPaths as $path) {
            $sort++;

            MediaItem::query()->create([
                'media_album_id' => $item->media_album_id,
                'type' => UploadRules::typeFromPath($path),
                'path' => $path,
                'sort_order' => $sort,
            ]);
        }
    }
}
