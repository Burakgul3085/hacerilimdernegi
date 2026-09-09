<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Support\UploadRules;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->label('Başlık')->required()->maxLength(255),
                TextInput::make('slug')->label('Bağlantı')->maxLength(255),
                TextInput::make('location')->label('Yer')->maxLength(255),
                TextInput::make('capacity')->label('Kapasite')->numeric(),
                DateTimePicker::make('starts_at')->label('Başlangıç'),
                DateTimePicker::make('ends_at')->label('Bitiş'),
                FileUpload::make('image')
                    ->label('Görsel')
                    ->image()
                    ->disk('public')
                    ->directory('events')
                    ->acceptedFileTypes(UploadRules::IMAGE_MIMES)
                    ->maxSize(UploadRules::MAX_IMAGE_KB),
                RichEditor::make('description')->label('Açıklama')->columnSpanFull(),
                Toggle::make('registration_open')->label('Kayıt açık')->default(true),
                Toggle::make('is_published')->label('Yayında')->default(true),
            ]);
    }
}
