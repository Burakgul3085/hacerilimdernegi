<?php

namespace App\Filament\Resources\MediaItems;

use App\Filament\Concerns\AuthorizesByRole;
use App\Filament\Resources\MediaItems\Pages\CreateMediaItem;
use App\Filament\Resources\MediaItems\Pages\EditMediaItem;
use App\Filament\Resources\MediaItems\Pages\ListMediaItems;
use App\Filament\Resources\MediaItems\Schemas\MediaItemForm;
use App\Filament\Resources\MediaItems\Tables\MediaItemsTable;
use App\Models\MediaItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MediaItemResource extends Resource
{
    use AuthorizesByRole;

    protected static ?string $model = MediaItem::class;

    protected static ?string $navigationLabel = 'Medya öğeleri';

    protected static ?string $modelLabel = 'medya öğesi';

    protected static ?string $pluralModelLabel = 'medya öğeleri';

    protected static string|UnitEnum|null $navigationGroup = 'Medya';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFilm;

    public static function canAccess(): bool
    {
        return static::mediaRoles();
    }

    public static function form(Schema $schema): Schema
    {
        return MediaItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaItemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMediaItems::route('/'),
            'create' => CreateMediaItem::route('/create'),
            'edit' => EditMediaItem::route('/{record}/edit'),
        ];
    }
}
