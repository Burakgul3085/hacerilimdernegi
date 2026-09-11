<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Enums\BoardTier;
use App\Support\CorporatePages;
use App\Support\UploadRules;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->label('Başlık')->required()->maxLength(255),
                TextInput::make('slug')->label('Bağlantı')->maxLength(255)->live(onBlur: true),
                Textarea::make('excerpt')->label('Özet')->rows(3),
                FileUpload::make('image')
                    ->label(fn (Get $get): string => CorporatePages::isMessage((string) $get('slug'))
                        ? 'Başkan fotoğrafı'
                        : 'Görsel')
                    ->image()
                    ->disk('public')
                    ->directory('pages')
                    ->acceptedFileTypes(UploadRules::IMAGE_MIMES)
                    ->maxSize(UploadRules::MAX_IMAGE_KB)
                    ->helperText(fn (Get $get): ?string => CorporatePages::isMessage((string) $get('slug'))
                        ? 'İsteğe bağlı. Yüklemezseniz sitede insan simgesi görünür.'
                        : null)
                    ->hidden(fn (Get $get): bool => CorporatePages::isBylaws((string) $get('slug')) || CorporatePages::isBoard((string) $get('slug'))),
                FileUpload::make('document')
                    ->label('Tüzük PDF')
                    ->acceptedFileTypes(UploadRules::PDF_MIMES)
                    ->disk('public')
                    ->directory('pages/documents')
                    ->maxSize(UploadRules::MAX_PDF_KB)
                    ->openable()
                    ->downloadable()
                    ->helperText('PDF sitede sayfada görüntülenir. En fazla 20 MB.')
                    ->visible(fn (Get $get): bool => CorporatePages::isBylaws((string) $get('slug')))
                    ->columnSpanFull(),
                Repeater::make('board_members')
                    ->label('Yönetim kadrosu')
                    ->addActionLabel('Kişi ekle')
                    ->itemLabel(fn (array $state): ?string => filled($state['name'] ?? null)
                        ? trim(($state['title'] ?? '').' — '.($state['name'] ?? ''), ' —')
                        : null)
                    ->helperText('Kademeye göre sitede yukarıdan aşağı sıralanır. Aynı kademede sürükleyerek sırayı değiştirin.')
                    ->defaultItems(0)
                    ->collapsed()
                    ->reorderableWithDragAndDrop()
                    ->columns(2)
                    ->visible(fn (Get $get): bool => CorporatePages::isBoard((string) $get('slug')))
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')->label('Ad soyad')->required()->maxLength(120),
                        TextInput::make('title')
                            ->label('Görev')
                            ->required()
                            ->maxLength(80)
                            ->placeholder('Başkan, Başkan yardımcısı, Sayman, Üye…'),
                        Select::make('tier')
                            ->label('Kademe')
                            ->options(BoardTier::formOptions())
                            ->default(BoardTier::Member->value)
                            ->required(),
                        FileUpload::make('photo')
                            ->label('Fotoğraf')
                            ->image()
                            ->disk('public')
                            ->directory('pages/board')
                            ->acceptedFileTypes(UploadRules::IMAGE_MIMES)
                            ->maxSize(UploadRules::MAX_IMAGE_KB),
                        Textarea::make('bio')
                            ->label('Kısa not')
                            ->rows(2)
                            ->maxLength(240)
                            ->columnSpanFull(),
                    ]),
                TextInput::make('president_name')
                    ->label('Ad soyad')
                    ->maxLength(120)
                    ->visible(fn (Get $get): bool => CorporatePages::isMessage((string) $get('slug'))),
                TextInput::make('president_title')
                    ->label('Ünvan')
                    ->maxLength(160)
                    ->placeholder(CorporatePages::DEFAULT_PRESIDENT_TITLE)
                    ->helperText('Boş bırakılırsa sitede «'.CorporatePages::DEFAULT_PRESIDENT_TITLE.'» yazılır.')
                    ->visible(fn (Get $get): bool => CorporatePages::isMessage((string) $get('slug'))),
                RichEditor::make('body')
                    ->label(fn (Get $get): string => CorporatePages::isMessage((string) $get('slug'))
                        ? 'Mesaj'
                        : 'İçerik')
                    ->columnSpanFull(),
                TextInput::make('seo_title')->label('SEO başlık')->maxLength(255),
                Textarea::make('seo_description')->label('SEO açıklama')->rows(2),
                Toggle::make('is_published')->label('Yayında')->default(true),
            ]);
    }
}
