<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Actions\NotifyVisitorPostApproved;
use App\Filament\Resources\Posts\PostResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Throwable;

class ViewPost extends ViewRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Onayla ve yayınla')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Yazıyı onayla ve yayınla')
                ->modalDescription('Gönderi sitede yayınlanır ve yazarına onay e-postası gider.')
                ->visible(fn (): bool => $this->getRecord()->isPendingVisitorSubmission())
                ->action(function (): void {
                    $record = $this->getRecord();
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

                    $this->record->refresh();
                }),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
