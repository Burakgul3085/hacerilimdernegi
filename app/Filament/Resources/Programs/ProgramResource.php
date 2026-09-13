<?php

namespace App\Filament\Resources\Programs;

use App\Filament\Concerns\AuthorizesByRole;
use App\Filament\Resources\Programs\Pages\CreateProgram;
use App\Filament\Resources\Programs\Pages\EditProgram;
use App\Filament\Resources\Programs\Pages\ListPrograms;
use App\Filament\Resources\Programs\Schemas\ProgramForm;
use App\Filament\Resources\Programs\Tables\ProgramsTable;
use App\Models\Program;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProgramResource extends Resource
{
    use AuthorizesByRole;

    protected static ?string $model = Program::class;

    protected static ?string $navigationLabel = 'Ders ve sohbetler';

    protected static ?string $modelLabel = 'program';

    protected static ?string $pluralModelLabel = 'ders ve sohbetler';

    protected static string|UnitEnum|null $navigationGroup = 'Faaliyetler';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    public static function canAccess(): bool
    {
        return static::editorRoles();
    }

    public static function form(Schema $schema): Schema
    {
        return ProgramForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProgramsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrograms::route('/'),
            'create' => CreateProgram::route('/create'),
            'edit' => EditProgram::route('/{record}/edit'),
        ];
    }
}
