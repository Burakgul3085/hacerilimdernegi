<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use App\Models\AuditLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AuditLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('summary')
                    ->label('Ne oldu')
                    ->getStateUsing(fn (AuditLog $record): string => $record->summary()),
                TextEntry::make('actor')
                    ->label('Kim')
                    ->getStateUsing(fn (AuditLog $record): string => $record->actorName()),
                TextEntry::make('when')
                    ->label('Ne zaman')
                    ->getStateUsing(function (AuditLog $record): string {
                        return $record->created_at?->timezone('Europe/Istanbul')->format('d.m.Y H:i') ?? '';
                    }),
                TextEntry::make('section')
                    ->label('Bölüm')
                    ->badge()
                    ->getStateUsing(fn (AuditLog $record): string => $record->typeLabel()),
                TextEntry::make('record_name')
                    ->label('Kayıt')
                    ->getStateUsing(fn (AuditLog $record): string => $record->recordLabel()),
                TextEntry::make('action_name')
                    ->label('İşlem')
                    ->badge()
                    ->getStateUsing(fn (AuditLog $record): string => $record->actionLabel()),
                TextEntry::make('changes')
                    ->label('Ne değişti')
                    ->html()
                    ->getStateUsing(function (AuditLog $record): string {
                        return nl2br(e($record->changeSummaryText()));
                    }),
                TextEntry::make('ip_address')
                    ->label('IP adresi')
                    ->placeholder('—'),
            ]);
    }
}
