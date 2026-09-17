<?php

namespace App\Filament\Resources\Activities\Pages;

use App\Actions\SyncActivityMediaAlbum;
use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\MediaAlbums\MediaAlbumResource;
use App\Models\Activity;
use App\Models\MediaAlbum;
use App\Support\ContentMedia;
use App\Support\RegistrationExportPlan;
use App\Support\RegistrationForm;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use InvalidArgumentException;
use Throwable;

class EditActivity extends EditRecord
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncMediaAlbum')
                ->label(fn (): string => $this->linkedMediaAlbum() === null
                    ? 'Medya albümü oluştur'
                    : 'Medya albümünü güncelle')
                ->icon('heroicon-o-photo')
                ->color('gray')
                ->visible(fn (): bool => $this->activityHasSyncableMedia())
                ->modalHeading(fn (): string => $this->linkedMediaAlbum() === null
                    ? 'Medya albümü oluştur'
                    : 'Medya albümünü güncelle')
                ->modalDescription('Faaliyet başlığı, açıklaması, kapağı ve galerisi Medya albümüne kopyalanır. Formdaki kaydedilmemiş değişiklikler önce kaydedilir.')
                ->modalSubmitActionLabel(fn (): string => $this->linkedMediaAlbum() === null
                    ? 'Albümü oluştur'
                    : 'Albümü güncelle')
                ->fillForm(fn (): array => [
                    'is_published' => $this->linkedMediaAlbum()?->is_published ?? false,
                ])
                ->schema([
                    Toggle::make('is_published')
                        ->label('Albümü hemen yayına al')
                        ->helperText('Kapalıysa albüm taslak kalır ve Medya sayfasında görünmez.'),
                ])
                ->action(function (array $data, SyncActivityMediaAlbum $sync): void {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

                    /** @var Activity $activity */
                    $activity = $this->getRecord()->fresh();

                    try {
                        $result = $sync->handle($activity, (bool) ($data['is_published'] ?? false));
                        $album = $result['album'];
                        $created = $result['created'];

                        $notification = Notification::make()
                            ->title($created ? 'Medya albümü oluşturuldu' : 'Medya albümü güncellendi')
                            ->body($album->is_published
                                ? 'Albüm yayında: '.$album->title
                                : 'Albüm taslak olarak kaydedildi: '.$album->title)
                            ->success();

                        if (MediaAlbumResource::canAccess()) {
                            $notification->actions([
                                Action::make('editAlbum')
                                    ->label('Albümü aç')
                                    ->url(MediaAlbumResource::getUrl('edit', ['record' => $album]))
                                    ->openUrlInNewTab(),
                            ]);
                        }

                        $notification->send();
                    } catch (InvalidArgumentException $exception) {
                        Notification::make()
                            ->title('Albüm oluşturulamadı')
                            ->body($exception->getMessage())
                            ->warning()
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Albüm oluşturulamadı')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
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

    private function linkedMediaAlbum(): ?MediaAlbum
    {
        /** @var Activity $activity */
        $activity = $this->getRecord();
        $activity->loadMissing('mediaAlbum');

        return $activity->mediaAlbum;
    }

    private function activityHasSyncableMedia(): bool
    {
        $image = data_get($this->data, 'image', $this->getRecord()->image);
        $gallery = data_get($this->data, 'gallery', $this->getRecord()->gallery);

        return filled($image) || ContentMedia::paths($gallery ?? []) !== [];
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
