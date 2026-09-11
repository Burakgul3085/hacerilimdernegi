<?php

namespace App\Filament\Resources\NewsletterSubscribers\Tables;

use App\Actions\ExportNewsletterSubscribers;
use App\Actions\SendNewsletterMail;
use App\Models\NewsletterSubscriber;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class NewsletterSubscribersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')->label('E-posta')->searchable(),
                TextColumn::make('confirmed_at')->label('Onay')->dateTime('d.m.Y'),
                TextColumn::make('created_at')->label('Kayıt')->since(),
            ])
            ->recordActions([
                Action::make('sendMail')
                    ->label('Mail gönder')
                    ->icon('heroicon-o-paper-airplane')
                    ->modalHeading('Aboneye mail gönder')
                    ->modalSubmitActionLabel('Gönder')
                    ->schema(self::mailSchema())
                    ->action(function (NewsletterSubscriber $record, array $data): void {
                        self::sendAndNotify(collect([$record]), $data);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('sendMail')
                        ->label('Seçilenlere mail gönder')
                        ->icon('heroicon-o-paper-airplane')
                        ->modalHeading('Seçilen abonelere mail gönder')
                        ->modalSubmitActionLabel('Gönder')
                        ->schema(self::mailSchema())
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, array $data): void {
                            self::sendAndNotify($records, $data);
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('sendAllMail')
                    ->label('Tümüne mail gönder')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->modalHeading(fn (): string => 'Tüm abonelere mail gönder ('.NewsletterSubscriber::query()->count().' kişi)')
                    ->modalDescription('Konu ve metin listedeki herkese, derneğin mail şablonuyla ayrı ayrı gönderilir.')
                    ->modalSubmitActionLabel('Gönder')
                    ->schema(self::mailSchema())
                    ->action(function (array $data): void {
                        self::sendAndNotify(NewsletterSubscriber::query()->orderBy('id')->get(), $data);
                    }),
                Action::make('export')
                    ->label('Excel dışa aktar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (ExportNewsletterSubscribers $export): StreamedResponse {
                        $workbook = $export->handle();

                        return response()->streamDownload(function () use ($workbook): void {
                            echo $workbook['contents'];
                        }, $workbook['filename'], [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ]);
                    }),
            ]);
    }

    /**
     * @return list<Component>
     */
    private static function mailSchema(): array
    {
        return [
            TextInput::make('subject')
                ->label('Konu')
                ->required()
                ->maxLength(180),
            Textarea::make('body')
                ->label('Metin')
                ->required()
                ->rows(10)
                ->maxLength(10000)
                ->helperText('Bu metin derneğin mail şablonuyla gönderilir. Her aboneye ayrı mail gider.'),
        ];
    }

    /**
     * @param  Collection<int, NewsletterSubscriber>  $subscribers
     * @param  array{subject?: string, body?: string}  $data
     */
    private static function sendAndNotify(Collection $subscribers, array $data): void
    {
        try {
            $result = app(SendNewsletterMail::class)->handle(
                $subscribers,
                (string) ($data['subject'] ?? ''),
                (string) ($data['body'] ?? ''),
            );

            if ($result['failed'] > 0) {
                Notification::make()
                    ->title($result['sent'].' kişiye gönderildi, '.$result['failed'].' kişiye ulaşılamadı.')
                    ->warning()
                    ->send();

                return;
            }

            Notification::make()
                ->title($result['sent'] === 1 ? 'Mail gönderildi.' : $result['sent'].' kişiye mail gönderildi.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Mail gönderilemedi')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
