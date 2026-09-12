<?php

namespace App\Filament\Resources\MediaAlbums\Pages;

use App\Filament\Resources\MediaAlbums\Concerns\StoresIncomingAlbumMedia;
use App\Filament\Resources\MediaAlbums\MediaAlbumResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMediaAlbum extends EditRecord
{
    use StoresIncomingAlbumMedia;

    protected static string $resource = MediaAlbumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->pullIncomingMedia($data);
    }

    protected function afterSave(): void
    {
        $this->storeIncomingMedia();
    }
}
