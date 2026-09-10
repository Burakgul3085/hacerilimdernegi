<?php

namespace App\Filament\Resources\MembershipApplications\Pages;

use App\Actions\ReplyToMembershipApplication;
use App\Filament\Resources\MembershipApplications\MembershipApplicationResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditMembershipApplication extends EditRecord
{
    protected static string $resource = MembershipApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Yanıtla')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->modalHeading('Başvuruya yanıt gönder')
                ->modalDescription('Yanıtınız başvurunun e-posta adresine PHPMailer ile iletilir.')
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
                        app(ReplyToMembershipApplication::class)->handle(
                            $this->getRecord(),
                            auth()->user(),
                            $data['body'],
                        );

                        Notification::make()
                            ->title('Yanıt gönderildi')
                            ->body('Mesaj başvurunun e-postasına iletildi.')
                            ->success()
                            ->send();

                        $this->refreshFormData(['replied_at']);
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

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label('Kaydet'),
            $this->getCancelFormAction(),
        ];
    }
}
