<?php

namespace App\Filament\Resources\RegistrationSpreadsheets\Pages;

use App\Filament\Resources\RegistrationSpreadsheets\RegistrationSpreadsheetResource;
use Filament\Resources\Pages\ListRecords;

class ListRegistrationSpreadsheets extends ListRecords
{
    protected static string $resource = RegistrationSpreadsheetResource::class;

    public function getSubheading(): ?string
    {
        return 'Aktarılan başvurular burada kalır. Durum ve not kaydedilince asıl başvuruya da yazılır.';
    }
}
