<?php

namespace App\Filament\Resources\RegistrationSpreadsheets;

use App\Actions\ExportRegistrationSpreadsheetCsv;
use App\Models\AuditLog;
use App\Models\RegistrationSpreadsheet;
use App\Models\RegistrationSpreadsheetShareLink;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

/**
 * E-tabloyu WhatsApp ile paylaşır: dosyanın kendisi (cihaz paylaşım menüsü) veya süreli indirme bağlantısı.
 */
class ShareSpreadsheetAction
{
    public static function make(): Action
    {
        return Action::make('shareWhatsapp')
            ->label('WhatsApp’ta paylaş')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('success')
            ->modalIcon(Heroicon::OutlinedChatBubbleLeftRight)
            ->modalIconColor('success')
            ->modalHeading('WhatsApp’ta paylaş')
            ->modalDescription(fn (RegistrationSpreadsheet $record): string => "«{$record->title}» · {$record->rows()->count()} satır. Bu dosya kişisel veri içerir; yalnızca yetkili kişilerle paylaşın.")
            ->modalWidth(Width::TwoExtraLarge)
            ->modalContent(fn (RegistrationSpreadsheet $record): View => view('filament.spreadsheet-share', [
                'fileUrl' => route('admin.spreadsheets.share-file', $record),
                'filename' => app(ExportRegistrationSpreadsheetCsv::class)->plan($record)['filename'],
                'title' => $record->title,
                'shareText' => "{$record->title} başvuru listesi",
            ]))
            ->modalContentFooter(fn (RegistrationSpreadsheet $record): View => view('filament.spreadsheet-share-links', [
                'links' => $record->shareLinks()->with('user')->limit(10)->get(),
            ]))
            ->fillForm(['hours' => 24, 'single_use' => true])
            ->schema([
                Grid::make(2)->schema([
                    Select::make('hours')
                        ->label('Bağlantı ne kadar açık kalsın?')
                        ->options(collect(RegistrationSpreadsheetShareLink::DURATION_HOURS)->mapWithKeys(
                            fn (int $hours): array => [$hours => RegistrationSpreadsheetShareLink::durationLabel($hours)],
                        ))
                        ->native(false)
                        ->required(),
                    Toggle::make('single_use')
                        ->label('Tek kullanımlık')
                        ->helperText('İlk indirmeden sonra bağlantı kapanır.')
                        ->inline(false),
                ]),
            ])
            ->modalSubmitActionLabel('Bağlantı oluştur')
            ->modalCancelActionLabel('Kapat')
            ->action(function (array $data, RegistrationSpreadsheet $record): void {
                $hours = in_array((int) $data['hours'], RegistrationSpreadsheetShareLink::DURATION_HOURS, true) ? (int) $data['hours'] : 24;
                $singleUse = (bool) ($data['single_use'] ?? false);

                $issued = RegistrationSpreadsheetShareLink::issue($record, auth()->id(), $hours, $singleUse);
                $url = route('spreadsheet-share.show', ['token' => $issued['token']]);
                $duration = RegistrationSpreadsheetShareLink::durationLabel($hours);

                AuditLog::record(
                    'shared',
                    RegistrationSpreadsheet::class,
                    $record->getKey(),
                    $record->title.' · bağlantı ('.$duration.($singleUse ? ', tek kullanımlık' : '').')',
                );

                $message = "{$record->title} başvuru listesi:\n{$url}\n\nBağlantı {$duration} geçerlidir".($singleUse ? ' ve yalnızca bir kez indirilebilir.' : '.');

                Notification::make()
                    ->title('Paylaşım bağlantısı hazır')
                    ->body('“WhatsApp’ta gönder”e basın; WhatsApp hazır mesajla açılır, kişiyi seçip gönderin.')
                    ->success()
                    ->persistent()
                    ->actions([
                        Action::make('openWhatsapp')
                            ->label('WhatsApp’ta gönder')
                            ->button()
                            ->color('success')
                            ->url('https://wa.me/?text='.rawurlencode($message), shouldOpenInNewTab: true),
                    ])
                    ->send();
            });
    }
}
