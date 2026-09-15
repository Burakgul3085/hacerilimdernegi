<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Actions\NotifyVisitorPostApproved;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Başlık')->searchable(),
                TextColumn::make('type')
                    ->label('Tür')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Post::labelForType($state)),
                TextColumn::make('author_name')
                    ->label('Yazar')
                    ->toggleable(),
                TextColumn::make('submitter_email')
                    ->label('Gönderen')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('source')
                    ->label('Kaynak')
                    ->state(fn (Post $record): string => $record->isPendingVisitorSubmission()
                        ? 'Onay bekliyor'
                        : ($record->submitted_from_public ? 'Ziyaretçi' : 'Panel'))
                    ->badge()
                    ->color(fn (Post $record): string => $record->isPendingVisitorSubmission()
                        ? 'warning'
                        : ($record->submitted_from_public ? 'info' : 'gray')),
                IconColumn::make('is_published')->label('Yayında')->boolean(),
                TextColumn::make('published_at')->label('Tarih')->date('d.m.Y'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tür')
                    ->options(Post::typeOptions()),
                SelectFilter::make('source')
                    ->label('Kaynak')
                    ->options([
                        'pending' => 'Onay bekliyor',
                        'visitor' => 'Ziyaretçi',
                        'panel' => 'Panel',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'pending' => $query->where('submitted_from_public', true)->where('is_published', false),
                            'visitor' => $query->where('submitted_from_public', true),
                            'panel' => $query->where('submitted_from_public', false),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Onayla')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Yazıyı onayla ve yayınla')
                    ->modalDescription('Gönderi sitede yayınlanır ve yazarına onay e-postası gider.')
                    ->visible(fn (Post $record): bool => $record->isPendingVisitorSubmission())
                    ->action(function (Post $record): void {
                        $record->forceFill([
                            'is_published' => true,
                            'published_at' => now(),
                        ])->save();

                        try {
                            app(NotifyVisitorPostApproved::class)->handle($record);

                            Notification::make()
                                ->title('Yazı onaylandı')
                                ->body('Gönderi yayınlandı ve yazarına e-posta gönderildi.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('Yazı yayınlandı')
                                ->body('Onay e-postası gönderilemedi.')
                                ->warning()
                                ->send();
                        }
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
