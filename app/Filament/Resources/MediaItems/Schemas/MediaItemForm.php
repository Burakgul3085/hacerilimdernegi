<?php

namespace App\Filament\Resources\MediaItems\Schemas;

use App\Enums\MediaType;
use App\Filament\Support\ContentUploads;
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
                    ->default(MediaType::Photo->value)
                    ->required(),
                TextInput::make('title')->label('Başlık'),
                ContentUploads::gallery('files', 'media')
                    ->visibleOn('create')
                    ->helperText('Birden fazla fotoğraf ve videoyu birlikte seçin. Her dosya ayrı öğe olarak kaydedilir.'),
                ContentUploads::mediaFile('path', 'media')
                    ->visibleOn('edit'),
                TextInput::make('external_url')->label('YouTube / harici bağlantı')->url(),
                TextInput::make('caption')->label('Açıklama'),
                TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
            ]);
    }
}
