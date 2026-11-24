<?php

namespace App\Filament\Resources\EventRegistrations;

use App\Actions\DecideEventRegistration;
use App\Enums\ApplicationStatus;
use App\Models\EventRegistration;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

/**
 * Başvuru dosyası ve listesi için Onayla / Reddet / Yeniden gönder işlemleri.
 */
class RegistrationDecisionActions
{
    public static function decide(ApplicationStatus $status, ?callable $after = null): Action
    {
        $approved = $status === ApplicationStatus::Approved;
        $mailName = $approved ? 'onay' : 'ret';

        return Action::make($approved ? 'approve' : 'reject')
            ->label($approved ? 'Onayla' : 'Reddet')
            ->icon($approved ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedXCircle)
            ->color($approved ? 'success' : 'danger')
            ->visible(fn (EventRegistration $record): bool => $record->status !== $status)
            ->modalIcon($approved ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedXCircle)
            ->modalIconColor($approved ? 'success' : 'danger')
            ->modalHeading($approved ? 'Başvuru onaylansın mı?' : 'Başvuru reddedilsin mi?')
            ->modalDescription(fn (EventRegistration $record): string => $approved
                ? "{$record->name} adlı kişinin «{$record->subjectTitle()}» başvurusu onaylanacak."
                : "{$record->name} adlı kişinin «{$record->subjectTitle()}» başvurusu reddedilecek.")
            ->modalSubmitActionLabel($approved ? 'Onayla' : 'Reddet')
            ->modalWidth('lg')
            ->schema(fn (EventRegistration $record): array => [
                self::sendMailToggle($record, $status, $mailName),
            ])
            ->action(function (array $data, EventRegistration $record) use ($status, $approved, $mailName, $after): void {
                $queued = app(DecideEventRegistration::class)->handle($record, $status, (bool) ($data['send_mail'] ?? false));

                Notification::make()
                    ->title($approved ? 'Başvuru onaylandı' : 'Başvuru reddedildi')
                    ->body($queued ? ucfirst($mailName).' e-postası gönderiliyor.' : 'E-posta gönderilmedi.')
                    ->success()
                    ->send();

                if ($after !== null) {
                    $after();
                }
            });
    }

    public static function resend(?callable $after = null): Action
    {
        return Action::make('resendDecisionMail')
            ->label('E-postayı yeniden gönder')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->visible(fn (EventRegistration $record): bool => $record->decision_mail_state !== null
                && $record->status !== ApplicationStatus::Pending
                && $record->decision_mail_status === $record->status)
            ->requiresConfirmation()
            ->modalHeading('E-posta yeniden gönderilsin mi?')
            ->modalDescription(fn (EventRegistration $record): string => ($record->status === ApplicationStatus::Approved ? 'Onay' : 'Ret')
                ." e-postası {$record->email} adresine yeniden gönderilecek.")
            ->modalSubmitActionLabel('Yeniden gönder')
            ->action(function (EventRegistration $record) use ($after): void {
                $queued = app(DecideEventRegistration::class)->resend($record);

                $notification = Notification::make();

                $queued
                    ? $notification->title('E-posta yeniden gönderiliyor')->success()
                    : $notification->title('E-posta gönderilemedi')->body('Bu faaliyette ilgili e-posta kapalı.')->warning();

                $notification->send();

                if ($after !== null) {
                    $after();
                }
            });
    }

    public static function bulkDecide(ApplicationStatus $status): BulkAction
    {
        $approved = $status === ApplicationStatus::Approved;
        $mailName = $approved ? 'onay' : 'ret';

        return BulkAction::make($approved ? 'bulkApprove' : 'bulkReject')
            ->label($approved ? 'Seçilenleri onayla' : 'Seçilenleri reddet')
            ->icon($approved ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedXCircle)
            ->color($approved ? 'success' : 'danger')
            ->modalIcon($approved ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedXCircle)
            ->modalIconColor($approved ? 'success' : 'danger')
            ->modalHeading($approved ? 'Seçilen başvurular onaylansın mı?' : 'Seçilen başvurular reddedilsin mi?')
            ->modalDescription(function (Collection $records) use ($status, $mailName, $approved): string {
                $decider = app(DecideEventRegistration::class);
                $mailCount = $records->filter(fn (EventRegistration $record): bool => $decider->mailBlocker($record, $status) === null)->count();
                $verb = $approved ? 'onaylanacak' : 'reddedilecek';

                return "{$records->count()} başvuru {$verb}. E-posta gönder seçiliyse {$mailCount} kişiye {$mailName} e-postası gider; e-postası kapalı faaliyetler ve daha önce e-posta almış kişiler atlanır.";
            })
            ->modalSubmitActionLabel($approved ? 'Onayla' : 'Reddet')
            ->modalWidth('lg')
            ->schema([
                Toggle::make('send_mail')
                    ->label("Kişilere {$mailName} e-postası gönder")
                    ->default(true),
            ])
            ->action(function (array $data, Collection $records) use ($status, $approved): void {
                $decider = app(DecideEventRegistration::class);
                $queued = 0;

                foreach ($records as $record) {
                    if ($decider->handle($record, $status, (bool) ($data['send_mail'] ?? false))) {
                        $queued++;
                    }
                }

                Notification::make()
                    ->title($records->count().($approved ? ' başvuru onaylandı' : ' başvuru reddedildi'))
                    ->body($queued > 0 ? "{$queued} e-posta gönderiliyor." : 'E-posta gönderilmedi.')
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    private static function sendMailToggle(EventRegistration $record, ApplicationStatus $status, string $mailName): Toggle
    {
        $blocker = app(DecideEventRegistration::class)->mailBlocker($record, $status);

        return Toggle::make('send_mail')
            ->label("Kişiye {$mailName} e-postası gönder")
            ->default($blocker === null)
            ->disabled($blocker !== null)
            ->helperText($blocker ?? "E-posta {$record->email} adresine, faaliyetin {$mailName} e-postası şablonuyla gider. İşareti kaldırırsanız yalnızca durum değişir.");
    }
}
