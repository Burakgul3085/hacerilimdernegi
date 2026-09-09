<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Concerns\AuthorizesByRole;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Categories\Tables\CategoriesTable;
use App\Models\Category;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CategoryResource extends Resource
{
    use AuthorizesByRole;

    protected static ?string $model = Category::class;

    protected static ?string $navigationLabel = 'Kategoriler';

    protected static ?string $modelLabel = 'kategori';

    protected static ?string $pluralModelLabel = 'kategoriler';

    protected static string|UnitEnum|null $navigationGroup = 'İçerik';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function canAccess(): bool
    {
        return static::editorRoles();
    }

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
