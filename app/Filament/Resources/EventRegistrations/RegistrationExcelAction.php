<?php

namespace App\Filament\Resources\EventRegistrations;

use App\Actions\ExportEventRegistrations;
use App\Enums\ApplicationStatus;
use App\Models\Activity;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;

class RegistrationExcelAction
{
    public static function make(string $name = 'excel', ?Activity $activity = null, bool $unassignedOnly = false): Action
    {
        return Action::make($name)
            ->label($activity ? 'Bu faaliyetin Excel\'i' : 'Excel indir')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->modalHeading($activity ? $activity->title : 'Excel indir')
            ->modalDescription('Dosya o andaki başvuruları ve seçili sütunları içerir. Paneldeki kayıtlar değişmez.')
            ->modalSubmitActionLabel('İndir')
            ->schema([
                Select::make('status')
                    ->label('Durum')
                    ->placeholder('Tümü')
                    ->options(collect(ApplicationStatus::cases())->mapWithKeys(
                        fn (ApplicationStatus $status): array => [$status->value => $status->label()],
                    )),
            ])
            ->action(function (array $data, ExportEventRegistrations $export) use ($activity, $unassignedOnly) {
                $status = filled($data['status'] ?? null)
                    ? ApplicationStatus::from($data['status'])
                    : null;

                return $export->download($activity, $status, $unassignedOnly);
            });
    }
}
