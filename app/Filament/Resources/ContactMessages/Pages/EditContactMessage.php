<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Actions\ReplyToContactMessage;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditContactMessage extends EditRecord
{
    protected static string $resource = ContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Yanıtla')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->modalHeading('Mesaja yanıt gönder')
                ->modalDescription('Yanıtınız ziyaretçinin e-posta adresine PHPMailer ile iletilir.')
                ->modalSubmitActionLabel('Gönder')
                ->schema([
                    Textarea::make('body')
                        ->label('Yanıtınız')
                        ->required()
                        ->rows(8)
                        ->maxLength(5000),
                ])
                ->action(function (array $data): void {
                    try {
                        app(ReplyToContactMessage::class)->handle(
                            $this->getRecord(),
                            auth()->user(),
                            $data['body'],
                        );

                        Notification::make()
                            ->title('Yanıt gönderildi')
                            ->body('Mesaj ziyaretçinin e-postasına iletildi.')
                            ->success()
                            ->send();

                        $this->refreshFormData(['is_read', 'replied_at']);
                        $this->dispatch('$refresh');
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Yanıt gönderilemedi')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! $this->getRecord()->is_read) {
            $this->getRecord()->forceFill(['is_read' => true])->save();
            $data['is_read'] = true;
        }

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label('Kaydet'),
            $this->getCancelFormAction(),
        ];
    }
}
