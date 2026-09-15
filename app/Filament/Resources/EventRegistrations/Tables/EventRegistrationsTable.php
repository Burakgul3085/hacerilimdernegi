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
            ->striped()
            ->columns([
                TextColumn::make('name')
                    ->label('Başvuran')
                    ->searchable()
                    ->description(fn (EventRegistration $record): string => $record->email)
                    ->wrap(),
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
                    ->description(fn (EventRegistration $record): ?string => filled($record->phone) ? $record->phone : null)
                    ->wrap()
                    ->limit(36),
                TextColumn::make('answers_preview')
                    ->label('Form özeti')
                    ->state(fn (EventRegistration $record): string => $record->answersPreview(64))
                    ->placeholder('—')
                    ->color('gray')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (ApplicationStatus|string|null $state): string => $state instanceof ApplicationStatus
                        ? $state->label()
                        : (string) $state)
                    ->color(fn (ApplicationStatus|string|null $state): string => match ($state instanceof ApplicationStatus ? $state : ApplicationStatus::tryFrom((string) $state)) {
                        ApplicationStatus::Approved => 'success',
                        ApplicationStatus::Rejected => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('replied_at')
                    ->label('Yanıt')
                    ->formatStateUsing(fn ($state): string => $state ? 'Yanıtlandı' : 'Bekliyor')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at_relative')
                    ->label('Ne zaman')
                    ->state(fn (EventRegistration $record): ?string => $record->created_at?->diffForHumans())
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('created_at', $direction)),
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
                EditAction::make()
                    ->label('Dosyayı aç')
                    ->icon('heroicon-o-folder-open'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Seçilenleri sil'),
                ]),
            ])
            ->emptyStateHeading('Henüz kayıt yok')
            ->emptyStateDescription('Faaliyet veya program formlarından gelen başvurular burada listelenir.');
    }
}
