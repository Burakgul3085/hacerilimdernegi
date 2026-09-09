<?php

namespace App\Filament\Resources\NewsletterSubscribers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                \Filament\Actions\Action::make('export')
                    ->label('CSV dışa aktar')
                    ->action(function (): \Symfony\Component\HttpFoundation\StreamedResponse {
                        $filename = 'bulten-'.now()->format('Y-m-d').'.csv';

                        return response()->streamDownload(function (): void {
                            $out = fopen('php://output', 'w');
                            fputcsv($out, ['email', 'confirmed_at', 'created_at']);
                            \App\Models\NewsletterSubscriber::query()->orderBy('id')->each(function ($row) use ($out): void {
                                fputcsv($out, [$row->email, $row->confirmed_at, $row->created_at]);
                            });
                            fclose($out);
                        }, $filename);
                    }),
            ]);
    }
}
