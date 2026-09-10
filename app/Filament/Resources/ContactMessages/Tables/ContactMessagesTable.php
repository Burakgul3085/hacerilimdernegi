<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Ad')->searchable()->weight(fn ($record) => $record->is_read ? null : 'bold'),
                TextColumn::make('email')->label('E-posta')->searchable(),
                TextColumn::make('subject')->label('Konu')->limit(40),
                IconColumn::make('is_read')->label('Okundu')->boolean(),
                TextColumn::make('replied_at')
                    ->label('Yanıt')
                    ->formatStateUsing(fn ($state) => $state ? 'Yanıtlandı' : 'Bekliyor')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning'),
                TextColumn::make('created_at')->label('Tarih')->since(),
            ])
            ->recordActions([
                EditAction::make()->label('Görüntüle'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
