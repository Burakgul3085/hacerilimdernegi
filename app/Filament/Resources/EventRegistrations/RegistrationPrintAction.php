<?php

namespace App\Filament\Resources\EventRegistrations;

use App\Actions\PrintEventRegistrations;
use App\Enums\ApplicationStatus;
use App\Models\Activity;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class RegistrationPrintAction
{
    public static function make(string $name = 'print', ?Activity $activity = null, bool $unassignedOnly = false): Action
    {
        if ($activity !== null) {
            return self::forActivity($name, $activity);
        }

        if ($unassignedOnly) {
            return self::forUnassigned($name);
        }

        return self::forAll($name);
    }

    public static function makeForFolderRow(string $name = 'print'): Action
    {
        return Action::make($name)
            ->label('Yazdır')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalHeading(fn (Activity $record): string => $record->title)
            ->modalDescription('Yalnızca seçtiğiniz başvurular ve alanlar yazdırılır. Paneldeki kayıtlar değişmez.')
            ->modalSubmitActionLabel('Yazdır')
            ->fillForm(fn (Activity $record): array => RegistrationExportForm::activityDefaults($record))
            ->schema(fn (Activity $record): array => RegistrationExportForm::activitySchema(
                (int) $record->getKey(),
                'Yazdırma sayfasına yalnızca işaretli satırlar gider.',
                'Yazdırılacak alanlar',
            ))
            ->action(function (array $data, Activity $record, PrintEventRegistrations $print) {
                return self::redirectToPrint($print->issueToken(
                    activity: $record,
                    registrationIds: $data['registration_ids'] ?? [],
                    columnKeys: $data['column_keys'] ?? [],
                    userId: Auth::id(),
                ));
            });
    }

    private static function forActivity(string $name, Activity $activity): Action
    {
        $activityId = (int) $activity->getKey();

        return Action::make($name)
            ->label('Yazdır')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalHeading($activity->title)
            ->modalDescription('Yalnızca seçtiğiniz başvurular ve alanlar yazdırılır. Paneldeki kayıtlar değişmez. Başvuru numarası her çıktıda durur.')
            ->modalSubmitActionLabel('Yazdır')
            ->fillForm(fn (): array => RegistrationExportForm::activityDefaults(Activity::query()->findOrFail($activityId)))
            ->schema(fn (): array => RegistrationExportForm::activitySchema(
                $activityId,
                'Yazdırma sayfasına yalnızca işaretli satırlar gider.',
                'Yazdırılacak alanlar',
            ))
            ->action(function (array $data, PrintEventRegistrations $print) use ($activityId) {
                return self::redirectToPrint($print->issueToken(
                    activity: Activity::query()->findOrFail($activityId),
                    registrationIds: $data['registration_ids'] ?? [],
                    columnKeys: $data['column_keys'] ?? [],
                    userId: Auth::id(),
                ));
            });
    }

    private static function forUnassigned(string $name): Action
    {
        return Action::make($name)
            ->label('Yazdır')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalHeading('Diğer başvurular')
            ->modalDescription('Yalnızca seçtiğiniz başvurular ve alanlar yazdırılır. Paneldeki kayıtlar değişmez.')
            ->modalSubmitActionLabel('Yazdır')
            ->fillForm(fn (): array => RegistrationExportForm::unassignedDefaults())
            ->schema(fn (): array => RegistrationExportForm::unassignedSchema(
                'Yazdırma sayfasına yalnızca işaretli satırlar gider.',
                'Yazdırılacak alanlar',
            ))
            ->action(function (array $data, PrintEventRegistrations $print) {
                return self::redirectToPrint($print->issueToken(
                    unassignedOnly: true,
                    registrationIds: $data['registration_ids'] ?? [],
                    columnKeys: $data['column_keys'] ?? [],
                    userId: Auth::id(),
                ));
            });
    }

    private static function forAll(string $name): Action
    {
        return Action::make($name)
            ->label('Yazdır')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->modalHeading('Yazdır')
            ->modalDescription('Tüm faaliyetler tek yazdırma sayfasında açılır. Alanları daraltmak için ilgili faaliyet klasöründen yazdırın.')
            ->modalSubmitActionLabel('Yazdır')
            ->schema([
                Select::make('status')
                    ->label('Durum')
                    ->placeholder('Tümü')
                    ->options(collect(ApplicationStatus::cases())->mapWithKeys(
                        fn (ApplicationStatus $status): array => [$status->value => $status->label()],
                    )),
            ])
            ->action(function (array $data, PrintEventRegistrations $print) {
                $status = filled($data['status'] ?? null)
                    ? ApplicationStatus::from($data['status'])
                    : null;

                return self::redirectToPrint($print->issueToken(
                    status: $status,
                    userId: Auth::id(),
                ));
            });
    }

    private static function redirectToPrint(string $token): mixed
    {
        return redirect()->route('admin.registrations.print', ['token' => $token]);
    }
}
