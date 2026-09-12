<?php

namespace App\Filament\Resources\Programs\Pages;

use App\Filament\Resources\Programs\ProgramResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrograms extends ListRecords
{
    protected static string $resource = ProgramResource::class;

    public function getSubheading(): ?string
    {
        return 'Haftalık ders, sohbet ve kitap tahlili. Katılım formu Program kayıtlarına düşer.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
