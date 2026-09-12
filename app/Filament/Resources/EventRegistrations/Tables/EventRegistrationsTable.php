<?php

namespace App\Filament\Resources\EventRegistrations\Tables;

use App\Models\EventRegistration;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventRegistrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('subject_title')
                    ->label('Program')
                    ->state(fn (EventRegistration $record): string => $record->subjectTitle())
                    ->searchable(query: function (Builder $query, string $search): void {
                        $query->where(function (Builder $builder) use ($search): void {
                            $builder->whereHas('event', fn (Builder $event) => $event->where('title', 'like', "%{$search}%"))
                                ->orWhereHas('program', fn (Builder $program) => $program->where('title', 'like', "%{$search}%"));
                        });
                    }),
                TextColumn::make('name')->label('Ad')->searchable(),
                TextColumn::make('email')->label('E-posta')->searchable(),
                TextColumn::make('phone')->label('Telefon'),
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
