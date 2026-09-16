<?php

namespace App\Filament\Resources\MediaAlbums\Tables;

use App\Models\MediaAlbum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MediaAlbumsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('title')
            ->columns([
                TextColumn::make('title')->label('Başlık')->searchable()->wrap(),
                TextColumn::make('parent.title')
                    ->label('Üst albüm')
                    ->placeholder('Ana albüm')
                    ->toggleable(),
                TextColumn::make('items_count')->counts('items')->label('Öğe'),
                TextColumn::make('children_count')->counts('children')->label('İç albüm'),
                IconColumn::make('is_published')->label('Yayında')->boolean(),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Üst albüm')
                    ->options(fn (): array => MediaAlbum::query()->roots()->orderBy('title')->pluck('title', 'id')->all())
                    ->placeholder('Tümü'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
