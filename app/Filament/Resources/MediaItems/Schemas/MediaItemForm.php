<?php

namespace App\Filament\Resources\MediaItems\Schemas;

use App\Enums\MediaType;
use App\Support\UploadRules;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MediaItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('media_album_id')->label('Albüm')->relationship('album', 'title')->required(),
                Select::make('type')
                    ->label('Tür')
                    ->options(collect(MediaType::cases())->mapWithKeys(fn (MediaType $type) => [$type->value => $type->label()]))
                    ->required(),
                TextInput::make('title')->label('Başlık'),
                FileUpload::make('path')
                    ->label('Dosya')
                    ->disk('public')
                    ->directory('media')
                    ->acceptedFileTypes([...UploadRules::IMAGE_MIMES, ...UploadRules::AUDIO_MIMES])
                    ->maxSize(UploadRules::MAX_AUDIO_KB),
                TextInput::make('external_url')->label('YouTube / harici bağlantı')->url(),
                TextInput::make('caption')->label('Açıklama'),
                TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
            ]);
    }
}
