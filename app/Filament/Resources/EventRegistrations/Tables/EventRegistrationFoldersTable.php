<?php

namespace App\Filament\Resources\EventRegistrations\Tables;

use App\Enums\ActivityStatus;
use App\Filament\Resources\EventRegistrations\EventRegistrationResource;
use App\Filament\Resources\EventRegistrations\RegistrationExcelAction;
use App\Filament\Resources\EventRegistrations\RegistrationPrintAction;
use App\Models\Activity;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventRegistrationFoldersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->striped()
            ->modelLabel('faaliyet')
            ->pluralModelLabel('faaliyet')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('title')
                    ->label('Faaliyet')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn (Activity $record): ?string => $record->cadence ?: null),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (ActivityStatus $state): string => $state->label()),
                TextColumn::make('registration_open')
                    ->label('Kayıt')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Açık' : 'Kapalı')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('registrations_count')
                    ->label('Başvuru')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pending_registrations_count')
                    ->label('Bekleyen')
                    ->badge()
                    ->color(fn (int|string|null $state): string => (int) $state > 0 ? 'warning' : 'gray')
                    ->sortable(),
                TextColumn::make('registrations_max_created_at')
                    ->label('Son başvuru')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Henüz yok')
                    ->sortable(),
            ])
            ->recordUrl(fn (Activity $record): string => EventRegistrationResource::getUrl('applicants', ['activity' => $record]))
            ->recordActions([
                RegistrationExcelAction::makeForFolderRow(),
                RegistrationPrintAction::makeForFolderRow(),
                Action::make('open')
                    ->label('Başvuruları aç')
                    ->icon('heroicon-o-users')
                    ->url(fn (Activity $record): string => EventRegistrationResource::getUrl('applicants', ['activity' => $record])),
            ])
            ->emptyStateHeading('Henüz faaliyet yok')
            ->emptyStateDescription('Sistemdeki faaliyetler burada klasör olarak listelenir.');
    }
}
