<?php

namespace App\Filament\Resources\Announcements\Schemas;

use App\Filament\Support\ContentUploads;
use App\Support\UploadRules;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Duyuru')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Başlık')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('slug')
                            ->label('Bağlantı')
                            ->maxLength(255),
                        Textarea::make('excerpt')
                            ->label('Özet')
                            ->rows(3)
                            ->helperText('Kartlarda ve duyuru girişinde görünür.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Görseller')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Kapak')
                            ->image()
                            ->disk('public')
                            ->directory('announcements')
                            ->acceptedFileTypes(UploadRules::IMAGE_MIMES)
                            ->maxSize(UploadRules::maxImageKb())
                            ->helperText('Görsel kırpılmaz. Sitede çerçeveye sığdırılır.'),
                        ContentUploads::gallery('gallery', 'announcements/gallery'),
                    ]),
                Section::make('İçerik')
                    ->schema([
                        ContentUploads::withEditorUploads(
                            RichEditor::make('body')->label('İçerik')->columnSpanFull(),
                        ),
                    ]),
                Section::make('Yayın')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('published_at')
                            ->label('Yayın tarihi')
                            ->default(now())
                            ->timezone('Europe/Istanbul')
                            ->helperText('Boş bırakılırsa hemen yayınlanır. Yarın veya sonrası seçilirse o güne kadar sitede görünmez.'),
                        Toggle::make('is_published')->label('Yayında')->default(true),
                    ]),
            ]);
    }
}
