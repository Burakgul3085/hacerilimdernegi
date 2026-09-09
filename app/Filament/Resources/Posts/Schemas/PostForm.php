<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Support\UploadRules;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Tür')
                    ->options([
                        'article' => 'Yazı',
                        'announcement' => 'Duyuru',
                    ])
                    ->required(),
                Select::make('category_id')->label('Kategori')->relationship('category', 'name'),
                TextInput::make('title')->label('Başlık')->required()->maxLength(255),
                TextInput::make('slug')->label('Bağlantı')->maxLength(255),
                Textarea::make('excerpt')->label('Özet')->rows(3),
                FileUpload::make('image')
                    ->label('Kapak')
                    ->image()
                    ->disk('public')
                    ->directory('posts')
                    ->acceptedFileTypes(UploadRules::IMAGE_MIMES)
                    ->maxSize(UploadRules::MAX_IMAGE_KB),
                RichEditor::make('body')->label('İçerik')->columnSpanFull(),
                DateTimePicker::make('published_at')->label('Yayın tarihi'),
                Toggle::make('is_published')->label('Yayında')->default(true),
            ]);
    }
}
