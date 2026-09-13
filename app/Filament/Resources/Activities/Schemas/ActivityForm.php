<?php

namespace App\Filament\Resources\Activities\Schemas;

use App\Enums\ActivityStatus;
use App\Filament\Support\ContentUploads;
use App\Support\UploadRules;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
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
                Section::make('Kimlik')
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
                            ->helperText('Kartta iki satır görünür.')
                            ->columnSpanFull(),
                        Toggle::make('is_published')->label('Yayında')->default(true),
                    ]),

                Section::make('Metin')
                    ->schema([
                        ContentUploads::withEditorUploads(
                            RichEditor::make('description')->label('Açıklama')->columnSpanFull(),
                        ),
                    ]),

                Section::make('Görsel')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Kapak')
                            ->image()
                            ->disk('public')
                            ->directory('activities')
                            ->acceptedFileTypes(UploadRules::IMAGE_MIMES)
                            ->maxSize(UploadRules::maxImageKb()),
                        ContentUploads::gallery('gallery', 'activities/gallery')->columnSpanFull(),
                    ]),

                Section::make('Kenar kutuları')
                    ->description('Detay sayfasında metnin yanında durur. En fazla üç kutu. Bağış bilgisi buraya yazılmaz.')
                    ->collapsed()
                    ->schema([
                        Repeater::make('highlights')
                            ->label('Kutular')
                            ->maxItems(3)
                            ->defaultItems(0)
                            ->addActionLabel('Kutu ekle')
                            ->columns(2)
                            ->schema([
                                TextInput::make('title')->label('Başlık')->maxLength(80)->required(),
                                Textarea::make('text')->label('Metin')->rows(2)->maxLength(280)->required(),
                            ]),
                    ]),

                Section::make('Oturumlar')
                    ->description('Düzenli hattın ritim cümlesi ve tarihleri. Geçmiş tarih sitede “yapıldı”, gelecek tarih “sonraki oturum” olur.')
                    ->schema([
                        TextInput::make('cadence')
                            ->label('Ritim')
                            ->maxLength(120)
                            ->placeholder('Her cumartesi 14.00'),
                        Repeater::make('sessions')
                            ->relationship()
                            ->label('Tarihler')
                            ->addActionLabel('Oturum ekle')
                            ->defaultItems(0)
                            ->collapsed()
                            ->reorderable(false)
                            ->columnSpanFull()
                            ->schema([
                                DateTimePicker::make('starts_at')->label('Tarih ve saat')->required(),
                                TextInput::make('location')->label('Yer')->maxLength(255),
                                TextInput::make('note')->label('Kısa not')->maxLength(180),
                            ]),
                    ]),
            ]);
    }
}
