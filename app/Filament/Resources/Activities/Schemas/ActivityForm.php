<?php

namespace App\Filament\Resources\Activities\Schemas;

use App\Enums\ActivityStatus;
use App\Filament\Support\ContentUploads;
use App\Support\RegistrationForm;
use App\Support\UploadRules;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Tabs::make('activityTabs')
                    ->persistTabInQueryString()
                    ->columnSpan(['default' => 1, 'lg' => 2])
                    ->tabs([
                        Tab::make('Genel bilgiler')
                            ->icon(Heroicon::OutlinedDocumentText)
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('title')->label('Başlık')->required()->maxLength(255),
                                    TextInput::make('slug')->label('Bağlantı')->maxLength(255),
                                ]),
                                Textarea::make('excerpt')
                                    ->label('Kısa özet')
                                    ->rows(3)
                                    ->maxLength(280)
                                    ->helperText('Kartta iki satır görünür.'),

                                ContentUploads::withEditorUploads(
                                    RichEditor::make('description')->label('Açıklama')->columnSpanFull(),
                                ),
                            ]),

                        Tab::make('Kayıt formu')
                            ->icon(Heroicon::OutlinedClipboardDocumentList)
                            ->schema([
                                Text::make('Ad soyad, e-posta, telefon ve KVKK her formda sabittir. Aşağıya faaliyete özel sorular ekleyin; yardımcı metin ve yer tutucu ile formu netleştirin. Boş bırakılırsa yalnızca “Not” alanı kalır.')
                                    ->color('gray'),

                                Repeater::make('registration_fields')
                                    ->label('Ek sorular')
                                    ->default([
                                        [
                                            'label' => 'Not',
                                            'type' => 'textarea',
                                            'required' => false,
                                            'help' => '',
                                            'placeholder' => 'Eklemek istedikleriniz',
                                            'options' => '',
                                        ],
                                    ])
                                    ->addActionLabel('Soru ekle')
                                    ->reorderable()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('label')
                                            ->label('Soru')
                                            ->required()
                                            ->maxLength(120)
                                            ->columnSpanFull(),
                                        Select::make('type')
                                            ->label('Tür')
                                            ->options(RegistrationForm::typeOptions())
                                            ->default('text')
                                            ->required()
                                            ->live(),
                                        Hidden::make('key'),
                                        Toggle::make('required')
                                            ->label('Zorunlu')
                                            ->default(false),
                                        TextInput::make('placeholder')
                                            ->label('Yer tutucu')
                                            ->maxLength(120)
                                            ->visible(fn ($get): bool => ! in_array($get('type'), ['checkbox', 'select', 'date'], true))
                                            ->columnSpanFull(),
                                        Textarea::make('help')
                                            ->label('Yardımcı metin')
                                            ->rows(2)
                                            ->maxLength(280)
                                            ->helperText('Soru altında gri açıklama olarak görünür.')
                                            ->columnSpanFull(),
                                        Textarea::make('options')
                                            ->label('Seçenekler')
                                            ->rows(4)
                                            ->helperText('Her satıra bir seçenek yazın.')
                                            ->formatStateUsing(function (mixed $state): string {
                                                if (is_array($state)) {
                                                    return implode("\n", $state);
                                                }

                                                return (string) ($state ?? '');
                                            })
                                            ->visible(fn ($get): bool => $get('type') === 'select')
                                            ->required(fn ($get): bool => $get('type') === 'select')
                                            ->columnSpanFull(),
                                    ])
                                    ->itemLabel(function (array $state): ?string {
                                        $label = trim((string) ($state['label'] ?? ''));

                                        if ($label === '') {
                                            return null;
                                        }

                                        $required = ! empty($state['required']) ? ' · Zorunlu' : '';

                                        return $label.$required;
                                    }),
                            ]),

                        DecisionMailSection::tab(),

                        Tab::make('Galeri')
                            ->icon(Heroicon::OutlinedPhoto)
                            ->schema([
                                ContentUploads::gallery('gallery', 'activities/gallery')->columnSpanFull(),
                            ]),

                        Tab::make('Oturumlar')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->schema([
                                Text::make('Düzenli hattın ritim cümlesi ve tarihleri. Geçmiş tarih sitede “yapıldı”, gelecek tarih “sonraki oturum” olur.')
                                    ->color('gray'),

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

                        Tab::make('Kenar kutuları')
                            ->icon(Heroicon::OutlinedSquares2x2)
                            ->schema([
                                Text::make('Detay sayfasında metnin yanında durur. En fazla üç kutu. Bağış bilgisi buraya yazılmaz.')
                                    ->color('gray'),

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
                    ]),

                Group::make()
                    ->columnSpan(['default' => 1, 'lg' => 1])
                    ->schema([
                        Section::make('Yayın')
                            ->icon(Heroicon::OutlinedGlobeAlt)
                            ->schema([
                                Select::make('status')
                                    ->label('Durum')
                                    ->options(collect(ActivityStatus::cases())->mapWithKeys(
                                        fn (ActivityStatus $status): array => [$status->value => $status->label()],
                                    ))
                                    ->required()
                                    ->default(ActivityStatus::Ongoing->value),
                                Toggle::make('is_published')->label('Yayında')->default(true),
                                Toggle::make('registration_open')
                                    ->label('Kayıt açık')
                                    ->helperText('Kapalıysa sitede başvuru formu görünmez.')
                                    ->default(true),
                                TextInput::make('sort_order')
                                    ->label('Sıra')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0)
                                    ->helperText('Büyük sayı üstte görünür (ör. 33 üstte, 1 altta). Negatif olamaz.'),
                            ]),

                        Section::make('Kapak görseli')
                            ->icon(Heroicon::OutlinedPhoto)
                            ->schema([

                                FileUpload::make('image')
                                    ->hiddenLabel()
                                    ->image()
                                    ->disk('public')
                                    ->directory('activities')
                                    ->acceptedFileTypes(UploadRules::IMAGE_MIMES)
                                    ->maxSize(UploadRules::maxImageKb()),
                            ]),
                    ]),
            ]);
    }
}
