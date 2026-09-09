<?php

namespace App\Filament\Resources\NewsletterSubscribers;

use App\Filament\Concerns\AuthorizesByRole;
use App\Filament\Resources\NewsletterSubscribers\Pages\ListNewsletterSubscribers;
use App\Filament\Resources\NewsletterSubscribers\Schemas\NewsletterSubscriberForm;
use App\Filament\Resources\NewsletterSubscribers\Tables\NewsletterSubscribersTable;
use App\Models\NewsletterSubscriber;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class NewsletterSubscriberResource extends Resource
{
    use AuthorizesByRole;

    protected static ?string $model = NewsletterSubscriber::class;

    protected static ?string $navigationLabel = 'E-bülten';

    protected static ?string $modelLabel = 'abone';

    protected static ?string $pluralModelLabel = 'aboneler';

    protected static string|UnitEnum|null $navigationGroup = 'Başvurular';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    public static function canAccess(): bool
    {
        return static::editorRoles();
    }

    public static function form(Schema $schema): Schema
    {
        return NewsletterSubscriberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NewsletterSubscribersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsletterSubscribers::route('/'),
        ];
    }
}
