<?php

namespace App\Filament\Resources\Activities\Schemas;

use App\Enums\ActivityStatus;
use App\Filament\Support\ContentUploads;
use App\Support\UploadRules;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Faaliyet hattı')
                    ->description('Sitede kart olarak durur. Tarihli ders veya kayıtlı programı buraya bağlayınca detayda oturum görünür.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')->label('Başlık')->required()->maxLength(255),
                        TextInput::make('slug')->label('Bağlantı')->maxLength(255),
                        Select::make('status')
                            ->label('Durum')
                            ->options(collect(ActivityStatus::cases())->mapWithKeys(
                                fn (ActivityStatus $status): array => [$status->value => $status->label()],
                            ))
                            ->required()
                            ->default(ActivityStatus::Ongoing->value),
                        TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                        Textarea::make('excerpt')
                            ->label('Kısa özet')
                            ->rows(3)
                            ->maxLength(280)
                            ->columnSpanFull(),
                        FileUpload::make('image')
                            ->label('Kapak')
                            ->image()
                            ->disk('public')
                            ->directory('activities')
                            ->acceptedFileTypes(UploadRules::IMAGE_MIMES)
                            ->maxSize(UploadRules::maxImageKb()),
                        ContentUploads::gallery('gallery', 'activities/gallery')->columnSpanFull(),
                        ContentUploads::withEditorUploads(
                            RichEditor::make('description')->label('Açıklama')->columnSpanFull(),
                        ),
                        Toggle::make('is_published')->label('Yayında')->default(true),
                    ]),
            ]);
    }
}
