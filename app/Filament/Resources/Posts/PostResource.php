<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Concerns\AuthorizesByRole;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\Schemas\PostForm;
use App\Filament\Resources\Posts\Tables\PostsTable;
use App\Models\Post;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PostResource extends Resource
{
    use AuthorizesByRole;

    protected static ?string $model = Post::class;

    protected static ?string $navigationLabel = 'Yazılar ve şiirler';

    protected static ?string $modelLabel = 'yazı / şiir';

    protected static ?string $pluralModelLabel = 'yazılar ve şiirler';

    protected static string|UnitEnum|null $navigationGroup = 'İçerik';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    public static function canAccess(): bool
    {
        return static::editorRoles();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Post::query()
            ->where('submitted_from_public', true)
            ->where('is_published', false)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return PostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
