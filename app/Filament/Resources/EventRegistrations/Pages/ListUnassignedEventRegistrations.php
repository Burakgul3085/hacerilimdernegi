<?php

namespace App\Filament\Resources\EventRegistrations\Pages;

use App\Filament\Resources\EventRegistrations\EventRegistrationResource;
use App\Filament\Resources\EventRegistrations\RegistrationExcelAction;
use App\Filament\Resources\EventRegistrations\RegistrationPrintAction;
use App\Filament\Resources\EventRegistrations\RegistrationSpreadsheetAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListUnassignedEventRegistrations extends ListRecords
{
    protected static string $resource = EventRegistrationResource::class;

    protected static ?string $breadcrumb = 'Diğer başvurular';

    public function getTitle(): string|Htmlable
    {
        return 'Diğer başvurular';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Diğer başvurular';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Bir faaliyete bağlı olmayan etkinlik veya program başvuruları. Faaliyete bağlandıklarında o klasöre taşınırlar.';
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->unassigned();
    }

    protected function getHeaderActions(): array
    {
        return [
            RegistrationExcelAction::make('excelUnassigned', unassignedOnly: true),
            RegistrationPrintAction::make('printUnassigned', unassignedOnly: true),
            RegistrationSpreadsheetAction::make('spreadsheetUnassigned', unassignedOnly: true),
            Action::make('back')
                ->label('Tüm faaliyetler')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(EventRegistrationResource::getUrl()),
        ];
    }
}
