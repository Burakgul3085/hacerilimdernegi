<?php

namespace App\Filament\Resources\MediaAlbums\Schemas;

use App\Support\UploadRules;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MediaAlbumForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->label('Başlık')->required(),
                TextInput::make('slug')->label('Bağlantı'),
                Textarea::make('description')->label('Açıklama'),
                FileUpload::make('cover')
                    ->label('Kapak')
                    ->image()
                    ->disk('public')
                    ->directory('albums')
                    ->acceptedFileTypes(UploadRules::IMAGE_MIMES)
                    ->maxSize(UploadRules::MAX_IMAGE_KB),
                Toggle::make('is_published')->label('Yayında')->default(true),
            ]);
    }
}
