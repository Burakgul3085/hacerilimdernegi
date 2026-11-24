<?php

namespace App\Filament\Resources\RegistrationSpreadsheets\Concerns;

use App\Filament\Resources\RegistrationSpreadsheets\RegistrationSpreadsheetResource;
use App\Models\RegistrationSpreadsheetShareLink;
use Filament\Notifications\Notification;

/**
 * Paylaşım penceresindeki "Bağlantıyı kapat" butonunu karşılar.
 */
trait RevokesSpreadsheetShareLinks
{
    public function revokeSpreadsheetShareLink(int $linkId): void
    {
        abort_unless(RegistrationSpreadsheetResource::canAccess(), 403);

        $link = RegistrationSpreadsheetShareLink::query()->find($linkId);

        if (! $link || $link->revoked_at !== null) {
            return;
        }

        $link->forceFill(['revoked_at' => now()])->save();

        Notification::make()
            ->title('Bağlantı kapatıldı')
            ->body('Bu bağlantıyla artık dosya indirilemez.')
            ->success()
            ->send();
    }
}
