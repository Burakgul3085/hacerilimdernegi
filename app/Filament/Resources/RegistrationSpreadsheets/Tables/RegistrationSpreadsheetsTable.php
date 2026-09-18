<?php

namespace App\Filament\Resources\RegistrationSpreadsheets\Tables;

use App\Actions\ExportRegistrationSpreadsheetCsv;
use App\Filament\Resources\RegistrationSpreadsheets\RegistrationSpreadsheetResource;
use App\Models\RegistrationSpreadsheet;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RegistrationSpreadsheetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('E-tablo')
                    ->searchable()
                    ->wrap()
                    ->description(fn ($record): ?string => $record->activity?->title),
                TextColumn::make('rows_count')
                    ->counts('rows')
                    ->label('Satır'),
                TextColumn::make('user.name')
                    ->label('Oluşturan')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Son kayıt')
                    ->since()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(fn ($record): string => RegistrationSpreadsheetResource::getUrl('edit', ['record' => $record]))
            ->recordActions([
                EditAction::make()->label('Aç'),
                Action::make('csv')
                    ->label('Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (RegistrationSpreadsheet $record, ExportRegistrationSpreadsheetCsv $export) {
                        return $export->download($record);
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Henüz e-tablo yok')
            ->emptyStateDescription('Program kayıtlarından “E-tablo” ile seçtiğiniz başvurular burada açılır.');
    }
}
