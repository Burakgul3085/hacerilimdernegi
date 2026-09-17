<?php

namespace App\Filament\Resources\Activities\Pages;

use App\Filament\Resources\Activities\ActivityResource;
use App\Models\Activity;
use App\Support\RegistrationExportPlan;
use App\Support\RegistrationForm;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditActivity extends EditRecord
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('copyRegistrationLink')
                ->label('Form linkini kopyala')
                ->icon('heroicon-o-clipboard-document')
                ->visible(fn (): bool => filled($this->getRecord()->slug))
                ->action(function (): void {
                    /** @var Activity $activity */
                    $activity = $this->getRecord();
                    $url = $activity->registrationFormUrl();

                    $this->js('window.navigator.clipboard.writeText('.json_encode($url).')');

                    Notification::make()
                        ->title('Form linki kopyalandı')
                        ->body($url)
                        ->success()
                        ->send();
                }),
            Action::make('openRegistrationForm')
                ->label('Formu aç')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): string => $this->getRecord()->registrationFormUrl())
                ->openUrlInNewTab()
                ->visible(fn (): bool => filled($this->getRecord()->slug)),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $fields = RegistrationForm::normalize($data['registration_fields'] ?? null);

        $data['registration_fields'] = array_map(function (array $field): array {
            $field['options'] = implode("\n", $field['options'] ?? []);

            return $field;
        }, $fields);
        $data['excel_columns'] = RegistrationExportPlan::fixedSelection(
            is_array($data['excel_columns'] ?? null) ? $data['excel_columns'] : null,
        );

        return $data;
    }
}
