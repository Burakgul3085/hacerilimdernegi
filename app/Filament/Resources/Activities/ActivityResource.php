<?php

namespace App\Filament\Resources\Activities;

use App\Filament\Concerns\AuthorizesByRole;
use App\Filament\Resources\Activities\Pages\CreateActivity;
use App\Filament\Resources\Activities\Pages\EditActivity;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Resources\Activities\Schemas\ActivityForm;
use App\Filament\Resources\Activities\Tables\ActivitiesTable;
use App\Models\Activity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ActivityResource extends Resource
{
    use AuthorizesByRole;

    protected static ?string $model = Activity::class;

    protected static ?string $navigationLabel = 'Faaliyetler';

    protected static ?string $modelLabel = 'faaliyet';

    protected static ?string $pluralModelLabel = 'faaliyetler';

    protected static string|UnitEnum|null $navigationGroup = 'Faaliyetler';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    public static function canAccess(): bool
    {
        return static::editorRoles();
    }

    public static function form(Schema $schema): Schema
    {
        return ActivityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
            'create' => CreateActivity::route('/create'),
            'edit' => EditActivity::route('/{record}/edit'),
        ];
    }
}
