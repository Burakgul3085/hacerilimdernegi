<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Models\Category;
use App\Models\Post;
use App\Support\UploadRules;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
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
                        TextInput::make('type')
                            ->label('Tür')
                            ->required()
                            ->maxLength(80)
                            ->placeholder('Yazı, Duyuru veya kendi türünüz')
                            ->helperText('Listeden seçmek zorunda değilsiniz. İstediğiniz türü yazın.'),
                        TextInput::make('category_name')
                            ->label('Kategori')
                            ->maxLength(120)
                            ->placeholder('Kategori adını yazın')
                            ->helperText('Yeni bir ad yazarsanız otomatik oluşturulur.'),
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
                        TextInput::make('author_name')
                            ->label('Yazar')
                            ->maxLength(120)
                            ->placeholder('Yazar adını yazın')
                            ->helperText('Kullanıcı listesine bağlı değildir. İstediğiniz adı yazın.'),
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
                            ->maxSize(UploadRules::MAX_IMAGE_KB)
                            ->helperText('Görsel kırpılmaz. Sitede çerçeveye sığdırılır.'),
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
                            ->helperText('Kapak dışındaki ek görseller. Kırpılmaz, çerçeveye sığdırılır. En fazla 8 görsel.'),
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function persistableData(array $data): array
    {
        $data['type'] = Post::normalizeType(is_string($data['type'] ?? null) ? $data['type'] : null);
        $data['category_id'] = Category::findOrCreateIdByName(
            is_string($data['category_name'] ?? null) ? $data['category_name'] : null,
        );
        unset($data['category_name']);

        $authorName = trim((string) ($data['author_name'] ?? ''));
        $data['author_name'] = $authorName === '' ? null : $authorName;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fillableData(array $data, Post $record): array
    {
        $data['type'] = $record->typeLabel();
        $data['category_name'] = $record->category?->name;
        $data['author_name'] = $record->author_name ?: $record->author?->name;

        return $data;
    }
}
