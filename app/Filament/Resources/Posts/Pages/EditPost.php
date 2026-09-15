<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Actions\NotifyVisitorPostApproved;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Posts\Schemas\PostForm;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditPost extends EditRecord
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

                    $this->refreshFormData(['is_published', 'published_at', 'approval_notified_at']);
                }),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Post $record */
        $record = $this->getRecord();

        return PostForm::fillableData($data, $record);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return PostForm::persistableData($data);
    }

    protected function afterSave(): void
    {
        app(NotifyVisitorPostApproved::class)->handle($this->getRecord());
    }
}
