<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Concerns\AuthorizesByRole;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Pages\Schemas\PageForm;
use App\Filament\Resources\Pages\Tables\PagesTable;
use App\Models\Page;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PageResource extends Resource
{
    use AuthorizesByRole;

    protected static ?string $model = Page::class;

    protected static ?string $navigationLabel = 'Kurumsal';

    protected static ?string $modelLabel = 'sayfa';

    protected static ?string $pluralModelLabel = 'sayfalar';

    protected static string|UnitEnum|null $navigationGroup = 'İçerik';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function canAccess(): bool
    {
        return static::editorRoles();
    }

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
