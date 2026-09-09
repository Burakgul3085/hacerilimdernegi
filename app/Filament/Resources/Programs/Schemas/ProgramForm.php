<?php

namespace App\Filament\Resources\Programs\Schemas;

use App\Enums\ProgramType;
use App\Support\UploadRules;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Tür')
                    ->options(collect(ProgramType::cases())->mapWithKeys(fn (ProgramType $type) => [$type->value => $type->label()]))
                    ->required(),
                TextInput::make('title')->label('Başlık')->required()->maxLength(255),
                TextInput::make('slug')->label('Bağlantı')->maxLength(255),
                TextInput::make('instructor')->label('Hoca / konuşmacı')->maxLength(255),
                TextInput::make('location')->label('Yer')->maxLength(255),
                DateTimePicker::make('starts_at')->label('Başlangıç'),
                DateTimePicker::make('ends_at')->label('Bitiş'),
                FileUpload::make('image')
                    ->label('Görsel')
                    ->image()
                    ->disk('public')
                    ->directory('programs')
                    ->acceptedFileTypes(UploadRules::IMAGE_MIMES)
                    ->maxSize(UploadRules::MAX_IMAGE_KB),
                RichEditor::make('description')->label('Açıklama')->columnSpanFull(),
                Toggle::make('is_published')->label('Yayında')->default(true),
            ]);
    }
}
