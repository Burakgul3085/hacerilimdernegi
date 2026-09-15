<?php

namespace App\Filament\Resources\EventRegistrations\Pages;

use App\Filament\Resources\EventRegistrations\EventRegistrationResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListEventRegistrations extends ListRecords
{
    protected static string $resource = EventRegistrationResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Program kayıtları';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Program kayıtları';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Faaliyet ve program katılım başvuruları. Her satır bir başvuru dosyasıdır.';
    }
}
