<?php

namespace App\Filament\Resources\RegistrationSpreadsheets;

use App\Filament\Concerns\AuthorizesByRole;
use App\Filament\Resources\RegistrationSpreadsheets\Pages\EditRegistrationSpreadsheet;
use App\Filament\Resources\RegistrationSpreadsheets\Pages\ListRegistrationSpreadsheets;
use App\Filament\Resources\RegistrationSpreadsheets\Tables\RegistrationSpreadsheetsTable;
use App\Models\RegistrationSpreadsheet;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RegistrationSpreadsheetResource extends Resource
{
    use AuthorizesByRole;

    protected static ?string $model = RegistrationSpreadsheet::class;

    protected static ?string $navigationLabel = 'E-tablolar';

    protected static ?string $modelLabel = 'e-tablo';

    protected static ?string $pluralModelLabel = 'e-tablolar';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'Başvurular';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?int $navigationSort = 25;

    public static function canAccess(): bool
    {
        return static::editorRoles();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('E-tablo adı')
                    ->required()
                    ->maxLength(180)
                    ->columnSpanFull(),
                SchemaView::make('filament.resources.registration-spreadsheets.pages.spreadsheet-grid')
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return RegistrationSpreadsheetsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegistrationSpreadsheets::route('/'),
            'edit' => EditRegistrationSpreadsheet::route('/{record}/edit'),
        ];
    }
}
