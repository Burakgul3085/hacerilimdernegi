<?php

namespace App\Filament\Resources\NewsletterSubscribers\Tables;

use App\Actions\ExportNewsletterSubscribers;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
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
}
