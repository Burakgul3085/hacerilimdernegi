<?php

namespace App\Filament\Resources\MediaAlbums\Pages;

use App\Filament\Resources\MediaAlbums\Concerns\StoresIncomingAlbumMedia;
use App\Filament\Resources\MediaAlbums\MediaAlbumResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMediaAlbum extends CreateRecord
{
    use StoresIncomingAlbumMedia;

    protected static string $resource = MediaAlbumResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->pullIncomingMedia($data);
    }

    protected function afterCreate(): void
    {
        $this->storeIncomingMedia();
    }
}
