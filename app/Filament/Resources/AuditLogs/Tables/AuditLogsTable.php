<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Tarih')->since()->sortable(),
                TextColumn::make('user.name')->label('Kullanıcı'),
                TextColumn::make('action')->label('İşlem')->badge(),
                TextColumn::make('model_type')->label('Kayıt')->limit(40),
                TextColumn::make('ip_address')->label('IP'),
            ])
            ->recordActions([
                EditAction::make()->label('Detay'),
            ]);
    }
}
