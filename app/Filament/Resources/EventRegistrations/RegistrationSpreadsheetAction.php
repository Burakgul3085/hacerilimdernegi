<?php

namespace App\Filament\Resources\EventRegistrations;

use App\Actions\CreateRegistrationSpreadsheet;
use App\Enums\ApplicationStatus;
use App\Filament\Resources\RegistrationSpreadsheets\RegistrationSpreadsheetResource;
use App\Models\Activity;
use App\Models\RegistrationSpreadsheet;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class RegistrationSpreadsheetAction
{
    public static function make(string $name = 'spreadsheet', ?Activity $activity = null, bool $unassignedOnly = false): Action
    {
        if ($activity !== null) {
            return self::forActivity($name, $activity);
        }

        if ($unassignedOnly) {
            return self::forUnassigned($name);
        }

        return self::forAll($name);
    }

    public static function makeForFolderRow(string $name = 'spreadsheet'): Action
    {
        return Action::make($name)
            ->label('E-tablo')
            ->icon('heroicon-o-table-cells')
            ->color('gray')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalHeading(fn (Activity $record): string => $record->title)
            ->modalDescription('Seçtiğiniz başvurular kalıcı bir e-tabloya aktarılır. Durum ve not kaydedilince panele de yazılır.')
            ->modalSubmitActionLabel('E-tabloya aktar')
            ->fillForm(fn (Activity $record): array => RegistrationExportForm::activityDefaults($record))
            ->schema(fn (Activity $record): array => RegistrationExportForm::activitySchema(
                (int) $record->getKey(),
                'E-tabloya yalnızca işaretli satırlar aktarılır.',
                'E-tablo alanları',
            ))
            ->action(function (array $data, Activity $record, CreateRegistrationSpreadsheet $create) {
                /** @var User $user */
                $user = Auth::user();

                return self::openCreated($create->handle(
                    user: $user,
                    activity: $record,
                    registrationIds: $data['registration_ids'] ?? [],
                    columnKeys: $data['column_keys'] ?? [],
                ));
            });
    }

    private static function forActivity(string $name, Activity $activity): Action
    {
        $activityId = (int) $activity->getKey();

        return Action::make($name)
            ->label('E-tablo')
            ->icon('heroicon-o-table-cells')
            ->color('gray')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalHeading($activity->title)
            ->modalDescription('Seçtiğiniz başvurular kalıcı bir e-tabloya aktarılır. Durum ve not kaydedilince panele de yazılır.')
            ->modalSubmitActionLabel('E-tabloya aktar')
            ->fillForm(fn (): array => RegistrationExportForm::activityDefaults(Activity::query()->findOrFail($activityId)))
            ->schema(fn (): array => RegistrationExportForm::activitySchema(
                $activityId,
                'E-tabloya yalnızca işaretli satırlar aktarılır.',
                'E-tablo alanları',
            ))
            ->action(function (array $data, CreateRegistrationSpreadsheet $create) use ($activityId) {
                /** @var User $user */
                $user = Auth::user();

                return self::openCreated($create->handle(
                    user: $user,
                    activity: Activity::query()->findOrFail($activityId),
                    registrationIds: $data['registration_ids'] ?? [],
                    columnKeys: $data['column_keys'] ?? [],
                ));
            });
    }

    private static function forUnassigned(string $name): Action
    {
        return Action::make($name)
            ->label('E-tablo')
            ->icon('heroicon-o-table-cells')
            ->color('gray')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalHeading('Diğer başvurular')
            ->modalDescription('Seçtiğiniz başvurular kalıcı bir e-tabloya aktarılır. Durum ve not kaydedilince panele de yazılır.')
            ->modalSubmitActionLabel('E-tabloya aktar')
            ->fillForm(fn (): array => RegistrationExportForm::unassignedDefaults())
            ->schema(fn (): array => RegistrationExportForm::unassignedSchema(
                'E-tabloya yalnızca işaretli satırlar aktarılır.',
                'E-tablo alanları',
            ))
            ->action(function (array $data, CreateRegistrationSpreadsheet $create) {
                /** @var User $user */
                $user = Auth::user();

                return self::openCreated($create->handle(
                    user: $user,
                    unassignedOnly: true,
                    registrationIds: $data['registration_ids'] ?? [],
                    columnKeys: $data['column_keys'] ?? [],
                ));
            });
    }

    private static function forAll(string $name): Action
    {
        return Action::make($name)
            ->label('E-tablo')
            ->icon('heroicon-o-table-cells')
            ->color('gray')
            ->modalHeading('E-tabloya aktar')
            ->modalDescription('Tüm faaliyetlerin başvuruları tek e-tabloya aktarılır. Alanları daraltmak için ilgili faaliyet klasöründen aktarın.')
            ->modalSubmitActionLabel('E-tabloya aktar')
            ->schema([
                Select::make('status')
                    ->label('Durum')
                    ->placeholder('Tümü')
                    ->options(collect(ApplicationStatus::cases())->mapWithKeys(
                        fn (ApplicationStatus $status): array => [$status->value => $status->label()],
                    )),
            ])
            ->action(function (array $data, CreateRegistrationSpreadsheet $create) {
                /** @var User $user */
                $user = Auth::user();
                $status = filled($data['status'] ?? null)
                    ? ApplicationStatus::from($data['status'])
                    : null;

                return self::openCreated($create->handle(
                    user: $user,
                    status: $status,
                ));
            });
    }

    private static function openCreated(RegistrationSpreadsheet $spreadsheet): mixed
    {
        Notification::make()
            ->title('E-tablo oluşturuldu')
            ->body($spreadsheet->title)
            ->success()
            ->send();

        return redirect()->to(
            RegistrationSpreadsheetResource::getUrl('edit', ['record' => $spreadsheet->getKey()]),
        );
    }
}
