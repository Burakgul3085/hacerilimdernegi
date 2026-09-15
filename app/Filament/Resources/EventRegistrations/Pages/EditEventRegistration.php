<?php

namespace App\Filament\Resources\EventRegistrations\Pages;

use App\Actions\ReplyToEventRegistration;
use App\Filament\Resources\EventRegistrations\EventRegistrationResource;
use App\Models\EventRegistration;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Throwable;

class EditEventRegistration extends EditRecord
{
    protected static string $resource = EventRegistrationResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Başvuru dosyası';
    }

    public function getHeading(): string|Htmlable|null
    {
        /** @var EventRegistration $record */
        $record = $this->getRecord();

        return $record->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        /** @var EventRegistration $record */
        $record = $this->getRecord();

        return $record->subjectTitle().' · '.$record->created_at?->translatedFormat('d F Y, H:i');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Yanıtla')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->modalHeading('Başvuruya yanıt gönder')
                ->modalDescription('Yanıt, başvuranın e-posta adresine gönderilir.')
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
                        app(ReplyToEventRegistration::class)->handle(
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
            DeleteAction::make()
                ->label('Sil')
                ->color('danger'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label('Durumu kaydet'),
            $this->getCancelFormAction()->label('Listeye dön'),
        ];
    }
}
