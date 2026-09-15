<?php

namespace App\Filament\Resources\EventRegistrations\Tables;

use App\Enums\ApplicationStatus;
use App\Models\EventRegistration;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
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
                                ->orWhereHas('program', fn (Builder $program) => $program->where('title', 'like', "%{$search}%"))
                                ->orWhereHas('activity', fn (Builder $activity) => $activity->where('title', 'like', "%{$search}%"));
                        });
                    })
                    ->wrap()
                    ->limit(40),
                TextColumn::make('name')->label('Ad')->searchable(),
                TextColumn::make('email')->label('E-posta')->searchable()->toggleable(),
                TextColumn::make('answers_preview')
                    ->label('Özet')
                    ->state(fn (EventRegistration $record): string => $record->answersPreview())
                    ->placeholder('—')
                    ->wrap()
                    ->limit(70)
                    ->toggleable(),
                TextColumn::make('status')->label('Durum')->badge(),
                TextColumn::make('replied_at')
                    ->label('Yanıt')
                    ->formatStateUsing(fn ($state) => $state ? 'Yanıtlandı' : 'Bekliyor')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning'),
                TextColumn::make('created_at')->label('Tarih')->since(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(collect(ApplicationStatus::cases())->mapWithKeys(
                        fn (ApplicationStatus $status): array => [$status->value => $status->label()],
                    )),
                TernaryFilter::make('replied')
                    ->label('Yanıt')
                    ->placeholder('Tümü')
                    ->trueLabel('Yanıtlandı')
                    ->falseLabel('Bekliyor')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('replied_at'),
                        false: fn (Builder $query) => $query->whereNull('replied_at'),
                    ),
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
