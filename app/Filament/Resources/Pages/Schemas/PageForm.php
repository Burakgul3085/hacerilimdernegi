<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Support\CorporatePages;
use App\Support\UploadRules;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
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
                    ->label('Görsel')
                    ->image()
                    ->disk('public')
                    ->directory('pages')
                    ->acceptedFileTypes(UploadRules::IMAGE_MIMES)
                    ->maxSize(UploadRules::MAX_IMAGE_KB)
                    ->hidden(fn (Get $get): bool => CorporatePages::isBylaws((string) $get('slug'))),
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
                RichEditor::make('body')->label('İçerik')->columnSpanFull(),
                TextInput::make('seo_title')->label('SEO başlık')->maxLength(255),
                Textarea::make('seo_description')->label('SEO açıklama')->rows(2),
                Toggle::make('is_published')->label('Yayında')->default(true),
            ]);
    }
}
