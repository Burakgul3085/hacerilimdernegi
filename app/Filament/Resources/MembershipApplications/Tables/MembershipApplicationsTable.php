<?php

namespace App\Filament\Resources\MembershipApplications\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MembershipApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Ad')->searchable(),
                TextColumn::make('email')->label('E-posta')->searchable(),
                TextColumn::make('phone')->label('Telefon'),
                TextColumn::make('city')->label('Şehir'),
                TextColumn::make('status')->label('Durum')->badge(),
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
