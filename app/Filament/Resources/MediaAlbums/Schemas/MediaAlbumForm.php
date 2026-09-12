<?php

namespace App\Filament\Resources\MediaAlbums\Schemas;

use App\Enums\MediaType;
use App\Filament\Support\ContentUploads;
use App\Support\UploadRules;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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
                    ->maxSize(UploadRules::maxImageKb()),
                ContentUploads::gallery('incoming_media', 'media')
                    ->label('Toplu fotoğraf ve video')
                    ->helperText('Birden fazla dosyayı birlikte seçin. Kayıt sonrası albüme eklenir. '.UploadRules::galleryHelperText())
                    ->columnSpanFull(),
                Repeater::make('items')
                    ->relationship()
                    ->label('Albüm içerikleri')
                    ->addActionLabel('İçerik ekle')
                    ->defaultItems(0)
                    ->collapsed()
                    ->reorderableWithDragAndDrop()
                    ->orderColumn('sort_order')
                    ->columnSpanFull()
                    ->schema([
                        ContentUploads::mediaFile('path', 'media'),
                        Select::make('type')
                            ->label('Tür')
                            ->options(collect(MediaType::cases())->mapWithKeys(fn (MediaType $type) => [$type->value => $type->label()]))
                            ->default(MediaType::Photo->value)
                            ->required(),
                        TextInput::make('title')->label('Başlık'),
                        TextInput::make('external_url')->label('YouTube / harici bağlantı')->url(),
                        TextInput::make('caption')->label('Açıklama'),
                    ]),
                Toggle::make('is_published')->label('Yayında')->default(true),
            ]);
    }
}
