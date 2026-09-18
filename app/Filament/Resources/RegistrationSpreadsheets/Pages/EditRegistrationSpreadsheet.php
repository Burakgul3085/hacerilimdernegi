<?php

namespace App\Filament\Resources\RegistrationSpreadsheets\Pages;

use App\Actions\ExportRegistrationSpreadsheetCsv;
use App\Actions\SaveRegistrationSpreadsheet;
use App\Enums\ApplicationStatus;
use App\Filament\Resources\RegistrationSpreadsheets\RegistrationSpreadsheetResource;
use App\Models\RegistrationSpreadsheet;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read RegistrationSpreadsheet $record
 */
class EditRegistrationSpreadsheet extends EditRecord
{
    protected static string $resource = RegistrationSpreadsheetResource::class;

    /**
     * @var list<array{id: int|string, cells: array<string, string>}>
     */
    public array $grid = [];

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->fillGrid();
    }

    public function getSubheading(): ?string
    {
        return 'Durum ve not sütunları kaydedilince paneldeki başvuruya da yazılır. Diğer alanlar yalnızca bu e-tabloda tutulur.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('csv')
                ->label('Kurumsal Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (ExportRegistrationSpreadsheetCsv $export) {
                    return $export->download($this->getRecord(), $this->grid);
                }),
            DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label('Kaydet'),
            $this->getCancelFormAction()->label('Listeye dön'),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var RegistrationSpreadsheet $record */
        return app(SaveRegistrationSpreadsheet::class)->handle($record, [
            'title' => $data['title'] ?? $record->title,
            'rows' => $this->grid,
        ]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('E-tablo kaydedildi')
            ->body('Durum ve not alanları panele de yazıldı.');
    }

    protected function afterSave(): void
    {
        $this->fillGrid();
    }

    /**
     * @return array{headers: list<string>, columnKeys: list<string>, statusOptions: array<string, string>}
     */
    public function gridMeta(): array
    {
        return [
            'headers' => $this->getRecord()->headers,
            'columnKeys' => $this->getRecord()->column_keys,
            'statusOptions' => collect(ApplicationStatus::cases())
                ->mapWithKeys(fn (ApplicationStatus $status): array => [$status->value => $status->label()])
                ->all(),
        ];
    }

    private function fillGrid(): void
    {
        $this->getRecord()->loadMissing('rows');

        $this->grid = $this->getRecord()->rows
            ->map(fn ($row): array => [
                'id' => $row->getKey(),
                'cells' => collect($this->getRecord()->column_keys)
                    ->mapWithKeys(fn (string $key): array => [
                        $key => (string) ($row->cells[$key] ?? ''),
                    ])
                    ->all(),
            ])
            ->values()
            ->all();
    }
}
