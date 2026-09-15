<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Models\Post;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PostInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Özet')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('title')
                            ->label('Başlık')
                            ->columnSpanFull(),
                        TextEntry::make('type')
                            ->label('Tür')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => Post::labelForType($state)),
                        TextEntry::make('source')
                            ->label('Kaynak')
                            ->badge()
                            ->getStateUsing(fn (Post $record): string => $record->isPendingVisitorSubmission()
                                ? 'Onay bekliyor'
                                : ($record->submitted_from_public ? 'Ziyaretçi' : 'Panel'))
                            ->color(fn (Post $record): string => $record->isPendingVisitorSubmission()
                                ? 'warning'
                                : ($record->submitted_from_public ? 'info' : 'gray')),
                        TextEntry::make('author_name')
                            ->label('Yazar')
                            ->getStateUsing(fn (Post $record): string => $record->byline() ?: '—'),
                        TextEntry::make('submitter_email')
                            ->label('Gönderen e-posta')
                            ->placeholder('—')
                            ->visible(fn (Post $record): bool => $record->submitted_from_public),
                        TextEntry::make('category.name')
                            ->label('Kategori')
                            ->placeholder('—'),
                        TextEntry::make('is_published')
                            ->label('Yayında')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Evet' : 'Hayır')
                            ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                        TextEntry::make('published_at')
                            ->label('Yayın tarihi')
                            ->dateTime('d.m.Y H:i')
                            ->timezone('Europe/Istanbul')
                            ->placeholder('—'),
                        TextEntry::make('subtitle')
                            ->label('Alt başlık')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('excerpt')
                            ->label('Özet')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                Section::make('Görseller')
                    ->schema([
                        ImageEntry::make('image')
                            ->label('Kapak')
                            ->disk('public')
                            ->imageHeight(220)
                            ->visible(fn (Post $record): bool => filled($record->image)),
                        ImageEntry::make('gallery_preview')
                            ->label('Fotoğraflar')
                            ->disk('public')
                            ->imageHeight(160)
                            ->getStateUsing(fn (Post $record): array => $record->galleryPaths())
                            ->visible(fn (Post $record): bool => $record->galleryPaths() !== []),
                        TextEntry::make('no_images')
                            ->label('Görseller')
                            ->state('Kapak veya fotoğraf yok.')
                            ->visible(fn (Post $record): bool => blank($record->image) && $record->galleryPaths() === []),
                    ]),
                Section::make('İçerik')
                    ->schema([
                        TextEntry::make('featured_quote')
                            ->label('Öne çıkan alıntı')
                            ->placeholder('—')
                            ->visible(fn (Post $record): bool => filled($record->featured_quote)),
                        TextEntry::make('body')
                            ->label('Metin')
                            ->html()
                            ->getStateUsing(fn (Post $record): string => filled($record->body)
                                ? (string) $record->body
                                : '<p>—</p>'),
                        TextEntry::make('source_label')
                            ->label('Kaynak')
                            ->getStateUsing(function (Post $record): string {
                                if (blank($record->source_label) && blank($record->source_url)) {
                                    return '—';
                                }

                                $label = filled($record->source_label) ? $record->source_label : 'Kaynak';
                                $href = $record->sourceHref();

                                return $href
                                    ? $label.' ('.$href.')'
                                    : $label;
                            }),
                    ]),
            ]);
    }
}
