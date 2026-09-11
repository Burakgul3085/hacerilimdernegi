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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Yazı')
                    ->columns(2)
                    ->schema([
                        Select::make('type')
                            ->label('Tür')
                            ->options([
                                'article' => 'Yazı',
                                'announcement' => 'Duyuru',
                            ])
                            ->required(),
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('title')
                            ->label('Başlık')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('subtitle')
                            ->label('Alt başlık')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('slug')
                            ->label('Bağlantı')
                            ->maxLength(255),
                        TextInput::make('location')
                            ->label('Yer')
                            ->maxLength(255),
                        Select::make('author_id')
                            ->label('Yazar')
                            ->relationship('author', 'name')
                            ->searchable()
                            ->preload(),
                        Textarea::make('excerpt')
                            ->label('Özet')
                            ->rows(3)
                            ->helperText('Kartlarda ve yazı girişinde görünür.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Görseller')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Kapak')
                            ->image()
                            ->disk('public')
                            ->directory('posts')
                            ->acceptedFileTypes(UploadRules::IMAGE_MIMES)
                            ->maxSize(UploadRules::MAX_IMAGE_KB),
                        FileUpload::make('gallery')
                            ->label('Galeri')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->disk('public')
                            ->directory('posts/gallery')
                            ->acceptedFileTypes(UploadRules::IMAGE_MIMES)
                            ->maxSize(UploadRules::MAX_IMAGE_KB)
                            ->maxFiles(8)
                            ->helperText('Kapak dışındaki ek görseller. En fazla 8 görsel.'),
                    ]),
                Section::make('İçerik')
                    ->columns(2)
                    ->schema([
                        Textarea::make('featured_quote')
                            ->label('Öne çıkan alıntı')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                        RichEditor::make('body')
                            ->label('İçerik')
                            ->columnSpanFull(),
                        TextInput::make('source_label')
                            ->label('Kaynak adı')
                            ->maxLength(120),
                        TextInput::make('source_url')
                            ->label('Kaynak bağlantısı')
                            ->url()
                            ->maxLength(500)
                            ->helperText('Yalnızca http veya https adresi.'),
                    ]),
                Section::make('Yayın')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('published_at')->label('Yayın tarihi'),
                        Toggle::make('is_published')->label('Yayında')->default(true),
                    ]),
            ]);
    }
}
